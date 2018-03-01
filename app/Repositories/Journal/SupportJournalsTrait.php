<?php
/**
 * SupportJournalsTrait.php
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

namespace FireflyIII\Repositories\Journal;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Log;

/**
 * Trait SupportJournalsTrait.
 */
trait SupportJournalsTrait
{
    /**
     * @param User            $user
     * @param TransactionType $type
     * @param array           $data
     *
     * @return array
     *
     * @throws FireflyException
     */
    protected function storeAccounts(User $user, TransactionType $type, array $data): array
    {
        $accounts = [
            'source'      => null,
            'destination' => null,
        ];

        Log::debug(sprintf('Going to store accounts for type %s', $type->type));
        switch ($type->type) {
            case TransactionType::WITHDRAWAL:
                $accounts = $this->storeWithdrawalAccounts($user, $data);
                break;

            case TransactionType::DEPOSIT:
                $accounts = $this->storeDepositAccounts($user, $data);

                break;
            case TransactionType::TRANSFER:
                $accounts['source']      = Account::where('user_id', $user->id)->where('id', $data['source_account_id'])->first();
                $accounts['destination'] = Account::where('user_id', $user->id)->where('id', $data['destination_account_id'])->first();
                break;
            case TransactionType::RECONCILIATION:
                $accounts['source']      = $data['source'];
                $accounts['destination'] = $data['destination'];
                unset($data['source'], $data['destination']);
                break;
            default:
                throw new FireflyException(sprintf('Did not recognise transaction type "%s".', $type->type));
        }

        if (null === $accounts['source']) {
            Log::error('"source"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"source"-account is null, so we cannot continue!');
        }

        if (null === $accounts['destination']) {
            Log::error('"destination"-account is null, so we cannot continue!', ['data' => $data]);
            throw new FireflyException('"destination"-account is null, so we cannot continue!');
        }

        return $accounts;
    }

    /**
     * @param TransactionJournal $journal
     * @param int                $budgetId
     */
    protected function storeBudgetWithJournal(TransactionJournal $journal, int $budgetId)
    {
        if (intval($budgetId) > 0 && TransactionType::WITHDRAWAL === $journal->transactionType->type) {
            /** @var \FireflyIII\Models\Budget $budget */
            $budget = Budget::find($budgetId);
            $journal->budgets()->save($budget);
        }
        $journal->touch();
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $category
     */
    protected function storeCategoryWithJournal(TransactionJournal $journal, string $category)
    {
        if (strlen($category) > 0) {
            $category = Category::firstOrCreateEncrypted(['name' => $category, 'user_id' => $journal->user_id]);
            $journal->categories()->save($category);
        }
        $journal->touch();
    }

    /**
     * @param User  $user
     * @param array $data
     *
     * @return array
     *
     * @throws FireflyException
     * @throws FireflyException
     */
    protected function storeDepositAccounts(User $user, array $data): array
    {
        Log::debug('Now in storeDepositAccounts().');
        $destinationAccount = Account::where('user_id', $user->id)->where('id', $data['destination_account_id'])->first(['accounts.*']);

        Log::debug(sprintf('Destination account is #%d ("%s")', $destinationAccount->id, $destinationAccount->name));

        if (strlen($data['source_account_name']) > 0) {
            $sourceType    = AccountType::where('type', 'Revenue account')->first();
            $sourceAccount = Account::firstOrCreateEncrypted(
                ['user_id' => $user->id, 'account_type_id' => $sourceType->id, 'name' => $data['source_account_name']]
            );
            // always make account active
            $sourceAccount->active = true;
            $sourceAccount->save();

            Log::debug(sprintf('source account name is "%s", account is %d', $data['source_account_name'], $sourceAccount->id));

            return [
                'source'      => $sourceAccount,
                'destination' => $destinationAccount,
            ];
        }

        Log::debug('source_account_name is empty, so default to cash account!');

        $sourceType    = AccountType::where('type', AccountType::CASH)->first();
        $sourceAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $user->id, 'account_type_id' => $sourceType->id, 'name' => 'Cash account']
        );
        // always make account active
        $sourceAccount->active = true;
        $sourceAccount->save();

        return [
            'source'      => $sourceAccount,
            'destination' => $destinationAccount,
        ];
    }

    /**
     * @param User  $user
     * @param array $data
     *
     * @return array
     *
     * @throws FireflyException
     * @throws FireflyException
     */
    protected function storeWithdrawalAccounts(User $user, array $data): array
    {
        Log::debug('Now in storeWithdrawalAccounts().');
        $sourceAccount = Account::where('user_id', $user->id)->where('id', $data['source_account_id'])->first(['accounts.*']);

        Log::debug(sprintf('Source account is #%d ("%s")', $sourceAccount->id, $sourceAccount->name));

        if (strlen($data['destination_account_name']) > 0) {
            $destinationType    = AccountType::where('type', AccountType::EXPENSE)->first();
            $destinationAccount = Account::firstOrCreateEncrypted(
                [
                    'user_id'         => $user->id,
                    'account_type_id' => $destinationType->id,
                    'name'            => $data['destination_account_name'],
                ]
            );

            // always make account active
            $destinationAccount->active = true;
            $destinationAccount->save();

            Log::debug(sprintf('destination account name is "%s", account is %d', $data['destination_account_name'], $destinationAccount->id));

            return [
                'source'      => $sourceAccount,
                'destination' => $destinationAccount,
            ];
        }
        Log::debug('destination_account_name is empty, so default to cash account!');
        $destinationType    = AccountType::where('type', AccountType::CASH)->first();
        $destinationAccount = Account::firstOrCreateEncrypted(
            ['user_id' => $user->id, 'account_type_id' => $destinationType->id, 'name' => 'Cash account']
        );
        // always make account active
        $destinationAccount->active = true;
        $destinationAccount->save();

        return [
            'source'      => $sourceAccount,
            'destination' => $destinationAccount,
        ];
    }

    /**
     * This method checks the data array and the given accounts to verify that the native amount, currency
     * and possible the foreign currency and amount are properly saved.
     *
     * @param array $data
     * @param array $accounts
     *
     * @return array
     *
     * @throws FireflyException
     */
    protected function verifyNativeAmount(array $data, array $accounts): array
    {
        /** @var TransactionType $transactionType */
        $transactionType             = TransactionType::where('type', ucfirst($data['what']))->first();
        $submittedCurrencyId         = $data['currency_id'];
        $data['foreign_amount']      = null;
        $data['foreign_currency_id'] = null;

        // which account to check for what the native currency is?
        $check = 'source';
        if (TransactionType::DEPOSIT === $transactionType->type) {
            $check = 'destination';
        }
        switch ($transactionType->type) {
            case TransactionType::RECONCILIATION:
                // do nothing.
                break;
            case TransactionType::DEPOSIT:
            case TransactionType::WITHDRAWAL:
                // continue:
                $nativeCurrencyId = intval($accounts[$check]->getMeta('currency_id'));
                if ($nativeCurrencyId === 0) {
                    // fall back to given ID (not everybody upgrades nicely).
                    $nativeCurrencyId = $submittedCurrencyId;
                }

                // does not match? Then user has submitted amount in a foreign currency:
                if ($nativeCurrencyId !== $submittedCurrencyId) {
                    // store amount and submitted currency in "foreign currency" fields:
                    $data['foreign_amount']      = $data['amount'];
                    $data['foreign_currency_id'] = $submittedCurrencyId;

                    // overrule the amount and currency ID fields to be the original again:
                    $data['amount']      = strval($data['native_amount']);
                    $data['currency_id'] = $nativeCurrencyId;
                }
                break;
            case TransactionType::TRANSFER:
                $sourceCurrencyId      = intval($accounts['source']->getMeta('currency_id'));
                $destinationCurrencyId = intval($accounts['destination']->getMeta('currency_id'));
                $data['amount']        = strval($data['source_amount']);
                $data['currency_id']   = intval($accounts['source']->getMeta('currency_id'));

                if ($sourceCurrencyId !== $destinationCurrencyId) {
                    // accounts have different id's, save this info:
                    $data['foreign_amount']      = strval($data['destination_amount']);
                    $data['foreign_currency_id'] = $destinationCurrencyId;
                }

                break;
            default:
                throw new FireflyException(sprintf('Cannot handle %s in verifyNativeAmount()', $transactionType->type));
        }

        return $data;
    }
}
