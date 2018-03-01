<?php
/**
 * PreferencesController.php
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

use FireflyIII\Http\Requests\TokenFormRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Google2FA;
use Illuminate\Http\Request;
use Preferences;
use Session;
use View;

/**
 * Class PreferencesController.
 */
class PreferencesController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.preferences'));
                app('view')->share('mainTitleIcon', 'fa-gear');

                return $next($request);
            }
        );
    }

    /**
     * @return View
     */
    public function code()
    {
        $domain = $this->getDomain();
        $secret = Google2FA::generateSecretKey();
        Session::flash('two-factor-secret', $secret);
        $image = Google2FA::getQRCodeInline($domain, auth()->user()->email, $secret, 200);

        return view('preferences.code', compact('image'));
    }

    /**
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws \Exception
     * @throws \Exception
     */
    public function deleteCode()
    {
        Preferences::delete('twoFactorAuthEnabled');
        Preferences::delete('twoFactorAuthSecret');
        Session::flash('success', strval(trans('firefly.pref_two_factor_auth_disabled')));
        Session::flash('info', strval(trans('firefly.pref_two_factor_auth_remove_it')));

        return redirect(route('preferences.index'));
    }

    /**
     * @param AccountRepositoryInterface $repository
     *
     * @return View
     */
    public function index(AccountRepositoryInterface $repository)
    {
        $accounts           = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $viewRangePref      = Preferences::get('viewRange', '1M');
        $viewRange          = $viewRangePref->data;
        $frontPageAccounts  = Preferences::get('frontPageAccounts', []);
        $language           = Preferences::get('language', config('firefly.default_language', 'en_US'))->data;
        $listPageSize       = Preferences::get('listPageSize', 50)->data;
        $customFiscalYear   = Preferences::get('customFiscalYear', 0)->data;
        $showDeps           = Preferences::get('showDepositsFrontpage', false)->data;
        $fiscalYearStartStr = Preferences::get('fiscalYearStart', '01-01')->data;
        $fiscalYearStart    = date('Y') . '-' . $fiscalYearStartStr;
        $tjOptionalFields   = Preferences::get('transaction_journal_optional_fields', [])->data;
        $is2faEnabled       = Preferences::get('twoFactorAuthEnabled', 0)->data; // twoFactorAuthEnabled
        $has2faSecret       = null !== Preferences::get('twoFactorAuthSecret'); // hasTwoFactorAuthSecret

        return view(
            'preferences.index',
            compact(
                'language',
                'accounts',
                'frontPageAccounts',
                'tjOptionalFields',
                'viewRange',
                'customFiscalYear',
                'listPageSize',
                'fiscalYearStart',
                'is2faEnabled',
                'has2faSecret',
                'showDeps'
            )
        );
    }

    /**
     * @param TokenFormRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) // it's unused but the class does some validation.
     */
    public function postCode(/** @scrutinizer ignore-unused */ TokenFormRequest $request)
    {
        Preferences::set('twoFactorAuthEnabled', 1);
        Preferences::set('twoFactorAuthSecret', Session::get('two-factor-secret'));

        Session::flash('success', strval(trans('firefly.saved_preferences')));
        Preferences::mark();

        return redirect(route('preferences.index'));
    }

    /**
     * @param Request                 $request
     * @param UserRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function postIndex(Request $request, UserRepositoryInterface $repository)
    {
        // front page accounts
        $frontPageAccounts = [];
        if (is_array($request->get('frontPageAccounts'))) {
            foreach ($request->get('frontPageAccounts') as $id) {
                $frontPageAccounts[] = intval($id);
            }
            Preferences::set('frontPageAccounts', $frontPageAccounts);
        }

        // view range:
        Preferences::set('viewRange', $request->get('viewRange'));
        // forget session values:
        Session::forget('start');
        Session::forget('end');
        Session::forget('range');

        // custom fiscal year
        $customFiscalYear = 1 === intval($request->get('customFiscalYear'));
        $fiscalYearStart  = date('m-d', strtotime(strval($request->get('fiscalYearStart'))));
        Preferences::set('customFiscalYear', $customFiscalYear);
        Preferences::set('fiscalYearStart', $fiscalYearStart);

        // show deposits frontpage:
        $showDepositsFrontpage = 1 === intval($request->get('showDepositsFrontpage'));
        Preferences::set('showDepositsFrontpage', $showDepositsFrontpage);

        // save page size:
        Preferences::set('listPageSize', 50);
        $listPageSize = intval($request->get('listPageSize'));
        if ($listPageSize > 0 && $listPageSize < 1337) {
            Preferences::set('listPageSize', $listPageSize);
        }

        $twoFactorAuthEnabled   = false;
        $hasTwoFactorAuthSecret = false;
        if (!$repository->hasRole(auth()->user(), 'demo')) {
            // two factor auth
            $twoFactorAuthEnabled   = intval($request->get('twoFactorAuthEnabled'));
            $hasTwoFactorAuthSecret = null !== Preferences::get('twoFactorAuthSecret');

            // If we already have a secret, just set the two factor auth enabled to 1, and let the user continue with the existing secret.
            if ($hasTwoFactorAuthSecret) {
                Preferences::set('twoFactorAuthEnabled', $twoFactorAuthEnabled);
            }
        }

        // language:
        $lang = $request->get('language');
        if (in_array($lang, array_keys(config('firefly.languages')))) {
            Preferences::set('language', $lang);
        }

        // optional fields for transactions:
        $setOptions = $request->get('tj');
        $optionalTj = [
            'interest_date'      => isset($setOptions['interest_date']),
            'book_date'          => isset($setOptions['book_date']),
            'process_date'       => isset($setOptions['process_date']),
            'due_date'           => isset($setOptions['due_date']),
            'payment_date'       => isset($setOptions['payment_date']),
            'invoice_date'       => isset($setOptions['invoice_date']),
            'internal_reference' => isset($setOptions['internal_reference']),
            'notes'              => isset($setOptions['notes']),
            'attachments'        => isset($setOptions['attachments']),
        ];
        Preferences::set('transaction_journal_optional_fields', $optionalTj);

        Session::flash('success', strval(trans('firefly.saved_preferences')));
        Preferences::mark();

        // if we don't have a valid secret yet, redirect to the code page.
        // AND USER HAS ACTUALLY ENABLED 2FA
        if (!$hasTwoFactorAuthSecret && 1 === $twoFactorAuthEnabled) {
            return redirect(route('preferences.code'));
        }

        return redirect(route('preferences.index'));
    }

    /**
     * @return string
     */
    private function getDomain(): string
    {
        $url   = url()->to('/');
        $parts = parse_url($url);

        return $parts['host'];
    }
}
