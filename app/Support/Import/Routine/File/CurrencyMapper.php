<?php
/**
 * CurrencyMapper.php
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

declare(strict_types=1);

namespace FireflyIII\Support\Import\Routine\File;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class CurrencyMapper
 */
class CurrencyMapper
{
    /** @var CurrencyRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * @param int|null $currencyId
     * @param array    $data
     *
     * @return TransactionCurrency|null
     */
    public function map(?int $currencyId, array $data): ?TransactionCurrency
    {
        Log::debug('Now in CurrencyMapper::map()');
        if ((int)$currencyId > 0) {
            $result = $this->repository->findNull($currencyId);
            if (null !== $result) {
                Log::debug(sprintf('Found currency %s based on ID, return it.', $result->code));

                return $result;
            }
        }
        // try to find it by all other fields.
        $fields = ['code' => 'findByCodeNull', 'symbol' => 'findBySymbolNull', 'name' => 'findByNameNull'];
        foreach ($fields as $field => $function) {
            $value = (string)($data[$field] ?? '');
            if ('' === $value) {
                Log::debug(sprintf('Array does not contain a value for %s. Continue', $field));
                continue;
            }
            Log::debug(sprintf('Will search for currency using %s() and argument "%s".', $function, $value));
            $result = $this->repository->$function($value);
            if (null !== $result) {
                Log::debug(sprintf('Found result: Currency #%d, code "%s"', $result->id, $result->code));

                return $result;
            }
        }
        if (!isset($data['code'])) {
            return null;
        }

        // if still nothing, and fields not null, try to create it
        $creation = [
            'code'           => $data['code'],
            'name'           => $data['name'] ?? $data['code'],
            'symbol'         => $data['symbol'] ?? $data['code'],
            'decimal_places' => 2,
        ];

        // could be NULL
        return $this->repository->store($creation);
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user       = $user;
        $this->repository = app(CurrencyRepositoryInterface::class);
        $this->repository->setUser($user);
    }

}
