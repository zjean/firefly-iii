<?php
/**
 * ReportControllerTest.php
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

namespace Tests\Feature\Controllers\Popup;

use Carbon\Carbon;
use FireflyIII\Helpers\Report\PopupReportInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class ReportControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReportControllerTest extends TestCase
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
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBadEndDate(): void
    {
        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'bla-bla',
                'startDate'  => Carbon::now()->endOfMonth()->format('Ymd'),
                'endDate'    => 'bla-bla',
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController
     * @expectedExceptionMessage Could not parse start date
     */
    public function testBadStartDate(): void
    {
        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'bla-bla',
                'startDate'  => 'bla-bla',
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBalanceAmountDefaultNoBudget(): void
    {
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $account       = factory(Account::class)->make();

        $popupHelper->shouldReceive('balanceForNoBudget')->andReturn(new Collection);
        $budgetRepos->shouldReceive('findNull')->andReturn(new Budget)->once()->withArgs([0]);
        $accountRepos->shouldReceive('findNull')->andReturn($account)->once()->withArgs([1]);
        $popupHelper->shouldReceive('balanceForBudget')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'balance-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 0,
                'role'       => 1, // ROLE_DEFAULTROLE
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBalanceAmountDefaultRole(): void
    {
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $budget        = factory(Budget::class)->make();
        $account       = factory(Account::class)->make();

        $budgetRepos->shouldReceive('findNull')->andReturn($budget)->once()->withArgs([1]);
        $accountRepos->shouldReceive('findNull')->andReturn($account)->once()->withArgs([1]);
        $popupHelper->shouldReceive('balanceForBudget')->once()->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'balance-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
                'role'       => 1, // ROLE_DEFAULTROLE
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBalanceAmountTagRole(): void
    {
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $budget        = factory(Budget::class)->make();
        $account       = factory(Account::class)->make();

        $budgetRepos->shouldReceive('findNull')->andReturn($budget)->once()->withArgs([1]);
        $accountRepos->shouldReceive('findNull')->andReturn($account)->once()->withArgs([1]);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'balance-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
                'role'       => 2, // ROLE_TAGROLE
            ],
        ];

        $uri      = route('popup.general') . '?' . http_build_query($arguments);
        $response = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testBudgetSpentAmount(): void
    {
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $budget        = factory(Budget::class)->make();

        $budgetRepos->shouldReceive('findNull')->andReturn($budget)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byBudget')->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'budget-spent-amount',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testCategoryEntry(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $category      = factory(Category::class)->make();

        $categoryRepos->shouldReceive('findNull')->andReturn($category)->once()->withArgs([1]);
        $popupHelper->shouldReceive('byCategory')->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'category-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testExpenseEntry(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $account       = factory(Account::class)->make();

        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn($account)->once();
        $popupHelper->shouldReceive('byExpenses')->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'expense-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Popup\ReportController
     */
    public function testIncomeEntry(): void
    {
        $budgetRepos   = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $popupHelper   = $this->mock(PopupReportInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $account       = factory(Account::class)->make();

        $accountRepos->shouldReceive('findNull')->withArgs([1])->andReturn($account)->once();
        $popupHelper->shouldReceive('byIncome')->andReturn(new Collection);

        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'income-entry',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }

    /**
     * @covers                   \FireflyIII\Http\Controllers\Popup\ReportController
     * @expectedExceptionMessage Firefly cannot handle
     */
    public function testWrongLocation(): void
    {
        $this->be($this->user());
        $arguments = [
            'attributes' => [
                'location'   => 'bla-bla',
                'startDate'  => Carbon::now()->startOfMonth()->format('Ymd'),
                'endDate'    => Carbon::now()->endOfMonth()->format('Ymd'),
                'accounts'   => 1,
                'accountId'  => 1,
                'categoryId' => 1,
                'budgetId'   => 1,
            ],
        ];
        $uri       = route('popup.general') . '?' . http_build_query($arguments);
        $response  = $this->get($uri);
        $response->assertStatus(200);
    }
}
