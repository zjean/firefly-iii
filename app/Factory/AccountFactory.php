<?php

/**
 * AccountFactory.php
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

/** @noinspection PhpDynamicAsStaticMethodCallInspection */
/** @noinspection PhpUndefinedMethodInspection */

declare(strict_types=1);

namespace FireflyIII\Factory;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Services\Internal\Support\AccountServiceTrait;
use FireflyIII\User;

/**
 * Factory to create or return accounts.
 *
 * Class AccountFactory
 */
class AccountFactory
{
    use AccountServiceTrait;
    /** @var User */
    private $user;

    /**
     * @param array $data
     *
     * @return Account
     * @throws FireflyException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function create(array $data): Account
    {
        $type = $this->getAccountType($data['account_type_id'], $data['accountType']);

        if (null === $type) {
            throw new FireflyException(
                sprintf('AccountFactory::create() was unable to find account type #%d ("%s").', $data['account_type_id'], $data['accountType'])
            );
        }

        $data['iban'] = $this->filterIban($data['iban']);

        // account may exist already:
        $return = $this->find($data['name'], $type->type);


        if (null === $return) {
            // create it:
            $databaseData
                = [
                'user_id'         => $this->user->id,
                'account_type_id' => $type->id,
                'name'            => $data['name'],
                'virtual_balance' => $data['virtualBalance'] ?? '0',
                'active'          => true === $data['active'],
                'iban'            => $data['iban'],
            ];

            // remove virtual balance when not an asset account:
            if ($type->type !== AccountType::ASSET) {
                $databaseData['virtual_balance'] = '0';
            }

            // fix virtual balance when it's empty
            if ('' === $databaseData['virtual_balance']) {
                $databaseData['virtual_balance'] = '0';
            }

            $return = Account::create($databaseData);
            $this->updateMetaData($return, $data);

            if ($type->type === AccountType::ASSET) {
                if ($this->validIBData($data)) {
                    $this->updateIB($return, $data);
                }
                if (!$this->validIBData($data)) {
                    $this->deleteIB($return);
                }
            }
            $this->updateNote($return, $data['notes'] ?? '');
        }

        return $return;
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account|null
     */
    public function find(string $accountName, string $accountType): ?Account
    {
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);
        $return   = null;
        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                $return = $object;
                break;
            }
        }

        return $return;
    }

    /**
     * @param string $accountName
     * @param string $accountType
     *
     * @return Account
     * @throws FireflyException
     */
    public function findOrCreate(string $accountName, string $accountType): Account
    {
        $type     = AccountType::whereType($accountType)->first();
        $accounts = $this->user->accounts()->where('account_type_id', $type->id)->get(['accounts.*']);
        $return   = null;
        /** @var Account $object */
        foreach ($accounts as $object) {
            if ($object->name === $accountName) {
                $return = $object;
                break;
            }
        }
        if (null === $return) {
            $return = $this->create(
                [
                    'user_id'         => $this->user->id,
                    'name'            => $accountName,
                    'account_type_id' => $type->id,
                    'accountType'     => null,
                    'virtualBalance'  => '0',
                    'iban'            => null,
                    'active'          => true,
                ]
            );
        }

        return $return;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param int|null    $accountTypeId
     * @param null|string $accountType
     *
     * @return AccountType|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getAccountType(?int $accountTypeId, ?string $accountType): ?AccountType
    {
        $accountTypeId = (int)$accountTypeId;
        $result        = null;
        if ($accountTypeId > 0) {
            $result = AccountType::find($accountTypeId);
        }
        if (null === $result) {
            /** @var string $type */
            $type   = (string)config('firefly.accountTypeByIdentifier.' . (string)$accountType);
            $result = AccountType::whereType($type)->first();
            if (null === $result && null !== $accountType) {
                // try as full name:
                $result = AccountType::whereType($accountType)->first();
            }
        }

        return $result;

    }

}
