<?php
/**
 * AccountController.php
 * Copyright (c) 2017 thegrumpydictator@gmail.com
 *
 * This file is part of Firefly III.
 *
 * Firefly III is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Firefly III is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Firefly III. If not, see <http://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AccountController.
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountController extends Controller
{
    use DateCalculation, AugumentData;

    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /** @var AccountRepositoryInterface Account repository. */
    private $accountRepository;

    /** @var CurrencyRepositoryInterface */
    private $currencyRepository;

    /**
     * AccountController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                $this->generator          = app(GeneratorInterface::class);
                $this->accountRepository  = app(AccountRepositoryInterface::class);
                $this->currencyRepository = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );


    }


    /**
     * Shows the balances for all the user's expense accounts (on the front page).
     *
     * This chart is (multi) currency aware.
     *
     * @return JsonResponse
     */
    public function expenseAccounts(): JsonResponse
    {
        /** @var Carbon $start */
        $start = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end   = clone session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-accounts');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $start->subDay();

        // prep some vars:
        $currencies = [];
        $chartData  = [];
        $tempData   = [];

        // grab all accounts and names
        $accounts     = $this->accountRepository->getAccountsByType([AccountType::EXPENSE, AccountType::BENEFICIARY]);
        $accountNames = $this->extractNames($accounts);

        // grab all balances
        $startBalances = app('steam')->balancesPerCurrencyByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesPerCurrencyByAccounts($accounts, $end);

        // loop the end balances. This is an array for each account ($expenses)
        foreach ($endBalances as $accountId => $expenses) {
            $accountId = (int)$accountId;
            // loop each expense entry (each entry can be a different currency).
            foreach ($expenses as $currencyId => $endAmount) {
                $currencyId = (int)$currencyId;

                // see if there is an accompanying start amount.
                // grab the difference and find the currency.
                $startAmount             = $startBalances[$accountId][$currencyId] ?? '0';
                $diff                    = bcsub($endAmount, $startAmount);
                $currencies[$currencyId] = $currencies[$currencyId] ?? $this->currencyRepository->findNull($currencyId);
                if (0 !== bccomp($diff, '0')) {
                    // store the values in a temporary array.
                    $tempData[] = [
                        'name'        => $accountNames[$accountId],
                        'difference'  => $diff,
                        'diff_float'  => (float)$diff,
                        'currency_id' => $currencyId,
                    ];
                }
            }
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'diff_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $dataSet
                                    = [
                'label'           => (string)trans('firefly.spent'),
                'type'            => 'bar',
                'currency_symbol' => $currency->symbol,
                'entries'         => $this->expandNames($tempData),
            ];
            $chartData[$currencyId] = $dataSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = $entry['difference'];
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Expenses per budget, as shown on account overview.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     */
    public function expenseBudget(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-budget');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withBudgetInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $transactions = $collector->getTransactions();
        $chartData    = [];
        $result       = [];
        $budgetIds    = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlBudgetId  = (int)$transaction->transaction_journal_budget_id;
            $transBudgetId = (int)$transaction->transaction_budget_id;
            $currencyName  = $transaction->transaction_currency_name;
            $budgetId      = max($jrnlBudgetId, $transBudgetId);
            $combi         = $budgetId . $currencyName;
            $budgetIds[]   = $budgetId;
            if (!isset($result[$combi])) {
                $result[$combi] = [
                    'total'           => '0',
                    'budget_id'       => $budgetId,
                    'currency'        => $currencyName,
                    'currency_symbol' => $transaction->transaction_currency_symbol,
                ];
            }
            $result[$combi]['total'] = bcadd($transaction->transaction_amount, $result[$combi]['total']);
        }

        $names = $this->getBudgetNames($budgetIds);

        foreach ($result as $row) {
            $budgetId          = $row['budget_id'];
            $name              = $names[$budgetId];
            $label             = (string)trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency']]);
            $chartData[$label] = ['amount' => $row['total'], 'currency_symbol' => $row['currency_symbol']];
        }

        $data = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Expenses per budget for all time, as shown on account overview.
     *
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return JsonResponse
     */
    public function expenseBudgetAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        return $this->expenseBudget($account, $start, $end);
    }


    /**
     * Expenses per category for one single account.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     */
    public function expenseCategory(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.expense-category');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::WITHDRAWAL]);
        $transactions = $collector->getTransactions();
        $result       = [];
        $chartData    = [];
        $categoryIds  = [];

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId     = (int)$transaction->transaction_journal_category_id;
            $transCatId    = (int)$transaction->transaction_category_id;
            $currencyName  = $transaction->transaction_currency_name;
            $categoryId    = max($jrnlCatId, $transCatId);
            $combi         = $categoryId . $currencyName;
            $categoryIds[] = $categoryId;
            if (!isset($result[$combi])) {
                $result[$combi] = [
                    'total'           => '0',
                    'category_id'     => $categoryId,
                    'currency'        => $currencyName,
                    'currency_symbol' => $transaction->transaction_currency_symbol,
                ];
            }
            $result[$combi]['total'] = bcadd($transaction->transaction_amount, $result[$combi]['total']);
        }

        $names = $this->getCategoryNames($categoryIds);

        foreach ($result as $row) {
            $categoryId        = $row['category_id'];
            $name              = $names[$categoryId] ?? '(unknown)';
            $label             = (string)trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency']]);
            $chartData[$label] = ['amount' => $row['total'], 'currency_symbol' => $row['currency_symbol']];
        }

        $data = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Expenses grouped by category for account.
     *
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return JsonResponse
     */
    public function expenseCategoryAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        return $this->expenseCategory($account, $start, $end);
    }


    /**
     * Shows the balances for all the user's frontpage accounts.
     *
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function frontpage(AccountRepositoryInterface $repository): JsonResponse
    {
        $start      = clone session('start', Carbon::now()->startOfMonth());
        $end        = clone session('end', Carbon::now()->endOfMonth());
        $defaultSet = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET])->pluck('id')->toArray();
        Log::debug('Default set is ', $defaultSet);
        $frontPage = app('preferences')->get('frontPageAccounts', $defaultSet);


        Log::debug('Frontpage preference set is ', $frontPage->data);
        if (0 === \count($frontPage->data)) {
            $frontPage->data = $defaultSet;
            Log::debug('frontpage set is empty!');
            $frontPage->save();
        }
        $accounts = $repository->getAccountsById($frontPage->data);

        return response()->json($this->accountBalanceChart($accounts, $start, $end));
    }


    /**
     * Shows all income per account for each category.
     *
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return JsonResponse
     */
    public function incomeCategory(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.income-category');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        // grab all journals:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))->setRange($start, $end)->withCategoryInformation()->setTypes([TransactionType::DEPOSIT]);
        $transactions = $collector->getTransactions();
        $result       = [];
        $chartData    = [];
        $categoryIds  = [];
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $jrnlCatId     = (int)$transaction->transaction_journal_category_id;
            $transCatId    = (int)$transaction->transaction_category_id;
            $categoryId    = max($jrnlCatId, $transCatId);
            $currencyName  = $transaction->transaction_currency_name;
            $combi         = $categoryId . $currencyName;
            $categoryIds[] = $categoryId;
            if (!isset($result[$combi])) {
                $result[$combi] = [
                    'total'           => '0',
                    'category_id'     => $categoryId,
                    'currency'        => $currencyName,
                    'currency_symbol' => $transaction->transaction_currency_symbol,
                ];
            }
            $result[$combi]['total'] = bcadd($transaction->transaction_amount, $result[$combi]['total']);
        }

        $names = $this->getCategoryNames($categoryIds);
        foreach ($result as $row) {
            $categoryId        = $row['category_id'];
            $name              = $names[$categoryId] ?? '(unknown)';
            $label             = (string)trans('firefly.name_in_currency', ['name' => $name, 'currency' => $row['currency']]);
            $chartData[$label] = ['amount' => $row['total'], 'currency_symbol' => $row['currency_symbol']];
        }
        $data = $this->generator->multiCurrencyPieChart($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows the income grouped by category for an account, in all time.
     *
     * @param AccountRepositoryInterface $repository
     * @param Account                    $account
     *
     * @return JsonResponse
     */
    public function incomeCategoryAll(AccountRepositoryInterface $repository, Account $account): JsonResponse
    {
        $start = $repository->oldestJournalDate($account) ?? Carbon::now()->startOfMonth();
        $end   = Carbon::now();

        return $this->incomeCategory($account, $start, $end);
    }


    /**
     * Shows overview of account during a single period.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Account $account
     * @param Carbon  $start
     *
     * @param Carbon  $end
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function period(Account $account, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.account.period');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($account->id);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $step      = $this->calculateStep($start, $end);
        $chartData = [];
        $current   = clone $start;
        switch ($step) {
            case '1D':
                $format   = (string)trans('config.month_and_day');
                $range    = app('steam')->balanceInRange($account, $start, $end);
                $previous = array_values($range)[0];
                while ($end >= $current) {
                    $theDate           = $current->format('Y-m-d');
                    $balance           = $range[$theDate] ?? $previous;
                    $label             = $current->formatLocalized($format);
                    $chartData[$label] = (float)$balance;
                    $previous          = $balance;
                    $current->addDay();
                }
                break;
            // @codeCoverageIgnoreStart
            case '1W':
            case '1M':
            case '1Y':
                while ($end >= $current) {
                    $balance           = (float)app('steam')->balance($account, $current);
                    $label             = app('navigation')->periodShow($current, $step);
                    $chartData[$label] = $balance;
                    $current           = app('navigation')->addPeriod($current, $step, 0);
                }
                break;
            // @codeCoverageIgnoreEnd
        }
        $data = $this->generator->singleSet($account->name, $chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows the balances for a given set of dates and accounts.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return JsonResponse
     */
    public function report(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        return response()->json($this->accountBalanceChart($accounts, $start, $end));
    }


    /**
     * Shows the balances for all the user's revenue accounts.
     *
     * This chart is multi-currency aware.
     *
     * @return JsonResponse
     */
    public function revenueAccounts(): JsonResponse
    {
        /** @var Carbon $start */
        $start = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end   = clone session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.revenue-accounts');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $start->subDay();

        // prep some vars:
        $currencies = [];
        $chartData  = [];
        $tempData   = [];

        // grab all accounts and names
        $accounts     = $this->accountRepository->getAccountsByType([AccountType::REVENUE]);
        $accountNames = $this->extractNames($accounts);

        // grab all balances
        $startBalances = app('steam')->balancesPerCurrencyByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesPerCurrencyByAccounts($accounts, $end);

        // loop the end balances. This is an array for each account ($expenses)
        foreach ($endBalances as $accountId => $expenses) {
            $accountId = (int)$accountId;
            // loop each expense entry (each entry can be a different currency).
            foreach ($expenses as $currencyId => $endAmount) {
                $currencyId = (int)$currencyId;

                // see if there is an accompanying start amount.
                // grab the difference and find the currency.
                $startAmount             = $startBalances[$accountId][$currencyId] ?? '0';
                $diff                    = bcsub($endAmount, $startAmount);
                $currencies[$currencyId] = $currencies[$currencyId] ?? $this->currencyRepository->findNull($currencyId);
                if (0 !== bccomp($diff, '0')) {
                    // store the values in a temporary array.
                    $tempData[] = [
                        'name'        => $accountNames[$accountId],
                        'difference'  => $diff,
                        'diff_float'  => (float)$diff,
                        'currency_id' => $currencyId,
                    ];
                }
            }
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'diff_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $dataSet
                                    = [
                'label'           => (string)trans('firefly.earned'),
                'type'            => 'bar',
                'currency_symbol' => $currency->symbol,
                'entries'         => $this->expandNames($tempData),
            ];
            $chartData[$currencyId] = $dataSet;
        }

        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = bcmul($entry['difference'], '-1');
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows an overview of the account balances for a set of accounts.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function accountBalanceChart(Collection $accounts, Carbon $start, Carbon $end): array // chart helper method.
    {
        // chart properties for cache:
        $cache = new CacheProperties();
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.account.account-balance-chart');
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        Log::debug('Regenerate chart.account.account-balance-chart from scratch.');

        /** @var CurrencyRepositoryInterface $repository */
        $repository = app(CurrencyRepositoryInterface::class);
        /** @var AccountRepositoryInterface $accountRepos */
        $accountRepos = app(AccountRepositoryInterface::class);

        $default   = app('amount')->getDefaultCurrency();
        $chartData = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $currency = $repository->findNull((int)$accountRepos->getMetaValue($account, 'currency_id'));
            if (null === $currency) {
                $currency = $default;
            }
            $currentSet = [
                'label'           => $account->name,
                'currency_symbol' => $currency->symbol,
                'entries'         => [],
            ];

            $currentStart = clone $start;
            $range        = app('steam')->balanceInRange($account, $start, clone $end);
            $previous     = array_values($range)[0];
            while ($currentStart <= $end) {
                $format   = $currentStart->format('Y-m-d');
                $label    = $currentStart->formatLocalized((string)trans('config.month_and_day'));
                $balance  = isset($range[$format]) ? round($range[$format], 12) : $previous;
                $previous = $balance;
                $currentStart->addDay();
                $currentSet['entries'][$label] = $balance;
            }
            $chartData[] = $currentSet;
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return $data;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param array $names
     *
     * @return array
     */
    private function expandNames(array $names): array
    {
        $result = [];
        foreach ($names as $entry) {
            $result[$entry['name']] = 0;
        }

        return $result;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param Collection $accounts
     *
     * @return array
     */
    private function extractNames(Collection $accounts): array
    {
        $return = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $return[$account->id] = $account->name;
        }

        return $return;
    }
}
