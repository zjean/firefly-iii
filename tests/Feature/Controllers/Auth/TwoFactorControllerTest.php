<?php
/**
 * TwoFactorControllerTest.php
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

use FireflyIII\Models\Preference;
use Google2FA;
use Preferences;
use Tests\TestCase;

/**
 * Class TwoFactorControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TwoFactorControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     */
    public function testIndex()
    {
        $this->be($this->user());

        $truePref               = new Preference;
        $truePref->data         = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'JZMES376Z6YXY4QZ';

        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference)->once();

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     */
    public function testIndexNo2FA()
    {
        $this->be($this->user());

        $falsePreference       = new Preference;
        $falsePreference->data = false;
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($falsePreference)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn(null)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn(null)->once();

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Auth\TwoFactorController::index
     * @expectedExceptionMessage Your two factor authentication secret is empty
     */
    public function testIndexNoSecret()
    {
        $this->be($this->user());

        $truePref               = new Preference;
        $truePref->data         = true;
        $secretPreference       = new Preference;
        $secretPreference->data = '';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePref)->twice();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference)->once();
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference)->once();

        $response = $this->get(route('two-factor.index'));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::lostTwoFactor
     */
    public function testLostTwoFactor()
    {
        $this->be($this->user());

        $truePreference         = new Preference;
        $truePreference->data   = true;
        $secretPreference       = new Preference;
        $secretPreference->data = 'JZMES376Z6YXY4QZ';
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthEnabled', false])->andReturn($truePreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret', null])->andReturn($secretPreference);
        Preferences::shouldReceive('get')->withArgs(['twoFactorAuthSecret'])->andReturn($secretPreference);

        $response = $this->get(route('two-factor.lost'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Auth\TwoFactorController::postIndex
     */
    public function testPostIndex()
    {
        $data = ['code' => '123456'];
        Google2FA::shouldReceive('verifyKey')->andReturn(true)->once();
        $this->session(['remember_login' => true]);

        $this->be($this->user());
        $response = $this->post(route('two-factor.post'), $data);
        $response->assertStatus(302);
    }
}
