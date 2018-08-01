<?php
/**
 * PrerequisitesControllerTest.php
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

namespace Tests\Feature\Controllers\Import;

use FireflyIII\Import\Prerequisites\FakePrerequisites;
use FireflyIII\Models\ImportJob;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use Illuminate\Support\MessageBag;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrerequisitesControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testIndex(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'A_pre_job_' . random_int(1, 10000);
        $job->status       = 'new';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        // mock stuff
        $prereq     = $this->mock(FakePrerequisites::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $prereq->shouldReceive('setUser')->times(2);
        $prereq->shouldReceive('isComplete')->times(2)->andReturn(false);
        $prereq->shouldReceive('getView')->once()->andReturn('import.fake.prerequisites');
        $prereq->shouldReceive('getViewParameters')->once()->andReturn(['api_key' => '']);


        $this->be($this->user());
        $response = $this->get(route('import.prerequisites.index', ['fake', $job->key]));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testIndexBadState(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'B_pre_job_' . random_int(1, 10000);
        $job->status       = 'some_Bad_state';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $this->be($this->user());
        $response = $this->get(route('import.prerequisites.index', ['fake', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testIndexComplete(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'C_pre_job_' . random_int(1, 10000);
        $job->status       = 'new';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        // mock stuff
        $prereq     = $this->mock(FakePrerequisites::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'has_prereq']);
        $prereq->shouldReceive('setUser')->times(2);
        $prereq->shouldReceive('isComplete')->times(2)->andReturn(true);

        $this->be($this->user());
        $response = $this->get(route('import.prerequisites.index', ['fake', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.job.configuration.index', [$job->key]));

    }

    /**
     * Redirects to configuration.
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPost(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'D_pre_job_' . random_int(1, 10000);
        $job->status       = 'new';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        // mock stuff
        $prereq     = $this->mock(FakePrerequisites::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $prereq->shouldReceive('setUser')->times(2);
        $prereq->shouldReceive('storePrerequisites')->once()->andReturn(new MessageBag);
        $repository->shouldReceive('setStatus')->once()->withArgs([Mockery::any(), 'has_prereq']);
        $prereq->shouldReceive('isComplete')->times(1)->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['fake', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.job.configuration.index', [$job->key]));
    }

    /**
     * Bad state gives errors.
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPostBadState(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'D_pre_job_' . random_int(1, 10000);
        $job->status       = 'badstate';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        // mock stuff
        $prereq     = $this->mock(FakePrerequisites::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $prereq->shouldReceive('setUser')->times(1);
        $prereq->shouldReceive('isComplete')->times(1)->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['fake', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.index'));
        $response->assertSessionHas('error', 'To access this page, your import job cannot have status "badstate".');
    }

    /**
     * Redirects to index.
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPostNoJob(): void
    {
        // mock stuff
        $prereq     = $this->mock(FakePrerequisites::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $prereq->shouldReceive('setUser')->once();
        $prereq->shouldReceive('storePrerequisites')->once()->andReturn(new MessageBag);

        $prereq->shouldReceive('setUser')->times(1);
        $prereq->shouldReceive('isComplete')->times(1)->andReturn(false);

        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['fake']));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.index'));
    }

    /**
     * Error messages? Redirect back
     *
     * @covers \FireflyIII\Http\Controllers\Import\PrerequisitesController
     */
    public function testPostWithMessages(): void
    {
        $job               = new ImportJob;
        $job->user_id      = $this->user()->id;
        $job->key          = 'D_pre_job_' . random_int(1, 10000);
        $job->status       = 'new';
        $job->provider     = 'fake';
        $job->transactions = [];
        $job->file_type    = '';
        $job->save();

        $messages = new MessageBag;
        $messages->add('some', 'message');

        // mock stuff
        $prereq     = $this->mock(FakePrerequisites::class);
        $repository = $this->mock(ImportJobRepositoryInterface::class);

        $prereq->shouldReceive('setUser')->times(1);
        $prereq->shouldReceive('isComplete')->times(1)->andReturn(false);

        $prereq->shouldReceive('setUser')->once();
        $prereq->shouldReceive('storePrerequisites')->once()->andReturn($messages);

        $this->be($this->user());
        $response = $this->post(route('import.prerequisites.post', ['fake', $job->key]));
        $response->assertStatus(302);
        $response->assertRedirect(route('import.prerequisites.index', ['fake', $job->key]));
        $response->assertSessionHas('error', 'message');
    }
}
