<?php
/**
 * IndexController.php
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
/** @noinspection CallableParameterUseCaseInTypeContextInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\BasicDataSupport;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 *
 * Class IndexController
 */
class IndexController extends Controller
{
    use BasicDataSupport;
    /** @var AccountRepositoryInterface The account repository */
    private $repository;

    /**
     * IndexController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', (string)trans('firefly.accounts'));

                $this->repository = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Show list of accounts.
     *
     * @param Request $request
     * @param string  $what
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request, string $what)
    {
        $what         = $what ?? 'asset';
        $subTitle     = (string)trans('firefly.' . $what . '_accounts');
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $what);
        $types        = config('firefly.accountTypesByIdentifier.' . $what);
        $collection   = $this->repository->getAccountsByType($types);
        $total        = $collection->count();

        // sort collection:
        $collection = $collection->sortBy(
            function (Account $account) {
                return ($account->active ? '0' : '1') . $account->name;
            }
        );


        $page     = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize = (int)app('preferences')->get('listPageSize', 50)->data;
        $accounts = $collection->slice(($page - 1) * $pageSize, $pageSize);
        unset($collection);
        /** @var Carbon $start */
        $start = clone session('start', Carbon::now()->startOfMonth());
        /** @var Carbon $end */
        $end = clone session('end', Carbon::now()->endOfMonth());
        $start->subDay();

        $ids           = $accounts->pluck('id')->toArray();
        $startBalances = app('steam')->balancesByAccounts($accounts, $start);
        $endBalances   = app('steam')->balancesByAccounts($accounts, $end);
        $activities    = app('steam')->getLastActivities($ids);

        $accounts->each(
            function (Account $account) use ($activities, $startBalances, $endBalances) {
                $account->lastActivityDate  = $this->isInArray($activities, $account->id);
                $account->startBalance      = $this->isInArray($startBalances, $account->id);
                $account->endBalance        = $this->isInArray($endBalances, $account->id);
                $account->difference        = bcsub($account->endBalance, $account->startBalance);
                $account->interest          = round($this->repository->getMetaValue($account, 'interest'), 6);
                $account->interestPeriod    = (string)trans('firefly.interest_calc_' . $this->repository->getMetaValue($account, 'interest_period'));
                $account->accountTypeString = (string)trans('firefly.account_type_' . $account->accountType->type);
            }
        );

        // make paginator:
        $accounts = new LengthAwarePaginator($accounts, $total, $pageSize, $page);
        $accounts->setPath(route('accounts.index', [$what]));

        return view('accounts.index', compact('what', 'subTitleIcon', 'subTitle', 'page', 'accounts'));
    }


}
