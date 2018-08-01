<?php
/**
 * StartFireflySession.php
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

use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

/**
 * Class StartFireflySession.
 *
 * @codeCoverageIgnore
 */
class StartFireflySession extends StartSession
{
    /**
     * Store the current URL for the request if necessary.
     *
     * @param \Illuminate\Http\Request              $request
     * @param \Illuminate\Contracts\Session\Session $session
     */
    protected function storeCurrentUrl(Request $request, $session): void
    {
        $uri    = $request->fullUrl();
        $strpos = strpos($uri, 'jscript');
        if (false === $strpos && 'GET' === $request->method() && !$request->ajax()) {
            $session->setPreviousUrl($uri);
        }
    }
}
