<?php
/**
 * ImportBudget.php
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

use FireflyIII\Models\Budget;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\User;
use Log;

/**
 * Class ImportBudget.
 */
class ImportBudget
{
    /** @var Budget */
    private $budget;
    /** @var array */
    private $id = [];
    /** @var array */
    private $name = [];
    /** @var BudgetRepositoryInterface */
    private $repository;
    /** @var User */
    private $user;

    /**
     * ImportBudget constructor.
     */
    public function __construct()
    {
        $this->repository = app(BudgetRepositoryInterface::class);
        Log::debug('Created ImportBudget.');
    }

    /**
     * @return Budget|null
     */
    public function getBudget(): ?Budget
    {
        if (null === $this->budget) {
            $this->store();
        }

        return $this->budget;
    }

    /**
     * @param array $id
     */
    public function setId(array $id)
    {
        $this->id = $id;
    }

    /**
     * @param array $name
     */
    public function setName(array $name)
    {
        $this->name = $name;
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
     * @return Budget|null
     */
    private function findById(): ?Budget
    {
        if (3 === count($this->id)) {
            Log::debug(sprintf('Finding budget with ID #%d', $this->id['value']));
            /** @var Budget $budget */
            $budget = $this->repository->findNull((int)$this->id['value']);
            if (null !== $budget) {
                Log::debug(sprintf('Found unmapped budget by ID (#%d): %s', $budget->id, $budget->name));

                return $budget;
            }
            Log::debug('Found nothing.');
        }

        return null;
    }

    /**
     * @return Budget|null
     */
    private function findByName(): ?Budget
    {
        if (3 === count($this->name)) {
            $budgets = $this->repository->getBudgets();
            $name    = $this->name['value'];
            Log::debug(sprintf('Finding budget with name %s', $name));
            $filtered = $budgets->filter(
                function (Budget $budget) use ($name) {
                    if ($budget->name === $name) {
                        Log::debug(sprintf('Found unmapped budget by name (#%d): %s', $budget->id, $budget->name));

                        return $budget;
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
     * @return Budget
     */
    private function findExistingObject(): ?Budget
    {
        Log::debug('In findExistingObject() for Budget');
        $result = $this->findById();
        if (null !== $result) {
            return $result;
        }
        $result = $this->findByName();
        if (null !== $result) {
            return $result;
        }

        Log::debug('Found NO existing budgets.');

        return null;
    }

    /**
     * @return Budget
     */
    private function findMappedObject(): ?Budget
    {
        Log::debug('In findMappedObject() for Budget');
        $fields = ['id', 'name'];
        foreach ($fields as $field) {
            $array = $this->$field;
            Log::debug(sprintf('Find mapped budget based on field "%s" with value', $field), $array);
            // check if a pre-mapped object exists.
            $mapped = $this->getMappedObject($array);
            if (null !== $mapped) {
                Log::debug(sprintf('Found budget #%d!', $mapped->id));

                return $mapped;
            }
        }
        Log::debug('Found no budget on mapped data or no map present.');

        return null;
    }

    /**
     * @param array $array
     *
     * @return Budget
     */
    private function getMappedObject(array $array): ?Budget
    {
        Log::debug('In getMappedObject() for Budget');
        if (0 === count($array)) {
            Log::debug('Array is empty, nothing will come of this.');

            return null;
        }

        if (array_key_exists('mapped', $array) && null === $array['mapped']) {
            Log::debug(sprintf('No map present for value "%s". Return NULL.', $array['value']));

            return null;
        }

        Log::debug('Finding a mapped budget based on', $array);

        $search = (int)$array['mapped'];
        $budget = $this->repository->find($search);

        if (null === $budget->id) {
            Log::error(sprintf('There is no budget with id #%d. Invalid mapping will be ignored!', $search));

            return null;
        }

        Log::debug(sprintf('Found budget! #%d ("%s"). Return it', $budget->id, $budget->name));

        return $budget;
    }

    /**
     * @return bool
     */
    private function store(): bool
    {
        // 1: find mapped object:
        $mapped = $this->findMappedObject();
        if (null !== $mapped) {
            $this->budget = $mapped;

            return true;
        }
        // 2: find existing by given values:
        $found = $this->findExistingObject();
        if (null !== $found) {
            $this->budget = $found;

            return true;
        }
        $name = $this->name['value'] ?? '';

        if (0 === strlen($name)) {
            return true;
        }

        Log::debug('Found no budget so must create one ourselves.');

        $data = [
            'name' => $name,
        ];

        $this->budget = $this->repository->store($data);
        Log::debug(sprintf('Successfully stored new budget #%d: %s', $this->budget->id, $this->budget->name));

        return true;
    }
}
