<?php
/**
 * SpectreRoutineTest.php
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

namespace Tests\Unit\Import\Routine;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\SpectreRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\Spectre\StageAuthenticatedHandler;
use FireflyIII\Support\Import\Routine\Spectre\StageImportDataHandler;
use FireflyIII\Support\Import\Routine\Spectre\StageNewHandler;
use Mockery;
use Tests\TestCase;
use Log;

/**
 * Class SpectreRoutineTest
 */
class SpectreRoutineTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Import\Routine\SpectreRoutine
     */
    public function testRunAuthenticated(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'SR2b' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'authenticated';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageAuthenticatedHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'choose-accounts'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new SpectreRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\SpectreRoutine
     */
    public function testRunDoAuthenticate(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'SR1A' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'do-authenticate';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();

        $routine = new SpectreRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\SpectreRoutine
     */
    public function testRunGoImport(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'SR3c' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageImportDataHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'do_import'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new SpectreRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\SpectreRoutine
     */
    public function testRunNewOneLogin(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'SR4A' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageNewHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'choose-login'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getCountLogins')->once()->andReturn(2);
        $handler->shouldReceive('run')->once();


        $routine = new SpectreRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\SpectreRoutine
     */
    public function testRunNewZeroLogins(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'SR5A' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'new';
        $job->provider      = 'spectre';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageNewHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'do-authenticate'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('getCountLogins')->once()->andReturn(0);
        $handler->shouldReceive('run')->once();


        $routine = new SpectreRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }
}
