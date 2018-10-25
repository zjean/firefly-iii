<?php
/**
 * BoxController.php
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

namespace FireflyIII\Http\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\RequestInformation;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * Class BoxController.
 */
class BoxController extends Controller
{
    use RequestInformation;

    /**
     * How much money user has available.
     *
     * @param BudgetRepositoryInterface $repository
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function available(BudgetRepositoryInterface $repository): JsonResponse
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end   = session('end', Carbon::now()->endOfMonth());
        $today = new Carbon;
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($today);
        $cache->addProperty('box-available');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        // get available amount
        $currency  = app('amount')->getDefaultCurrency();
        $available = $repository->getAvailableBudget($currency, $start, $end);

        // get spent amount:
        $budgets           = $repository->getActiveBudgets();
        $budgetInformation = $repository->collectBudgetInformation($budgets, $start, $end);
        $spent             = (string)array_sum(array_column($budgetInformation, 'spent'));
        $left              = bcadd($available, $spent);
        $days              = $today->diffInDays($end) + 1;
        $perDay            = '0';
        $text              = (string)trans('firefly.left_to_spend');
        $overspent         = false;
        if (bccomp($left, '0') === -1) {
            $text      = (string)trans('firefly.overspent');
            $overspent = true;
        }
        if (0 !== $days && bccomp($left, '0') > -1) {
            $perDay = bcdiv($left, (string)$days);
        }

        $return = [
            'perDay'    => app('amount')->formatAnything($currency, $perDay, false),
            'left'      => app('amount')->formatAnything($currency, $left, false),
            'text'      => $text,
            'overspent' => $overspent,
        ];

        $cache->store($return);

        return response()->json($return);
    }


    /**
     * Current total balance.
     *
     * @param CurrencyRepositoryInterface $repository
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function balance(CurrencyRepositoryInterface $repository): JsonResponse
    {
        // Cache result, return cache if present.
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end   = session('end', Carbon::now()->endOfMonth());
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-balance');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        // prep some arrays:
        $incomes  = [];
        $expenses = [];
        $sums     = [];

        // collect income of user:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::DEPOSIT])
                  ->withOpposingAccount();
        $set = $collector->getTransactions();
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId           = (int)$transaction->transaction_currency_id;
            $incomes[$currencyId] = $incomes[$currencyId] ?? '0';
            $incomes[$currencyId] = bcadd($incomes[$currencyId], $transaction->transaction_amount);
            $sums[$currencyId]    = $sums[$currencyId] ?? '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], $transaction->transaction_amount);
        }

        // collect expenses
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::WITHDRAWAL])
                  ->withOpposingAccount();
        $set = $collector->getTransactions();
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId            = (int)$transaction->transaction_currency_id;
            $expenses[$currencyId] = $expenses[$currencyId] ?? '0';
            $expenses[$currencyId] = bcadd($expenses[$currencyId], $transaction->transaction_amount);
            $sums[$currencyId]     = $sums[$currencyId] ?? '0';
            $sums[$currencyId]     = bcadd($sums[$currencyId], $transaction->transaction_amount);
        }

        // format amounts:
        $keys = array_keys($sums);
        foreach ($keys as $currencyId) {
            $currency              = $repository->findNull($currencyId);
            $sums[$currencyId]     = app('amount')->formatAnything($currency, $sums[$currencyId], false);
            $incomes[$currencyId]  = app('amount')->formatAnything($currency, $incomes[$currencyId] ?? '0', false);
            $expenses[$currencyId] = app('amount')->formatAnything($currency, $expenses[$currencyId] ?? '0', false);
        }
        if (0 === \count($sums)) {
            $currency                = app('amount')->getDefaultCurrency();
            $sums[$currency->id]     = app('amount')->formatAnything($currency, '0', false);
            $incomes[$currency->id]  = app('amount')->formatAnything($currency, '0', false);
            $expenses[$currency->id] = app('amount')->formatAnything($currency, '0', false);
        }

        $response = [
            'incomes'  => $incomes,
            'expenses' => $expenses,
            'sums'     => $sums,
            'size'     => \count($sums),
        ];


        $cache->store($response);

        return response()->json($response);
    }


    /**
     * Bills to pay and paid.
     *
     * @param BillRepositoryInterface $repository
     *
     * @return JsonResponse
     */
    public function bills(BillRepositoryInterface $repository): JsonResponse
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());

        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('box-bills');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        /*
         * Since both this method and the chart use the exact same data, we can suffice
         * with calling the one method in the bill repository that will get this amount.
         */
        $paidAmount   = bcmul($repository->getBillsPaidInRange($start, $end), '-1');
        $unpaidAmount = $repository->getBillsUnpaidInRange($start, $end); // will be a positive amount.
        $currency     = app('amount')->getDefaultCurrency();

        $return = [
            'paid'   => app('amount')->formatAnything($currency, $paidAmount, false),
            'unpaid' => app('amount')->formatAnything($currency, $unpaidAmount, false),
        ];
        $cache->store($return);

        return response()->json($return);
    }


    /**
     * Total user net worth.
     *
     * @return JsonResponse
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function netWorth(): JsonResponse
    {
        $date = Carbon::create()->startOfDay();

        // start and end in the future? use $end
        if ($this->notInSessionRange($date)) {
            /** @var Carbon $date */
            $date = session('end', Carbon::now()->endOfMonth());
        }

        /** @var NetWorthInterface $netWorthHelper */
        $netWorthHelper = app(NetWorthInterface::class);
        $netWorthHelper->setUser(auth()->user());

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $allAccounts = $accountRepository->getActiveAccountsByType(
            [AccountType::DEFAULT, AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD]
        );
        Log::debug(sprintf('Found %d accounts.', $allAccounts->count()));

        // filter list on preference of being included.
        $filtered = $allAccounts->filter(
            function (Account $account) use ($accountRepository) {
                $includeNetWorth = $accountRepository->getMetaValue($account, 'include_net_worth');
                $result          = null === $includeNetWorth ? true : '1' === $includeNetWorth;
                if (false === $result) {
                    Log::debug(sprintf('Will not include "%s" in net worth charts.', $account->name));
                }

                return $result;
            }
        );

        $netWorthSet = $netWorthHelper->getNetWorthByCurrency($filtered, $date);


        $return = [];
        foreach ($netWorthSet as $index => $data) {
            /** @var TransactionCurrency $currency */
            $currency = $data['currency'];
            $return[$currency->id] = app('amount')->formatAnything($currency, $data['balance'], false);
        }
        $return = [
            'net_worths' => array_values($return),
        ];

        return response()->json($return);
    }

}
