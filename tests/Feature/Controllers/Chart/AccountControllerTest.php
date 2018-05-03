<?php
/**
 * AccountControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use Carbon\Carbon;
use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Steam;
use Tests\TestCase;

/**
 * Class AccountControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AccountControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseAccounts
     * @covers       \FireflyIII\Generator\Chart\Basic\GeneratorInterface::singleSet
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseAccounts(string $range)
    {
        $account       = factory(Account::class)->make();
        $generator     = $this->mock(GeneratorInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::EXPENSE, AccountType::BENEFICIARY]])->andReturn(new Collection([$account]));
        $generator->shouldReceive('singleSet')->andReturn([]);
        Steam::shouldReceive('balancesByAccounts')->twice()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseBudget
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::getBudgetNames
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseBudget(string $range)
    {
        $generator   = $this->mock(GeneratorInterface::class);
        $collector   = $this->mock(JournalCollectorInterface::class);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $transaction = factory(Transaction::class)->make();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('pieChart')->andReturn([]);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense-budget', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseBudgetAll
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::getBudgetNames
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseBudgetAll(string $range)
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(JournalCollectorInterface::class);
        $budgetRepos  = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transaction  = factory(Transaction::class)->make();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withBudgetInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('pieChart')->andReturn([]);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection);
        $accountRepos->shouldReceive('oldestJournalDate')->andReturn(Carbon::createFromTimestamp(time())->startOfMonth());

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense-budget-all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseCategory
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::getCategoryNames
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseCategory(string $range)
    {
        $transaction   = factory(Transaction::class)->make();
        $category      = factory(Category::class)->make();
        $generator     = $this->mock(GeneratorInterface::class);
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('pieChart')->andReturn([]);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense-category', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::expenseCategoryAll
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::getCategoryNames
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testExpenseCategoryAll(string $range)
    {
        $transaction   = factory(Transaction::class)->make();
        $category      = factory(Category::class)->make();
        $generator     = $this->mock(GeneratorInterface::class);
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('pieChart')->andReturn([]);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        $accountRepos->shouldReceive('oldestJournalDate')->andReturn(Carbon::createFromTimestamp(time())->startOfMonth());

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.expense-category-all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::frontpage
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::__construct
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::accountBalanceChart
     * @covers       \FireflyIII\Generator\Chart\Basic\GeneratorInterface::multiSet
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testFrontpage(string $range)
    {
        $generator     = $this->mock(GeneratorInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);

        // change the preference:
        Preferences::setForUser($this->user(), 'frontPageAccounts', []);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);
        $accountRepos->shouldReceive('getAccountsById')->andReturn(new Collection);
        Steam::shouldReceive('balanceInRange')->andReturn([]);
        $generator->shouldReceive('multiSet')->andReturn([]);


        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.frontpage'));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::incomeCategory
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIncomeCategory(string $range)
    {
        $transaction   = factory(Transaction::class)->make();
        $account       = factory(Account::class)->make();
        $generator     = $this->mock(GeneratorInterface::class);
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('pieChart')->andReturn([]);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$account]));

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.income-category', [1, '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::incomeCategoryAll
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testIncomeCategoryAll(string $range)
    {
        $transaction   = factory(Transaction::class)->make();
        $account       = factory(Account::class)->make();
        $generator     = $this->mock(GeneratorInterface::class);
        $collector     = $this->mock(JournalCollectorInterface::class);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $accountRepos  = $this->mock(AccountRepositoryInterface::class);

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('withCategoryInformation')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT]])->andReturnSelf();
        $collector->shouldReceive('getJournals')->andReturn(new Collection([$transaction]));
        $generator->shouldReceive('pieChart')->andReturn([]);
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$account]));
        $accountRepos->shouldReceive('oldestJournalDate')->andReturn(Carbon::createFromTimestamp(time())->startOfMonth());

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.income-category-all', [1]));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::period
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testPeriod(string $range)
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('oldestJournalDate')->andReturn(new Carbon);
        Steam::shouldReceive('balanceInRange')->andReturn(['2012-01-01' => '0']);
        $generator->shouldReceive('singleSet')->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.period', [1, '2012-01-01', '2012-01-31']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\AccountController::report
     * @covers \FireflyIII\Http\Controllers\Chart\AccountController::accountBalanceChart
     */
    public function testReport()
    {
        $currencyRepos = $this->mock(CurrencyRepositoryInterface::class);
        $currencyRepos->shouldReceive('findNull')->andReturn(TransactionCurrency::find(1));
        $generator = $this->mock(GeneratorInterface::class);
        $generator->shouldReceive('multiSet')->andReturn([]);
        Steam::shouldReceive('balanceInRange')->andReturn(['2012-01-01' => '0']);

        $this->be($this->user());
        $response = $this->get(route('chart.account.report', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Chart\AccountController::revenueAccounts
     * @dataProvider dateRangeProvider
     *
     * @param string $range
     */
    public function testRevenueAccounts(string $range)
    {
        $account      = factory(Account::class)->make();
        $generator    = $this->mock(GeneratorInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);

        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::REVENUE]])->andReturn(new Collection([$account]));
        $generator->shouldReceive('singleSet')->andReturn([]);
        Steam::shouldReceive('balancesByAccounts')->twice()->andReturn([]);

        $this->be($this->user());
        $this->changeDateRange($this->user(), $range);
        $response = $this->get(route('chart.account.revenue'));
        $response->assertStatus(200);
    }

}
