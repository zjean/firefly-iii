<?php
/**
 * TransactionJournalTrait.php
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

namespace FireflyIII\Support\Models;

use Carbon\Carbon;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Class TransactionJournalTrait.
 *
 * @property int             $id
 * @property Carbon          $date
 * @property string          $transaction_type_type
 * @property TransactionType $transactionType
 */
trait TransactionJournalTrait
{
    /**
     * @param Builder $query
     * @param string  $table
     *
     * @return bool
     */
    public static function isJoined(Builder $query, string $table): bool
    {
        $joins = $query->getQuery()->joins;
        if (null === $joins) {
            return false;
        }
        foreach ($joins as $join) {
            if ($join->table === $table) {
                return true;
            }
        }

        return false;
    }

    /**
     * @deprecated
     * @return Collection
     */
    public function destinationAccountList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-account-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $this->transactions()->where('amount', '>', 0)->orderBy('transactions.account_id')->with('account')->get();
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
     * @deprecated
     * @return Collection
     */
    public function destinationTransactionList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('destination-transaction-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $list = $this->transactions()->where('amount', '>', 0)->with('account')->get();
        $cache->store($list);

        return $list;
    }

    /**
     * @deprecated
     * @return Collection
     */
    public function sourceAccountList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-account-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $transactions = $this->transactions()->where('amount', '<', 0)->orderBy('transactions.account_id')->with('account')->get();
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
     * @deprecated
     * @return Collection
     */
    public function sourceTransactionList(): Collection
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('source-transaction-list');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $list = $this->transactions()->where('amount', '<', 0)->with('account')->get();
        $cache->store($list);

        return $list;
    }

    /**
     * @deprecated
     * @return string
     */
    public function transactionTypeStr(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty($this->id);
        $cache->addProperty('transaction-journal');
        $cache->addProperty('type-string');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $typeStr = $this->transaction_type_type ?? $this->transactionType->type;
        $cache->store($typeStr);

        return $typeStr;
    }

    /**
     * @return HasMany
     */
    abstract public function transactions(): HasMany;
}
