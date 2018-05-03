<?php
/**
 * IsDemoUser.php
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

use Closure;
use FireflyIII\Exceptions\IsDemoUserException;
use FireflyIII\User;
use Illuminate\Http\Request;

/**
 * Class IsDemoUser.
 */
class IsDemoUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var User $user */
        $user = $request->user();
        if (null === $user) {
            return $next($request);
        }

        if ($user->hasRole('demo')) {
            $request->session()->flash('info', (string)trans('firefly.not_available_demo_user'));
            $current  = $request->url();
            $previous = $request->session()->previousUrl();
            if ($current !== $previous) {
                return response()->redirectTo($previous);
            }

            return response()->redirectTo(route('index'));
        }

        return $next($request);
    }
}
