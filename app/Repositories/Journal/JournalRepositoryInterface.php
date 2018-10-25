<?php
/**
 * JournalRepositoryInterface.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Interface JournalRepositoryInterface.
 */
interface JournalRepositoryInterface
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $type
     * @param Account            $source
     * @param Account            $destination
     *
     * @return MessageBag
     */
    public function convert(TransactionJournal $journal, TransactionType $type, Account $source, Account $destination): MessageBag;

    /**
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function countTransactions(TransactionJournal $journal): int;

    /**
     * Deletes a journal.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function destroy(TransactionJournal $journal): bool;

    /**
     * Find a journal by its hash.
     *
     * @param string $hash
     *
     * @return TransactionJournalMeta|null
     */
    public function findByHash(string $hash): ?TransactionJournalMeta;

    /**
     * Find a specific journal.
     *
     * @param int $journalId
     *
     * @return TransactionJournal|null
     */
    public function findNull(int $journalId): ?TransactionJournal;

    /**
     * @param Transaction $transaction
     *
     * @return Transaction|null
     */
    public function findOpposingTransaction(Transaction $transaction): ?Transaction;

    /**
     * @param int $transactionid
     *
     * @return Transaction|null
     */
    public function findTransaction(int $transactionid): ?Transaction;

    /**
     * Get users very first transaction journal.
     *
     * @return TransactionJournal|null
     */
    public function firstNull(): ?TransactionJournal;

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction|null
     */
    public function getAssetTransaction(TransactionJournal $journal): ?Transaction;

    /**
     * Returns the first positive transaction for the journal. Useful when editing journals.
     *
     * @param TransactionJournal $journal
     *
     * @return Transaction
     */
    public function getFirstPosTransaction(TransactionJournal $journal): Transaction;

    /**
     * Return the ID of the budget linked to the journal (if any) or the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return int
     */
    public function getJournalBudgetId(TransactionJournal $journal): int;

    /**
     * Return the name of the category linked to the journal (if any) or to the transactions (if any).
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getJournalCategoryName(TransactionJournal $journal): string;

    /**
     * Return requested date as string. When it's a NULL return the date of journal,
     * otherwise look for meta field and return that one.
     *
     * @param TransactionJournal $journal
     * @param null|string        $field
     *
     * @return string
     */
    public function getJournalDate(TransactionJournal $journal, ?string $field): string;

    /**
     * Return a list of all destination accounts related to journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getJournalDestinationAccounts(TransactionJournal $journal): Collection;

    /**
     * Return a list of all source accounts related to journal.
     *
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getJournalSourceAccounts(TransactionJournal $journal): Collection;

    /**
     * Return total amount of journal. Is always positive.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getJournalTotal(TransactionJournal $journal): string;

    /**
     * Return Carbon value of a meta field (or NULL).
     *
     * @param TransactionJournal $journal
     * @param string             $field
     *
     * @return null|Carbon
     */
    public function getMetaDate(TransactionJournal $journal, string $field): ?Carbon;

    /**
     * Return value of a meta field (or NULL).
     *
     * @param TransactionJournal $journal
     * @param string             $field
     *
     * @return null|string
     */
    public function getMetaField(TransactionJournal $journal, string $field): ?string;

    /**
     * Return text of a note attached to journal, or NULL
     *
     * @param TransactionJournal $journal
     *
     * @return string|null
     */
    public function getNoteText(TransactionJournal $journal): ?string;

    /**
     * @param TransactionJournal $journal
     *
     * @return Collection
     */
    public function getPiggyBankEvents(TransactionJournal $journal): Collection;

    /**
     * Return all tags as strings in an array.
     *
     * @param TransactionJournal $journal
     *
     * @return array
     */
    public function getTags(TransactionJournal $journal): array;

    /**
     * Return the transaction type of the journal.
     *
     * @param TransactionJournal $journal
     *
     * @return string
     */
    public function getTransactionType(TransactionJournal $journal): string;

    /**
     * @return Collection
     */
    public function getTransactionTypes(): Collection;

    /**
     * @param array $transactionIds
     *
     * @return Collection
     */
    public function getTransactionsById(array $transactionIds): Collection;

    /**
     * Will tell you if journal is reconciled or not.
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function isJournalReconciled(TransactionJournal $journal): bool;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function reconcile(Transaction $transaction): bool;

    /**
     * @param int $transactionId
     *
     * @return bool
     */
    public function reconcileById(int $transactionId): bool;

    /**
     * Set meta field for journal that contains a date.
     *
     * @param TransactionJournal $journal
     * @param string             $name
     * @param Carbon             $date
     *
     * @return void
     */
    public function setMetaDate(TransactionJournal $journal, string $name, Carbon $date): void;

    /**
     * @param TransactionJournal $journal
     * @param int                $order
     *
     * @return bool
     */
    public function setOrder(TransactionJournal $journal, int $order): bool;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @throws FireflyException
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal;

    /**
     * Update budget for a journal.
     *
     * @param TransactionJournal $journal
     * @param int                $budgetId
     *
     * @return TransactionJournal
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal;

    /**
     * Update category for a journal.
     *
     * @param TransactionJournal $journal
     * @param string             $category
     *
     * @return TransactionJournal
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal;

    /**
     * Update tag(s) for a journal.
     *
     * @param TransactionJournal $journal
     * @param array              $tags
     *
     * @return TransactionJournal
     */
    public function updateTags(TransactionJournal $journal, array $tags): TransactionJournal;
}
