<?php
/**
 * JournalRepository.php
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

use Carbon\Carbon;
use Exception;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Destroy\JournalDestroyService;
use FireflyIII\Services\Internal\Update\JournalUpdateService;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use Preferences;

/**
 * Class JournalRepository.
 */
class JournalRepository implements JournalRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $type
     * @param Account            $source
     * @param Account            $destination
     *
     * @return MessageBag
     */
    public function convert(TransactionJournal $journal, TransactionType $type, Account $source, Account $destination): MessageBag
    {
        // default message bag that shows errors for everything.
        $messages = new MessageBag;
        $messages->add('source_account_revenue', trans('firefly.invalid_convert_selection'));
        $messages->add('destination_account_asset', trans('firefly.invalid_convert_selection'));
        $messages->add('destination_account_expense', trans('firefly.invalid_convert_selection'));
        $messages->add('source_account_asset', trans('firefly.invalid_convert_selection'));

        if ($source->id === $destination->id || null === $source->id || null === $destination->id) {
            return $messages;
        }

        $sourceTransaction             = $journal->transactions()->where('amount', '<', 0)->first();
        $destinationTransaction        = $journal->transactions()->where('amount', '>', 0)->first();
        $sourceTransaction->account_id = $source->id;
        $sourceTransaction->save();
        $destinationTransaction->account_id = $destination->id;
        $destinationTransaction->save();
        $journal->transaction_type_id = $type->id;
        $journal->save();

        // if journal is a transfer now, remove budget:
        if (TransactionType::TRANSFER === $type->type) {
            $journal->budgets()->detach();
        }

        Preferences::mark();

        return new MessageBag;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function countTransactions(TransactionJournal $journal): int
    {
        return $journal->transactions()->count();
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return bool
     *

     */
    public function destroy(TransactionJournal $journal): bool
    {
        /** @var JournalDestroyService $service */
        $service = app(JournalDestroyService::class);
        $service->destroy($journal);

        return true;
    }

    /**
     * @param int $journalId
     *
     * @return TransactionJournal
     */
    public function find(int $journalId): TransactionJournal
    {
        /** @var TransactionJournal $journal */
        $journal = $this->user->transactionJournals()->where('id', $journalId)->first();
        if (null === $journal) {
            return new TransactionJournal;
        }

        return $journal;
    }

    /**
     * @param Transaction $transaction
     *
     * @return Transaction|null
     */
    public function findOpposingTransaction(Transaction $transaction): ?Transaction
    {
        $opposing = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                               ->where('transaction_journals.user_id', $this->user->id)
                               ->where('transactions.transaction_journal_id', $transaction->transaction_journal_id)
                               ->where('transactions.identifier', $transaction->identifier)
                               ->where('amount', bcmul($transaction->amount, '-1'))
                               ->first(['transactions.*']);

        return $opposing;
    }

    /**
     * @param int $transactionid
     *
     * @return Transaction|null
     */
    public function findTransaction(int $transactionid): ?Transaction
    {
        $transaction = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                  ->where('transaction_journals.user_id', $this->user->id)
                                  ->where('transactions.id', $transactionid)
                                  ->first(['transactions.*']);

        return $transaction;
    }

    /**
     * Get users first transaction journal.
     *
     * @deprecated
     * @return TransactionJournal
     */
    public function first(): TransactionJournal
    {
        /** @var TransactionJournal $entry */
        $entry = $this->user->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);

        if (null === $entry) {
            return new TransactionJournal;
        }

        return $entry;
    }

    /**
     * Get users first transaction journal or NULL.
     *
     * @return TransactionJournal|null
     */
    public function firstNull(): ?TransactionJournal
    {
        /** @var TransactionJournal $entry */
        $entry  = $this->user->transactionJournals()->orderBy('date', 'ASC')->first(['transaction_journals.*']);
        $result = null;
        if (null !== $entry) {
            $result = $entry;
        }

        return $result;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction|null
     */
    public function getAssetTransaction(TransactionJournal $journal): ?Transaction
    {
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            if (AccountType::ASSET === $transaction->account->accountType->type) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * Returns the first positive transaction for the journal. Useful when editing journals.
     *
     * @param TransactionJournal $journal
     *
     * @return Transaction
     */
    public function getFirstPosTransaction(TransactionJournal $journal): Transaction
    {
        return $journal->transactions()->where('amount', '>', 0)->first();
    }

    /**
     * Return the ID of the budget linked to the journal (if any) or the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getJournalBudgetId(TransactionJournal $journal): int
    {
        $budget = $journal->budgets()->first();
        if (null !== $budget) {
            return $budget->id;
        }
        $budget = $journal->transactions()->first()->budgets()->first();
        if (null !== $budget) {
            return $budget->id;
        }

        return 0;
    }

    /**
     * Return the name of the category linked to the journal (if any) or to the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getJournalCategoryName(TransactionJournal $journal): string
    {
        $category = $journal->categories()->first();
        if (null !== $category) {
            return $category->name;
        }
        $category = $journal->transactions()->first()->categories()->first();
        if (null !== $category) {
            return $category->name;
        }

        return '';
    }

    /**
     * Return requested date as string. When it's a NULL return the date of journal,
     * otherwise look for meta field and return that one.
     *
     * @param TransactionJournal $journal
     * @param null|string        $field
     *
     * @return string
     */
    public function getJournalDate(TransactionJournal $journal, ?string $field): string
    {
        if (null === $field) {
            return $journal->date->format('Y-m-d');
        }
        if (null !== $journal->$field && $journal->$field instanceof Carbon) {
            // make field NULL
            $carbon          = clone $journal->$field;
            $journal->$field = null;
            $journal->save();

            // create meta entry
            $journal->setMeta($field, $carbon);

            // return that one instead.
            return $carbon->format('Y-m-d');
        }
        $metaField = $journal->getMeta($field);
        if (null !== $metaField) {
            $carbon = new Carbon($metaField);

            return $carbon->format('Y-m-d');
        }

        return '';
    }

    /**
     * Return a list of all destination accounts related to journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getJournalDestinationAccounts(TransactionJournal $journal): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('destination-account-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $journal->transactions()->where('amount', '>', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $list = $list->unique('id');
        $cache->store($list);

        return $list;
    }

    /**
     * Return a list of all source accounts related to journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getJournalSourceAccounts(TransactionJournal $journal): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('source-account-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $journal->transactions()->where('amount', '<', 0)->orderBy('transactions.account_id')->with('account')->get();
        $list         = new Collection;
        /** @var Transaction $t */
        foreach ($transactions as $t) {
            $list->push($t->account);
        }
        $list = $list->unique('id');
        $cache->store($list);

        return $list;
    }

    /**
     * Return total amount of journal. Is always positive.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getJournalTotal(TransactionJournal $journal): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($journal->id);
        $cache->addProperty('amount-positive');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        // saves on queries:
        $amount = $journal->transactions()->where('amount', '>', 0)->get()->sum('amount');
        $amount = (string)$amount;
        $cache->store($amount);

        return $amount;
    }

    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param TransactionJournal $journal
     * @param string             $field
     *
     * @return null|Carbon
     */
    public function getMetaDate(TransactionJournal $journal, string $field): ?Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journal->id);
        $cache->addProperty($field);

        if ($cache->has()) {
            return new Carbon($cache->get()); // @codeCoverageIgnore
        }

        $entry = $journal->transactionJournalMeta()->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }
        $value = new Carbon($entry->data);
        $cache->store($entry->data);

        return $value;
    }

    /**
     * Return value of a meta field (or NULL) as a string.
     *
     * @param TransactionJournal $journal
     * @param string             $field
     *
     * @return null|string
     */
    public function getMetaField(TransactionJournal $journal, string $field): ?string
    {
        $cache = new CacheProperties;
        $cache->addProperty('journal-meta-updated');
        $cache->addProperty($journal->id);
        $cache->addProperty($field);

        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $entry = $journal->transactionJournalMeta()->where('name', $field)->first();
        if (null === $entry) {
            return null;
        }

        $value = $entry->data;

        // return when array:
        if (is_array($value)) {
            $return = implode(',', $value);
            $cache->store($return);

            return $return;
        }

        // return when something else:
        try {
            $return = (string)$value;
            $cache->store($return);
        } catch (Exception $e) {
            Log::error($e->getMessage());

            return '';
        }

        return $return;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Note|null
     */
    public function getNote(TransactionJournal $journal): ?Note
    {
        return $journal->notes()->first();
    }

    /**
     * Return text of a note attached to journal, or NULL
     *
     * @param TransactionJournal $journal
     *
     * @return string|null
     */
    public function getNoteText(TransactionJournal $journal): ?string
    {
        $note = $this->getNote($journal);
        if (null === $note) {
            return null;
        }

        return $note->text;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getPiggyBankEvents(TransactionJournal $journal): Collection
    {
        /** @var Collection $set */
        $events = $journal->piggyBankEvents()->get();
        $events->each(
            function (PiggyBankEvent $event) {
                $event->piggyBank = $event->piggyBank()->withTrashed()->first();
            }
        );

        return $events;
    }

    /**
     * Return all tags as strings in an array.
     *
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function getTags(TransactionJournal $journal): array
    {
        return $journal->tags()->get()->pluck('tag')->toArray();
    }

    /**
     * Return the transaction type of the journal.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getTransactionType(TransactionJournal $journal): string
    {
        return $journal->transactionType->type;
    }

    /**
     * @return Collection
     */
    public function getTransactionTypes(): Collection
    {
        return TransactionType::orderBy('type', 'ASC')->get();
    }

    /**
     * @param array $transactionIds
     *
     * @return Collection
     */
    public function getTransactionsById(array $transactionIds): Collection
    {
        $set = Transaction::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                          ->whereIn('transactions.id', $transactionIds)
                          ->where('transaction_journals.user_id', $this->user->id)
                          ->whereNull('transaction_journals.deleted_at')
                          ->whereNull('transactions.deleted_at')
                          ->get(['transactions.*']);

        return $set;
    }

    /**
     * Will tell you if journal is reconciled or not.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function isJournalReconciled(TransactionJournal $journal): bool
    {
        foreach ($journal->transactions as $transaction) {
            if ($transaction->reconciled) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function reconcile(Transaction $transaction): bool
    {
        Log::debug(sprintf('Going to reconcile transaction #%d', $transaction->id));
        $opposing = $this->findOpposingTransaction($transaction);

        if (null === $opposing) {
            Log::debug('Opposing transaction is NULL. Cannot reconcile.');

            return false;
        }
        Log::debug(sprintf('Opposing transaction ID is #%d', $opposing->id));

        $transaction->reconciled = true;
        $opposing->reconciled    = true;
        $transaction->save();
        $opposing->save();

        return true;
    }

    /**
     * @param int $transactionId
     *
     * @return bool
     */
    public function reconcileById(int $transactionId): bool
    {
        /** @var Transaction $transaction */
        $transaction = $this->user->transactions()->find($transactionId);
        if (null !== $transaction) {
            return $this->reconcile($transaction);
        }

        return false;
    }

    /**
     * Set meta field for journal that contains a date.
     *
     * @param TransactionJournal $journal
     * @param string             $name
     * @param Carbon             $date
     *
     * @return void
     */
    public function setMetaDate(TransactionJournal $journal, string $name, Carbon $date): void
    {
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate(
            [
                'data'    => $date,
                'journal' => $journal,
                'name'    => $name,
            ]
        );

        return;
    }

    /**
     * Set meta field for journal that contains string.
     *
     * @param TransactionJournal $journal
     * @param string             $name
     * @param string             $value
     */
    public function setMetaString(TransactionJournal $journal, string $name, string $value): void
    {
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate(
            [
                'data'    => $value,
                'journal' => $journal,
                'name'    => $name,
            ]
        );

        return;
    }

    /**
     * @param TransactionJournal $journal
     * @param int                $order
     *
     * @return bool
     */
    public function setOrder(TransactionJournal $journal, int $order): bool
    {
        $journal->order = $order;
        $journal->save();

        return true;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return TransactionJournal
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function store(array $data): TransactionJournal
    {
        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->user);

        return $factory->create($data);
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     *
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $journal = $service->update($journal, $data);

        return $journal;
    }

    /**
     * Update budget for a journal.
     *
     * @param TransactionJournal $journal
     * @param int                $budgetId
     *
     * @return TransactionJournal
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);

        return $service->updateBudget($journal, $budgetId);
    }

    /**
     * Update category for a journal.
     *
     * @param TransactionJournal $journal
     * @param string             $category
     *
     * @return TransactionJournal
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);

        return $service->updateCategory($journal, $category);
    }

    /**
     * Update tag(s) for a journal.
     *
     * @param TransactionJournal $journal
     * @param array              $tags
     *
     * @return TransactionJournal
     */
    public function updateTags(TransactionJournal $journal, array $tags): TransactionJournal
    {
        /** @var JournalUpdateService $service */
        $service = app(JournalUpdateService::class);
        $service->connectTags($journal, $tags);

        return $journal;

    }
}
