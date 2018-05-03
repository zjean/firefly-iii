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
declare(strict_types=1);

namespace FireflyIII\Http\Controllers\Import;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Import\Prerequisites\PrerequisitesInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use View;


/**
 * Class FileController.
 */
class IndexController extends Controller
{
    /** @var ImportJobRepositoryInterface */
    public $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-archive');
                app('view')->share('title', trans('firefly.import_index_title'));
                $this->repository = app(ImportJobRepositoryInterface::class);

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index']);
    }

    /**
     * Creates a new import job for $importProvider.
     *
     * @param string $importProvider
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    public function create(string $importProvider)
    {
        $importJob = $this->repository->create($importProvider);

        // if job provider has no prerequisites:
        if (!(bool)config(sprintf('import.has_prereq.%s', $importProvider))) {

            // if job provider also has no configuration:
            if (!(bool)config(sprintf('import.has_config.%s', $importProvider))) {
                $this->repository->updateStatus($importJob, 'ready_to_run');

                return redirect(route('import.job.status.index', [$importJob->key]));
            }

            // update job to say "has_prereq".
            $this->repository->setStatus($importJob, 'has_prereq');

            // redirect to job configuration.
            return redirect(route('import.job.configuration.index', [$importJob->key]));
        }

        // if need to set prerequisites, do that first.
        $class = (string)config(sprintf('import.prerequisites.%s', $importProvider));
        if (!class_exists($class)) {
            throw new FireflyException(sprintf('No class to handle configuration for "%s".', $importProvider)); // @codeCoverageIgnore
        }
        /** @var PrerequisitesInterface $providerPre */
        $providerPre = app($class);
        $providerPre->setUser(auth()->user());

        if (!$providerPre->isComplete()) {
            // redirect to global prerequisites
            return redirect(route('import.prerequisites.index', [$importProvider, $importJob->key]));
        }

        // update job to say "has_prereq".
        $this->repository->setStatus($importJob, 'has_prereq');

        // Otherwise just redirect to job configuration.
        return redirect(route('import.job.configuration.index', [$importJob->key]));

    }


    /**
     * General import index.
     *
     * @return View
     */
    public function index()
    {
        // get all import routines:
        /** @var array $config */
        $config    = config('import.enabled');
        $providers = [];
        foreach ($config as $name => $enabled) {
            if ($enabled || (bool)config('app.debug')) {
                $providers[$name] = [];
            }
        }

        // has prereq or config?
        foreach (array_keys($providers) as $name) {
            $providers[$name]['has_prereq'] = (bool)config('import.has_prereq.' . $name);
            $providers[$name]['has_config'] = (bool)config('import.has_config.' . $name);
            $class                          = (string)config('import.prerequisites.' . $name);
            $result                         = false;
            if ($class !== '' && class_exists($class)) {
                /** @var PrerequisitesInterface $object */
                $object = app($class);
                $object->setUser(auth()->user());
                $result = $object->isComplete();
            }
            $providers[$name]['prereq_complete'] = $result;
        }

        $subTitle     = trans('import.index_breadcrumb');
        $subTitleIcon = 'fa-home';

        return view('import.index', compact('subTitle', 'subTitleIcon', 'providers'));
    }
    //
    //    /**
    //     * @param Request $request
    //     * @param string  $bank
    //     *
    //     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
    //     */
    //    public function reset(Request $request, string $bank)
    //    {
    //        if ($bank === 'bunq') {
    //            // remove bunq related preferences.
    //            Preferences::delete('bunq_api_key');
    //            Preferences::delete('bunq_server_public_key');
    //            Preferences::delete('bunq_private_key');
    //            Preferences::delete('bunq_public_key');
    //            Preferences::delete('bunq_installation_token');
    //            Preferences::delete('bunq_installation_id');
    //            Preferences::delete('bunq_device_server_id');
    //            Preferences::delete('external_ip');
    //
    //        }
    //
    //        if ($bank === 'spectre') {
    //            // remove spectre related preferences:
    //            Preferences::delete('spectre_client_id');
    //            Preferences::delete('spectre_app_secret');
    //            Preferences::delete('spectre_service_secret');
    //            Preferences::delete('spectre_app_id');
    //            Preferences::delete('spectre_secret');
    //            Preferences::delete('spectre_private_key');
    //            Preferences::delete('spectre_public_key');
    //            Preferences::delete('spectre_customer');
    //        }
    //
    //        Preferences::mark();
    //        $request->session()->flash('info', (string)trans('firefly.settings_reset_for_' . $bank));
    //
    //        return redirect(route('import.index'));
    //
    //    }

    //    /**
    //     * @param ImportJob $job
    //     *
    //     * @return \Illuminate\Http\JsonResponse
    //     *
    //     * @throws FireflyException
    //     */
    //    public function start(ImportJob $job)
    //    {
    //        $type      = $job->file_type;
    //        $key       = sprintf('import.routine.%s', $type);
    //        $className = config($key);
    //        if (null === $className || !class_exists($className)) {
    //            throw new FireflyException(sprintf('Cannot find import routine class for job of type "%s".', $type)); // @codeCoverageIgnore
    //        }
    //
    //        /** @var RoutineInterface $routine */
    //        $routine = app($className);
    //        $routine->setJob($job);
    //        $result = $routine->run();
    //
    //        if ($result) {
    //            return response()->json(['run' => 'ok']);
    //        }
    //
    //        throw new FireflyException('Job did not complete successfully. Please review the log files.');
    //    }


    //    /**
    //     * Generate a JSON file of the job's configuration and send it to the user.
    //     *
    //     * @param ImportJob $job
    //     *
    //     * @return LaravelResponse
    //     */
    //    public function download(ImportJob $job)
    //    {
    //        Log::debug('Now in download()', ['job' => $job->key]);
    //        $config = $job->configuration;
    //
    //        // This is CSV import specific:
    //        $config['column-roles-complete']   = false;
    //        $config['column-mapping-complete'] = false;
    //        $config['initial-config-complete'] = false;
    //        $config['has-file-upload']         = false;
    //        $config['delimiter']               = "\t" === $config['delimiter'] ? 'tab' : $config['delimiter'];
    //        unset($config['stage']);
    //
    //        $result = json_encode($config, JSON_PRETTY_PRINT);
    //        $name   = sprintf('"%s"', addcslashes('import-configuration-' . date('Y-m-d') . '.json', '"\\'));
    //
    //        /** @var LaravelResponse $response */
    //        $response = response($result, 200);
    //        $response->header('Content-disposition', 'attachment; filename=' . $name)
    //                 ->header('Content-Type', 'application/json')
    //                 ->header('Content-Description', 'File Transfer')
    //                 ->header('Connection', 'Keep-Alive')
    //                 ->header('Expires', '0')
    //                 ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
    //                 ->header('Pragma', 'public')
    //                 ->header('Content-Length', \strlen($result));
    //
    //        return $response;
    //    }
}
