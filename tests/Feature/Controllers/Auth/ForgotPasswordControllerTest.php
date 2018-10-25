<?php
/**
 * ForgotPasswordControllerTest.php
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

namespace Tests\Feature\Controllers\Auth;

use FireflyIII\Repositories\User\UserRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class ForgotPasswordControllerTest
 */
class ForgotPasswordControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Auth\ForgotPasswordController
     */
    public function testSendResetLinkEmail(): void
    {

        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->andReturn(false)->once();
        $data = [
            'email' => 'thegrumpydictator@gmail.com',
        ];

        $response = $this->post(route('password.email'), $data);
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\ForgotPasswordController
     */
    public function testSendResetLinkEmailDemo(): void
    {
        $repository = $this->mock(UserRepositoryInterface::class);
        $repository->shouldReceive('hasRole')->andReturn(true)->once();
        $data = [
            'email' => 'thegrumpydictator@gmail.com',
        ];

        $response = $this->post(route('password.email'), $data);
        $response->assertStatus(302);
    }
}
