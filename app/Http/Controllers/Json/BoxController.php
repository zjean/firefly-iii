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
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
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
use Illuminate\Http\JsonResponse;

/**
 * Class BoxController.
 */
class BoxController extends Controller
{

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
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::DEPOSIT])
                  ->withOpposingAccount();
        $set = $collector->getJournals();
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $currencyId           = (int)$transaction->transaction_currency_id;
            $incomes[$currencyId] = $incomes[$currencyId] ?? '0';
            $incomes[$currencyId] = bcadd($incomes[$currencyId], $transaction->transaction_amount);
            $sums[$currencyId]    = $sums[$currencyId] ?? '0';
            $sums[$currencyId]    = bcadd($sums[$currencyId], $transaction->transaction_amount);
        }

        // collect expenses
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes([TransactionType::WITHDRAWAL])
                  ->withOpposingAccount();
        $set = $collector->getJournals();
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
        $end   = session('end', Carbon::now()->endOfMonth());

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
     * @param AccountRepositoryInterface $repository
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function netWorth(AccountRepositoryInterface $repository): JsonResponse
    {
        $date = new Carbon(date('Y-m-d')); // needed so its per day.

        // start and end in the future? use $end
        if ($this->notInSessionRange($date)) {
            /** @var Carbon $date */
            $date = session('end', Carbon::now()->endOfMonth());
        }

        // start in the past, end in the future? use $date
        $cache = new CacheProperties;
        $cache->addProperty($date);
        $cache->addProperty('box-net-worth');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $netWorth = [];
        $accounts = $repository->getActiveAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        $balances = app('steam')->balancesByAccounts($accounts, $date);

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountCurrency = $this->getCurrencyOrDefault($account);
            $balance         = $balances[$account->id] ?? '0';

            // if the account is a credit card, subtract the virtual balance from the balance,
            // to better reflect that this is not money that is actually "yours".
            $role           = (string)$repository->getMetaValue($account, 'accountRole');
            $virtualBalance = (string)$account->virtual_balance;
            if ('ccAsset' === $role && '' !== $virtualBalance && (float)$virtualBalance > 0) {
                $balance = bcsub($balance, $virtualBalance);
            }

            if (!isset($netWorth[$accountCurrency->id])) {
                $netWorth[$accountCurrency->id]['currency'] = $accountCurrency;
                $netWorth[$accountCurrency->id]['sum']      = '0';
            }
            $netWorth[$accountCurrency->id]['sum'] = bcadd($netWorth[$accountCurrency->id]['sum'], $balance);
        }

        $return = [];
        foreach ($netWorth as $currencyId => $data) {
            $return[$currencyId] = app('amount')->formatAnything($data['currency'], $data['sum'], false);
        }
        $return = [
            'net_worths' => array_values($return),
        ];

        $cache->store($return);

        return response()->json($return);
    }

    /**
     * Get a currency or return default currency.
     *
     * @param Account $account
     *
     * @return TransactionCurrency
     */
    private function getCurrencyOrDefault(Account $account): TransactionCurrency
    {
        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        /** @var CurrencyRepositoryInterface $currencyRepos */
        $currencyRepos = app(CurrencyRepositoryInterface::class);

        $currency        = app('amount')->getDefaultCurrency();
        $accountCurrency = null;
        $currencyId      = (int)$repository->getMetaValue($account, 'currency_id');
        if (0 !== $currencyId) {
            $accountCurrency = $currencyRepos->findNull($currencyId);
        }
        if (null === $accountCurrency) {
            $accountCurrency = $currency;
        }

        return $accountCurrency;
    }

    /**
     * Check if date is outside session range.
     *
     * @param Carbon $date
     *
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function notInSessionRange(Carbon $date): bool
    {
        /** @var Carbon $start */
        $start = session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end    = session('end', Carbon::now()->endOfMonth());
        $result = false;
        if ($start->greaterThanOrEqualTo($date) && $end->greaterThanOrEqualTo($date)) {
            $result = true;
        }
        // start and end in the past? use $end
        if ($start->lessThanOrEqualTo($date) && $end->lessThanOrEqualTo($date)) {
            $result = true;
        }

        return $result;
    }
}
