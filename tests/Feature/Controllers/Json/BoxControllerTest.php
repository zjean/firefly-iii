<?php
/**
 * BoxControllerTest.php
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

namespace Tests\Feature\Controllers\Json;

use Carbon\Carbon;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Report\NetWorthInterface;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class BoxControllerTest
 */
class BoxControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testAvailable(): void
    {
        $return     = [
            0 => [
                'spent' => '-1200', // more than budgeted.
            ],
        ];
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $repository->shouldReceive('getAvailableBudget')->andReturn('1000');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('collectBudgetInformation')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('json.box.available'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testAvailableDays(): void
    {
        $return     = [
            0 => [
                'spent' => '-800', // more than budgeted.
            ],
        ];
        $repository = $this->mock(BudgetRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $repository->shouldReceive('getAvailableBudget')->andReturn('1000');
        $repository->shouldReceive('getActiveBudgets')->andReturn(new Collection);
        $repository->shouldReceive('collectBudgetInformation')->andReturn($return);

        $this->be($this->user());
        $response = $this->get(route('json.box.available'));
        $response->assertStatus(200);

    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testBalance(): void
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);


        // try a collector for income:

        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('json.box.balance'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testBalanceTransactions(): void
    {
        $transaction                          = new Transaction;
        $transaction->transaction_currency_id = 1;
        $transaction->transaction_amount      = '5';

        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $currencyRepos->shouldReceive('findNull')->withArgs([1])->andReturn(TransactionCurrency::find(1))->atLeast()->once();


        // try a collector for income:
        $collector->shouldReceive('setAllAssetAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn(new Collection([$transaction]));

        $this->be($this->user());
        $response = $this->get(route('json.box.balance'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testBills(): void
    {
        $billRepos = $this->mock(BillRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $billRepos->shouldReceive('getBillsPaidInRange')->andReturn('0');
        $billRepos->shouldReceive('getBillsUnpaidInRange')->andReturn('0');

        $this->be($this->user());
        $response = $this->get(route('json.box.bills'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorth(): void
    {
        $result = [
            [
                'currency' => TransactionCurrency::find(1),
                'balance'  => '3',
            ],
        ];


        $netWorthHelper = $this->mock(NetWorthInterface::class);

        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');


        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthFuture(): void
    {
        $result = [
            [
                'currency' => TransactionCurrency::find(1),
                'balance'  => '3',
            ],
        ];

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $start = new Carbon;
        $start->addMonths(6)->startOfMonth();
        $end = clone $start;
        $end->endOfMonth();
        $this->session(['start' => $start, 'end' => $end]);
        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthNoCurrency(): void
    {
        $result = [
            [
                'currency' => TransactionCurrency::find(1),
                'balance'  => '3',
            ],
        ];

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(null);
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthNoInclude(): void
    {
        $result = [
            [
                'currency' => TransactionCurrency::find(1),
                'balance'  => '3',
            ],
        ];


        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$this->user()->accounts()->first()]));
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('0');


        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Json\BoxController
     */
    public function testNetWorthVirtual(): void
    {
        $result = [
            [
                'currency' => TransactionCurrency::find(1),
                'balance'  => '3',
            ],
        ];

        $account                  = $this->user()->accounts()->first();
        $account->virtual_balance = '1000';
        $accountRepos             = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos            = $this->mock(CurrencyRepositoryInterface::class);

        $netWorthHelper = $this->mock(NetWorthInterface::class);
        $netWorthHelper->shouldReceive('setUser')->once();
        $netWorthHelper->shouldReceive('getNetWorthByCurrency')->once()->andReturn($result);

        $accountRepos->shouldReceive('getActiveAccountsByType')->andReturn(new Collection([$account]));
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('ccAsset');
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'include_net_worth'])->andReturn('1');

        $this->be($this->user());
        $response = $this->get(route('json.box.net-worth'));
        $response->assertStatus(200);
    }
}
