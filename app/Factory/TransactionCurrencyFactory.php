<?php
declare(strict_types=1);
/**
 * TransactionCurrencyFactory.php
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

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Database\QueryException;
use Log;

/**
 * Class TransactionCurrencyFactory
 */
class TransactionCurrencyFactory
{
    /**
     * @param array $data
     *
     * @return TransactionCurrency|null
     */
    public function create(array $data): ?TransactionCurrency
    {
        $result = null;
        try {
            /** @var TransactionCurrency $currency */
            $result = TransactionCurrency::create(
                [
                    'name'           => $data['name'],
                    'code'           => $data['code'],
                    'symbol'         => $data['symbol'],
                    'decimal_places' => $data['decimal_places'],
                ]
            );
        } catch (QueryException $e) {
            Log::error(sprintf('Could not create new currency: %s', $e->getMessage()));
        }

        return $result;
    }

    /**
     * @param int|null    $currencyId
     * @param null|string $currencyCode
     *
     * @return TransactionCurrency|null
     */
    public function find(?int $currencyId, ?string $currencyCode): ?TransactionCurrency
    {
        $currencyCode = (string)$currencyCode;
        $currencyId   = (int)$currencyId;

        if (strlen($currencyCode) === 0 && (int)$currencyId === 0) {
            return null;
        }

        // first by ID:
        if ($currencyId > 0) {
            $currency = TransactionCurrency::find($currencyId);
            if (null !== $currency) {
                return $currency;
            }
        }
        // then by code:
        if (strlen($currencyCode) > 0) {
            $currency = TransactionCurrency::whereCode($currencyCode)->first();
            if (null !== $currency) {
                return $currency;
            }
        }

        return null;
    }


}
