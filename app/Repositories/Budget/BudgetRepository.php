<?php
/**
 * BudgetRepository.php
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
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\AvailableBudget;
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Log;
use Navigation;
use stdClass;

/**
 * Class BudgetRepository.
 */
class BudgetRepository implements BudgetRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * A method that returns the amount of money budgeted per day for this budget,
     * on average.
     *
     * @param Budget $budget
     *
     * @return string
     */
    public function budgetedPerDay(Budget $budget): string
    {
        $total = '0';
        $count = 0;
        foreach ($budget->budgetlimits as $limit) {
            $diff = (string)$limit->start_date->diffInDays($limit->end_date);
            if (bccomp('0', $diff) === 0) {
                $diff = '1';
            }
            $amount = (string)$limit->amount;
            $perDay = bcdiv($amount, $diff);
            $total  = bcadd($total, $perDay);
            $count++;
        }
        $avg = $total;
        if ($count > 0) {
            $avg = bcdiv($total, (string)$count);
        }

        return $avg;
    }

    /**
     * @return bool
     *


     */
    public function cleanupBudgets(): bool
    {
        // delete limits with amount 0:
        BudgetLimit::where('amount', 0)->delete();

        // do the clean up by hand because Sqlite can be tricky with this.
        $budgetLimits = BudgetLimit::orderBy('created_at', 'DESC')->get(['id', 'budget_id', 'start_date', 'end_date']);
        $count        = [];
        /** @var BudgetLimit $budgetLimit */
        foreach ($budgetLimits as $budgetLimit) {
            $key = $budgetLimit->budget_id . '-' . $budgetLimit->start_date->format('Y-m-d') . $budgetLimit->end_date->format('Y-m-d');
            if (isset($count[$key])) {
                // delete it!
                BudgetLimit::find($budgetLimit->id)->delete();
            }
            $count[$key] = true;
        }

        return true;
    }

    /**
     * This method collects various info on budgets, used on the budget page and on the index.
     *
     * @param Collection $budgets
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function collectBudgetInformation(Collection $budgets, Carbon $start, Carbon $end): array
    {
        // get account information
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $defaultCurrency   = app('amount')->getDefaultCurrency();
        $return            = [];
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $budgetId          = $budget->id;
            $return[$budgetId] = [
                'spent'      => $this->spentInPeriod(new Collection([$budget]), $accounts, $start, $end),
                'budgeted'   => '0',
                'currentRep' => false,
            ];
            $budgetLimits      = $this->getBudgetLimits($budget, $start, $end);
            $otherLimits       = new Collection;

            // get all the budget limits relevant between start and end and examine them:
            /** @var BudgetLimit $limit */
            foreach ($budgetLimits as $limit) {
                if ($limit->start_date->isSameDay($start) && $limit->end_date->isSameDay($end)
                ) {
                    $return[$budgetId]['currentLimit'] = $limit;
                    $return[$budgetId]['budgeted']     = round($limit->amount, $defaultCurrency->decimal_places);
                    continue;
                }
                // otherwise it's just one of the many relevant repetitions:
                $otherLimits->push($limit);
            }
            $return[$budgetId]['otherLimits'] = $otherLimits;
        }

        return $return;
    }

    /**
     * @param Budget $budget
     *
     * @return bool
     *

     */
    public function destroy(Budget $budget): bool
    {
        $budget->delete();

        return true;
    }

    /**
     * Filters entries from the result set generated by getBudgetPeriodReport.
     *
     * @param Collection $set
     * @param int        $budgetId
     * @param array      $periods
     *
     * @return array
     */
    public function filterAmounts(Collection $set, int $budgetId, array $periods): array
    {
        $arr  = [];
        $keys = array_keys($periods);
        foreach ($keys as $period) {
            /** @var stdClass $object */
            $result = $set->filter(
                function (TransactionJournal $object) use ($budgetId, $period) {
                    $result = (string)$object->period_marker === (string)$period && $budgetId === (int)$object->budget_id;

                    return $result;
                }
            );
            $amount = '0';
            if (null !== $result->first()) {
                $amount = $result->first()->sum_of_period;
            }

            $arr[$period] = $amount;
        }

        return $arr;
    }

    /**
     * Find a budget.
     *
     * @param int $budgetId
     *
     * @return Budget
     */
    public function find(int $budgetId): Budget
    {
        $budget = $this->user->budgets()->find($budgetId);
        if (null === $budget) {
            $budget = new Budget;
        }

        return $budget;
    }

    /**
     * Find a budget.
     *
     * @param string $name
     *
     * @return Budget|null
     */
    public function findByName(string $name): ?Budget
    {
        $budgets = $this->user->budgets()->get(['budgets.*']);
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            if ($budget->name === $name) {
                return $budget;
            }
        }

        return null;
    }

    /**
     * Find a budget or return NULL
     *
     * @param int $budgetId
     *
     * @return Budget|null
     */
    public function findNull(int $budgetId): ?Budget
    {
        return $this->user->budgets()->find($budgetId);
    }

    /**
     * This method returns the oldest journal or transaction date known to this budget.
     * Will cache result.
     *
     * @param Budget $budget
     *
     * @return Carbon
     */
    public function firstUseDate(Budget $budget): Carbon
    {
        $oldest  = Carbon::create()->startOfYear();
        $journal = $budget->transactionJournals()->orderBy('date', 'ASC')->first();
        if (null !== $journal) {
            $oldest = $journal->date < $oldest ? $journal->date : $oldest;
        }

        $transaction = $budget
            ->transactions()
            ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.id')
            ->orderBy('transaction_journals.date', 'ASC')->first(['transactions.*', 'transaction_journals.date']);
        if (null !== $transaction) {
            $carbon = new Carbon($transaction->date);
            $oldest = $carbon < $oldest ? $carbon : $oldest;
        }

        return $oldest;
    }

    /**
     * @return Collection
     */
    public function getActiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 1)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getAllBudgetLimits(Carbon $start, Carbon $end): Collection
    {
        $set = BudgetLimit::leftJoin('budgets', 'budgets.id', '=', 'budget_limits.budget_id')
                          ->with(['budget'])
                          ->where('budgets.user_id', $this->user->id)
                          ->where(
                              function (Builder $q5) use ($start, $end) {
                                  $q5->where(
                                      function (Builder $q1) use ($start, $end) {
                                          $q1->where(
                                              function (Builder $q2) use ($start, $end) {
                                                  $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 00:00:00'));
                                              }
                                          )
                                             ->orWhere(
                                                 function (Builder $q3) use ($start, $end) {
                                                     $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                     $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 00:00:00'));
                                                 }
                                             );
                                      }
                                  )
                                     ->orWhere(
                                         function (Builder $q4) use ($start, $end) {
                                             // or start is before start AND end is after end.
                                             $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 00:00:00'));
                                             $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                                         }
                                     );
                              }
                          )->get(['budget_limits.*']);

        return $set;
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     *
     * @return string
     */
    public function getAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end): string
    {
        $amount          = '0';
        $availableBudget = $this->user->availableBudgets()
                                      ->where('transaction_currency_id', $currency->id)
                                      ->where('start_date', $start->format('Y-m-d 00:00:00'))
                                      ->where('end_date', $end->format('Y-m-d 00:00:00'))->first();
        if (null !== $availableBudget) {
            $amount = (string)$availableBudget->amount;
        }

        return $amount;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getBudgetLimits(Budget $budget, Carbon $start, Carbon $end): Collection
    {
        $set = $budget->budgetlimits()
                      ->where(
                          function (Builder $q5) use ($start, $end) {
                              $q5->where(
                                  function (Builder $q1) use ($start, $end) {
                                      // budget limit ends within period
                                      $q1->where(
                                          function (Builder $q2) use ($start, $end) {
                                              $q2->where('budget_limits.end_date', '>=', $start->format('Y-m-d 00:00:00'));
                                              $q2->where('budget_limits.end_date', '<=', $end->format('Y-m-d 00:00:00'));
                                          }
                                      )
                                          // budget limit start within period
                                         ->orWhere(
                                              function (Builder $q3) use ($start, $end) {
                                                  $q3->where('budget_limits.start_date', '>=', $start->format('Y-m-d 00:00:00'));
                                                  $q3->where('budget_limits.start_date', '<=', $end->format('Y-m-d 00:00:00'));
                                              }
                                          );
                                  }
                              )
                                 ->orWhere(
                                     function (Builder $q4) use ($start, $end) {
                                         // or start is before start AND end is after end.
                                         $q4->where('budget_limits.start_date', '<=', $start->format('Y-m-d 00:00:00'));
                                         $q4->where('budget_limits.end_date', '>=', $end->format('Y-m-d 00:00:00'));
                                     }
                                 );
                          }
                      )->orderBy('budget_limits.start_date', 'DESC')->get(['budget_limits.*']);

        return $set;
    }

    /**
     * This method is being used to generate the budget overview in the year/multi-year report. Its used
     * in both the year/multi-year budget overview AND in the accompanying chart.
     *
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getBudgetPeriodReport(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        $data         = [];
        // prep data array:
        /** @var Budget $budget */
        foreach ($budgets as $budget) {
            $data[$budget->id] = [
                'name'    => $budget->name,
                'sum'     => '0',
                'entries' => [],
            ];
        }

        // get all transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setBudgets($budgets);
        $transactions = $collector->getJournals();

        // loop transactions:
        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $budgetId                          = max((int)$transaction->transaction_journal_budget_id, (int)$transaction->transaction_budget_id);
            $date                              = $transaction->date->format($carbonFormat);
            $data[$budgetId]['entries'][$date] = bcadd($data[$budgetId]['entries'][$date] ?? '0', $transaction->transaction_amount);
        }

        return $data;
    }

    /**
     * @return Collection
     */
    public function getBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @return Collection
     */
    public function getInactiveBudgets(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->budgets()->where('active', 0)->get();

        $set = $set->sortBy(
            function (Budget $budget) {
                return strtolower($budget->name);
            }
        );

        return $set;
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    public function getNoBudgetPeriodReport(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $carbonFormat = Navigation::preferredCarbonFormat($start, $end);
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts($accounts)->setRange($start, $end);
        $collector->setTypes([TransactionType::WITHDRAWAL]);
        $collector->withoutBudget();
        $transactions = $collector->getJournals();
        $result       = [
            'entries' => [],
            'name'    => (string)trans('firefly.no_budget'),
            'sum'     => '0',
        ];

        foreach ($transactions as $transaction) {
            $date = $transaction->date->format($carbonFormat);

            if (!isset($result['entries'][$date])) {
                $result['entries'][$date] = '0';
            }
            $result['entries'][$date] = bcadd($result['entries'][$date], $transaction->transaction_amount);
        }

        return $result;
    }

    /**
     * @param TransactionCurrency $currency
     * @param Carbon              $start
     * @param Carbon              $end
     * @param string              $amount
     *
     * @return bool
     */
    public function setAvailableBudget(TransactionCurrency $currency, Carbon $start, Carbon $end, string $amount): bool
    {
        $availableBudget = $this->user->availableBudgets()
                                      ->where('transaction_currency_id', $currency->id)
                                      ->where('start_date', $start->format('Y-m-d 00:00:00'))
                                      ->where('end_date', $end->format('Y-m-d 00:00:00'))->first();
        if (null === $availableBudget) {
            $availableBudget = new AvailableBudget;
            $availableBudget->user()->associate($this->user);
            $availableBudget->transactionCurrency()->associate($currency);
            $availableBudget->start_date = $start->format('Y-m-d 00:00:00');
            $availableBudget->end_date   = $end->format('Y-m-d 00:00:00');
        }
        $availableBudget->amount = $amount;
        $availableBudget->save();

        return true;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param Collection $budgets
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriod(Collection $budgets, Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setBudgets($budgets)->withBudgetInformation();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getJournals();

        return strval($set->sum('transaction_amount'));
    }

    /**
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return string
     */
    public function spentInPeriodWoBudget(Collection $accounts, Carbon $start, Carbon $end): string
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser($this->user);
        $collector->setRange($start, $end)->setTypes([TransactionType::WITHDRAWAL])->withoutBudget();

        if ($accounts->count() > 0) {
            $collector->setAccounts($accounts);
        }
        if (0 === $accounts->count()) {
            $collector->setAllAssetAccounts();
        }

        $set = $collector->getJournals();
        $set = $set->filter(
            function (Transaction $transaction) {
                if (bccomp($transaction->transaction_amount, '0') === -1) {
                    return $transaction;
                }

                return null;
            }
        );

        return strval($set->sum('transaction_amount'));
    }

    /**
     * @param array $data
     *
     * @return Budget
     */
    public function store(array $data): Budget
    {
        $newBudget = new Budget(
            [
                'user_id' => $this->user->id,
                'name'    => $data['name'],
            ]
        );
        $newBudget->save();

        return $newBudget;
    }

    /**
     * @param Budget $budget
     * @param array  $data
     *
     * @return Budget
     */
    public function update(Budget $budget, array $data): Budget
    {
        // update the account:
        $budget->name   = $data['name'];
        $budget->active = $data['active'];
        $budget->save();

        return $budget;
    }

    /**
     * @param Budget $budget
     * @param Carbon $start
     * @param Carbon $end
     * @param string $amount
     *
     * @return BudgetLimit
     *

     */
    public function updateLimitAmount(Budget $budget, Carbon $start, Carbon $end, string $amount): BudgetLimit
    {
        $this->cleanupBudgets();
        // count the limits:
        $limits = $budget->budgetlimits()
                         ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                         ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                         ->get(['budget_limits.*'])->count();
        Log::debug(sprintf('Found %d budget limits.', $limits));
        // there might be a budget limit for these dates:
        /** @var BudgetLimit $limit */
        $limit = $budget->budgetlimits()
                        ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                        ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                        ->first(['budget_limits.*']);

        // if more than 1 limit found, delete the others:
        if ($limits > 1 && null !== $limit) {
            Log::debug(sprintf('Found more than 1, delete all except #%d', $limit->id));
            $budget->budgetlimits()
                   ->where('budget_limits.start_date', $start->format('Y-m-d 00:00:00'))
                   ->where('budget_limits.end_date', $end->format('Y-m-d 00:00:00'))
                   ->where('budget_limits.id', '!=', $limit->id)->delete();
        }

        // delete if amount is zero.
        // Returns 0 if the two operands are equal,
        // 1 if the left_operand is larger than the right_operand, -1 otherwise.
        if (null !== $limit && bccomp($amount, '0') <= 0) {
            Log::debug(sprintf('%s is zero, delete budget limit #%d', $amount, $limit->id));
            $limit->delete();

            return new BudgetLimit;
        }
        // update if exists:
        if (null !== $limit) {
            Log::debug(sprintf('Existing budget limit is #%d, update this to amount %s', $limit->id, $amount));
            $limit->amount = $amount;
            $limit->save();

            return $limit;
        }
        Log::debug('No existing budget limit, create a new one');
        // or create one and return it.
        $limit = new BudgetLimit;
        $limit->budget()->associate($budget);
        $limit->start_date = $start->format('Y-m-d 00:00:00');
        $limit->end_date   = $end->format('Y-m-d 00:00:00');
        $limit->amount     = $amount;
        $limit->save();
        Log::debug(sprintf('Created new budget limit with ID #%d and amount %s', $limit->id, $amount));

        return $limit;
    }
}
