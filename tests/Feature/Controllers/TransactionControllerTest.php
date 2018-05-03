<?php
/**
 * TransactionControllerTest.php
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
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class TransactionControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TransactionControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     * @covers \FireflyIII\Http\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Http\Controllers\TransactionController::getPeriodOverview
     * @covers \FireflyIII\Http\Controllers\TransactionController::sumPerCurrency
     */
    public function testIndex()
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('first')->once()->andReturn($transfer);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     */
    public function testIndexAll()
    {
        $date = new Carbon;
        $this->session(['start' => $date, 'end' => clone $date]);

        // mock stuff
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $repository->shouldReceive('first')->twice()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getJournals')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index.all', ['transfer']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     * @covers \FireflyIII\Http\Controllers\TransactionController::getPeriodOverview
     * @covers \FireflyIII\Http\Controllers\TransactionController::sumPerCurrency
     */
    public function testIndexByDate()
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);


        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);
        $repository->shouldReceive('first')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getJournals')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['transfer', '2016-01-01']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     * @covers \FireflyIII\Http\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Http\Controllers\TransactionController::getPeriodOverview
     * @covers \FireflyIII\Http\Controllers\TransactionController::sumPerCurrency
     */
    public function testIndexDeposit()
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);

        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository->shouldReceive('first')->once()->andReturn($transfer);
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getJournals')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['deposit']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::index
     * @covers \FireflyIII\Http\Controllers\TransactionController::__construct
     * @covers \FireflyIII\Http\Controllers\TransactionController::getPeriodOverview
     * @covers \FireflyIII\Http\Controllers\TransactionController::sumPerCurrency
     */
    public function testIndexWithdrawal()
    {
        $transaction                              = new Transaction;
        $transaction->transaction_currency_id     = 1;
        $transaction->transaction_currency_symbol = 'x';
        $transaction->transaction_currency_code   = 'ABC';
        $transaction->transaction_currency_dp     = 2;
        $transaction->transaction_amount          = '5';
        $collection                               = new Collection([$transaction]);

        // mock stuff
        $repository = $this->mock(JournalRepositoryInterface::class);
        $collector  = $this->mock(JournalCollectorInterface::class);
        $transfer = $this->user()->transactionJournals()->inRandomOrder()->where('transaction_type_id', 3)->first();
        $repository->shouldReceive('firstNull')->once()->andReturn($transfer);
        $repository->shouldReceive('first')->once()->andReturn($transfer);

        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('setLimit')->andReturnSelf();
        $collector->shouldReceive('setPage')->andReturnSelf();
        $collector->shouldReceive('addFilter')->andReturnSelf();
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([InternalTransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('getPaginatedJournals')->andReturn(new LengthAwarePaginator([], 0, 10));
        $collector->shouldReceive('getJournals')->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('transactions.index', ['withdrawal']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::reconcile
     */
    public function testReconcile()
    {
        $data       = ['transactions' => [1, 2]];
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->times(1)->andReturn(new TransactionJournal);

        $repository->shouldReceive('findTransaction')->andReturn(new Transaction)->twice();
        $repository->shouldReceive('reconcile')->twice();

        $this->be($this->user());
        $response = $this->post(route('transactions.reconcile'), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::reorder
     */
    public function testReorder()
    {
        // mock stuff
        $journal       = factory(TransactionJournal::class)->make();
        $journal->date = new Carbon('2016-01-01');
        $repository    = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('find')->once()->andReturn($journal);
        $repository->shouldReceive('setOrder')->once()->andReturn(true);

        $data = [
            'date'  => '2016-01-01',
            'items' => [1],
        ];
        $this->be($this->user());
        $response = $this->post(route('transactions.reorder'), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\TransactionController::show
     * @covers \FireflyIII\Http\Controllers\Controller::isOpeningBalance
     */
    public function testShow()
    {
        // mock stuff
        $linkRepos = $this->mock(LinkTypeRepositoryInterface::class);
        $linkRepos->shouldReceive('get')->andReturn(new Collection);
        $linkRepos->shouldReceive('getLinks')->andReturn(new Collection);


        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('getPiggyBankEvents')->andReturn(new Collection);
        $journalRepos->shouldReceive('first')->andReturn(new TransactionJournal);
        $journalRepos->shouldReceive('getMetaField')->andReturn('');

        $this->be($this->user());
        $response = $this->get(route('transactions.show', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Controller::redirectToAccount
     * @covers \FireflyIII\Http\Controllers\TransactionController::show
     */
    public function testShowOpeningBalance()
    {
        $linkRepos = $this->mock(LinkTypeRepositoryInterface::class);
        $linkRepos->shouldReceive('get')->andReturn(new Collection);
        $linkRepos->shouldReceive('getLinks')->andReturn(new Collection);

        $this->be($this->user());
        $journal  = $this->user()->transactionJournals()->where('transaction_type_id', 4)->first();
        $response = $this->get(route('transactions.show', [$journal->id]));
        $response->assertStatus(302);
    }
}
