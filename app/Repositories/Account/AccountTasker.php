<?php
/**
 * AccountTasker.php
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

namespace FireflyIII\Repositories\Account;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;
use Steam;

/**
 * Class AccountTasker.
 */
class AccountTasker implements AccountTaskerInterface
{
    /** @var User */
    private $user;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getAccountReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $yesterday = clone $start;
        $yesterday->subDay();
        $startSet = Steam::balancesByAccounts($accounts, $yesterday);
        $endSet   = Steam::balancesByAccounts($accounts, $end);

        Log::debug('Start of accountreport');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);

        $return = [
            'start'      => '0',
            'end'        => '0',
            'difference' => '0',
            'accounts'   => [],
        ];

        foreach ($accounts as $account) {
            $id    = $account->id;
            $entry = [
                'name'          => $account->name,
                'id'            => $account->id,
                'start_balance' => '0',
                'end_balance'   => '0',
            ];

            // get first journal date:
            $first                  = $repository->oldestJournal($account);
            $entry['start_balance'] = $startSet[$account->id] ?? '0';
            $entry['end_balance']   = $endSet[$account->id] ?? '0';

            // first journal exists, and is on start, then this is the actual opening balance:
            if (null !== $first->id && $first->date->isSameDay($start)) {
                Log::debug(sprintf('Date of first journal for %s is %s', $account->name, $first->date->format('Y-m-d')));
                $entry['start_balance'] = $first->transactions()->where('account_id', $account->id)->first()->amount;
                Log::debug(sprintf('Account %s was opened on %s, so opening balance is %f', $account->name, $start->format('Y-m-d'), $entry['start_balance']));
            }
            $return['start'] = bcadd($return['start'], $entry['start_balance']);
            $return['end']   = bcadd($return['end'], $entry['end_balance']);

            $return['accounts'][$id] = $entry;
        }

        $return['difference'] = bcsub($return['end'], $return['start']);

        return $return;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getExpenseReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getJournals();
        $transactions = $transactions->filter(
            function (Transaction $transaction) {
                // return negative amounts only.
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return false;
            }
        );
        $expenses     = $this->groupByOpposing($transactions);

        // sort the result
        // Obtain a list of columns
        $sum = [];
        foreach ($expenses as $accountId => $row) {
            $sum[$accountId] = (float)$row['sum'];
        }

        array_multisort($sum, SORT_ASC, $expenses);

        return $expenses;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getIncomeReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        // get all expenses for the given accounts in the given period!
        // also transfers!
        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::DEPOSIT, TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $transactions = $collector->getJournals();
        $transactions = $transactions->filter(
            function (Transaction $transaction) {
                // return positive amounts only.
                if (1 === bccomp($transaction->transaction_amount, '0')) {
                    return $transaction;
                }

                return false;
            }
        );
        $income       = $this->groupByOpposing($transactions);

        // sort the result
        // Obtain a list of columns
        $sum = [];
        foreach ($income as $accountId => $row) {
            $sum[$accountId] = (float)$row['sum'];
        }

        array_multisort($sum, SORT_DESC, $income);

        return $income;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Collection $transactions
     *
     * @return array
     */
    private function groupByOpposing(Collection $transactions): array
    {
        $expenses = [];
        // join the result together:
        foreach ($transactions as $transaction) {
            $opposingId = $transaction->opposing_account_id;
            $name       = $transaction->opposing_account_name;
            if (!isset($expenses[$opposingId])) {
                $expenses[$opposingId] = [
                    'id'      => $opposingId,
                    'name'    => $name,
                    'sum'     => '0',
                    'average' => '0',
                    'count'   => 0,
                ];
            }
            $expenses[$opposingId]['sum'] = bcadd($expenses[$opposingId]['sum'], $transaction->transaction_amount);
            ++$expenses[$opposingId]['count'];
        }
        // do averages:
        $keys = array_keys($expenses);
        foreach ($keys as $key) {
            if ($expenses[$key]['count'] > 1) {
                $expenses[$key]['average'] = bcdiv($expenses[$key]['sum'], (string)$expenses[$key]['count']);
            }
        }

        return $expenses;
    }
}
