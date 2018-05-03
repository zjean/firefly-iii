<?php
/**
 * UserControllerTest.php
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

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class UserControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UserControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', get_class($this)));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.users.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::destroy
     */
    public function testDestroy()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('destroy')->once();
        $this->be($this->user());
        $response = $this->post(route('admin.users.destroy', ['2']));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::edit
     */
    public function testEdit()
    {
        $this->be($this->user());
        $response = $this->get(route('admin.users.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::index
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::__construct
     */
    public function testIndex()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $user       = $this->user();
        $repository->shouldReceive('all')->andReturn(new Collection([$user]));

        $this->be($user);
        $response = $this->get(route('admin.users'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::show
     */
    public function testShow()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('getUserData')->andReturn(
            [
                'export_jobs_success' => 0,
                'import_jobs_success' => 0,
                'attachments_size'    => 0,
            ]
        );

        $this->be($this->user());
        $response = $this->get(route('admin.users.show', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Admin\UserController::update
     */
    public function testUpdate()
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('changePassword')->once();
        $repository->shouldReceive('changeStatus')->once();
        $repository->shouldReceive('updateEmail')->once();
        $data = [
            'id'                    => 1,
            'email'                 => 'test@example.com',
            'password'              => 'james',
            'password_confirmation' => 'james',
            'blocked_code'          => 'blocked',
            'blocked'               => 1,
        ];

        $this->be($this->user());
        $response = $this->post(route('admin.users.update', ['1']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
