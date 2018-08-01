<?php
/**
 * UpdateController.php
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
/** @noinspection PhpMethodParametersCountMismatchInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;

use FireflyConfig;
use FireflyIII\Helpers\Update\UpdateTrait;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Middleware\IsSandStormUser;
use Illuminate\Http\Request;

/**
 * Class HomeController.
 */
class UpdateController extends Controller
{
    use UpdateTrait;

    /**
     * ConfigurationController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.administration'));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
        $this->middleware(IsSandStormUser::class)->except(['index']);
    }

    /**
     * Show page with update options.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function index()
    {
        $subTitle     = (string)trans('firefly.update_check_title');
        $subTitleIcon = 'fa-star';
        $permission   = app('fireflyconfig')->get('permission_update_check', -1);
        $selected     = $permission->data;
        $options      = [
            -1 => (string)trans('firefly.updates_ask_me_later'),
            0  => (string)trans('firefly.updates_do_not_check'),
            1  => (string)trans('firefly.updates_enable_check'),
        ];

        return view('admin.update.index', compact('subTitle', 'subTitleIcon', 'selected', 'options'));
    }

    /**
     * Post new settings.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function post(Request $request)
    {
        $checkForUpdates = (int)$request->get('check_for_updates');
        FireflyConfig::set('permission_update_check', $checkForUpdates);
        FireflyConfig::set('last_update_check', time());
        session()->flash('success', (string)trans('firefly.configuration_updated'));

        return redirect(route('admin.update-check'));
    }

    /**
     * Does a manual update check.
     */
    public function updateCheck()
    {
        $latestRelease = $this->getLatestRelease();
        $versionCheck  = $this->versionCheck($latestRelease);
        $resultString  = $this->parseResult($versionCheck, $latestRelease);

        if (0 !== $versionCheck && '' !== $resultString) {
            // flash info
            session()->flash('info', $resultString);
        }
        FireflyConfig::set('last_update_check', time());

        return response()->json(['result' => $resultString]);
    }
}
