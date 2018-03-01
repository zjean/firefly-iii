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

use Carbon\Carbon;
use Exception;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Object\ImportAccount;
use FireflyIII\Import\Object\ImportJournal;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Rule;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use FireflyIII\TransactionRules\Processor;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Log;

/**
 * Trait ImportSupport.
 */
trait ImportSupport
{
    /** @var BillRepositoryInterface */
    protected $billRepository;
    /** @var Collection */
    protected $bills;
    /** @var int */
    protected $defaultCurrencyId = 1;
    /** @var ImportJob */
    protected $job;
    /** @var Collection */
    protected $rules;

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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    protected function matchBills(TransactionJournal $journal): bool
    {
        if (!is_null($journal->bill_id)) {
            Log::debug('Journal is already linked to a bill, will not scan.');

            return true;
        }
        if ($this->bills->count() > 0) {
            $this->bills->each(
                function (Bill $bill) use ($journal) {
                    Log::debug(sprintf('Going to match bill #%d to journal %d.', $bill->id, $journal->id));
                    $this->billRepository->scan($bill, $journal);
                }
            );
        }

        return true;
    }

    /**
     * @param array $parameters
     *
     * @return bool
     *
     * @throws FireflyException
     */
    private function createTransaction(array $parameters): bool
    {
        $transaction                          = new Transaction;
        $transaction->account_id              = $parameters['account'];
        $transaction->transaction_journal_id  = intval($parameters['id']);
        $transaction->transaction_currency_id = intval($parameters['currency']);
        $transaction->amount                  = $parameters['amount'];
        $transaction->foreign_currency_id     = intval($parameters['foreign_currency']) === 0 ? null : intval($parameters['foreign_currency']);
        $transaction->foreign_amount          = null === $transaction->foreign_currency_id ? null : $parameters['foreign_amount'];
        $transaction->save();
        if (null === $transaction->id) {
            $errorText = join(', ', $transaction->getErrors()->all());
            throw new FireflyException($errorText);
        }
        Log::debug(sprintf('Created transaction with ID #%d, account #%d, amount %s', $transaction->id, $parameters['account'], $parameters['amount']));

        return true;
    }

    /**
     * @return Collection
     */
    private function getBills(): Collection
    {
        $set = Bill::where('user_id', $this->job->user->id)->where('active', 1)->where('automatch', 1)->get(['bills.*']);
        Log::debug(sprintf('Found %d user bills.', $set->count()));

        return $set;
    }

    /**
     * This method finds out what the import journal's currency should be. The account itself
     * is favoured (and usually it stops there). If no preference is found, the journal has a say
     * and thirdly the default currency is used.
     *
     * @param ImportJournal $importJournal
     *
     * @return int
     * @throws FireflyException
     */
    private function getCurrencyId(ImportJournal $importJournal): int
    {
        // start with currency pref of account, if any:
        $account    = $importJournal->asset->getAccount();
        $currencyId = intval($account->getMeta('currency_id'));
        if ($currencyId > 0) {
            return $currencyId;
        }

        // use given currency
        $currency = $importJournal->currency->getTransactionCurrency();
        if (null !== $currency->id) {
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
        $currency = $importJournal->currency->getTransactionCurrency();
        if (null !== $currency->id && intval($currency->id) !== intval($currencyId)) {
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

        $databaseAccount = $account->getAccount();

        return $databaseAccount;
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

    /**
     * @param TransactionJournal $journal
     * @param Bill               $bill
     */
    private function storeBill(TransactionJournal $journal, Bill $bill)
    {
        if (null !== $bill->id) {
            Log::debug(sprintf('Linked bill #%d to journal #%d', $bill->id, $journal->id));
            $journal->bill()->associate($bill);
            $journal->save();
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param Budget             $budget
     */
    private function storeBudget(TransactionJournal $journal, Budget $budget)
    {
        if (null !== $budget->id) {
            Log::debug(sprintf('Linked budget #%d to journal #%d', $budget->id, $journal->id));
            $journal->budgets()->save($budget);
        }
    }

    /**
     * @param TransactionJournal $journal
     * @param Category           $category
     */
    private function storeCategory(TransactionJournal $journal, Category $category)
    {
        if (null !== $category->id) {
            Log::debug(sprintf('Linked category #%d to journal #%d', $category->id, $journal->id));
            $journal->categories()->save($category);
        }
    }

    /**
     * @param array $parameters
     *
     * @return TransactionJournal
     *
     * @throws FireflyException
     */
    private function storeJournal(array $parameters): TransactionJournal
    {
        // find transaction type:
        $transactionType = TransactionType::whereType($parameters['type'])->first();

        // create a journal:
        $journal                          = new TransactionJournal;
        $journal->user_id                 = $this->job->user_id;
        $journal->transaction_type_id     = $transactionType->id;
        $journal->transaction_currency_id = $parameters['currency'];
        $journal->description             = $parameters['description'];
        $journal->date                    = $parameters['date'];
        $journal->order                   = 0;
        $journal->tag_count               = 0;
        $journal->completed               = false;

        if (!$journal->save()) {
            $errorText = join(', ', $journal->getErrors()->all());
            // throw error
            throw new FireflyException($errorText);
        }
        // save meta data:
        $journal->setMeta('importHash', $parameters['hash']);
        Log::debug(sprintf('Created journal with ID #%d', $journal->id));

        // create transactions:
        $one      = [
            'id'               => $journal->id,
            'account'          => $parameters['asset']->id,
            'currency'         => $parameters['currency'],
            'amount'           => $parameters['amount'],
            'foreign_currency' => $parameters['foreign_currency'],
            'foreign_amount'   => null === $parameters['foreign_currency'] ? null : $parameters['amount'],
        ];
        $opposite = app('steam')->opposite($parameters['amount']);
        $two      = [
            'id'               => $journal->id,
            'account'          => $parameters['opposing']->id,
            'currency'         => $parameters['currency'],
            'amount'           => $opposite,
            'foreign_currency' => $parameters['foreign_currency'],
            'foreign_amount'   => null === $parameters['foreign_currency'] ? null : $opposite,
        ];
        $this->createTransaction($one);
        $this->createTransaction($two);

        return $journal;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $dates
     */
    private function storeMeta(TransactionJournal $journal, array $dates)
    {
        // all other date fields as meta thing:
        foreach ($dates as $name => $value) {
            try {
                $date = new Carbon($value);
                $journal->setMeta($name, $date);
            } catch (Exception $e) {
                // don't care, ignore:
                Log::warning(sprintf('Could not parse "%s" into a valid Date object for field %s', $value, $name));
            }
        }
    }

    /**
     * @param array              $tags
     * @param TransactionJournal $journal
     */
    private function storeTags(array $tags, TransactionJournal $journal): void
    {
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($journal->user);

        foreach ($tags as $tag) {
            $dbTag = $repository->findByTag($tag);
            if (null === $dbTag->id) {
                $dbTag = $repository->store(
                    ['tag'       => $tag, 'date' => null, 'description' => null, 'latitude' => null, 'longitude' => null,
                     'zoomLevel' => null, 'tagMode' => 'nothing',]
                );
            }
            $journal->tags()->save($dbTag);
            Log::debug(sprintf('Linked tag %d ("%s") to journal #%d', $dbTag->id, $dbTag->tag, $journal->id));
        }

        return;
    }
}
