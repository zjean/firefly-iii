<?php
/**
 * JavascriptControllerTest.php
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

use Amount;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class JavascriptControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JavascriptControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\JavascriptController
     */
    public function testAccounts(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = factory(Account::class)->make();

        $accountRepos->shouldReceive('getAccountsByType')->andReturn(new Collection([$account]))
                     ->withArgs(
                         [[AccountType::DEFAULT, AccountType::ASSET, AccountType::DEBT, AccountType::LOAN, AccountType::MORTGAGE, AccountType::CREDITCARD]]
                     )->once();
        $currencyRepos->shouldReceive('findByCodeNull')->withArgs(['EUR'])->andReturn(new TransactionCurrency);
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        $this->be($this->user());
        $response = $this->get(route('javascript.accounts'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController
     */
    public function testCurrencies(): void
    {
        $repository = $this->mock(CurrencyRepositoryInterface::class);
        $currency   = factory(TransactionCurrency::class)->make();
        $repository->shouldReceive('get')->andReturn(new Collection([$currency]));


        $this->be($this->user());
        $response = $this->get(route('javascript.currencies'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController
     * @covers       \FireflyIII\Http\Controllers\JavascriptController
     *
     * @param string $range
     *
     * @dataProvider dateRangeProvider
     */
    public function testVariables(string $range): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn(new Account);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('javascript.variables'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController
     *
     * @param string $range
     *
     * @dataProvider dateRangeProvider
     */
    public function testVariablesCustom(string $range): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn(new Account);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $this->session(['is_custom_range' => true]);
        $response = $this->get(route('javascript.variables'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\JavascriptController
     *
     * @param string $range
     *
     * @dataProvider dateRangeProvider
     */
    public function testVariablesNull(string $range): void
    {
        Amount::shouldReceive('getDefaultCurrency')->andReturn(TransactionCurrency::find(1))->times(2);
        Amount::shouldReceive('getJsConfig')->andReturn([])->once();

        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn(new Account);
        $currencyRepos->shouldReceive('findNull')->andReturn(null);

        $accountRepos->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('javascript.variables'));
        $response->assertStatus(200);
    }
}
