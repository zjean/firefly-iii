<?php
/**
 * NewUserControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Log;
use Tests\TestCase;

/**
 * Class NewUserControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class NewUserControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', \get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::index
     * @covers \FireflyIII\Http\Controllers\NewUserController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(0);

        $this->be($this->emptyUser());
        $response = $this->get(route('new-user.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::index
     * @covers \FireflyIII\Http\Controllers\NewUserController::__construct
     */
    public function testIndexExisting()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(1);

        $this->be($this->user());
        $response = $this->get(route('new-user.index'));
        $response->assertStatus(302);
        $response->assertRedirect(route('index'));
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::submit
     * @covers \FireflyIII\Http\Controllers\NewUserController::createAssetAccount
     * @covers \FireflyIII\Http\Controllers\NewUserController::createSavingsAccount
     */
    public function testSubmit()
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));

        $data = [
            'bank_name'                       => 'New bank',
            'savings_balance'                 => '1000',
            'bank_balance'                    => '100',
            'language'                        => 'en_US',
            'amount_currency_id_bank_balance' => 1,
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\NewUserController::submit
     */
    public function testSubmitSingle()
    {
        // mock stuff
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('store')->times(3);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));

        $data = [
            'bank_name'                       => 'New bank',
            'bank_balance'                    => '100',
            'amount_currency_id_bank_balance' => 1,
        ];
        $this->be($this->emptyUser());
        $response = $this->post(route('new-user.submit'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
