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

use FireflyIII\Models\Account;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Interface JournalRepositoryInterface.
 */
interface JournalRepositoryInterface
{
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
     * Find a specific journal.
     *
     * @param int $journalId
     *
     * @return TransactionJournal
     */
    public function find(int $journalId): TransactionJournal;

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
     * @return TransactionJournal
     */
    public function first(): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     *
     * @return Transaction|null
     */
    public function getAssetTransaction(TransactionJournal $journal): ?Transaction;

    /**
     * @param TransactionJournal $journal
     *
     * @return Note|null
     */
    public function getNote(TransactionJournal $journal): ?Note;

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
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function isTransfer(TransactionJournal $journal): bool;

    /**
     * Mark journal as completed and return it.
     *
     * @param TransactionJournal $journal
     *
     * @return TransactionJournal
     */
    public function markCompleted(TransactionJournal $journal): TransactionJournal;

    /**
     * @param Transaction $transaction
     *
     * @return bool
     */
    public function reconcile(Transaction $transaction): bool;

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
     * @return TransactionJournal
     */
    public function store(array $data): TransactionJournal;

    /**
     * Store a new transaction journal based on the values given.
     *
     * @param array $values
     *
     * @return TransactionJournal
     */
    public function storeBasic(array $values): TransactionJournal;

    /**
     * Store a new transaction based on the values given.
     *
     * @param array $values
     *
     * @return Transaction
     */
    public function storeBasicTransaction(array $values): Transaction;

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function update(TransactionJournal $journal, array $data): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param int                $budgetId
     *
     * @return TransactionJournal
     */
    public function updateBudget(TransactionJournal $journal, int $budgetId): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param string             $category
     *
     * @return TransactionJournal
     */
    public function updateCategory(TransactionJournal $journal, string $category): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return TransactionJournal
     */
    public function updateSplitJournal(TransactionJournal $journal, array $data): TransactionJournal;

    /**
     * @param TransactionJournal $journal
     * @param array              $tags
     *
     * @return bool
     */
    public function updateTags(TransactionJournal $journal, array $tags): bool;
}
