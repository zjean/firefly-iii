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

namespace Tests\Feature\Controllers\Category;


use Tests\TestCase;
use Log;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Navigation;
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
     * @covers       \FireflyIII\Http\Controllers\Category\ShowController
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range): void
    {
        Log::debug(sprintf('Test show(%s)', $range));
        $transaction   = factory(Transaction::class)->make();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->twice()->andReturn(TransactionJournal::first());

        // mock stuff
        $categoryRepos->shouldReceive('spentInPeriod')->andReturn('0');
        $categoryRepos->shouldReceive('earnedInPeriod')->andReturn('0');

        $accountRepos->shouldReceive('getAccountsByType')->once()->andReturn(new Collection);

        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->atLeast(2);
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast(2);
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->atLeast(2);
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->atLeast(2);
        $collector->shouldReceive('setCategory')->andReturnSelf()->atLeast(2);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10))->once();

        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('getJournals')->andReturn(new Collection)->atLeast(1);

        Navigation::shouldReceive('updateStartDate')->andReturn(new Carbon);
        Navigation::shouldReceive('updateEndDate')->andReturn(new Carbon);
        Navigation::shouldReceive('startOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('endOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('periodShow')->andReturn('Some date');
        Navigation::shouldReceive('blockPeriods')->andReturn([['period' => '1M', 'start' => new Carbon, 'end' => new Carbon]])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range): void
    {
        Log::debug(sprintf('Test showAll(%s)', $range));
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->once();
        $collector->shouldReceive('setRange')->andReturnSelf()->once();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->once();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->once();

        $collector->shouldReceive('setCategory')->andReturnSelf()->once();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10))->once();

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(TransactionJournal::first());
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1, 'all']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\ShowController
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range): void
    {
        Log::debug(sprintf('Test testShowByDate(%s)', $range));
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $month        = new Carbon();
        $month->startOfMonth();
        $journal = TransactionJournal::where('date', '>=', $month->format('Y-m-d') . ' 00:00:00')->first();
        $journalRepos->shouldReceive('firstNull')->twice()->andReturn($journal);

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->once();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('setCategory')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('getJournals')->andReturn(new Collection)->atLeast(1);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([$transaction], 0, 10))->once();

        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('earnedInPeriod')->andReturn('1');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $today = new Carbon();
        $today->subDay();
        $response = $this->get(route('categories.show', [1, $today->format('Y-m-d')]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Category\ShowController
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowEmpty(string $range): void
    {
        $latestJournal = $this->user()->transactionJournals()
            ->orderBy('date','DESC')->first();

        Log::debug(sprintf('Test testShowEmpty(%s)', $range));
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->twice()->andReturn($latestJournal);

        // mock stuff
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('earnedInPeriod')->andReturn('0');

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')->once()->andReturn(new Collection);

        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setPage')->andReturnSelf()->once();
        $collector->shouldReceive('setLimit')->andReturnSelf()->once();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('setRange')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('setCategory')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('setTypes')->andReturnSelf()->atLeast(1);
        $collector->shouldReceive('getJournals')->andReturn(new Collection)->atLeast(1);

        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10))->atLeast(1);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

}
