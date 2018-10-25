<?php
/**
 * AccountControllerTest.php
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

namespace Tests\Api\V1\Controllers;

use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Laravel\Passport\Passport;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 */
class AccountControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Passport::actingAs($this->user());
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Destroy account over API.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testDelete(): void
    {
        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $repository->shouldReceive('destroy')->once()->andReturn(true);
        $currencyRepos->shouldReceive('setUser')->once();

        // get account:
        $account = $this->user()->accounts()->first();

        // call API
        $response = $this->delete('/api/v1/accounts/' . $account->id);
        $response->assertStatus(204);

    }

    /**
     * Test the list of accounts.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */
    public function testIndex(): void
    {
        // create stuff
        $accounts = factory(Account::class, 10)->create();

        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('getAccountsByType')->withAnyArgs()->andReturn($accounts)->once();
        $currencyRepos->shouldReceive('setUser');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');

        // test API
        $response = $this->get('/api/v1/accounts');
        $response->assertStatus(200);
        $response->assertJson(['data' => [],]);
        $response->assertJson(['meta' => ['pagination' => ['total' => 10, 'count' => 10, 'per_page' => true, 'current_page' => 1, 'total_pages' => 1]],]);
        $response->assertJson(
            ['links' => ['self' => true, 'first' => true, 'last' => true,],]
        );
        $response->assertSee('type=all'); // default returns this.
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Opening balance without date.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testInvalidBalance(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
            'opening_balance'   => '123.45',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'opening_balance_date' => ['The opening balance date field is required when opening balance is present.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * CC type present when account is a credit card.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testNoCreditCardData(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'ccAsset',
            'currency_id'       => 1,
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'cc_monthly_payment_date' => ['The cc monthly payment date field is required when account role is ccAsset.'],
                    'cc_type'                 => ['The cc type field is required when account role is ccAsset.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * No currency information
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testNoCurrencyInfo(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'currency_code' => ['The currency code field is required when currency id is not present.'],
                    'currency_id'   => ['The currency id field is required when currency code is not present.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Show an account.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     */

    public function testShow(): void
    {
        // create stuff
        $account = $this->user()->accounts()->first();

        // mock stuff:
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10')->once();
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01')->once();
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');


        // test API
        $response = $this->get('/api/v1/accounts/' . $account->id);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertSee('2018-01-01'); // opening balance date
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreLiability(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = $this->user()->accounts()->first();
        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($account);
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');

        // data to submit
        $data = [
            'name'                 => 'Some new liability account #' . random_int(1, 10000),
            'currency_id'          => 1,
            'type'                 => 'liability',
            'active'               => 1,
            'include_net_worth'    => 1,
            'liability_amount'     => '10000',
            'liability_start_date' => '2016-01-01',
            'liability_type'       => 'mortgage',
            'interest'             => '1',
            'interest_period'      => 'daily',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertSee($account->name);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Name already in use.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreNotUnique(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser')->once();
        $currencyRepos->shouldReceive('setUser')->once();

        $account = $this->user()->accounts()->where('account_type_id', 3)->first();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(422);
        $response->assertExactJson(
            [
                'message' => 'The given data was invalid.',
                'errors'  => [
                    'name' => ['This account name is already in use.'],
                ],
            ]
        );
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreValid(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = $this->user()->accounts()->first();
        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('store')->once()->andReturn($account);
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('setUser')->once();

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }

    /**
     * Send correct data. Should call account repository store method.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testStoreWithCurrencyCode(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $account       = $this->user()->accounts()->first();

        // mock calls:
        $repository->shouldReceive('setUser');
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('store')->once()->andReturn($account);
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('findByCodeNull')->andReturn(TransactionCurrency::find(1));

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');

        // functions to expect:

        // data to submit
        $data = [
            'name'              => 'Some new asset account #' . random_int(1, 10000),
            'currency_code'     => 'EUR',
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->post('/api/v1/accounts', $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }

    /**
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testUpdate(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');

        $account = $this->user()->accounts()->first();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_id'       => 1,
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->put('/api/v1/accounts/' . $account->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }

    /**
     * Update first asset account we find. Name can be the same as it was.
     *
     * @covers \FireflyIII\Api\V1\Controllers\AccountController
     * @covers \FireflyIII\Api\V1\Requests\AccountRequest
     */
    public function testUpdateCurrencyCode(): void
    {
        // mock repositories
        $repository    = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // mock calls:
        $repository->shouldReceive('setUser');
        $repository->shouldReceive('update')->once();
        $currencyRepos->shouldReceive('setUser')->once();
        $repository->shouldReceive('getOpeningBalanceAmount')->andReturn('10');
        $repository->shouldReceive('getOpeningBalanceDate')->andReturn('2018-01-01');
        $currencyRepos->shouldReceive('findByCodeNull')->andReturn(TransactionCurrency::find(1));

        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountRole'])->andReturn('defaultAsset');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'currency_id'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'accountNumber'])->andReturn('1');
        $repository->shouldReceive('getMetaValue')->withArgs([Mockery::any(), 'BIC'])->andReturn('BIC');
        $repository->shouldReceive('getNoteText')->withArgs([Mockery::any()])->andReturn('Hello');

        $account = $this->user()->accounts()->first();
        // data to submit
        $data = [
            'name'              => $account->name,
            'currency_code'     => 'EUR',
            'type'              => 'asset',
            'active'            => 1,
            'include_net_worth' => 1,
            'account_role'      => 'defaultAsset',
        ];

        // test API
        $response = $this->put('/api/v1/accounts/' . $account->id, $data, ['Accept' => 'application/json']);
        $response->assertStatus(200);
        $response->assertJson(['data' => ['type' => 'accounts', 'links' => true],]);
        $response->assertHeader('Content-Type', 'application/vnd.api+json');
        $response->assertSee($account->name);
    }


}
