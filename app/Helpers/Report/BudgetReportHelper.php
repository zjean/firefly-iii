<?php
/**
 * BudgetReportHelper.php
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

namespace FireflyIII\Helpers\Report;

use Carbon\Carbon;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Class BudgetReportHelper.
 */
class BudgetReportHelper implements BudgetReportHelperInterface
{
    /** @var BudgetRepositoryInterface */
    private $repository;

    /**
     * BudgetReportHelper constructor.
     *
     * @param BudgetRepositoryInterface $repository
     */
    public function __construct(BudgetRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity) // it's exactly 5.
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength) // all the arrays make it long.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return array
     */
    public function getBudgetReport(Carbon $start, Carbon $end, Collection $accounts): array
    {
        $set   = $this->repository->getBudgets();
        $array = [];

        /** @var Budget $budget */
        foreach ($set as $budget) {
            $budgetLimits = $this->repository->getBudgetLimits($budget, $start, $end);
            if (0 === $budgetLimits->count()) { // no budget limit(s) for this budget
                $spent = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end); // spent for budget in time range
                if (bccomp($spent, '0') === -1) {
                    $line    = [
                        'type'      => 'budget',
                        'id'        => $budget->id,
                        'name'      => $budget->name,
                        'budgeted'  => '0',
                        'spent'     => $spent,
                        'left'      => '0',
                        'overspent' => '0',
                    ];
                    $array[] = $line;
                }
                continue;
            }
            /** @var BudgetLimit $budgetLimit */
            foreach ($budgetLimits as $budgetLimit) { // one or more repetitions for budget
                $data    = $this->calculateExpenses($budget, $budgetLimit, $accounts);
                $line    = [
                    'type'  => 'budget-line',
                    'start' => $budgetLimit->start_date,
                    'end'   => $budgetLimit->end_date,
                    'limit' => $budgetLimit->id,
                    'id'    => $budget->id,
                    'name'  => $budget->name,

                    'budgeted'  => (string)$budgetLimit->amount,
                    'spent'     => $data['expenses'],
                    'left'      => $data['left'],
                    'overspent' => $data['overspent'],
                ];
                $array[] = $line;
            }
        }
        $noBudget = $this->repository->spentInPeriodWoBudget($accounts, $start, $end); // stuff outside of budgets
        $line     = [
            'type'      => 'no-budget',
            'budgeted'  => '0',
            'spent'     => $noBudget,
            'left'      => '0',
            'overspent' => '0',
        ];
        $array[]  = $line;

        return $array;
    }

    /**
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBudgetsWithExpenses(Carbon $start, Carbon $end, Collection $accounts): Collection
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $budgets    = $repository->getActiveBudgets();

        $set = new Collection;
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $total = $repository->spentInPeriod(new Collection([$budget]), $accounts, $start, $end);
            if (bccomp($total, '0') === -1) {
                $set->push($budget);
            }
        }
        $set = $set->sortBy(
            function (Budget $budget) {
                return $budget->name;
            }
        );

        return $set;
    }

    /**
     * @param Budget      $budget
     * @param BudgetLimit $budgetLimit
     * @param Collection  $accounts
     *
     * @return array
     */
    private function calculateExpenses(Budget $budget, BudgetLimit $budgetLimit, Collection $accounts): array
    {
        $array              = [];
        $expenses           = $this->repository->spentInPeriod(new Collection([$budget]), $accounts, $budgetLimit->start_date, $budgetLimit->end_date);
        $array['left']      = 1 === bccomp(bcadd($budgetLimit->amount, $expenses), '0') ? bcadd($budgetLimit->amount, $expenses) : '0';
        $array['spent']     = 1 === bccomp(bcadd($budgetLimit->amount, $expenses), '0') ? $expenses : '0';
        $array['overspent'] = 1 === bccomp(bcadd($budgetLimit->amount, $expenses), '0') ? '0' : bcadd($expenses, $budgetLimit->amount);
        $array['expenses']  = $expenses;

        return $array;
    }
}
