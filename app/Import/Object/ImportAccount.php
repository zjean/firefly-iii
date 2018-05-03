<?php
/**
 * ImportAccount.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class ImportAccount.
 */
class ImportAccount
{
    /** @var Account */
    private $account;
    /** @var array */
    private $accountBic = [];
    /** @var array */
    private $accountIban = [];
    /** @var array */
    private $accountId = [];
    /** @var array */
    private $accountName = [];
    /** @var array */
    private $accountNumber = [];
    /** @var int */
    private $defaultAccountId = 0;
    /** @var string */
    private $expectedType;
    /**
     * This value is used to indicate the other account ID (the opposing transaction's account),
     * if it is know. If so, this particular import account may never return an Account with this ID.
     * If it would, this would result in a transaction from-to the same account.
     *
     * @var int
     */
    private $forbiddenAccountId = 0;
    /** @var AccountRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * ImportAccount constructor.
     */
    public function __construct()
    {
        $this->expectedType = AccountType::ASSET;
        $this->repository   = app(AccountRepositoryInterface::class);
        Log::debug('Created ImportAccount.');
    }

    /**
     * @return Account
     *
     * @throws FireflyException
     */
    public function getAccount(): Account
    {
        if (null === $this->account) {
            $this->store();
        }

        return $this->account;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return string
     */
    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $expectedType
     */
    public function setExpectedType(string $expectedType)
    {
        $this->expectedType = $expectedType;
    }

    /**
     * @param array $accountBic
     */
    public function setAccountBic(array $accountBic): void
    {
        $this->accountBic = $accountBic;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array $accountIban
     */
    public function setAccountIban(array $accountIban)
    {
        $this->accountIban = $accountIban;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array $value
     */
    public function setAccountId(array $value)
    {
        $this->accountId = $value;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array $accountName
     */
    public function setAccountName(array $accountName)
    {
        $this->accountName = $accountName;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array $accountNumber
     */
    public function setAccountNumber(array $accountNumber)
    {
        $this->accountNumber = $accountNumber;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param int $defaultAccountId
     */
    public function setDefaultAccountId(int $defaultAccountId)
    {
        $this->defaultAccountId = $defaultAccountId;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param int $forbiddenAccountId
     */
    public function setForbiddenAccountId(int $forbiddenAccountId)
    {
        $this->forbiddenAccountId = $forbiddenAccountId;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        $this->repository->setUser($user);
    }

    /**
     * Find account by IBAN and type.
     *
     * @param AccountType $type
     *
     * @return Account|null
     */
    private function findByIBAN(AccountType $type): ?Account
    {
        if (3 === count($this->accountIban)) {
            $accounts = $this->repository->getAccountsByType([$type->type]);
            $iban     = $this->accountIban['value'];
            Log::debug(sprintf('Finding account of type %d and IBAN %s', $type->id, $iban));
            $filtered = $accounts->filter(
                function (Account $account) use ($iban) {
                    if ($account->iban === $iban && $account->id !== $this->forbiddenAccountId) {
                        Log::debug(
                            sprintf(
                                'Found unmapped %s account by IBAN (#%d): %s (%s)',
                                $this->expectedType, $account->id, $account->name, $account->iban
                            )
                        );

                        return $account;
                    }

                    return null;
                }
            );
            if (1 === $filtered->count()) {
                return $filtered->first();
            }
            Log::debug('Found nothing.');
        }

        return null;
    }

    /**
     * Find account of type X by its ID.
     *
     * @param AccountType $type
     *
     * @return Account|null
     */
    private function findById(AccountType $type): ?Account
    {
        if (3 === count($this->accountId)) {
            Log::debug(sprintf('Finding account of type %d and ID %d', $type->id, $this->accountId['value']));
            /** @var Account $account */
            $account = $this->user->accounts()
                                  ->where('id', '!=', $this->forbiddenAccountId)
                                  ->where('account_type_id', $type->id)
                                  ->where('id', $this->accountId['value'])
                                  ->first();

            if (null !== $account) {
                Log::debug(sprintf('Found unmapped %s account by ID (#%d): %s', $this->expectedType, $account->id, $account->name));

                return $account;
            }
            Log::debug('Found nothing.');
        }

        return null;
    }

    /**
     * Find account by account type and name.
     *
     * @param AccountType $type
     *
     * @return Account|null
     */
    private function findByName(AccountType $type): ?Account
    {
        // Three: find by name (and type):
        if (3 === count($this->accountName)) {
            $accounts = $this->repository->getAccountsByType([$type->type]);
            $name     = $this->accountName['value'];
            Log::debug(sprintf('Finding account of type %d and name %s', $type->id, $name));
            $filtered = $accounts->filter(
                function (Account $account) use ($name) {
                    if ($account->name === $name && $account->id !== $this->forbiddenAccountId) {
                        Log::debug(sprintf('Found unmapped %s account by name (#%d): %s', $this->expectedType, $account->id, $account->name));

                        return $account;
                    }

                    return null;
                }
            );

            if (1 === $filtered->count()) {
                return $filtered->first();
            }
            Log::debug('Found nothing.');
        }

        return null;
    }

    /**
     * Determin account type to find, then use fields in object to try and find it.
     *
     * @return Account|null
     */
    private function findExistingObject(): ?Account
    {
        Log::debug('In findExistingObject() for Account');
        /** @var AccountType $accountType */
        $accountType = $this->repository->getAccountType($this->expectedType);
        $result      = $this->findById($accountType);
        if (null !== $result) {
            return $result;
        }

        $result = $this->findByIBAN($accountType);

        if (null !== $result) {
            return $result;
        }

        $result = $this->findByName($accountType);

        if (null !== $result) {
            return $result;
        }

        Log::debug('Found NO existing accounts.');

        return null;
    }

    /**
     * @return Account|null
     */
    private function findMappedObject(): ?Account
    {
        Log::debug('In findMappedObject() for Account');
        $fields = ['accountId', 'accountIban', 'accountNumber', 'accountName'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped account based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (null !== $mapped) {
                Log::debug(sprintf('Found account #%d!', $mapped->id));

                return $mapped;
            }
        }
        Log::debug('Found no account on mapped data or no map present.');

        return null;
    }

    /**
     * @param array $array
     *
     * @return Account|null
     */
    private function getMappedObject(array $array): ?Account
    {
        Log::debug('In getMappedObject() for Account');
        if (0 === count($array)) {
            Log::debug('Array is empty, nothing will come of this.');

            return null;
        }

        if (array_key_exists('mapped', $array) && null === $array['mapped']) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return null;
        }

        Log::debug('Finding a mapped account based on', $array);

        $search  = (int)($array['mapped'] ?? 0.0);
        $account = $this->repository->findNull($search);

        if (null === $account) {
            Log::error(sprintf('There is no account with id #%d. Invalid mapping will be ignored!', $search));

            return null;
        }
        // must be of the same type
        // except when mapped is an asset, then it's fair game.
        // which only shows that user must map very carefully.
        if ($account->accountType->type !== $this->expectedType && AccountType::ASSET !== $account->accountType->type) {
            Log::error(
                sprintf(
                    'Mapped account #%d is of type "%s" but we expect a "%s"-account. Mapping will be ignored.',
                    $account->id,
                    $account->accountType->type,
                    $this->expectedType
                )
            );

            return null;
        }

        Log::debug(sprintf('Found account! #%d ("%s"). Return it', $account->id, $account->name));

        return $account;
    }

    /**
     * @return bool
     *
     * @throws FireflyException
     */
    private function store(): bool
    {
        if (null === $this->user) {
            throw new FireflyException('ImportAccount cannot continue without user.');
        }
        if ((null === $this->defaultAccountId || 0 === (int)$this->defaultAccountId) && AccountType::ASSET === $this->expectedType) {
            throw new FireflyException('ImportAccount cannot continue without a default account to fall back on.');
        }
        // 1: find mapped object:
        $mapped = $this->findMappedObject();
        if (null !== $mapped) {
            $this->account = $mapped;

            return true;
        }
        // 2: find existing by given values:
        $found = $this->findExistingObject();
        if (null !== $found) {
            $this->account = $found;

            return true;
        }

        // 3: if found nothing, retry the search with an asset account:
        Log::debug('Will try to find an asset account just in case.');
        $oldExpectedType    = $this->expectedType;
        $this->expectedType = AccountType::ASSET;
        $found              = $this->findExistingObject();
        if (null !== $found) {
            Log::debug('Found asset account!');
            $this->account = $found;

            return true;
        }
        $this->expectedType = $oldExpectedType;

        // 4: if search for an asset account, fall back to given "default account" (mandatory)
        if (AccountType::ASSET === $this->expectedType) {
            $this->account = $this->repository->findNull($this->defaultAccountId);
            Log::debug(sprintf('Fall back to default account #%d "%s"', $this->account->id, $this->account->name));

            return true;
        }

        // 5: then maybe, create one:
        Log::debug(sprintf('Found no account of type %s so must create one ourselves.', $this->expectedType));

        // make sure name field is sensible.
        $name = '(no name)';
        if (isset($this->accountNumber['value'])) {
            $name = $this->accountNumber['value'];
        }
        if (isset($this->accountIban['value'])) {
            $name = $this->accountIban['value'];
        }
        if (isset($this->accountName['value'])) {
            $name = $this->accountName['value'];
        }

        $data = [
            'accountType'     => config('firefly.shortNamesByFullName.' . $this->expectedType),
            'name'            => $name,
            'iban'            => $this->accountIban['value'] ?? null,
            'active'          => true,
            'accountNumber'   => $this->accountNumber['value'] ?? null,
            'virtualBalance'  => '0',
            'account_type_id' => null,
            'BIC'             => $this->accountBic['value'] ?? null,
        ];

        $this->account = $this->repository->store($data);
        Log::debug(sprintf('Successfully stored new account #%d: %s', $this->account->id, $this->account->name));

        return true;
    }
}
