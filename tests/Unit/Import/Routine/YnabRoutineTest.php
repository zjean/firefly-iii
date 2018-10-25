<?php
/**
 * YnabRoutineTest.php
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

namespace tests\Unit\Import\Routine;


use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Import\Routine\YnabRoutine;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Routine\Ynab\GetAccountsHandler;
use FireflyIII\Support\Import\Routine\Ynab\ImportDataHandler;
use FireflyIII\Support\Import\Routine\Ynab\StageGetAccessHandler;
use FireflyIII\Support\Import\Routine\Ynab\StageGetBudgetsHandler;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class YnabRoutineTest
 */
class YnabRoutineTest extends TestCase
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
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunGetAccessToken(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_1_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'get_access_token';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageGetAccessHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();

        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'get_budgets'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunMultiBudgets(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_2_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'get_budgets';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageGetBudgetsHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $config = ['budgets' => [1, 2, 3]];

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('getConfiguration')->once()->andReturn($config);

        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'select_budgets'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunSingleBudget(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_3_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'get_budgets';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(StageGetBudgetsHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $config = ['budgets' => [1]];

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('getConfiguration')->once()->andReturn($config);

        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'ready_to_run'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'get_accounts'])->once();

        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunGetAccounts(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_4_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'get_accounts';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(GetAccountsHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();

        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'select_accounts'])->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'need_job_config'])->once();


        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunGoForImport(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_5_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'go-for-import';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(ImportDataHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'running'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'do_import'])->once();

        $repository->shouldReceive('setStatus')->withArgs([Mockery::any(), 'provider_finished'])->once();
        $repository->shouldReceive('setStage')->withArgs([Mockery::any(), 'final'])->once();



        // mock calls for handler
        $handler->shouldReceive('setImportJob')->once();
        $handler->shouldReceive('run')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertTrue(false, $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunException(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_6_' . random_int(1, 10000);
        $job->status        = 'ready_to_run';
        $job->stage         = 'bad_state';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(ImportDataHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertEquals('YNAB import routine cannot handle stage "bad_state"', $e->getMessage());
        }
    }

    /**
     * @covers \FireflyIII\Import\Routine\YnabRoutine
     */
    public function testRunBadStatus(): void
    {
        $job                = new ImportJob;
        $job->user_id       = $this->user()->id;
        $job->key           = 'ynab_r_7_' . random_int(1, 10000);
        $job->status        = 'not_ready_to_run';
        $job->stage         = 'bad_state';
        $job->provider      = 'ynab';
        $job->file_type     = '';
        $job->configuration = [];
        $job->save();

        // mock handler and repository
        $handler    = $this->mock(ImportDataHandler::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        // mock calls for repository
        $repository->shouldReceive('setUser')->once();

        $routine = new YnabRoutine;
        $routine->setImportJob($job);
        try {
            $routine->run();
        } catch (FireflyException $e) {
            $this->assertEquals('YNAB import routine cannot handle stage "bad_state"', $e->getMessage());
        }
    }
}