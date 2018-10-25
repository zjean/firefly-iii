<?php
/**
 * ExpenseController.php
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

namespace FireflyIII\Http\Controllers\Report;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\AugumentData;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class ExpenseController
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ExpenseController extends Controller
{
    use AugumentData;

    /** @var AccountRepositoryInterface The account repository */
    protected $accountRepository;

    /**
     * Constructor for ExpenseController
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                $this->accountRepository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Generates the overview per budget.
     *
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function budget(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): string
    {
        // Properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-budget');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($expense->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $combined = $this->combineAccounts($expense);
        $all      = new Collection;
        foreach ($combined as $combi) {
            $all = $all->merge($combi);
        }
        // now find spent / earned:
        $spent = $this->spentByBudget($accounts, $all, $start, $end);
        // join arrays somehow:
        $together = [];
        foreach ($spent as $categoryId => $spentInfo) {
            if (!isset($together[$categoryId])) {
                $together[$categoryId]['spent']       = $spentInfo;
                $together[$categoryId]['budget']      = $spentInfo['name'];
                $together[$categoryId]['grand_total'] = '0';
            }
            $together[$categoryId]['grand_total'] = bcadd($spentInfo['grand_total'], $together[$categoryId]['grand_total']);
        }
        try {
            $result = view('reports.partials.exp-budgets', compact('together'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::budget: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }


    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Generates the overview per category (spent and earned).
     *
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function category(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): string
    {
        // Properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-category');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($expense->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $combined = $this->combineAccounts($expense);
        $all      = new Collection;
        foreach ($combined as $combi) {
            $all = $all->merge($combi);
        }
        // now find spent / earned:
        $spent  = $this->spentByCategory($accounts, $all, $start, $end);
        $earned = $this->earnedByCategory($accounts, $all, $start, $end);
        // join arrays somehow:
        $together = [];
        foreach ($spent as $categoryId => $spentInfo) {
            if (!isset($together[$categoryId])) {
                $together[$categoryId]['spent']       = $spentInfo;
                $together[$categoryId]['category']    = $spentInfo['name'];
                $together[$categoryId]['grand_total'] = '0';
            }
            $together[$categoryId]['grand_total'] = bcadd($spentInfo['grand_total'], $together[$categoryId]['grand_total']);
        }
        foreach ($earned as $categoryId => $earnedInfo) {
            if (!isset($together[$categoryId])) {
                $together[$categoryId]['earned']      = $earnedInfo;
                $together[$categoryId]['category']    = $earnedInfo['name'];
                $together[$categoryId]['grand_total'] = '0';
            }
            $together[$categoryId]['grand_total'] = bcadd($earnedInfo['grand_total'], $together[$categoryId]['grand_total']);
        }
        try {
            $result = view('reports.partials.exp-categories', compact('together'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Overview of spending.
     *
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array|mixed|string
     */
    public function spent(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-spent');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($expense->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $combined = $this->combineAccounts($expense);
        $result   = [];

        foreach ($combined as $name => $combi) {
            /**
             * @var string
             * @var Collection $combi
             */
            $spent         = $this->spentInPeriod($accounts, $combi, $start, $end);
            $earned        = $this->earnedInPeriod($accounts, $combi, $start, $end);
            $result[$name] = [
                'spent'  => $spent,
                'earned' => $earned,
            ];
        }
        try {
            $result = view('reports.partials.exp-not-grouped', compact('result'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::expenses: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
        // for period, get spent and earned for each account (by name)
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * List of top expenses.
     *
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function topExpense(Collection $accounts, Collection $expense, Carbon $start, Carbon $end): string
    {
        // Properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-budget');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($expense->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $combined = $this->combineAccounts($expense);
        $all      = new Collection;
        foreach ($combined as $combi) {
            $all = $all->merge($combi);
        }
        // get all expenses in period:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($accounts);
        $collector->setOpposingAccounts($all);
        $set    = $collector->getTransactions();
        $sorted = $set->sortBy(
            function (Transaction $transaction) {
                return (float)$transaction->transaction_amount;
            }
        );
        try {
            $result = view('reports.partials.top-transactions', compact('sorted'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::topExpense: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * List of top income.
     *
     * @param Collection $accounts
     * @param Collection $expense
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return mixed|string
     */
    public function topIncome(Collection $accounts, Collection $expense, Carbon $start, Carbon $end)
    {
        // Properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('expense-budget');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($expense->pluck('id')->toArray());
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $combined = $this->combineAccounts($expense);
        $all      = new Collection;
        foreach ($combined as $combi) {
            $all = $all->merge($combi);
        }
        // get all expenses in period:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($accounts);
        $collector->setOpposingAccounts($all);
        $set    = $collector->getTransactions();
        $sorted = $set->sortByDesc(
            function (Transaction $transaction) {
                return (float)$transaction->transaction_amount;
            }
        );
        try {
            $result = view('reports.partials.top-transactions', compact('sorted'))->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $e) {
            Log::error(sprintf('Could not render category::topIncome: %s', $e->getMessage()));
            $result = 'An error prevented Firefly III from rendering. Apologies.';
        }
        // @codeCoverageIgnoreEnd
        $cache->store($result);

        return $result;
    }



    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Group by category (earnings).
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function earnedByCategory(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($assets);
        $collector->setOpposingAccounts($opposing)->withCategoryInformation();
        $set = $collector->getTransactions();
        $sum = [];
        // loop to support multi currency
        foreach ($set as $transaction) {
            $currencyId   = $transaction->transaction_currency_id;
            $categoryName = $transaction->transaction_category_name;
            $categoryId   = (int)$transaction->transaction_category_id;
            // if null, grab from journal:
            if (0 === $categoryId) {
                $categoryName = $transaction->transaction_journal_category_name;
                $categoryId   = (int)$transaction->transaction_journal_category_id;
            }
            if (0 !== $categoryId) {
                $categoryName = app('steam')->tryDecrypt($categoryName);
            }

            // if not set, set to zero:
            if (!isset($sum[$categoryId][$currencyId])) {
                $sum[$categoryId] = [
                    'grand_total'  => '0',
                    'name'         => $categoryName,
                    'per_currency' => [
                        $currencyId => [
                            'sum'      => '0',
                            'category' => [
                                'id'   => $categoryId,
                                'name' => $categoryName,
                            ],
                            'currency' => [
                                'symbol' => $transaction->transaction_currency_symbol,
                                'dp'     => $transaction->transaction_currency_dp,
                            ],
                        ],
                    ],
                ];
            }

            // add amount
            $sum[$categoryId]['per_currency'][$currencyId]['sum'] = bcadd(
                $sum[$categoryId]['per_currency'][$currencyId]['sum'], $transaction->transaction_amount
            );
            $sum[$categoryId]['grand_total']                      = bcadd($sum[$categoryId]['grand_total'], $transaction->transaction_amount);
        }

        return $sum;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Earned in period for accounts.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    protected function earnedInPeriod(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::DEPOSIT])->setAccounts($assets);
        $collector->setOpposingAccounts($opposing);
        $set = $collector->getTransactions();
        $sum = [
            'grand_sum'    => '0',
            'per_currency' => [],
        ];
        // loop to support multi currency
        foreach ($set as $transaction) {
            $currencyId = $transaction->transaction_currency_id;

            // if not set, set to zero:
            if (!isset($sum['per_currency'][$currencyId])) {
                $sum['per_currency'][$currencyId] = [
                    'sum'      => '0',
                    'currency' => [
                        'symbol' => $transaction->transaction_currency_symbol,
                        'dp'     => $transaction->transaction_currency_dp,
                    ],
                ];
            }

            // add amount
            $sum['per_currency'][$currencyId]['sum'] = bcadd($sum['per_currency'][$currencyId]['sum'], $transaction->transaction_amount);
            $sum['grand_sum']                        = bcadd($sum['grand_sum'], $transaction->transaction_amount);
        }

        return $sum;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Spent by budget.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function spentByBudget(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($assets);
        $collector->setOpposingAccounts($opposing)->withBudgetInformation();
        $set = $collector->getTransactions();
        $sum = [];
        // loop to support multi currency
        foreach ($set as $transaction) {
            $currencyId = $transaction->transaction_currency_id;
            $budgetName = $transaction->transaction_budget_name;
            $budgetId   = (int)$transaction->transaction_budget_id;
            // if null, grab from journal:
            if (0 === $budgetId) {
                $budgetName = $transaction->transaction_journal_budget_name;
                $budgetId   = (int)$transaction->transaction_journal_budget_id;
            }
            if (0 !== $budgetId) {
                $budgetName = app('steam')->tryDecrypt($budgetName);
            }

            // if not set, set to zero:
            if (!isset($sum[$budgetId][$currencyId])) {
                $sum[$budgetId] = [
                    'grand_total'  => '0',
                    'name'         => $budgetName,
                    'per_currency' => [
                        $currencyId => [
                            'sum'      => '0',
                            'budget'   => [
                                'id'   => $budgetId,
                                'name' => $budgetName,
                            ],
                            'currency' => [
                                'symbol' => $transaction->transaction_currency_symbol,
                                'dp'     => $transaction->transaction_currency_dp,
                            ],
                        ],
                    ],
                ];
            }

            // add amount
            $sum[$budgetId]['per_currency'][$currencyId]['sum'] = bcadd(
                $sum[$budgetId]['per_currency'][$currencyId]['sum'], $transaction->transaction_amount
            );
            $sum[$budgetId]['grand_total']                      = bcadd($sum[$budgetId]['grand_total'], $transaction->transaction_amount);
        }

        return $sum;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Spent by category.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function spentByCategory(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($assets);
        $collector->setOpposingAccounts($opposing)->withCategoryInformation();
        $set = $collector->getTransactions();
        $sum = [];
        // loop to support multi currency
        foreach ($set as $transaction) {
            $currencyId   = $transaction->transaction_currency_id;
            $categoryName = $transaction->transaction_category_name;
            $categoryId   = (int)$transaction->transaction_category_id;
            // if null, grab from journal:
            if (0 === $categoryId) {
                $categoryName = $transaction->transaction_journal_category_name;
                $categoryId   = (int)$transaction->transaction_journal_category_id;
            }
            if (0 !== $categoryId) {
                $categoryName = app('steam')->tryDecrypt($categoryName);
            }

            // if not set, set to zero:
            if (!isset($sum[$categoryId][$currencyId])) {
                $sum[$categoryId] = [
                    'grand_total'  => '0',
                    'name'         => $categoryName,
                    'per_currency' => [
                        $currencyId => [
                            'sum'      => '0',
                            'category' => [
                                'id'   => $categoryId,
                                'name' => $categoryName,
                            ],
                            'currency' => [
                                'symbol' => $transaction->transaction_currency_symbol,
                                'dp'     => $transaction->transaction_currency_dp,
                            ],
                        ],
                    ],
                ];
            }

            // add amount
            $sum[$categoryId]['per_currency'][$currencyId]['sum'] = bcadd(
                $sum[$categoryId]['per_currency'][$currencyId]['sum'], $transaction->transaction_amount
            );
            $sum[$categoryId]['grand_total']                      = bcadd($sum[$categoryId]['grand_total'], $transaction->transaction_amount);
        }

        return $sum;
    }

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * Spent in a period.
     *
     * @param Collection $assets
     * @param Collection $opposing
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    protected function spentInPeriod(Collection $assets, Collection $opposing, Carbon $start, Carbon $end): array // get data + augment with info
    {
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->setAccounts($assets);
        $collector->setOpposingAccounts($opposing);
        $set = $collector->getTransactions();
        $sum = [
            'grand_sum'    => '0',
            'per_currency' => [],
        ];
        // loop to support multi currency
        foreach ($set as $transaction) {
            $currencyId = (int)$transaction->transaction_currency_id;

            // if not set, set to zero:
            if (!isset($sum['per_currency'][$currencyId])) {
                $sum['per_currency'][$currencyId] = [
                    'sum'      => '0',
                    'currency' => [
                        'symbol' => $transaction->transaction_currency_symbol,
                        'dp'     => $transaction->transaction_currency_dp,
                    ],
                ];
            }

            // add amount
            $sum['per_currency'][$currencyId]['sum'] = bcadd($sum['per_currency'][$currencyId]['sum'], $transaction->transaction_amount);
            $sum['grand_sum']                        = bcadd($sum['grand_sum'], $transaction->transaction_amount);
        }

        return $sum;
    }
}
