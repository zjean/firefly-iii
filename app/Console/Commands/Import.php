<?php
/**
 * Import.php
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

namespace FireflyIII\Console\Commands;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Logging\CommandHandler;
use FireflyIII\Import\Routine\RoutineInterface;
use FireflyIII\Models\ImportJob;
use Illuminate\Console\Command;
use Illuminate\Support\MessageBag;
use Log;

/**
 * Class Import.
 */
class Import extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This will start a new import.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:start-import {key}';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run the import routine.
     *
     * @throws FireflyException
     */
    public function handle()
    {
        Log::debug('Start start-import command');
        $jobKey = $this->argument('key');
        $job    = ImportJob::where('key', $jobKey)->first();
        if (null === $job) {
            $this->error(sprintf('No job found with key "%s"', $jobKey));

            return;
        }
        if (!$this->isValid($job)) {
            Log::error('Job is not valid for some reason. Exit.');

            return;
        }

        $this->line(sprintf('Going to import job with key "%s" of type "%s"', $job->key, $job->file_type));

        $monolog = Log::getMonolog();
        $handler = new CommandHandler($this);
        $monolog->pushHandler($handler);

        // actually start job:
        $type      = 'csv' === $job->file_type ? 'file' : $job->file_type;
        $key       = sprintf('import.routine.%s', $type);
        $className = config($key);
        if (null === $className || !class_exists($className)) {
            throw new FireflyException(sprintf('Cannot find import routine class for job of type "%s".', $type)); // @codeCoverageIgnore
        }

        /** @var RoutineInterface $routine */
        $routine = app($className);
        $routine->setJob($job);
        $routine->run();

        /** @var MessageBag $error */
        foreach ($routine->getErrors() as $index => $error) {
            $this->error(sprintf('Error importing line #%d: %s', $index, $error));
        }

        $this->line(
            sprintf('The import has finished. %d transactions have been imported out of %d records.', $routine->getJournals()->count(), $routine->getLines())
        );

        return;
    }

    /**
     * Check if job is valid to be imported.
     *
     * @param ImportJob $job
     *
     * @return bool
     */
    private function isValid(ImportJob $job): bool
    {
        if (null === $job) {
            Log::error('This job does not seem to exist.');
            $this->error('This job does not seem to exist.');

            return false;
        }

        if ('configured' !== $job->status) {
            Log::error(sprintf('This job is not ready to be imported (status is %s).', $job->status));
            $this->error('This job is not ready to be imported.');

            return false;
        }

        return true;
    }
}
