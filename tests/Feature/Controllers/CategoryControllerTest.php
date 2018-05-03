<?php
/**
 * CategoryControllerTest.php
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
use Log;
use Navigation;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\CategoryController::create
     */
    public function testCreate()
    {
        // mock stuff
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('categories.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::delete
     */
    public function testDelete()
    {
        // mock stuff
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('categories.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::destroy
     */
    public function testDestroy()
    {
        // mock stuff
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $categoryRepos->shouldReceive('destroy')->andReturn(true);

        $this->session(['categories.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('categories.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('categories.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::index
     * @covers \FireflyIII\Http\Controllers\CategoryController::__construct
     */
    public function testIndex()
    {
        // mock stuff
        $category      = factory(Category::class)->make();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]))->once();
        $categoryRepos->shouldReceive('lastUseDate')->andReturn(new Carbon)->once();

        $this->be($this->user());
        $response = $this->get(route('categories.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::noCategory
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getNoCategoryPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoCategory(string $range)
    {
        // mock stuff
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::noCategory
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getNoCategoryPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     */
    public function testNoCategoryAll(string $range)
    {
        // mock stuff
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['all']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::noCategory
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getNoCategoryPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoCategoryDate(string $range)
    {
        // mock stuff
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutCategory')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();

        Navigation::shouldReceive('updateStartDate')->andReturn(new Carbon);
        Navigation::shouldReceive('updateEndDate')->andReturn(new Carbon);
        Navigation::shouldReceive('startOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('endOfPeriod')->andReturn(new Carbon);
        Navigation::shouldReceive('periodShow')->andReturn('Some date');
        Navigation::shouldReceive('blockPeriods')->andReturn([['period' => '1M', 'start' => new Carbon, 'end' => new Carbon]])->once();

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.no-category', ['2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getPeriodOverview
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        $transaction   = factory(Transaction::class)->make();
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $journalRepos  = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

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
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowAll(string $range)
    {
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

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('firstUseDate')->andReturn(new Carbon);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('categories.show', [1, 'all']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByDate(string $range)
    {
        // mock stuff
        $transaction  = factory(Transaction::class)->make();
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

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
        $response = $this->get(route('categories.show', [1, '2015-01-01']));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\CategoryController::show
     * @covers       \FireflyIII\Http\Controllers\CategoryController::getPeriodOverview
     *
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowEmpty(string $range)
    {
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->twice()->andReturn(new TransactionJournal);

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

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::store
     * @covers \FireflyIII\Http\Requests\CategoryFormRequest
     */
    public function testStore()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->andReturn(new Category);
        $repository->shouldReceive('store')->andReturn(new Category);

        $this->session(['categories.create.uri' => 'http://localhost']);

        $data = [
            'name' => 'New Category ' . random_int(1000, 9999),
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\CategoryController::update
     * @covers \FireflyIII\Http\Requests\CategoryFormRequest
     */
    public function testUpdate()
    {
        $category     = Category::first();
        $repository   = $this->mock(CategoryRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update');
        $repository->shouldReceive('findNull')->andReturn($category);

        $this->session(['categories.edit.uri' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Category ' . random_int(1000, 9999),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('categories.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
