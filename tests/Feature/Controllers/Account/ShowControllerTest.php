<?php
/**
 * ShowControllerTest.php
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

namespace Tests\Feature\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Account\AccountTaskerInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 *
 * Class ShowControllerTest
 */
class ShowControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range): void
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff:
        $tasker        = $this->mock(AccountTaskerInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $tasker->shouldReceive('amountOutInPeriod')->withAnyArgs()->andReturn('-1');
        $tasker->shouldReceive('amountInInPeriod')->withAnyArgs()->andReturn('1');

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(clone $date)->once();
        $repository->shouldReceive('getMetaValue')->andReturn('');


        $transaction = factory(Transaction::class)->make();
        $collector   = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }


    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range): void
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff:
        $tasker        = $this->mock(AccountTaskerInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $tasker->shouldReceive('amountOutInPeriod')->withAnyArgs()->andReturn('-1');
        $tasker->shouldReceive('amountInInPeriod')->withAnyArgs()->andReturn('1');

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(clone $date)->once();
        $repository->shouldReceive('getMetaValue')->andReturn('');


        $transaction = factory(Transaction::class)->make();
        $collector   = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show.all', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @expectedExceptionMessage End is after start!
     */
    public function testShowBrokenBadDates(): void
    {
        // mock
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $this->session(['start' => '2018-01-01', 'end' => '2017-12-01']);

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 3)->orderBy('id', 'ASC')->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.show', [$account->id, '2018-01-01', '2017-12-01']));
        $response->assertStatus(500);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @expectedExceptionMessage Expected a transaction
     */
    public function testShowBrokenInitial(): void
    {
        // mock
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 6)->orderBy('id', 'ASC')->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.show', [$account->id]));
        $response->assertStatus(500);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDateEmpty(string $range): void
    {
        // mock stuff
        $collector     = $this->mock(JournalCollectorInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('oldestJournalDate')->andReturn(new Carbon);
        $repository->shouldReceive('getMetaValue')->andReturn('');

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('accounts.show', [1, '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Account\ShowController
     */
    public function testShowInitial(): void
    {
        // mock stuff
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $account  = $this->user()->accounts()->where('account_type_id', 6)->orderBy('id', 'DESC')->whereNull('deleted_at')->first();
        $response = $this->get(route('accounts.show', [$account->id]));
        $response->assertStatus(302);
    }

}