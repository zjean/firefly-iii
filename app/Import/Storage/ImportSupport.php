<?php
/**
 * ImportSupport.php
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

namespace FireflyIII\Import\Storage;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportAccount;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Rule;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Log;

/**
 * Trait ImportSupport.
 *
 * @property int $defaultCurrencyId
 * @property ImportJob $job
 * @property JournalRepositoryInterface $journalRepository;
 * @property Collection $rules
 */
trait ImportSupport
{
    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function applyRules(TransactionJournal $journal): bool
    {
        if ($this->rules->count() > 0) {
            $this->rules->each(
                function (Rule $rule) use ($journal) {
                    Log::debug(sprintf('Going to apply rule #%d to journal %d.', $rule->id, $journal->id));
                    $processor = Processor::make($rule);
                    $processor->handleTransactionJournal($journal);
                    if ($rule->stop_processing) {
                        return false;
                    }

                    return true;
                }
            );
        }

        return true;
    }

    /**
     * This method finds out what the import journal's currency should be. The account itself
     * is favoured (and usually it stops there). If no preference is found, the journal has a say
     * and thirdly the default currency is used.
     *
     * @param ImportJournal $importJournal
     *
     * @return int
     *
     * @throws FireflyException
     */
    private function getCurrencyId(ImportJournal $importJournal): int
    {
        // start with currency pref of account, if any:
        $account    = $importJournal->asset->getAccount();
        $currencyId = (int)$account->getMeta('currency_id');
        if ($currencyId > 0) {
            return $currencyId;
        }

        // use given currency
        $currency = $importJournal->currency->getTransactionCurrency();
        if (null !== $currency) {
            return $currency->id;
        }

        // backup to default
        $currency = $this->defaultCurrencyId;

        return $currency;
    }

    /**
     * The foreign currency is only returned when the journal has a different value from the
     * currency id (see other method).
     *
     * @param ImportJournal $importJournal
     * @param int           $currencyId
     *
     * @see ImportSupport::getCurrencyId
     *
     * @return int|null
     */
    private function getForeignCurrencyId(ImportJournal $importJournal, int $currencyId): ?int
    {
        // use given currency by import journal.
        $currency = $importJournal->foreignCurrency->getTransactionCurrency();
        if (null !== $currency && (int)$currency->id !== (int)$currencyId) {
            return $currency->id;
        }

        // return null, because no different:
        return null;
    }

    /**
     * The search for the opposing account is complex. Firstly, we forbid the ImportAccount to resolve into the asset
     * account to prevent a situation where the transaction flows from A to A. Given the amount, we "expect" the opposing
     * account to be an expense or a revenue account. However, the mapping given by the user may return something else
     * entirely (usually an asset account). So whatever the expectation, the result may be anything.
     *
     * When the result does not match the expected type (a negative amount cannot be linked to a revenue account) the next step
     * will return an error.
     *
     * @param ImportAccount $account
     * @param int           $forbiddenAccount
     * @param string        $amount
     *
     * @see ImportSupport::getTransactionType
     *
     * @return Account
     *
     * @throws FireflyException
     */
    private function getOpposingAccount(ImportAccount $account, int $forbiddenAccount, string $amount): Account
    {
        $account->setForbiddenAccountId($forbiddenAccount);
        if (bccomp($amount, '0') === -1) {
            Log::debug(sprintf('%s is negative, create opposing expense account.', $amount));
            $account->setExpectedType(AccountType::EXPENSE);

            return $account->getAccount();
        }
        Log::debug(sprintf('%s is positive, create opposing revenue account.', $amount));
        // amount is positive, it's a deposit, opposing is an revenue:
        $account->setExpectedType(AccountType::REVENUE);

        return $account->getAccount();
    }

    /**
     * @return Collection
     */
    private function getRules(): Collection
    {
        $set = Rule::distinct()
                   ->where('rules.user_id', $this->job->user->id)
                   ->leftJoin('rule_groups', 'rule_groups.id', '=', 'rules.rule_group_id')
                   ->leftJoin('rule_triggers', 'rules.id', '=', 'rule_triggers.rule_id')
                   ->where('rule_groups.active', 1)
                   ->where('rule_triggers.trigger_type', 'user_action')
                   ->where('rule_triggers.trigger_value', 'store-journal')
                   ->where('rules.active', 1)
                   ->orderBy('rule_groups.order', 'ASC')
                   ->orderBy('rules.order', 'ASC')
                   ->get(['rules.*', 'rule_groups.order']);
        Log::debug(sprintf('Found %d user rules.', $set->count()));

        return $set;
    }

    /**
     * Given the amount and the opposing account its easy to define which kind of transaction type should be associated with the new
     * import. This may however fail when there is an unexpected mismatch between the transaction type and the opposing account.
     *
     * @param string  $amount
     * @param Account $account
     *
     * @return string
     *x
     *
     * @throws FireflyException
     *
     * @see ImportSupport::getOpposingAccount()
     */
    private function getTransactionType(string $amount, Account $account): string
    {
        $transactionType = TransactionType::WITHDRAWAL;
        // amount is negative, it's a withdrawal, opposing is an expense:
        if (bccomp($amount, '0') === -1) {
            $transactionType = TransactionType::WITHDRAWAL;
        }

        if (1 === bccomp($amount, '0')) {
            $transactionType = TransactionType::DEPOSIT;
        }

        // if opposing is an asset account, it's a transfer:
        if (AccountType::ASSET === $account->accountType->type) {
            Log::debug(sprintf('Opposing account #%d %s is an asset account, make transfer.', $account->id, $account->name));
            $transactionType = TransactionType::TRANSFER;
        }

        // verify that opposing account is of the correct type:
        if (AccountType::EXPENSE === $account->accountType->type && TransactionType::WITHDRAWAL !== $transactionType) {
            $message = 'This row is imported as a withdrawal but opposing is an expense account. This cannot be!';
            Log::error($message);
            throw new FireflyException($message);
        }

        return $transactionType;
    }

    /**
     * This method returns a collection of the current transfers in the system and some meta data for
     * this set. This can later be used to see if the journal that firefly is trying to import
     * is not already present.
     *
     * @return array
     */
    private function getTransfers(): array
    {
        $set   = TransactionJournal::leftJoin('transaction_types', 'transaction_types.id', '=', 'transaction_journals.transaction_type_id')
                                   ->leftJoin(
                                       'transactions AS source',
                                       function (JoinClause $join) {
                                           $join->on('transaction_journals.id', '=', 'source.transaction_journal_id')->where('source.amount', '<', 0);
                                       }
                                   )
                                   ->leftJoin(
                                       'transactions AS destination',
                                       function (JoinClause $join) {
                                           $join->on('transaction_journals.id', '=', 'destination.transaction_journal_id')->where(
                                               'destination.amount',
                                               '>',
                                               0
                                           );
                                       }
                                   )
                                   ->leftJoin('accounts as source_accounts', 'source.account_id', '=', 'source_accounts.id')
                                   ->leftJoin('accounts as destination_accounts', 'destination.account_id', '=', 'destination_accounts.id')
                                   ->where('transaction_journals.user_id', $this->job->user_id)
                                   ->where('transaction_types.type', TransactionType::TRANSFER)
                                   ->get(
                                       ['transaction_journals.id', 'transaction_journals.encrypted', 'transaction_journals.description',
                                        'source_accounts.name as source_name', 'destination_accounts.name as destination_name', 'destination.amount',
                                        'transaction_journals.date',]
                                   );
        $array = [];
        /** @var TransactionJournal $entry */
        foreach ($set as $entry) {
            $original = [app('steam')->tryDecrypt($entry->source_name), app('steam')->tryDecrypt($entry->destination_name)];
            sort($original);
            $array[] = [
                'names'       => $original,
                'amount'      => $entry->amount,
                'date'        => $entry->date->format('Y-m-d'),
                'description' => $entry->description,
            ];
        }

        return $array;
    }

    /**
     * Checks if the import journal has not been imported before.
     *
     * @param string $hash
     *
     * @return bool
     */
    private function hashAlreadyImported(string $hash): bool
    {
        $json = json_encode($hash);
        /** @var TransactionJournalMeta $entry */
        $entry = TransactionJournalMeta::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
                                       ->where('data', $json)
                                       ->where('name', 'importHash')
                                       ->first();
        if (null !== $entry) {
            Log::error(sprintf('A journal with hash %s has already been imported (spoiler: it\'s journal #%d)', $hash, $entry->transaction_journal_id));

            return true;
        }

        return false;
    }
}
