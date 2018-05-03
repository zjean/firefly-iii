<?php
/**
 * ImportCurrency.php
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

namespace FireflyIII\Import\Object;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class ImportCurrency
 */
class ImportCurrency
{
    /** @var array */
    private $code = [];
    /** @var TransactionCurrency */
    private $currency;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var CurrencyRepositoryInterface */
    private $repository;
    /** @var array */
    private $symbol = [];
    /** @var User */
    private $user;

    /**
     * ImportCurrency constructor.
     */
    public function __construct()
    {
        $this->repository = app(CurrencyRepositoryInterface::class);
    }

    /**
     * @return TransactionCurrency
     */
    public function getTransactionCurrency(): ?TransactionCurrency
    {
        if (null !== $this->currency) {
            return $this->currency;
        }
        Log::debug('In createCurrency()');
        // check if any of them is mapped:
        $mapped = $this->findMappedObject();

        if (null !== $mapped) {
            Log::debug('Mapped existing currency.', ['new' => $mapped->toArray()]);
            $this->currency = $mapped;

            return $mapped;
        }

        $searched = $this->findExistingObject();
        if (null !== $searched) {
            Log::debug('Found existing currency.', ['found' => $searched->toArray()]);
            $this->currency = $searched;

            return $searched;
        }
        $data = [
            'code'           => $this->code['value'] ?? null,
            'symbol'         => $this->symbol['value'] ?? null,
            'name'           => $this->name['value'] ?? null,
            'decimal_places' => 2,
        ];
        if (null === $data['code']) {
            Log::debug('Need at least a code to create currency, return nothing.');

            return null;
        }

        Log::debug('Search for maps resulted in nothing, create new one based on', $data);
        $currency       = $this->repository->store($data);
        $this->currency = $currency;
        Log::info('Made new currency.', ['input' => $data, 'new' => $currency->toArray()]);

        return $currency;
    }

    /**
     * @param array $code
     */
    public function setCode(array $code)
    {
        $this->code = $code;
    }

    /**
     * @param array $id
     */
    public function setId(array $id)
    {
        $id['value'] = (int)$id['value'];
        $this->id    = $id;
    }

    /**
     * @param array $name
     */
    public function setName(array $name)
    {
        $this->name = $name;
    }

    /**
     * @param array $symbol
     */
    public function setSymbol(array $symbol)
    {
        $this->symbol = $symbol;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->repository->setUser($user);
    }

    /**
     * @return TransactionCurrency
     */
    private function findExistingObject(): ?TransactionCurrency
    {
        $search = [
            'id'     => 'findNull',
            'code'   => 'findByCodeNull',
            'symbol' => 'findBySymbolNull',
            'name'   => 'findByNameNull',
        ];
        foreach ($search as $field => $function) {
            $value = $this->$field['value'] ?? null;
            if (null !== $value) {
                Log::debug(sprintf('Searching for %s using function %s and value %s', $field, $function, $value));
                /** @var TransactionCurrency|null $currency */
                $currency = $this->repository->$function($value);

                if (null !== $currency) {
                    return $currency;
                }
            }
        }

        return null;
    }

    /**
     * @return TransactionCurrency
     */
    private function findMappedObject(): ?TransactionCurrency
    {
        Log::debug('In findMappedObject()');
        $fields = ['id', 'code', 'name', 'symbol'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped currency based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (null !== $mapped) {
                Log::debug(sprintf('Found currency #%d!', $mapped->id));

                return $mapped;
            }
        }
        Log::debug('Found no currency on mapped data or no map present.');

        return null;
    }

    /**
     * @param array $array
     *
     * @return TransactionCurrency
     */
    private function getMappedObject(array $array): ?TransactionCurrency
    {
        Log::debug('In getMappedObject()');
        if (0 === count($array)) {
            Log::debug('Array is empty, nothing will come of this.');

            return null;
        }

        if (array_key_exists('mapped', $array) && null === $array['mapped']) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return null;
        }

        Log::debug('Finding a mapped object based on', $array);

        $search   = (int)$array['mapped'];
        $currency = $this->repository->findNull($search);

        if (null === $currency) {
            Log::error(sprintf('There is no currency with id #%d. Invalid mapping will be ignored!', $search));

            return null;
        }

        Log::debug(sprintf('Found currency! #%d ("%s"). Return it', $currency->id, $currency->name));

        return $currency;
    }
}
