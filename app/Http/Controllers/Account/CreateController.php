<?php
/**
 * CreateController.php
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

namespace FireflyIII\Http\Controllers\Account;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\AccountFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Http\Request;

/**
 *
 * Class CreateController
 */
class CreateController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $repository;

    /**
     *
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
     * @param Request     $request
     * @param string|null $what
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create(Request $request, string $what = null)
    {
        $what            = $what ?? 'asset';
        $defaultCurrency = app('amount')->getDefaultCurrency();
        $subTitleIcon    = config('firefly.subIconsByIdentifier.' . $what);
        $subTitle        = (string)trans('firefly.make_new_' . $what . '_account');
        $roles           = [];
        foreach (config('firefly.accountRoles') as $role) {
            $roles[$role] = (string)trans('firefly.account_role_' . $role);
        }

        // pre fill some data
        $request->session()->flash('preFilled', ['currency_id' => $defaultCurrency->id]);

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('accounts.create.fromStore')) {
            $this->rememberPreviousUri('accounts.create.uri');
        }
        $request->session()->forget('accounts.create.fromStore');

        return view('accounts.create', compact('subTitleIcon', 'what', 'subTitle', 'roles'));
    }


    /**
     * @param AccountFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(AccountFormRequest $request)
    {
        $data    = $request->getAccountData();
        $account = $this->repository->store($data);
        $request->session()->flash('success', (string)trans('firefly.stored_new_account', ['name' => $account->name]));
        app('preferences')->mark();

        // update preferences if necessary:
        $frontPage = app('preferences')->get('frontPageAccounts', [])->data;
        if (AccountType::ASSET === $account->accountType->type && \count($frontPage) > 0) {
            // @codeCoverageIgnoreStart
            $frontPage[] = $account->id;
            app('preferences')->set('frontPageAccounts', $frontPage);
            // @codeCoverageIgnoreEnd
        }
        // redirect to previous URL.
        $redirect = redirect($this->getPreviousUri('accounts.create.uri'));
        if (1 === (int)$request->get('create_another')) {
            // set value so create routine will not overwrite URL:
            $request->session()->put('accounts.create.fromStore', true);

            $redirect = redirect(route('accounts.create', [$request->input('what')]))->withInput();
        }

        return $redirect;
    }

}