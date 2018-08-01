<?php
/**
 * LoginController.php
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
/** @noinspection PhpDynamicAsStaticMethodCallInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Auth;

use DB;
use FireflyConfig;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\User;
use Illuminate\Cookie\CookieJar;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

/**
 * Class LoginController
 *
 * This controller handles authenticating users for the application and
 * redirecting them to your home screen. The controller uses a trait
 * to conveniently provide its functionality to your applications.
 *
 * @codeCoverageIgnore
 */
class LoginController extends Controller
{
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware('guest')->except('logout');
    }

    /**
     * Log in a user.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\Response|void
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            /** @noinspection PhpInconsistentReturnPointsInspection */
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            // user is logged in. Save in session if the user requested session to be remembered:
            $request->session()->put('remember_login', $request->filled('remember'));

            /** @noinspection PhpInconsistentReturnPointsInspection */
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        /** @noinspection PhpInconsistentReturnPointsInspection */
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Log the user out of the application.
     *
     * @param Request   $request
     * @param CookieJar $cookieJar
     *
     * @return $this|\Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request, CookieJar $cookieJar)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $cookie = $cookieJar->forget('twoFactorAuthenticated');

        return redirect('/')->withCookie($cookie);
    }

    /**
     * Show the application's login form.
     *
     * @param Request $request
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showLoginForm(Request $request)
    {
        $count = DB::table('users')->count();
        if (0 === $count) {
            return redirect(route('register')); // @codeCoverageIgnore
        }

        // forget 2fa session thing.
        $request->session()->forget('twoFactorAuthenticated');

        // is allowed to?
        $singleUserMode    = FireflyConfig::get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $userCount         = User::count();
        $allowRegistration = true;
        if (true === $singleUserMode && $userCount > 0) {
            $allowRegistration = false;
        }

        $email    = $request->old('email');
        $remember = $request->old('remember');

        return view('auth.login', compact('allowRegistration', 'email', 'remember'));
    }
}
