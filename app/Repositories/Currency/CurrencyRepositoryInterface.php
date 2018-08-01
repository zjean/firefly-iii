<?php
/**
 * CurrencyRepositoryInterface.php
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

namespace FireflyIII\Repositories\Currency;

use Carbon\Carbon;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\Preference;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface CurrencyRepositoryInterface.
 */
interface CurrencyRepositoryInterface
{
    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function canDeleteCurrency(TransactionCurrency $currency): bool;

    /**
     * @param TransactionCurrency $currency
     *
     * @return int
     */
    public function countJournals(TransactionCurrency $currency): int;

    /**
     * @param TransactionCurrency $currency
     *
     * @return bool
     */
    public function destroy(TransactionCurrency $currency): bool;

    /**
     * Find by currency code, return NULL if unfound.
     *
     * @param string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function findByCodeNull(string $currencyCode): ?TransactionCurrency;

    /**
     * Find by currency name.
     *
     * @param string $currencyName
     *
     * @return TransactionCurrency
     */
    public function findByNameNull(string $currencyName): ?TransactionCurrency;

    /**
     * Find by currency symbol.
     *
     * @param string $currencySymbol
     *
     * @return TransactionCurrency
     */
    public function findBySymbolNull(string $currencySymbol): ?TransactionCurrency;

    /**
     * Find by ID, return NULL if not found.
     *
     * @param int $currencyId
     *
     * @return TransactionCurrency|null
     */
    public function findNull(int $currencyId): ?TransactionCurrency;

    /**
     * @return Collection
     */
    public function get(): Collection;

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function getByIds(array $ids): Collection;

    /**
     * @param Preference $preference
     *
     * @return TransactionCurrency
     */
    public function getCurrencyByPreference(Preference $preference): TransactionCurrency;

    /**
     * Get currency exchange rate.
     *
     * @param TransactionCurrency $fromCurrency
     * @param TransactionCurrency $toCurrency
     * @param Carbon              $date
     *
     * @return CurrencyExchangeRate|null
     */
    public function getExchangeRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): ?CurrencyExchangeRate;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * @param array $data
     *
     * @return TransactionCurrency|null
     */
    public function store(array $data): ?TransactionCurrency;

    /**
     * @param TransactionCurrency $currency
     * @param array               $data
     *
     * @return TransactionCurrency
     */
    public function update(TransactionCurrency $currency, array $data): TransactionCurrency;
}
