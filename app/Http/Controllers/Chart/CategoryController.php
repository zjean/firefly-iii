<?php
/**
 * CategoryController.php
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

namespace FireflyIII\Http\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Http\Controllers\DateCalculation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Class CategoryController.
 */
class CategoryController extends Controller
{
    use DateCalculation;
    /** @var GeneratorInterface Chart generation methods. */
    protected $generator;

    /**
     * CategoryController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }


    /**
     * Show an overview for a category for all time, per month/week/year.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param CategoryRepositoryInterface $repository
     * @param AccountRepositoryInterface  $accountRepository
     * @param Category                    $category
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function all(CategoryRepositoryInterface $repository, AccountRepositoryInterface $accountRepository, Category $category): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.category.all');
        $cache->addProperty($category->id);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $start     = $repository->firstUseDate($category);
        $start     = $start ?? new Carbon;
        $range     = app('preferences')->get('viewRange', '1M')->data;
        $start     = app('navigation')->startOfPeriod($start, $range);
        $end       = new Carbon;
        $accounts  = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $chartData = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [], 'type' => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [], 'type' => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [], 'type' => 'line', 'fill' => false,
            ],
        ];
        $step      = $this->calculateStep($start, $end);
        $current = clone $start;
        switch ($step) {
            case '1D':
                while ($current <= $end) {
                    $spent                           = $repository->spentInPeriod(new Collection([$category]), $accounts, $current, $current);
                    $earned                          = $repository->earnedInPeriod(new Collection([$category]), $accounts, $current, $current);
                    $sum                             = bcadd($spent, $earned);
                    $label                           = app('navigation')->periodShow($current, $step);
                    $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
                    $chartData[1]['entries'][$label] = round($earned, 12);
                    $chartData[2]['entries'][$label] = round($sum, 12);
                    $current->addDay();
                }
                break;
            case '1W':
            case '1M':
            case '1Y':
                while ($current <= $end) {
                    $currentEnd                      = app('navigation')->endOfPeriod($current, $range);
                    $spent                           = $repository->spentInPeriod(new Collection([$category]), $accounts, $current, $currentEnd);
                    $earned                          = $repository->earnedInPeriod(new Collection([$category]), $accounts, $current, $currentEnd);
                    $sum                             = bcadd($spent, $earned);
                    $label                           = app('navigation')->periodShow($current, $step);
                    $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
                    $chartData[1]['entries'][$label] = round($earned, 12);
                    $chartData[2]['entries'][$label] = round($sum, 12);
                    $current= app('navigation')->addPeriod($current, $step, 0);
                }
                break;
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /**
     * Shows the category chart on the front page.
     *
     * @param CategoryRepositoryInterface $repository
     * @param AccountRepositoryInterface  $accountRepository
     *
     * @return JsonResponse
     */
    public function frontpage(CategoryRepositoryInterface $repository, AccountRepositoryInterface $accountRepository): JsonResponse
    {
        $start = session('start', Carbon::now()->startOfMonth());
        $end   = session('end', Carbon::now()->endOfMonth());
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.frontpage');
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        // currency repos:
        /** @var CurrencyRepositoryInterface $currencyRepository */
        $currencyRepository = app(CurrencyRepositoryInterface::class);
        $currencies         = [];


        $chartData  = [];
        $tempData   = [];
        $categories = $repository->getCategories();
        $accounts   = $accountRepository->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);

        /** @var Category $category */
        foreach ($categories as $category) {
            $spentArray = $repository->spentInPeriodPerCurrency(new Collection([$category]), $accounts, $start, $end);
            foreach ($spentArray as $currencyId => $spent) {
                if (bccomp($spent, '0') === -1) {
                    $currencies[$currencyId] = $currencies[$currencyId] ?? $currencyRepository->findNull($currencyId);
                    $tempData[]              = [
                        'name'        => $category->name,
                        'spent'       => bcmul($spent, '-1'),
                        'spent_float' => (float)bcmul($spent, '-1'),
                        'currency_id' => $currencyId,
                    ];
                }
            }
        }
        // no category per currency:
        $noCategory = $repository->spentInPeriodPcWoCategory(new Collection, $start, $end);
        foreach ($noCategory as $currencyId => $spent) {
            $currencies[$currencyId] = $currencies[$currencyId] ?? $currencyRepository->findNull($currencyId);
            $tempData[]              = [
                'name'        => trans('firefly.no_category'),
                'spent'       => bcmul($spent, '-1'),
                'spent_float' => (float)bcmul($spent, '-1'),
                'currency_id' => $currencyId,
            ];
        }

        // sort temp array by amount.
        $amounts = array_column($tempData, 'spent_float');
        array_multisort($amounts, SORT_DESC, $tempData);

        // loop all found currencies and build the data array for the chart.
        /**
         * @var int                 $currencyId
         * @var TransactionCurrency $currency
         */
        foreach ($currencies as $currencyId => $currency) {
            $dataSet                = [
                'label'           => (string)trans('firefly.spent'),
                'type'            => 'bar',
                'currency_symbol' => $currency->symbol,
                'entries'         => $this->expandNames($tempData),
            ];
            $chartData[$currencyId] = $dataSet;
        }
        // loop temp data and place data in correct array:
        foreach ($tempData as $entry) {
            $currencyId                               = $entry['currency_id'];
            $name                                     = $entry['name'];
            $chartData[$currencyId]['entries'][$name] = $entry['spent'];
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart report.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Category   $category
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function reportPeriod(Category $category, Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period');
        $cache->addProperty($accounts->pluck('id')->toArray());
        $cache->addProperty($category);
        if ($cache->has()) {
            return response()->json($cache->get());// @codeCoverageIgnore
        }
        $repository = app(CategoryRepositoryInterface::class);
        $expenses   = $repository->periodExpenses(new Collection([$category]), $accounts, $start, $end);
        $income     = $repository->periodIncome(new Collection([$category]), $accounts, $start, $end);
        $periods    = app('navigation')->listOfPeriods($start, $end);
        $chartData  = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $expenses[$category->id]['entries'][$period] ?? '0';
            $earned                          = $income[$category->id]['entries'][$period] ?? '0';
            $sum                             = bcadd($spent, $earned);
            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = round($earned, 12);
            $chartData[2]['entries'][$label] = round($sum, 12);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }


    /** @noinspection MoreThanThreeArgumentsInspection */

    /**
     * Chart for period for transactions without a category.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function reportPeriodNoCategory(Collection $accounts, Carbon $start, Carbon $end): JsonResponse
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty('chart.category.period.no-cat');
        $cache->addProperty($accounts->pluck('id')->toArray());
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $repository = app(CategoryRepositoryInterface::class);
        $expenses   = $repository->periodExpensesNoCategory($accounts, $start, $end);
        $income     = $repository->periodIncomeNoCategory($accounts, $start, $end);
        $periods    = app('navigation')->listOfPeriods($start, $end);
        $chartData  = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        foreach (array_keys($periods) as $period) {
            $label                           = $periods[$period];
            $spent                           = $expenses['entries'][$period] ?? '0';
            $earned                          = $income['entries'][$period] ?? '0';
            $sum                             = bcadd($spent, $earned);
            $chartData[0]['entries'][$label] = bcmul($spent, '-1');
            $chartData[1]['entries'][$label] = $earned;
            $chartData[2]['entries'][$label] = $sum;
        }
        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Chart for a specific period.
     *
     * TODO this chart is not multi-currency aware.
     *
     * @param Category                    $category
     * @param                             $date
     *
     * @return JsonResponse
     */
    public function specificPeriod(Category $category, Carbon $date): JsonResponse
    {
        $range = app('preferences')->get('viewRange', '1M')->data;
        $start = app('navigation')->startOfPeriod($date, $range);
        $end   = app('navigation')->endOfPeriod($date, $range);
        $data  = $this->makePeriodChart($category, $start, $end);

        return response()->json($data);
    }

    /**
     * Chart for a specific period (start and end).
     *
     *
     * @param Category $category
     * @param Carbon   $start
     * @param Carbon   $end
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function makePeriodChart(Category $category, Carbon $start, Carbon $end): array // chart helper method.
    {
        $cache = new CacheProperties;
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($category->id);
        $cache->addProperty('chart.category.period-chart');


        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository = app(AccountRepositoryInterface::class);
        $accounts          = $accountRepository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $repository        = app(CategoryRepositoryInterface::class);

        // chart data
        $chartData = [
            [
                'label'           => (string)trans('firefly.spent'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(219, 68, 55, 0.5)', // red
            ],
            [
                'label'           => (string)trans('firefly.earned'),
                'entries'         => [],
                'type'            => 'bar',
                'backgroundColor' => 'rgba(0, 141, 76, 0.5)', // green
            ],
            [
                'label'   => (string)trans('firefly.sum'),
                'entries' => [],
                'type'    => 'line',
                'fill'    => false,
            ],
        ];

        while ($start <= $end) {
            $spent  = $repository->spentInPeriod(new Collection([$category]), $accounts, $start, $start);
            $earned = $repository->earnedInPeriod(new Collection([$category]), $accounts, $start, $start);
            $sum    = bcadd($spent, $earned);
            $label  = trim(app('navigation')->periodShow($start, '1D'));

            $chartData[0]['entries'][$label] = round(bcmul($spent, '-1'), 12);
            $chartData[1]['entries'][$label] = round($earned, 12);
            $chartData[2]['entries'][$label] = round($sum, 12);

            $start->addDay();
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return $data;
    }

    /**
     * Small helper function for the revenue and expense account charts.
     *
     * @param array $names
     *
     * @return array
     */
    private function expandNames(array $names): array
    {
        $result = [];
        foreach ($names as $entry) {
            $result[$entry['name']] = 0;
        }

        return $result;
    }
}
