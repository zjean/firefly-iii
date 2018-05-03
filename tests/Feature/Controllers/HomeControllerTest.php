<?php
/**
 * HomeControllerTest.php
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
use Mockery;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class HomeControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HomeControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\HomeController::dateRange
     * @covers \FireflyIII\Http\Controllers\HomeController::__construct
     */
    public function testDateRange()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
        ];

        $response = $this->post(route('daterange'), $args);
        $response->assertStatus(200);
        $response->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::dateRange
     * @covers \FireflyIII\Http\Controllers\HomeController::__construct
     */
    public function testDateRangeCustom()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());

        $args = [
            'start' => '2012-01-01',
            'end'   => '2012-04-01',
            'label' => 'Custom range',
        ];

        $response = $this->post(route('daterange'), $args);
        $response->assertStatus(200);
        $response->assertSessionHas('warning', '91 days of data may take a while to load.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::displayError
     */
    public function testDisplayError()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('error'));
        $response->assertStatus(500);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::flush
     */
    public function testFlush()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('flush'));
        $response->assertStatus(302);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\HomeController::index
     * @covers       \FireflyIII\Http\Controllers\HomeController::__construct
     * @covers       \FireflyIII\Http\Controllers\Controller::__construct
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndex(string $range)
    {
        // mock stuff
        $account      = factory(Account::class)->make();
        $collector    = $this->mock(JournalCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $billRepos    = $this->mock(BillRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(1);
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection([$account]));
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection([$account]));
        $billRepos->shouldReceive('getBills')->andReturn(new Collection);
        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1));

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('index'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\HomeController::index
     * @covers       \FireflyIII\Http\Controllers\HomeController::__construct
     * @covers       \FireflyIII\Http\Controllers\Controller::__construct
     * @dataProvider dateRangeProvider
     *
     * @param $range
     */
    public function testIndexEmpty(string $range)
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('count')->andReturn(0);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('index'));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::routes()
     */
    public function testRoutes()
    {
        $this->be($this->user());
        $response = $this->get(route('routes'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\HomeController::testFlash
     */
    public function testTestFlash()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('test-flash'));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('info');
        $response->assertSessionHas('warning');
        $response->assertSessionHas('error');
    }
}
