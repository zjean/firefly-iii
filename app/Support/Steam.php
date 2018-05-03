<?php
/**
 * Steam.php
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

namespace FireflyIII\Support;

use Carbon\Carbon;
use Crypt;
use DB;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Collection;

/**
 * Class Steam.
 */
class Steam
{
    /**
     * @param \FireflyIII\Models\Account $account
     * @param \Carbon\Carbon             $date
     *
     * @return string
     */
    public function balance(Account $account, Carbon $date): string
    {
        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty('balance');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyId = (int)$account->getMeta('currency_id');
        // use system default currency:
        if (0 === $currencyId) {
            $currency   = app('amount')->getDefaultCurrencyByUser($account->user);
            $currencyId = $currency->id;
        }
        // first part: get all balances in own currency:
        $nativeBalance = (string)$account->transactions()
                                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                         ->where('transaction_journals.date', '<=', $date->format('Y-m-d 23:59:59'))
                                         ->where('transactions.transaction_currency_id', $currencyId)
                                         ->sum('transactions.amount');

        // get all balances in foreign currency:
        $foreignBalance = (string)$account->transactions()
                                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                          ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                                          ->where('transactions.foreign_currency_id', $currencyId)
                                          ->where('transactions.transaction_currency_id', '!=', $currencyId)
                                          ->sum('transactions.foreign_amount');
        $balance        = bcadd($nativeBalance, $foreignBalance);
        $virtual        = null === $account->virtual_balance ? '0' : (string)$account->virtual_balance;
        $balance        = bcadd($balance, $virtual);
        $cache->store($balance);

        return $balance;
    }

    /**
     * @param \FireflyIII\Models\Account $account
     * @param \Carbon\Carbon             $date
     *
     * @return string
     */
    public function balanceIgnoreVirtual(Account $account, Carbon $date): string
    {
        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty('balance-no-virtual');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyId = (int)$account->getMeta('currency_id');

        $nativeBalance = (string)$account->transactions()
                                         ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                         ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                                         ->where('transactions.transaction_currency_id', $currencyId)
                                         ->sum('transactions.amount');

        // get all balances in foreign currency:
        $foreignBalance = (string)$account->transactions()
                                          ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                          ->where('transaction_journals.date', '<=', $date->format('Y-m-d'))
                                          ->where('transactions.foreign_currency_id', $currencyId)
                                          ->where('transactions.transaction_currency_id', '!=', $currencyId)
                                          ->sum('transactions.foreign_amount');
        $balance        = bcadd($nativeBalance, $foreignBalance);

        $cache->store($balance);

        return $balance;
    }

    /**
     * Gets the balance for the given account during the whole range, using this format:.
     *
     * [yyyy-mm-dd] => 123,2
     *
     * @param \FireflyIII\Models\Account $account
     * @param \Carbon\Carbon             $start
     * @param \Carbon\Carbon             $end
     *
     * @return array
     */
    public function balanceInRange(Account $account, Carbon $start, Carbon $end): array
    {
        // abuse chart properties:
        $cache = new CacheProperties;
        $cache->addProperty($account->id);
        $cache->addProperty('balance-in-range');
        $cache->addProperty($start);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $start->subDay();
        $end->addDay();
        $balances     = [];
        $formatted    = $start->format('Y-m-d');
        $startBalance = $this->balance($account, $start);

        $balances[$formatted] = $startBalance;
        $currencyId           = (int)$account->getMeta('currency_id');
        $start->addDay();

        // query!
        $set = $account->transactions()
                       ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                       ->where('transaction_journals.date', '>=', $start->format('Y-m-d 00:00:00'))
                       ->where('transaction_journals.date', '<=', $end->format('Y-m-d  23:59:59'))
                       ->groupBy('transaction_journals.date')
                       ->groupBy('transactions.transaction_currency_id')
                       ->groupBy('transactions.foreign_currency_id')
                       ->orderBy('transaction_journals.date', 'ASC')
                       ->whereNull('transaction_journals.deleted_at')
                       ->get(
                           [
                               'transaction_journals.date',
                               'transactions.transaction_currency_id',
                               DB::raw('SUM(transactions.amount) AS modified'),
                               'transactions.foreign_currency_id',
                               DB::raw('SUM(transactions.foreign_amount) AS modified_foreign'),
                           ]
                       );

        $currentBalance = $startBalance;
        /** @var Transaction $entry */
        foreach ($set as $entry) {
            // normal amount and foreign amount
            $modified        = null === $entry->modified ? '0' : (string)$entry->modified;
            $foreignModified = null === $entry->modified_foreign ? '0' : (string)$entry->modified_foreign;
            $amount          = '0';
            if ($currencyId === (int)$entry->transaction_currency_id || 0 === $currencyId) {
                // use normal amount:
                $amount = $modified;
            }
            if ($currencyId === (int)$entry->foreign_currency_id) {
                // use foreign amount:
                $amount = $foreignModified;
            }

            $currentBalance  = bcadd($currentBalance, $amount);
            $carbon          = new Carbon($entry->date);
            $date            = $carbon->format('Y-m-d');
            $balances[$date] = $currentBalance;
        }

        $cache->store($balances);

        return $balances;
    }

    /**
     * This method always ignores the virtual balance.
     *
     * @param \Illuminate\Support\Collection $accounts
     * @param \Carbon\Carbon                 $date
     *
     * @return array
     */
    public function balancesByAccounts(Collection $accounts, Carbon $date): array
    {
        $ids = $accounts->pluck('id')->toArray();
        // cache this property.
        $cache = new CacheProperties;
        $cache->addProperty($ids);
        $cache->addProperty('balances');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        // need to do this per account.
        $result = [];
        /** @var Account $account */
        foreach ($accounts as $account) {
            $result[$account->id] = $this->balance($account, $date);
        }

        $cache->store($result);

        return $result;
    }

    /**
     * @param int $isEncrypted
     * @param     $value
     *
     * @return string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decrypt(int $isEncrypted, string $value)
    {
        if (1 === $isEncrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @param array $accounts
     *
     * @return array
     */
    public function getLastActivities(array $accounts): array
    {
        $list = [];

        $set = auth()->user()->transactions()
                     ->whereIn('transactions.account_id', $accounts)
                     ->groupBy(['transactions.account_id', 'transaction_journals.user_id'])
                     ->get(['transactions.account_id', DB::raw('MAX(transaction_journals.date) AS max_date')]);

        foreach ($set as $entry) {
            $list[(int)$entry->account_id] = new Carbon($entry->max_date);
        }

        return $list;
    }

    /**
     * @param string $amount
     *
     * @return string
     */
    public function negative(string $amount): string
    {
        if (1 === bccomp($amount, '0')) {
            $amount = bcmul($amount, '-1');
        }

        return $amount;
    }

    /**
     * @param string $amount
     *
     * @return string|null
     */
    public function opposite(string $amount = null): ?string
    {
        if (null === $amount) {
            return null;
        }
        $amount = bcmul($amount, '-1');

        return $amount;
    }

    /**
     * @param $string
     *
     * @return int
     */
    public function phpBytes($string): int
    {
        $string = strtolower($string);

        if (!(false === stripos($string, 'k'))) {
            // has a K in it, remove the K and multiply by 1024.
            $bytes = bcmul(rtrim($string, 'kK'), '1024');

            return (int)$bytes;
        }

        if (!(false === stripos($string, 'm'))) {
            // has a M in it, remove the M and multiply by 1048576.
            $bytes = bcmul(rtrim($string, 'mM'), '1048576');

            return (int)$bytes;
        }

        if (!(false === stripos($string, 'g'))) {
            // has a G in it, remove the G and multiply by (1024)^3.
            $bytes = bcmul(rtrim($string, 'gG'), '1073741824');

            return (int)$bytes;
        }

        return (int)$string;
    }

    /**
     * @param string $amount
     *
     * @return string
     */
    public function positive(string $amount): string
    {
        if (bccomp($amount, '0') === -1) {
            $amount = bcmul($amount, '-1');
        }

        return $amount;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function tryDecrypt($value)
    {
        try {
            $value = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            // do not care.
        }

        return $value;
    }
}
