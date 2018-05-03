<?php
declare(strict_types=1);
/**
 * TransactionFactory.php
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


namespace FireflyIII\Factory;


use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Services\Internal\Support\TransactionServiceTrait;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Class TransactionFactory
 */
class TransactionFactory
{
    use TransactionServiceTrait;

    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Transaction
     */
    public function create(array $data): Transaction
    {
        $currencyId = isset($data['currency']) ? $data['currency']->id : $data['currency_id'];

        return Transaction::create(
            [
                'reconciled'              => $data['reconciled'],
                'account_id'              => $data['account']->id,
                'transaction_journal_id'  => $data['transaction_journal']->id,
                'description'             => $data['description'],
                'transaction_currency_id' => $currencyId,
                'amount'                  => $data['amount'],
                'foreign_amount'          => $data['foreign_amount'],
                'foreign_currency_id'     => null,
                'identifier'              => $data['identifier'],
            ]
        );
    }

    /**
     * Create a pair of transactions based on the data given in the array.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     *
     * @return Collection
     */
    public function createPair(TransactionJournal $journal, array $data): Collection
    {
        // all this data is the same for both transactions:
        $currency    = $this->findCurrency($data['currency_id'], $data['currency_code']);
        $description = $journal->description === $data['description'] ? null : $data['description'];

        // type of source account depends on journal type:
        $sourceType    = $this->accountType($journal, 'source');
        $sourceAccount = $this->findAccount($sourceType, $data['source_id'], $data['source_name']);

        // same for destination account:
        $destinationType    = $this->accountType($journal, 'destination');
        $destinationAccount = $this->findAccount($destinationType, $data['destination_id'], $data['destination_name']);
        // first make a "negative" (source) transaction based on the data in the array.
        $source = $this->create(
            [
                'description'         => $description,
                'amount'              => app('steam')->negative((string)$data['amount']),
                'foreign_amount'      => null,
                'currency'            => $currency,
                'account'             => $sourceAccount,
                'transaction_journal' => $journal,
                'reconciled'          => $data['reconciled'],
                'identifier'          => $data['identifier'],
            ]
        );
        // then make a "positive" transaction based on the data in the array.
        $dest = $this->create(
            [
                'description'         => $description,
                'amount'              => app('steam')->positive((string)$data['amount']),
                'foreign_amount'      => null,
                'currency'            => $currency,
                'account'             => $destinationAccount,
                'transaction_journal' => $journal,
                'reconciled'          => $data['reconciled'],
                'identifier'          => $data['identifier'],
            ]
        );

        // set foreign currency
        $foreign = $this->findCurrency($data['foreign_currency_id'], $data['foreign_currency_code']);
        $this->setForeignCurrency($source, $foreign);
        $this->setForeignCurrency($dest, $foreign);

        // set foreign amount:
        if (null !== $data['foreign_amount']) {
            $this->setForeignAmount($source, app('steam')->negative((string)$data['foreign_amount']));
            $this->setForeignAmount($dest, app('steam')->positive((string)$data['foreign_amount']));
        }

        // set budget:
        if ($journal->transactionType->type === TransactionType::TRANSFER) {
            $data['budget_id']   = null;
            $data['budget_name'] = null;
        }

        $budget = $this->findBudget($data['budget_id'], $data['budget_name']);
        $this->setBudget($source, $budget);
        $this->setBudget($dest, $budget);

        // set category
        $category = $this->findCategory($data['category_id'], $data['category_name']);
        $this->setCategory($source, $category);
        $this->setCategory($dest, $category);

        return new Collection([$source, $dest]);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }


}
