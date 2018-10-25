<?php
/**
 * AmountController.php
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

namespace FireflyIII\Http\Controllers\Budget;


use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\BudgetIncomeRequest;
use FireflyIII\Models\Budget;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;

/**
 * Class AmountController
 */
class AmountController extends Controller
{
    use DateCalculation;
    /** @var BudgetRepositoryInterface The budget repository */
    private $repository;

    /**
     * AmountController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        app('view')->share('hideBudgets', true);

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.budgets'));
                app('view')->share('mainTitleIcon', 'fa-tasks');
                $this->repository = app(BudgetRepositoryInterface::class);

                return $next($request);
            }
        );
    }


    /**
     * Set the amount for a single budget in a specific period. Shows a waring when its a lot.
     *
     * @param Request                   $request
     * @param BudgetRepositoryInterface $repository
     * @param Budget                    $budget
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function amount(Request $request, BudgetRepositoryInterface $repository, Budget $budget): JsonResponse
    {
        // grab vars from URI
        $amount = (string)$request->get('amount');
        $start  = Carbon::createFromFormat('Y-m-d', $request->get('start'));
        $end    = Carbon::createFromFormat('Y-m-d', $request->get('end'));

        // grab other useful vars
        $currency       = app('amount')->getDefaultCurrency();
        $activeDaysLeft = $this->activeDaysLeft($start, $end);
        $periodLength   = $start->diffInDays($end) + 1; // absolute period length.

        // update limit amount:
        $budgetLimit = $this->repository->updateLimitAmount($budget, $start, $end, $amount);

        // calculate what the user has spent in current period.
        $spent = $repository->spentInPeriod(new Collection([$budget]), new Collection, $start, $end);

        // given the new budget, this is what they have left (and left per day?)
        $left       = app('amount')->formatAnything($currency, bcadd($amount, $spent), true);
        $leftPerDay = null; //

        // If the user budgets ANY amount per day for this budget (anything but zero) Firefly III calculates how much he could spend per day.
        if (1 === bccomp(bcadd($amount, $spent), '0')) {
            $leftPerDay = app('amount')->formatAnything($currency, bcdiv(bcadd($amount, $spent), (string)$activeDaysLeft), true);
        }

        $largeDiff = false;
        $warnText  = '';

        // Get the average amount of money the user budgets for this budget. And calculate the same for the current amount.
        // If the difference is very large, give the user a notification.
        $average = $this->repository->budgetedPerDay($budget);
        $current = bcdiv($amount, (string)$periodLength);
        if (bccomp(bcmul('1.1', $average), $current) === -1) {
            $largeDiff = true;
            $warnText  = (string)trans(
                'firefly.over_budget_warn',
                [
                    'amount'      => app('amount')->formatAnything($currency, $average, false),
                    'over_amount' => app('amount')->formatAnything($currency, $current, false),
                ]
            );
        }

        app('preferences')->mark();

        return response()->json(
            [
                'left'         => $left,
                'name'         => $budget->name,
                'limit'        => $budgetLimit ? $budgetLimit->id : 0,
                'amount'       => $amount,
                'current'      => $current,
                'average'      => $average,
                'large_diff'   => $largeDiff,
                'left_per_day' => $leftPerDay,
                'warn_text'    => $warnText,
            ]
        );
    }


    /**
     * Shows some basic info about the income and the suggested budget.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function infoIncome(Carbon $start, Carbon $end)
    {
        $range = app('preferences')->get('viewRange', '1M')->data;
        /** @var Carbon $searchBegin */
        $searchBegin        = app('navigation')->subtractPeriod($start, $range, 3);
        $searchEnd          = app('navigation')->addPeriod($end, $range, 3);
        $daysInPeriod       = $start->diffInDays($end);
        $daysInSearchPeriod = $searchBegin->diffInDays($searchEnd);
        $average            = $this->repository->getAverageAvailable($start, $end);
        $available          = bcmul($average, (string)$daysInPeriod);

        Log::debug(sprintf('Average is %s, so total available is %s because days is %d.', $average, $available, $daysInPeriod));

        // amount earned in this period:
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($searchBegin, $searchEnd)->setTypes([TransactionType::DEPOSIT])->withOpposingAccount();
        $earned = (string)$collector->getTransactions()->sum('transaction_amount');
        // Total amount earned divided by the number of days in the whole search period is the average amount earned per day.
        // This is multiplied by the number of days in the current period, showing you the average.
        $earnedAverage = bcmul(bcdiv($earned, (string)$daysInSearchPeriod), (string)$daysInPeriod);

        Log::debug(sprintf('Earned is %s, earned average is %s', $earned, $earnedAverage));

        // amount spent in period
        /** @var TransactionCollectorInterface $collector */
        $collector = app(TransactionCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($searchBegin, $searchEnd)->setTypes([TransactionType::WITHDRAWAL])->withOpposingAccount();
        $spent        = (string)$collector->getTransactions()->sum('transaction_amount');
        $spentAverage = app('steam')->positive(bcmul(bcdiv($spent, (string)$daysInSearchPeriod), (string)$daysInPeriod));

        Log::debug(sprintf('Spent is %s, spent average is %s', $earned, $earnedAverage));

        // the default suggestion is the money the user has spent, on average, over this period.
        $suggested = $spentAverage;

        Log::debug(sprintf('Suggested is now %s (spent average)',$suggested));

        // if the user makes less per period, suggest that amount instead.
        if (1 === bccomp($spentAverage, $earnedAverage)) {
            Log::debug(sprintf('Because earned average (%s) is less than spent average (%s) will suggest earned average instead.', $earnedAverage, $spentAverage));
            $suggested = $earnedAverage;
        }

        $result = ['available' => $available, 'earned' => $earnedAverage, 'spent' => $spentAverage, 'suggested' => $suggested,];

        return view('budgets.info', compact('result', 'searchBegin', 'searchEnd', 'start', 'end'));
    }


    /**
     * Store an available budget for the current period.
     *
     * @param BudgetIncomeRequest $request
     *
     * @return RedirectResponse
     */
    public function postUpdateIncome(BudgetIncomeRequest $request): RedirectResponse
    {
        $start           = Carbon::createFromFormat('Y-m-d', $request->string('start'));
        $end             = Carbon::createFromFormat('Y-m-d', $request->string('end'));
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $amount          = $request->get('amount');
        $page            = 0 === $request->integer('page') ? 1 : $request->integer('page');
        $this->repository->cleanupBudgets();
        $this->repository->setAvailableBudget($defaultCurrency, $start, $end, $amount);
        app('preferences')->mark();

        return redirect(route('budgets.index', [$start->format('Y-m-d')]) . '?page=' . $page);
    }

    /**
     * Shows the form to update available budget.
     *
     * @param Request $request
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function updateIncome(Request $request, Carbon $start, Carbon $end)
    {
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $available       = $this->repository->getAvailableBudget($defaultCurrency, $start, $end);
        $available       = round($available, $defaultCurrency->decimal_places);
        $page            = (int)$request->get('page');

        return view('budgets.income', compact('available', 'start', 'end', 'page'));
    }
}
