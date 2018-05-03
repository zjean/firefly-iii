<?php
/**
 * PiggyBankController.php
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
use FireflyIII\Http\Requests\PiggyBankFormRequest;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Log;
use Preferences;
use Session;
use Steam;
use View;

/**
 * Class PiggyBankController.
 */
class PiggyBankController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.piggyBanks'));
                app('view')->share('mainTitleIcon', 'fa-sort-amount-asc');

                return $next($request);
            }
        );
    }

    /**
     * Add money to piggy bank.
     *
     * @param PiggyBank                    $piggyBank
     *
     * @param PiggyBankRepositoryInterface $repository
     *
     * @return View
     */
    public function add(PiggyBank $piggyBank, PiggyBankRepositoryInterface $repository)
    {
        /** @var Carbon $date */
        $date          = session('end', Carbon::now()->endOfMonth());
        $leftOnAccount = $piggyBank->leftOnAccount($date);
        $savedSoFar    = $repository->getCurrentAmount($piggyBank);
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);

        return view('piggy-banks.add', compact('piggyBank', 'maxAmount'));
    }

    /**
     * Add money to piggy bank (for mobile devices).
     *
     * @param PiggyBank                    $piggyBank
     *
     * @param PiggyBankRepositoryInterface $repository
     *
     * @return View
     */
    public function addMobile(PiggyBank $piggyBank, PiggyBankRepositoryInterface $repository)
    {
        /** @var Carbon $date */
        $date          = session('end', Carbon::now()->endOfMonth());
        $leftOnAccount = $piggyBank->leftOnAccount($date);
        $savedSoFar    = $repository->getCurrentAmount($piggyBank);
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = min($leftOnAccount, $leftToSave);

        return view('piggy-banks.add-mobile', compact('piggyBank', 'maxAmount'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $subTitle     = trans('firefly.new_piggy_bank');
        $subTitleIcon = 'fa-plus';

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('piggy-banks.create.fromStore')) {
            $this->rememberPreviousUri('piggy-banks.create.uri');
        }
        Session::forget('piggy-banks.create.fromStore');

        return view('piggy-banks.create', compact('subTitle', 'subTitleIcon'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return View
     */
    public function delete(PiggyBank $piggyBank)
    {
        $subTitle = trans('firefly.delete_piggy_bank', ['name' => $piggyBank->name]);

        // put previous url in session
        $this->rememberPreviousUri('piggy-banks.delete.uri');

        return view('piggy-banks.delete', compact('piggyBank', 'subTitle'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        Session::flash('success', (string)trans('firefly.deleted_piggy_bank', ['name' => $piggyBank->name]));
        Preferences::mark();
        $repository->destroy($piggyBank);

        return redirect($this->getPreviousUri('piggy-banks.delete.uri'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return View
     */
    public function edit(PiggyBank $piggyBank)
    {
        $subTitle     = trans('firefly.update_piggy_title', ['name' => $piggyBank->name]);
        $subTitleIcon = 'fa-pencil';
        $targetDate   = null;
        $startDate    = null;
        $note         = $piggyBank->notes()->first();
        // Flash some data to fill the form.
        if (null !== $piggyBank->targetdate) {
            $targetDate = $piggyBank->targetdate->format('Y-m-d');
        }
        if (null !== $piggyBank->startdate) {
            $startDate = $piggyBank->startdate->format('Y-m-d');
        }

        $preFilled = ['name'         => $piggyBank->name,
                      'account_id'   => $piggyBank->account_id,
                      'targetamount' => $piggyBank->targetamount,
                      'targetdate'   => $targetDate,
                      'startdate'    => $startDate,
                      'note'         => null === $note ? '' : $note->text,
        ];
        Session::flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('piggy-banks.edit.fromUpdate')) {
            $this->rememberPreviousUri('piggy-banks.edit.uri');
        }
        Session::forget('piggy-banks.edit.fromUpdate');

        return view('piggy-banks.edit', compact('subTitle', 'subTitleIcon', 'piggyBank', 'preFilled'));
    }

    /**
     * @param Request                      $request
     * @param PiggyBankRepositoryInterface $piggyRepository
     *
     * @return View
     */
    public function index(Request $request, PiggyBankRepositoryInterface $piggyRepository)
    {
        $collection = $piggyRepository->getPiggyBanks();
        $total      = $collection->count();
        $page       = 0 === (int)$request->get('page') ? 1 : (int)$request->get('page');
        $pageSize   = (int)Preferences::get('listPageSize', 50)->data;
        /** @var Carbon $end */
        $end = session('end', Carbon::now()->endOfMonth());

        $accounts = [];
        Log::debug('Looping piggues');
        /** @var PiggyBank $piggyBank */
        foreach ($collection as $piggyBank) {

            $piggyBank->savedSoFar = $piggyRepository->getCurrentAmount($piggyBank);
            $piggyBank->percentage = (int)(0 !== bccomp('0', $piggyBank->savedSoFar) ? $piggyBank->savedSoFar / $piggyBank->targetamount * 100 : 0);
            $piggyBank->leftToSave = bcsub($piggyBank->targetamount, (string)$piggyBank->savedSoFar);
            $piggyBank->percentage = $piggyBank->percentage > 100 ? 100 : $piggyBank->percentage;

            // Fill account information:
            $account = $piggyBank->account;
            $new     = false;
            if (!isset($accounts[$account->id])) {
                $new                    = true;
                $accounts[$account->id] = [
                    'name'              => $account->name,
                    'balance'           => Steam::balanceIgnoreVirtual($account, $end),
                    'leftForPiggyBanks' => $piggyBank->leftOnAccount($end),
                    'sumOfSaved'        => (string)$piggyBank->savedSoFar,
                    'sumOfTargets'      => $piggyBank->targetamount,
                    'leftToSave'        => $piggyBank->leftToSave,
                ];
            }
            if (isset($accounts[$account->id]) && false === $new) {
                $accounts[$account->id]['sumOfSaved']   = bcadd($accounts[$account->id]['sumOfSaved'], (string)$piggyBank->savedSoFar);
                $accounts[$account->id]['sumOfTargets'] = bcadd($accounts[$account->id]['sumOfTargets'], $piggyBank->targetamount);
                $accounts[$account->id]['leftToSave']   = bcadd($accounts[$account->id]['leftToSave'], $piggyBank->leftToSave);
            }
        }

        // paginate piggy banks
        $collection = $collection->slice(($page - 1) * $pageSize, $pageSize);
        $piggyBanks = new LengthAwarePaginator($collection, $total, $pageSize, $page);
        $piggyBanks->setPath(route('piggy-banks.index'));

        return view('piggy-banks.index', compact('piggyBanks', 'accounts'));
    }

    /**
     * @param Request                      $request
     * @param PiggyBankRepositoryInterface $repository
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function order(Request $request, PiggyBankRepositoryInterface $repository)
    {
        $data = $request->get('order');

        // set all users piggy banks to zero:
        $repository->reset();

        if (is_array($data)) {
            foreach ($data as $order => $id) {
                $repository->setOrder((int)$id, $order + 1);
            }
        }

        return response()->json(['result' => 'ok']);
    }

    /**
     * @param Request                      $request
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postAdd(Request $request, PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        $amount   = $request->get('amount') ?? '0';
        $currency = app('amount')->getDefaultCurrency();
        if ($repository->canAddAmount($piggyBank, $amount)) {
            $repository->addAmount($piggyBank, $amount);
            Session::flash(
                'success',
                (string)trans(
                    'firefly.added_amount_to_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            Preferences::mark();

            return redirect(route('piggy-banks.index'));
        }

        Log::error('Cannot add ' . $amount . ' because canAddAmount returned false.');
        Session::flash(
            'error',
            (string)trans(
                'firefly.cannot_add_amount_piggy',
                ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * @param Request                      $request
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemove(Request $request, PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        $amount   = $request->get('amount') ?? '0';
        $currency = app('amount')->getDefaultCurrency();
        if ($repository->canRemoveAmount($piggyBank, $amount)) {
            $repository->removeAmount($piggyBank, $amount);
            Session::flash(
                'success',
                (string)trans(
                    'firefly.removed_amount_from_piggy',
                    ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
                )
            );
            Preferences::mark();

            return redirect(route('piggy-banks.index'));
        }

        $amount = (string)round($request->get('amount'), 12);

        Session::flash(
            'error',
            (string)trans(
                'firefly.cannot_remove_from_piggy',
                ['amount' => app('amount')->formatAnything($currency, $amount, false), 'name' => $piggyBank->name]
            )
        );

        return redirect(route('piggy-banks.index'));
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return View
     */
    public function remove(PiggyBank $piggyBank)
    {
        return view('piggy-banks.remove', compact('piggyBank'));
    }

    /**
     * Remove money from piggy bank (for mobile devices).
     *
     * @param PiggyBank $piggyBank
     *
     * @return View
     */
    public function removeMobile(PiggyBank $piggyBank)
    {
        return view('piggy-banks.remove-mobile', compact('piggyBank'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBank                    $piggyBank
     *
     * @return View
     */
    public function show(PiggyBankRepositoryInterface $repository, PiggyBank $piggyBank)
    {
        $note     = $piggyBank->notes()->first();
        $events   = $repository->getEvents($piggyBank);
        $subTitle = $piggyBank->name;

        return view('piggy-banks.show', compact('piggyBank', 'events', 'subTitle', 'note'));
    }

    /**
     * @param PiggyBankFormRequest         $request
     * @param PiggyBankRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(PiggyBankFormRequest $request, PiggyBankRepositoryInterface $repository)
    {
        $data = $request->getPiggyBankData();
        if (null === $data['startdate']) {
            $data['startdate'] = new Carbon;
        }
        $piggyBank = $repository->store($data);

        Session::flash('success', (string)trans('firefly.stored_piggy_bank', ['name' => $piggyBank->name]));
        Preferences::mark();

        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            Session::put('piggy-banks.create.fromStore', true);

            return redirect(route('piggy-banks.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('piggy-banks.create.uri'));
    }

    /**
     * @param PiggyBankRepositoryInterface $repository
     * @param PiggyBankFormRequest         $request
     * @param PiggyBank                    $piggyBank
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(PiggyBankRepositoryInterface $repository, PiggyBankFormRequest $request, PiggyBank $piggyBank)
    {
        $data      = $request->getPiggyBankData();
        $piggyBank = $repository->update($piggyBank, $data);

        Session::flash('success', (string)trans('firefly.updated_piggy_bank', ['name' => $piggyBank->name]));
        Preferences::mark();

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            Session::put('piggy-banks.edit.fromUpdate', true);

            return redirect(route('piggy-banks.edit', [$piggyBank->id]));
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('piggy-banks.edit.uri'));
    }
}
