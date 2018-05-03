<?php
/**
 * ReportController.php
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
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Support\CacheProperties;
use Illuminate\Support\Collection;
use Log;
use Steam;

/**
 * Class ReportController.
 */
class ReportController extends Controller
{
    /** @var GeneratorInterface */
    protected $generator;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        // create chart generator:
        $this->generator = app(GeneratorInterface::class);
    }

    /**
     * This chart, by default, is shown on the multi-year and year report pages,
     * which means that giving it a 2 week "period" should be enough granularity.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function netWorth(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.net-worth');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        $current   = clone $start;
        $chartData = [];
        while ($current < $end) {
            $balances          = Steam::balancesByAccounts($accounts, $current);
            $sum               = $this->arraySum($balances);
            $label             = $current->formatLocalized((string)trans('config.month_and_day'));
            $chartData[$label] = $sum;
            $current->addDays(7);
        }

        $data = $this->generator->singleSet((string)trans('firefly.net_worth'), $chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows income and expense, debit/credit: operations.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function operations(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.operations');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }
        Log::debug('Going to do operations for accounts ', $accounts->pluck('id')->toArray());
        $format    = app('navigation')->preferredCarbonLocalizedFormat($start, $end);
        $source    = $this->getChartData($accounts, $start, $end);
        $chartData = [
            [
                'label'   => trans('firefly.income'),
                'type'    => 'bar',
                'entries' => [],
            ],
            [
                'label'   => trans('firefly.expenses'),
                'type'    => 'bar',
                'entries' => [],
            ],
        ];

        foreach ($source['earned'] as $date => $amount) {
            $carbon                          = new Carbon($date);
            $label                           = $carbon->formatLocalized($format);
            $earned                          = $chartData[0]['entries'][$label] ?? '0';
            $chartData[0]['entries'][$label] = bcadd($earned, $amount);
        }
        foreach ($source['spent'] as $date => $amount) {
            $carbon                          = new Carbon($date);
            $label                           = $carbon->formatLocalized($format);
            $spent                           = $chartData[1]['entries'][$label] ?? '0';
            $chartData[1]['entries'][$label] = bcadd($spent, $amount);
        }

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * Shows sum income and expense, debit/credit: operations.
     *
     * @param Carbon     $start
     * @param Carbon     $end
     * @param Collection $accounts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sum(Collection $accounts, Carbon $start, Carbon $end)
    {
        // chart properties for cache:
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.sum');
        $cache->addProperty($start);
        $cache->addProperty($end);
        $cache->addProperty($accounts);
        if ($cache->has()) {
            return response()->json($cache->get()); // @codeCoverageIgnore
        }

        $source  = $this->getChartData($accounts, $start, $end);
        $numbers = [
            'sum_earned'   => '0',
            'avg_earned'   => '0',
            'count_earned' => 0,
            'sum_spent'    => '0',
            'avg_spent'    => '0',
            'count_spent'  => 0,
        ];
        foreach ($source['earned'] as $amount) {
            $numbers['sum_earned'] = bcadd($amount, $numbers['sum_earned']);
            ++$numbers['count_earned'];
        }
        if ($numbers['count_earned'] > 0) {
            $numbers['avg_earned'] = $numbers['sum_earned'] / $numbers['count_earned'];
        }
        foreach ($source['spent'] as $amount) {
            $numbers['sum_spent'] = bcadd($amount, $numbers['sum_spent']);
            ++$numbers['count_spent'];
        }
        if ($numbers['count_spent'] > 0) {
            $numbers['avg_spent'] = $numbers['sum_spent'] / $numbers['count_spent'];
        }

        $chartData = [
            [
                'label'   => (string)trans('firefly.income'),
                'type'    => 'bar',
                'entries' => [
                    (string)trans('firefly.sum_of_period')     => $numbers['sum_earned'],
                    (string)trans('firefly.average_in_period') => $numbers['avg_earned'],
                ],
            ],
            [
                'label'   => trans('firefly.expenses'),
                'type'    => 'bar',
                'entries' => [
                    (string)trans('firefly.sum_of_period')     => $numbers['sum_spent'],
                    (string)trans('firefly.average_in_period') => $numbers['avg_spent'],
                ],
            ],
        ];

        $data = $this->generator->multiSet($chartData);
        $cache->store($data);

        return response()->json($data);
    }

    /**
     * @param $array
     *
     * @return string
     */
    private function arraySum($array): string
    {
        $sum = '0';
        foreach ($array as $entry) {
            $sum = bcadd($sum, $entry);
        }

        return $sum;
    }

    /**
     * Collects the incomes and expenses for the given periods, grouped per month. Will cache its results.
     *
     * @param Collection $accounts
     * @param Carbon     $start
     * @param Carbon     $end
     *
     * @return array
     */
    private function getChartData(Collection $accounts, Carbon $start, Carbon $end): array
    {
        $cache = new CacheProperties;
        $cache->addProperty('chart.report.get-chart-data');
        $cache->addProperty($start);
        $cache->addProperty($accounts);
        $cache->addProperty($end);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }

        $currentStart = clone $start;
        $spentArray   = [];
        $earnedArray  = [];

        /** @var AccountTaskerInterface $tasker */
        $tasker = app(AccountTaskerInterface::class);

        while ($currentStart <= $end) {
            $currentEnd = app('navigation')->endOfPeriod($currentStart, '1M');
            $earned     = (string)array_sum(
                array_map(
                    function ($item) {
                        return $item['sum'];
                    },
                    $tasker->getIncomeReport($currentStart, $currentEnd, $accounts)
                )
            );

            $spent = (string)array_sum(
                array_map(
                    function ($item) {
                        return $item['sum'];
                    },
                    $tasker->getExpenseReport($currentStart, $currentEnd, $accounts)
                )
            );

            $label               = $currentStart->format('Y-m') . '-01';
            $spentArray[$label]  = bcmul($spent, '-1');
            $earnedArray[$label] = $earned;
            $currentStart        = app('navigation')->addPeriod($currentStart, '1M', 0);
        }
        $result = [
            'spent'  => $spentArray,
            'earned' => $earnedArray,
        ];
        $cache->store($result);

        return $result;
    }
}
