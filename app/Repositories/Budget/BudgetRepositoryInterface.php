<?php
/**
 * BudgetRepositoryInterface.php
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

namespace FireflyIII\Repositories\Budget;

use Carbon\Carbon;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;

/**
 * Interface BudgetRepositoryInterface.
 */
interface BudgetRepositoryInterface
{

    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     *
     * @param Budget $budget
     *
     * @return string
     */
    public function budgetedPerDay(Budget $budget): string;

    /**
     * @return bool
     */
    public function cleanupBudgets(): bool;

    /**
     * This method collects various info on budgets, used on the budget page and on the index.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function collectBudgetInformation(Collection $budgets, Carbon $start, Carbon $end): array;

    /**
     * @param Budget $budget
     *
     * @return bool
     */
    public function destroy(Budget $budget): bool;

    /**
     * @param AvailableBudget $availableBudget
     */
    public function destroyAvailableBudget(AvailableBudget $availableBudget): void;

    /**
     * Destroy a budget limit.
     *
     * @param BudgetLimit $budgetLimit
     */
    public function destroyBudgetLimit(BudgetLimit $budgetLimit): void;

    /**
     * Find a budget.
     *
     * @param string $name
     *
     * @return Budget|null
     */
    public function findByName(string $name): ?Budget;

    /**
     * @param int|null $budgetId
     *
     * @return Budget|null
     */
    public function findNull(int $budgetId = null): ?Budget;

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstUseDate(Budget $budget): ?Carbon;

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection;

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllBudgetLimits(Carbon $start = null, Carbon $end = null): Collection;

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return string
     */
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string;

    /**
     * Returns all available budget objects.
     *
     * @return Collection
     */
    public function getAvailableBudgets(): Collection;

    /**
     * Calculate the average amount in the budgets available in this period.
     * Grouped by day.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getAverageAvailable(Carbon $start, Carbon $end): string;

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBudgetLimits(Budget $budget, Carbon $start = null, Carbon $end = null): Collection;

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array;

    /**
     * @return Collection
     */
    public function getBudgets(): Collection;

    /**
     * Get all budgets with these ID's.
     *
     * @param array $budgetIds
     *
     * @return Collection
     */
    public function getByIds(array $budgetIds): Collection;

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getNoBudgetPeriodReport(Collection $accounts, Carbon $start, Carbon $end): array;

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     * @param string              $amount
     *
     * @return AvailableBudget
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): AvailableBudget;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWoBudget(Collection $accounts, Carbon $start, Carbon $end): string;

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget;

    /**
     * @param array $data
     *
     * @return BudgetLimit
     */
    public function storeBudgetLimit(array $data): BudgetLimit;

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget;

    /**
     * @param AvailableBudget $availableBudget
     * @param array           $data
     *
     * @return AvailableBudget
     */
    public function updateAvailableBudget(AvailableBudget $availableBudget, array $data): AvailableBudget;

    /**
     * @param BudgetLimit $budgetLimit
     * @param array       $data
     *
     * @return BudgetLimit
     */
    public function updateBudgetLimit(BudgetLimit $budgetLimit, array $data): BudgetLimit;

    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $amount
     *
     * @return BudgetLimit|null
     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): ?BudgetLimit;
}
