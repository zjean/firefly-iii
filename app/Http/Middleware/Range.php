<?php
/**
 * Range.php
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

namespace FireflyIII\Http\Middleware;

use App;
use Carbon\Carbon;
use Closure;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Http\Request;
use Preferences;
use Session;
use View;

/**
 * Class SessionFilter.
 */
class Range
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure                  $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            // set start, end and finish:
            $this->setRange();

            // set view variables.
            $this->configureView();

            // set more view variables:
            $this->configureList();

            // flash a big fat warning when users use SQLite in Docker
            $this->loseItAll($request);
        }

        return $next($request);
    }

    /**
     *
     */
    private function configureList()
    {
        $pref = Preferences::get('list-length', config('firefly.list_length', 10))->data;
        View::share('listLength', $pref);
    }

    /**
     *
     */
    private function configureView()
    {
        $pref = Preferences::get('language', config('firefly.default_language', 'en_US'));
        $lang = $pref->data;
        App::setLocale($lang);
        Carbon::setLocale(substr($lang, 0, 2));
        $locale = explode(',', trans('config.locale'));
        $locale = array_map('trim', $locale);

        setlocale(LC_TIME, $locale);
        $moneyResult = setlocale(LC_MONETARY, $locale);

        // send error to view if could not set money format
        if (false === $moneyResult) {
            View::share('invalidMonetaryLocale', true); // @codeCoverageIgnore
        }

        // save some formats:
        $monthAndDayFormat = (string)trans('config.month_and_day');
        $dateTimeFormat    = (string)trans('config.date_time');
        $defaultCurrency   = app('amount')->getDefaultCurrency();

        View::share('monthAndDayFormat', $monthAndDayFormat);
        View::share('dateTimeFormat', $dateTimeFormat);
        View::share('defaultCurrency', $defaultCurrency);
    }

    /**
     * @param Request $request
     */
    private function loseItAll(Request $request)
    {
        if (getenv('DB_CONNECTION') === 'sqlite' && getenv('IS_DOCKER') === true) {
            $request->session()->flash(
                'error', 'You seem to be using SQLite in a Docker container. Don\'t do this. If the container restarts all your data will be gone.'
            );
        }
    }

    /**
     *
     */
    private function setRange()
    {
        // ignore preference. set the range to be the current month:
        if (!Session::has('start') && !Session::has('end')) {
            $viewRange = Preferences::get('viewRange', '1M')->data;
            if (null === $viewRange) {
                $viewRange = '1M';
                Preferences::set('viewRange', '1M');
            }
            $start = new Carbon;
            $start = app('navigation')->updateStartDate($viewRange, $start);
            $end   = app('navigation')->updateEndDate($viewRange, $start);

            Session::put('start', $start);
            Session::put('end', $end);
        }
        if (!Session::has('first')) {
            /** @var JournalRepositoryInterface $repository */
            $repository = app(JournalRepositoryInterface::class);
            $journal    = $repository->firstNull();
            $first      = Carbon::now()->startOfYear();

            if (null !== $journal) {
                $first = $journal->date ?? $first;
            }
            Session::put('first', $first);
        }
    }
}
