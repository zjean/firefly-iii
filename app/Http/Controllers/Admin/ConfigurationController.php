<?php
/**
 * ConfigurationController.php
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
/** @noinspection PhpUndefinedClassInspection */
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Middleware\IsSandStormUser;
use FireflyIII\Http\Requests\ConfigurationRequest;
use FireflyIII\Support\Facades\FireflyConfig;
use Illuminate\Http\RedirectResponse;

/**
 * Class ConfigurationController.
 */
class ConfigurationController extends Controller
{
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
        $this->middleware(IsSandStormUser::class);
    }

    /**
     * Show configuration index.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $subTitle     = (string)trans('firefly.instance_configuration');
        $subTitleIcon = 'fa-wrench';

        // all available configuration and their default value in case
        // they don't exist yet.
        $singleUserMode = FireflyConfig::get('single_user_mode', config('firefly.configuration.single_user_mode'))->data;
        $isDemoSite     = FireflyConfig::get('is_demo_site', config('firefly.configuration.is_demo_site'))->data;
        $siteOwner      = env('SITE_OWNER');

        return view(
            'admin.configuration.index',
            compact('subTitle', 'subTitleIcon', 'singleUserMode', 'isDemoSite', 'siteOwner')
        );
    }

    /**
     * Store new configuration values.
     *
     * @param ConfigurationRequest $request
     *
     * @return RedirectResponse
     */
    public function postIndex(ConfigurationRequest $request): RedirectResponse
    {
        // get config values:
        $data = $request->getConfigurationData();

        // store config values
        FireflyConfig::set('single_user_mode', $data['single_user_mode']);
        FireflyConfig::set('is_demo_site', $data['is_demo_site']);

        // flash message
        session()->flash('success', (string)trans('firefly.configuration_updated'));
        app('preferences')->mark();

        return redirect()->route('admin.configuration.index');
    }
}
