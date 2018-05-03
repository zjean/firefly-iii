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

namespace Tests\Feature\Controllers;

use FireflyIII\Generator\Report\Account\YearReportGenerator as AcYRG;
use FireflyIII\Generator\Report\Audit\YearReportGenerator as AYRG;
use FireflyIII\Generator\Report\Budget\YearReportGenerator as BYRG;
use FireflyIII\Generator\Report\Category\YearReportGenerator as CYRG;
use FireflyIII\Generator\Report\Standard\YearReportGenerator as SYRG;
use FireflyIII\Generator\Report\Tag\YearReportGenerator as TYRG;
use FireflyIII\Helpers\Report\ReportHelperInterface;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Budget;
use FireflyIII\Models\Category;
use FireflyIII\Models\Tag;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
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
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::accountReport()
     */
    public function testAccountReport()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator    = $this->mock(AcYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);


        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setExpense')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.account', [1, 2, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::auditReport
     */
    public function testAuditReport()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator    = $this->mock(AYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.audit', [1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::budgetReport
     */
    public function testBudgetReport()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator    = $this->mock(BYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setBudgets')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.budget', [1, 1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::categoryReport
     */
    public function testCategoryReport()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator    = $this->mock(CYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setCategories')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.category', [1, 1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::defaultReport
     */
    public function testDefaultReport()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $generator    = $this->mock(SYRG::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20161231']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::defaultReport
     */
    public function testDefaultReportBadDate()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('reports.report.default', [1, '20160101', '20150131']));
        $response->assertStatus(200);
        $response->assertSee('End date of report must be after start date.');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::index
     * @covers \FireflyIII\Http\Controllers\ReportController::__construct
     */
    public function testIndex()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepository->shouldReceive('cleanupBudgets');

        $helper       = $this->mock(ReportHelperInterface::class);
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $helper->shouldReceive('listOfMonths')->andReturn([]);
        $accountRepos->shouldReceive('getAccountsByType')->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::noReportOptions
     */
    public function testOptions()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['default']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::accountReportOptions()
     */
    public function testOptionsAccount()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);

        $account       = new Account();
        $account->name = 'Something';
        $account->id   = 3;
        $collection    = new Collection([$account]);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::EXPENSE]])->once()->andReturn($collection);
        $repository->shouldReceive('getActiveAccountsByType')->withArgs([[AccountType::REVENUE]])->once()->andReturn($collection);

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['account']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::budgetReportOptions
     */
    public function testOptionsBudget()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budget      = factory(Budget::class)->make();
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection([$budget]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['budget']));
        $response->assertStatus(200);
        $response->assertSee($budget->name);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::categoryReportOptions
     */
    public function testOptionsCategory()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos = $this->mock(CategoryRepositoryInterface::class);
        $category      = factory(Category::class)->make();
        $categoryRepos->shouldReceive('getCategories')->andReturn(new Collection([$category]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['category']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::options
     * @covers \FireflyIII\Http\Controllers\ReportController::tagReportOptions
     */
    public function testOptionsTag()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);

        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $tag      = factory(Tag::class)->make();
        $tagRepos = $this->mock(TagRepositoryInterface::class);
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $this->be($this->user());
        $response = $this->get(route('reports.options', ['tag']));
        $response->assertStatus(200);
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexAccountOK()
    {
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->times(3);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [
            'accounts'    => ['1'],
            'exp_rev'     => ['4'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'account',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.account', ['1', '1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexAuditOK()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'audit',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.audit', ['1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexBudgetError()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'budget'      => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'budget',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexBudgetOK()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();
        $budgetRepository->shouldReceive('findNull')->andReturn($this->user()->budgets()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'budget'      => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'budget',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.budget', ['1', '1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexCategoryError()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);

        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'category'    => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'category',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexCategoryOK()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $categoryRepos->shouldReceive('findNull')->andReturn($this->user()->categories()->find(1))->twice();
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'category'    => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'category',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.category', ['1', '1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexDefaultOK()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'default',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.default', ['1', '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexDefaultStartEnd()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'daterange'   => '2016-01-01 - 2015-01-31',
            'report_type' => 'default',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(200);
        $response->assertSee('End date of report must be after start date.');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexTagError()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();

        $data = [
            'accounts'    => ['1'],
            'tag'         => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'tag',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexTagOK()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $tag              = $this->user()->tags()->find(1);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $accountRepos->shouldReceive('findNull')->andReturn($this->user()->accounts()->find(1))->twice();
        $tagRepos->shouldReceive('findByTag')->andReturn($tag)->twice();

        $data = [
            'accounts'    => ['1'],
            'tag'         => ['housing'],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'tag',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.report.tag', ['1', $tag->tag, '20160101', '20160131']));
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\ReportController::postIndex
     * @covers       \FireflyIII\Http\Requests\ReportFormRequest
     */
    public function testPostIndexZeroAccounts()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $data = [
            'accounts'    => [],
            'daterange'   => '2016-01-01 - 2016-01-31',
            'report_type' => 'default',
        ];

        $this->be($this->user());
        $response = $this->post(route('reports.index.post'), $data);
        $response->assertStatus(302);
        $response->assertRedirect(route('reports.index'));
        $response->assertSessionHas('error');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\ReportController::tagReport
     */
    public function testTagReport()
    {
        $accountRepos     = $this->mock(AccountRepositoryInterface::class);
        $budgetRepository = $this->mock(BudgetRepositoryInterface::class);
        $journalRepos     = $this->mock(JournalRepositoryInterface::class);
        $categoryRepos    = $this->mock(CategoryRepositoryInterface::class);
        $tagRepos         = $this->mock(TagRepositoryInterface::class);
        $generator        = $this->mock(TYRG::class);
        $tag              = $this->user()->tags()->find(1);

        $tagRepos->shouldReceive('setUser');
        $tagRepos->shouldReceive('get')->andReturn(new Collection([$tag]));

        $budgetRepository->shouldReceive('cleanupBudgets');
        $journalRepos->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $generator->shouldReceive('setStartDate')->once();
        $generator->shouldReceive('setEndDate')->once();
        $generator->shouldReceive('setAccounts')->once();
        $generator->shouldReceive('setTags')->once();
        $generator->shouldReceive('generate')->once()->andReturn('here-be-report');

        $this->be($this->user());
        $response = $this->get(route('reports.report.tag', [1, $tag->tag, '20160101', '20161231']));
        $response->assertStatus(200);
    }
}
