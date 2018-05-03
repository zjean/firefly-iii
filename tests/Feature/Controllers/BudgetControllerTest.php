<?php
/**
 * BudgetControllerTest.php
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
use FireflyIII\Models\Budget;
use FireflyIII\Models\BudgetLimit;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class BudgetControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BudgetControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\BudgetController::amount
     */
    public function testAmount()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');


        $data = ['amount' => 200, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::amount
     */
    public function testAmountZero()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('updateLimitAmount')->andReturn(new BudgetLimit);
        $repository->shouldReceive('spentInPeriod')->andReturn('0');
        $repository->shouldReceive('budgetedPerDay')->andReturn('10');

        $data = ['amount' => 0, 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.amount', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::create
     */
    public function testCreate()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('budgets.create'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::delete
     */
    public function testDelete()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('budgets.delete', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::destroy
     */
    public function testDestroy()
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $repository->shouldReceive('destroy')->andReturn(true);

        $this->session(['budgets.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('budgets.destroy', [1]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::edit
     */
    public function testEdit()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('budgets.edit', [1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::index
     * @covers       \FireflyIII\Http\Controllers\BudgetController::__construct
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndex(string $range)
    {
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();
        $budgetInfo              = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::index
     * @covers       \FireflyIII\Http\Controllers\BudgetController::__construct
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndexWithDate(string $range)
    {
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();
        $budgetInfo  = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', ['2017-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::index
     * @covers       \FireflyIII\Http\Controllers\BudgetController::__construct
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIndexWithInvalidDate(string $range)
    {
        // mock stuff
        $budget      = factory(Budget::class)->make();
        $budgetLimit = factory(BudgetLimit::class)->make();

        // set budget limit to current month:
        $budgetLimit->start_date = Carbon::now()->startOfMonth();
        $budgetLimit->end_date   = Carbon::now()->endOfMonth();
        $budgetInfo              = [
            $budget->id => [
                'spent'      => '0',
                'budgeted'   => '0',
                'currentRep' => false,
            ],
        ];

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository->shouldReceive('cleanupBudgets');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));
        $repository->shouldReceive('getInactiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('collectBudgetInformation')->andReturn($budgetInfo);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.index', ['Hello-there']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::infoIncome
     */
    public function testInfoIncome()
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);

        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('budgets.income.info', ['20170101', '20170131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::infoIncome
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testInfoIncomeExpanded(string $range)
    {
        // mock stuff
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('getAvailableBudget')->andReturn('100.123');
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.income.info', ['20170301', '20170430']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::noBudget
     * @covers       \FireflyIII\Http\Controllers\BudgetController::getPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     *
     */
    public function testNoBudget(string $range)
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $date = new Carbon();
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget'));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::noBudget
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoBudgetAll(string $range)
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $date = new Carbon();
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget', ['all']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::noBudget
     * @covers       \FireflyIII\Http\Controllers\BudgetController::getPeriodOverview
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testNoBudgetDate(string $range)
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection);
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withoutBudget')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $date = new Carbon();
        $this->session(['start' => $date, 'end' => clone $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.no-budget', ['2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::postUpdateIncome
     */
    public function testPostUpdateIncome()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('setAvailableBudget');
        $repository->shouldReceive('cleanupBudgets');

        $data = ['amount' => '200', 'start' => '2017-01-01', 'end' => '2017-01-31'];
        $this->be($this->user());
        $response = $this->post(route('budgets.income.post'), $data);
        $response->assertStatus(302);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::show
     * @covers       \FireflyIII\Http\Controllers\BudgetController::getLimits
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShow(string $range)
    {
        // mock stuff

        $budgetLimit = factory(BudgetLimit::class)->make();

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);

        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection);

        $repository = $this->mock(BudgetRepositoryInterface::class);
        $repository->shouldReceive('getBudgetLimits')->andReturn(new Collection([$budgetLimit]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');

        $date = new Carbon();
        $date->subDay();
        $this->session(['first' => $date]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\BudgetController::showByBudgetLimit
     * @expectedExceptionMessage This budget limit is not part of
     */
    public function testShowByBadBudgetLimit()
    {
        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('budgets.show.limit', [1, 8]));
        $response->assertStatus(500);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\BudgetController::showByBudgetLimit()
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testShowByBudgetLimit(string $range)
    {
        // mock stuff
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        // mock account repository
        $accountRepository = $this->mock(AccountRepositoryInterface::class);
        $accountRepository->shouldReceive('getAccountsByType')->andReturn(new Collection);

        // mock budget repository
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('spentInPeriod')->andReturn('1');
        $budgetRepository->shouldReceive('getBudgetLimits')->andReturn(new Collection);

        // mock journal collector:
        $collector = $this->mock(JournalCollectorInterface::class);
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setBudget')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('budgets.show.limit', [1, 1]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::store
     */
    public function testStore()
    {
        // mock stuff
        $budget       = factory(Budget::class)->make();
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('store')->andReturn($budget);
        $repository->shouldReceive('cleanupBudgets');

        $this->session(['budgets.create.uri' => 'http://localhost']);

        $data = [
            'name' => 'New Budget ' . random_int(1000, 9999),
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.store'), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::update
     */
    public function testUpdate()
    {
        // mock stuff
        $budget       = factory(Budget::class)->make();
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('findNull')->andReturn($budget);
        $repository->shouldReceive('update');
        $repository->shouldReceive('cleanupBudgets');

        $this->session(['budgets.edit.uri' => 'http://localhost']);

        $data = [
            'name'   => 'Updated Budget ' . random_int(1000, 9999),
            'active' => 1,
        ];
        $this->be($this->user());
        $response = $this->post(route('budgets.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\BudgetController::updateIncome
     */
    public function testUpdateIncome()
    {
        // must be in list
        $this->be($this->user());

        // mock stuff
        $repository   = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getAvailableBudget')->andReturn('1');
        $repository->shouldReceive('cleanupBudgets');

        $response = $this->get(route('budgets.income', ['2017-01-01', '2017-01-31']));
        $response->assertStatus(200);
    }
}
