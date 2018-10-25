<?php
/**
 * UpdateControllerTest.php
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

namespace Tests\Feature\Controllers\Admin;

use Carbon\Carbon;
use FireflyConfig;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Configuration;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use FireflyIII\Services\Github\Object\Release;
use FireflyIII\Services\Github\Request\UpdateRequest;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class UpdateControllerTest
 */
class UpdateControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     */
    public function testIndex(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();

        $this->be($this->user());

        $config       = new Configuration;
        $config->data = -1;

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        FireflyConfig::shouldReceive('get')->withArgs(['permission_update_check', -1])->once()->andReturn($config);
        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);

        $response = $this->get(route('admin.update-check'));
        $response->assertStatus(200);

        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     */
    public function testPost(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        $falseConfig       = new Configuration;
        $falseConfig->data = false;

        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['permission_update_check', 1])->once()->andReturn(new Configuration);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);
        $this->be($this->user());
        $response = $this->post(route('admin.update-check.post'), ['check_for_updates' => 1]);
        $response->assertSessionHas('success');
        $response->assertStatus(302);
        $response->assertRedirect(route('admin.update-check'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheck(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        $falseConfig       = new Configuration;
        $falseConfig->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);

        $version = config('firefly.version');
        $date    = new Carbon;
        $date->subDays(5);
        $releases = [
            new Release(['id' => 'x', 'title' => $version . '.1', 'content' => '', 'updated' => $date]),
        ];
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('call')->andReturnNull();
        $updater->shouldReceive('getReleases')->andReturn($releases);

        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee($version);
        $response->assertSee('which was released on');
        $response->assertSee($version . '.1');
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheckCurrent(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        $falseConfig       = new Configuration;
        $falseConfig->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);

        $date = new Carbon;
        $date->subDays(5);
        $version  = config('firefly.version');
        $releases = [
            new Release(['id' => 'x', 'title' => $version, 'content' => '', 'updated' => $date]),
        ];
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('call')->andReturnNull();
        $updater->shouldReceive('getReleases')->andReturn($releases);

        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee($version);
        $response->assertSee('the latest available release');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheckError(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        $falseConfig       = new Configuration;
        $falseConfig->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);

        $version  = config('firefly.version') . '-alpha';
        $releases = [];
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('call')->andThrow(FireflyException::class, 'Something broke.');
        $updater->shouldReceive('getReleases')->andReturn($releases);

        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee('An error occurred while checking');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UpdateController
     * @covers \FireflyIII\Helpers\Update\UpdateTrait
     */
    public function testUpdateCheckNewer(): void
    {
        $userRepos = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->andReturn(true)->atLeast()->once();
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'demo'])->andReturn(false)->atLeast()->once();

        $falseConfig       = new Configuration;
        $falseConfig->data = false;
        FireflyConfig::shouldReceive('get')->withArgs(['is_demo_site', false])->once()->andReturn($falseConfig);
        FireflyConfig::shouldReceive('set')->withArgs(['last_update_check', Mockery::any()])->once()->andReturn(new Configuration);

        $version  = config('firefly.version') . '-alpha';
        $releases = [
            new Release(['id' => 'x', 'title' => $version, 'content' => '', 'updated' => new Carbon]),
        ];
        $updater  = $this->mock(UpdateRequest::class);
        $updater->shouldReceive('call')->andReturnNull();
        $updater->shouldReceive('getReleases')->andReturn($releases);

        // expect a new release (because of .1)
        $this->be($this->user());
        $response = $this->post(route('admin.update-check.manual'));
        $response->assertStatus(200);
        $response->assertSee($version);
        $response->assertSee('which is newer than the');
    }
}
