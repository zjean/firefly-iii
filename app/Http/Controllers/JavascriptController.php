<?php
/**
 * JavascriptController.php
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

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Http\Request;
use Log;
use Preferences;

/**
 * Class JavascriptController.
 */
class JavascriptController extends Controller
{
    /**
     * @param AccountRepositoryInterface  $repository
     * @param CurrencyRepositoryInterface $currencyRepository
     *
     * @return \Illuminate\Http\Response
     */
    public function accounts(AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository)
    {
        $accounts   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $preference = Preferences::get('currencyPreference', config('firefly.default_currency', 'EUR'));
        $default    = $currencyRepository->findByCodeNull($preference->data);

        $data = ['accounts' => []];

        /** @var Account $account */
        foreach ($accounts as $account) {
            $accountId                    = $account->id;
            $currency                     = (int)$repository->getMetaValue($account, 'currency_id');
            $currency                     = 0 === $currency ? $default->id : $currency;
            $entry                        = ['preferredCurrency' => $currency, 'name' => $account->name];
            $data['accounts'][$accountId] = $entry;
        }

        return response()
            ->view('javascript.accounts', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * @param CurrencyRepositoryInterface $repository
     *
     * @return \Illuminate\Http\Response
     */
    public function currencies(CurrencyRepositoryInterface $repository)
    {
        $currencies = $repository->get();
        $data       = ['currencies' => []];
        /** @var TransactionCurrency $currency */
        foreach ($currencies as $currency) {
            $currencyId                      = $currency->id;
            $entry                           = ['name' => $currency->name, 'code' => $currency->code, 'symbol' => $currency->symbol];
            $data['currencies'][$currencyId] = $entry;
        }

        return response()
            ->view('javascript.currencies', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * @param Request                     $request
     * @param AccountRepositoryInterface  $repository
     * @param CurrencyRepositoryInterface $currencyRepository
     *
     * @return \Illuminate\Http\Response
     */
    public function variables(Request $request, AccountRepositoryInterface $repository, CurrencyRepositoryInterface $currencyRepository)
    {
        $account    = $repository->findNull((int)$request->get('account'));
        $currencyId = 0;
        if (null !== $account) {
            $currencyId = (int)$repository->getMetaValue($account, 'currency_id');
        }
        /** @var TransactionCurrency $currency */
        $currency = $currencyRepository->findNull($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency();
        }

        $localeconv                = localeconv();
        $accounting                = app('amount')->getJsConfig($localeconv);
        $localeconv                = localeconv();
        $localeconv['frac_digits'] = $currency->decimal_places;
        $pref                      = Preferences::get('language', config('firefly.default_language', 'en_US'));
        $lang                      = $pref->data;
        $dateRange                 = $this->getDateRangeConfig();

        $data = [
            'currencyCode'    => $currency->code,
            'currencySymbol'  => $currency->symbol,
            'accounting'      => $accounting,
            'localeconv'      => $localeconv,
            'language'        => $lang,
            'dateRangeTitle'  => $dateRange['title'],
            'dateRangeConfig' => $dateRange['configuration'],
        ];
        $request->session()->keep(['two-factor-secret']);

        return response()
            ->view('javascript.variables', $data, 200)
            ->header('Content-Type', 'text/javascript');
    }

    /**
     * @return array
     */
    private function getDateRangeConfig(): array
    {
        $viewRange = Preferences::get('viewRange', '1M')->data;
        $start     = session('start');
        $end       = session('end');
        $first     = session('first');
        $title     = sprintf('%s - %s', $start->formatLocalized($this->monthAndDayFormat), $end->formatLocalized($this->monthAndDayFormat));
        $isCustom  = true === session('is_custom_range', false);
        $today     = new Carbon;
        $ranges    = [
            // first range is the current range:
            $title => [$start, $end],
        ];
        Log::debug(sprintf('viewRange is %s', $viewRange));
        Log::debug(sprintf('isCustom is %s', var_export($isCustom, true)));

        // when current range is a custom range, add the current period as the next range.
        if ($isCustom) {
            Log::debug('Custom is true.');
            $index             = app('navigation')->periodShow($start, $viewRange);
            $customPeriodStart = app('navigation')->startOfPeriod($start, $viewRange);
            $customPeriodEnd   = app('navigation')->endOfPeriod($customPeriodStart, $viewRange);
            $ranges[$index]    = [$customPeriodStart, $customPeriodEnd];
        }
        // then add previous range and next range
        $previousDate   = app('navigation')->subtractPeriod($start, $viewRange);
        $index          = app('navigation')->periodShow($previousDate, $viewRange);
        $previousStart  = app('navigation')->startOfPeriod($previousDate, $viewRange);
        $previousEnd    = app('navigation')->endOfPeriod($previousStart, $viewRange);
        $ranges[$index] = [$previousStart, $previousEnd];

        $nextDate       = app('navigation')->addPeriod($start, $viewRange, 0);
        $index          = app('navigation')->periodShow($nextDate, $viewRange);
        $nextStart      = app('navigation')->startOfPeriod($nextDate, $viewRange);
        $nextEnd        = app('navigation')->endOfPeriod($nextStart, $viewRange);
        $ranges[$index] = [$nextStart, $nextEnd];

        // today:
        $todayStart = app('navigation')->startOfPeriod($today, $viewRange);
        $todayEnd   = app('navigation')->endOfPeriod($todayStart, $viewRange);
        if ($todayStart->ne($start) || $todayEnd->ne($end)) {
            $ranges[ucfirst((string)trans('firefly.today'))] = [$todayStart, $todayEnd];
        }

        // everything
        $index          = (string)trans('firefly.everything');
        $ranges[$index] = [$first, new Carbon];

        $return = [
            'title'         => $title,
            'configuration' => [
                'apply'       => (string)trans('firefly.apply'),
                'cancel'      => (string)trans('firefly.cancel'),
                'from'        => (string)trans('firefly.from'),
                'to'          => (string)trans('firefly.to'),
                'customRange' => (string)trans('firefly.customRange'),
                'start'       => $start->format('Y-m-d'),
                'end'         => $end->format('Y-m-d'),
                'ranges'      => $ranges,
            ],
        ];

        return $return;
    }
}
