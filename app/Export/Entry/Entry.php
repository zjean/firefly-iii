<?php

/**
 * Entry.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace FireflyIII\Export\Entry;

use FireflyIII\Models\Transaction;

/**
 * To extend the exported object, in case of new features in Firefly III for example,
 * do the following:.
 *
 * - Add the field(s) to this class. If you add more than one related field, add a new object.
 * - Make sure the "fromJournal"-routine fills these fields.
 * - Add them to the static function that returns its type (key=value. Remember that the only
 *   valid types can be found in config/csv.php (under "roles").
 *
 * These new entries should be should be strings and numbers as much as possible.
 *
 *
 *
 * Class Entry
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.TooManyFields)
 *
 * @codeCoverageIgnore
 * @deprecated
 */
final class Entry
{
    /** @var int ID of the journal */
    public $journal_id;
    /** @var int ID of the transaction */
    public $transaction_id = 0;
    /** @var string The date. */
    public $date;
    /** @var string The description */
    public $description;
    /** @var string The currency code. */
    public $currency_code;
    /** @var string The amount. */
    public $amount;
    /** @var string The foreign currency code */
    public $foreign_currency_code = '';
    /** @var string Foreign amount */
    public $foreign_amount = '0';
    /** @var string Transaction type */
    public $transaction_type;
    /** @var string Asset account ID */
    public $asset_account_id;
    /** @var string Asset account name */
    public $asset_account_name;
    /** @var string Asset account IBAN */
    public $asset_account_iban;
    /** @var string Asset account BIC */
    public $asset_account_bic;
    /** @var string Asset account number */
    public $asset_account_number;
    /** @var string Asset account currency code */
    public $asset_currency_code;
    /** @var string Opposing account ID */
    public $opposing_account_id;
    /** @var string Opposing account name */
    public $opposing_account_name;
    /** @var string Opposing account IBAN */
    public $opposing_account_iban;
    /** @var string Opposing account BIC */
    public $opposing_account_bic;
    /** @var string Opposing account number */
    public $opposing_account_number;
    /** @var string Opposing account code */
    public $opposing_currency_code;
    /** @var string Budget ID */
    public $budget_id;
    /** @var string Budget name */
    public $budget_name;
    /** @var string Category ID */
    public $category_id;
    /** @var string Category name */
    public $category_name;
    /** @var string Bill ID */
    public $bill_id;
    /** @var string Bill name */
    public $bill_name;
    /** @var string Notes */
    public $notes;
    /** @var string Tags */
    public $tags;

    /**
     * Entry constructor.
     */
    private function __construct()
    {
    }

    /**
     * Converts a given transaction (as collected by the collector) into an export entry.
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // complex but little choice.
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // cannot be helped
     *
     * @param Transaction $transaction
     *
     * @return Entry
     */
    public static function fromTransaction(Transaction $transaction): Entry
    {
        $entry                 = new self();
        $entry->journal_id     = $transaction->journal_id;
        $entry->transaction_id = $transaction->id;
        $entry->date           = $transaction->date->format('Ymd');
        $entry->description    = $transaction->description;
        if (\strlen((string)$transaction->transaction_description) > 0) {
            $entry->description = $transaction->transaction_description . '(' . $transaction->description . ')';
        }
        $entry->currency_code = $transaction->transactionCurrency->code;
        $entry->amount        = (string)round($transaction->transaction_amount, $transaction->transactionCurrency->decimal_places);

        $entry->foreign_currency_code = null === $transaction->foreign_currency_id ? null : $transaction->foreignCurrency->code;
        $entry->foreign_amount        = null === $transaction->foreign_currency_id
            ? null
            : (string)round(
                $transaction->transaction_foreign_amount,
                $transaction->foreignCurrency->decimal_places
            );

        $entry->transaction_type     = $transaction->transaction_type_type;
        $entry->asset_account_id     = (string)$transaction->account_id;
        $entry->asset_account_name   = app('steam')->tryDecrypt($transaction->account_name);
        $entry->asset_account_iban   = $transaction->account_iban;
        $entry->asset_account_number = $transaction->account_number;
        $entry->asset_account_bic    = $transaction->account_bic;
        $entry->asset_currency_code  = $transaction->account_currency_code;

        $entry->opposing_account_id     = (string)$transaction->opposing_account_id;
        $entry->opposing_account_name   = app('steam')->tryDecrypt($transaction->opposing_account_name);
        $entry->opposing_account_iban   = $transaction->opposing_account_iban;
        $entry->opposing_account_number = $transaction->opposing_account_number;
        $entry->opposing_account_bic    = $transaction->opposing_account_bic;
        $entry->opposing_currency_code  = $transaction->opposing_currency_code;

        // budget
        $entry->budget_id   = (string)$transaction->transaction_budget_id;
        $entry->budget_name = app('steam')->tryDecrypt($transaction->transaction_budget_name);
        if (null === $transaction->transaction_budget_id) {
            $entry->budget_id   = $transaction->transaction_journal_budget_id;
            $entry->budget_name = app('steam')->tryDecrypt($transaction->transaction_journal_budget_name);
        }

        // category
        $entry->category_id   = (string)$transaction->transaction_category_id;
        $entry->category_name = app('steam')->tryDecrypt($transaction->transaction_category_name);
        if (null === $transaction->transaction_category_id) {
            $entry->category_id   = $transaction->transaction_journal_category_id;
            $entry->category_name = app('steam')->tryDecrypt($transaction->transaction_journal_category_name);
        }

        // budget
        $entry->bill_id   = (string)$transaction->bill_id;
        $entry->bill_name = app('steam')->tryDecrypt($transaction->bill_name);

        $entry->tags  = $transaction->tags;
        $entry->notes = $transaction->notes;

        return $entry;
    }
}
