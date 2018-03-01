<?php
/**
 * TransactionJournal.php
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

namespace FireflyIII\Support\Twig\Extension;

use FireflyIII\Models\Transaction as TransactionModel;
use FireflyIII\Models\TransactionJournal as JournalModel;
use FireflyIII\Models\TransactionType;
use Twig_Extension;

/**
 * Class TransactionJournal
 */
class TransactionJournal extends Twig_Extension
{
    /**
     * @param JournalModel $journal
     *
     * @return string
     */
    public function totalAmount(JournalModel $journal): string
    {
        $transactions = $journal->transactions()->where('amount', '>', 0)->get();
        $totals       = [];
        $type         = $journal->transactionType->type;
        /** @var TransactionModel $transaction */
        foreach ($transactions as $transaction) {
            $currencyId = $transaction->transaction_currency_id;
            $currency   = $transaction->transactionCurrency;

            if (!isset($totals[$currencyId])) {
                $totals[$currencyId] = [
                    'amount'   => '0',
                    'currency' => $currency,
                ];
            }
            $totals[$currencyId]['amount'] = bcadd($transaction->amount, $totals[$currencyId]['amount']);

            if (null !== $transaction->foreign_currency_id) {
                $foreignId = $transaction->foreign_currency_id;
                $foreign   = $transaction->foreignCurrency;
                if (!isset($totals[$foreignId])) {
                    $totals[$foreignId] = [
                        'amount'   => '0',
                        'currency' => $foreign,
                    ];
                }
                $totals[$foreignId]['amount'] = bcadd($transaction->foreign_amount, $totals[$foreignId]['amount']);
            }
        }
        $array = [];
        foreach ($totals as $total) {
            if (TransactionType::WITHDRAWAL === $type) {
                $total['amount'] = bcmul($total['amount'], '-1');
            }
            $array[] = app('amount')->formatAnything($total['currency'], $total['amount']);
        }
        $txt = join(' / ', $array);

        return $txt;
    }
}
