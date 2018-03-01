<?php
/**
 * SingleControllerTest.php
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

namespace Tests\Feature\Controllers\Transaction;

use DB;
use FireflyIII\Events\StoredTransactionJournal;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Steam;
use Tests\TestCase;

/**
 * Class SingleControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SingleControllerTest extends TestCase
{
    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::cloneTransaction
     */
    public function testCloneTransaction()
    {
        $note       = new Note();
        $note->id   = 5;
        $note->text = 'I see you...';
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('getNote')->andReturn($note)->once();
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.clone', [$withdrawal->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::create
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::__construct
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedActiveAccountList
     */
    public function testCreate()
    {
        $accounts = $this->user()->accounts()->where('account_type_id', 3)->get();
        Steam::shouldReceive('phpBytes')->andReturn(2048);
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getActiveAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn($accounts);
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection)->once();
        $piggyRepos = $this->mock(PiggyBankRepositoryInterface::class);
        $piggyRepos->shouldReceive('getPiggyBanksWithAmount')->andReturn(new Collection)->once();

        $this->be($this->user());
        $response = $this->get(route('transactions.create', ['withdrawal']));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::delete
     */
    public function testDelete()
    {
        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.delete', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::destroy
     */
    public function testDestroy()
    {
        // mock
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('destroy')->once();

        $this->session(['transactions.delete.uri' => 'http://localhost']);
        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->post(route('transactions.destroy', [$withdrawal->id]));
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::isSplitJournal
     */
    public function testEdit()
    {
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $note       = new Note();
        $note->id   = 5;
        $note->text = 'I see you...';
        $repository = $this->mock(JournalRepositoryInterface::class);
        $repository->shouldReceive('getNote')->andReturn($note)->once();
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('countTransactions')->andReturn(2);

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)->whereNull('deleted_at')->where('user_id', $this->user()->id)->first();
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     */
    public function testEditCashDeposit()
    {
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $this->be($this->user());
        $withdrawal = Transaction::leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                 ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                 ->where('accounts.account_type_id', 2)
                                 ->where('transaction_journals.transaction_type_id', 2)
                                 ->whereNull('transaction_journals.deleted_at')
                                 ->where('transaction_journals.user_id', $this->user()->id)->first(['transactions.transaction_journal_id']);
        $response   = $this->get(route('transactions.edit', [$withdrawal->transaction_journal_id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee(' name="source_account_name" type="text" value="">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     */
    public function testEditCashWithdrawal()
    {
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $this->be($this->user());
        $withdrawal = Transaction::leftJoin('accounts', 'transactions.account_id', '=', 'accounts.id')
                                 ->leftJoin('transaction_journals', 'transaction_journals.id', '=', 'transactions.transaction_journal_id')
                                 ->where('accounts.account_type_id', 2)
                                 ->where('transaction_journals.transaction_type_id', 1)
                                 ->whereNull('transaction_journals.deleted_at')
                                 ->where('transaction_journals.user_id', $this->user()->id)->first(['transactions.transaction_journal_id']);
        $response   = $this->get(route('transactions.edit', [$withdrawal->transaction_journal_id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
        $response->assertSee(' name="destination_account_name" type="text" value="">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     */
    public function testEditReconcile()
    {
        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 5)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                        ->groupBy('transaction_journals.id')
                                        ->orderBy('ct', 'DESC')
                                        ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')]);
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     */
    public function testEditRedirect()
    {
        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                        ->groupBy('transaction_journals.id')
                                        ->orderBy('ct', 'DESC')
                                        ->where('user_id', $this->user()->id)->first(['transaction_journals.id', DB::raw('count(transactions.`id`) as ct')]);
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(302);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     */
    public function testEditTransferWithForeignAmount()
    {
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 3)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin(
                                            'transactions', function (JoinClause $join) {
                                            $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('amount', '<', 0);
                                        }
                                        )
                                        ->where('user_id', $this->user()->id)
                                        ->whereNotNull('transactions.foreign_amount')
                                        ->first(['transaction_journals.*']);
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::edit
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::groupedAccountList
     */
    public function testEditWithForeignAmount()
    {
        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->once()->withArgs([[AccountType::DEFAULT, AccountType::ASSET]])->andReturn(new Collection);

        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('getBudgets')->andReturn(new Collection)->once();

        $this->be($this->user());
        $withdrawal = TransactionJournal::where('transaction_type_id', 1)
                                        ->whereNull('transaction_journals.deleted_at')
                                        ->leftJoin(
                                            'transactions', function (JoinClause $join) {
                                            $join->on('transactions.transaction_journal_id', '=', 'transaction_journals.id')->where('amount', '<', 0);
                                        }
                                        )
                                        ->where('user_id', $this->user()->id)
                                        ->whereNotNull('transactions.foreign_amount')
                                        ->first(['transaction_journals.*']);
        $response   = $this->get(route('transactions.edit', [$withdrawal->id]));
        $response->assertStatus(200);
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::store
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::groupedActiveAccountList
     */
    public function testStoreError()
    {
        // mock results:
        $repository           = $this->mock(JournalRepositoryInterface::class);
        $journal              = new TransactionJournal();
        $journal->description = 'New journal';
        $repository->shouldReceive('store')->andReturn($journal);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'withdrawal',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'source_account_id'         => 1,
            'destination_account_name'  => 'Some destination',
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['withdrawal']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::store
     * @covers       \FireflyIII\Http\Controllers\Transaction\SingleController::groupedActiveAccountList
     * @throws \Exception
     */
    public function testStoreSuccess()
    {
        // mock results:
        $repository           = $this->mock(JournalRepositoryInterface::class);
        $journal              = new TransactionJournal();
        $journal->id          = 1000;
        $journal->description = 'New journal';
        $repository->shouldReceive('store')->andReturn($journal);
        $repository->shouldReceive('first')->once()->andReturn(new TransactionJournal);
        $this->expectsEvents(StoredTransactionJournal::class);

        $errors = new MessageBag;
        $errors->add('attachments', 'Fake error');

        $messages = new MessageBag;
        $messages->add('attachments', 'Fake error');

        // mock attachment helper, trigger an error AND and info thing.
        $attachmentRepo = $this->mock(AttachmentHelperInterface::class);
        $attachmentRepo->shouldReceive('saveAttachmentsForModel');
        $attachmentRepo->shouldReceive('getErrors')->andReturn($errors);
        $attachmentRepo->shouldReceive('getMessages')->andReturn($messages);

        $this->session(['transactions.create.uri' => 'http://localhost']);
        $this->be($this->user());

        $data     = [
            'what'                      => 'withdrawal',
            'amount'                    => '10',
            'amount_currency_id_amount' => 1,
            'source_account_id'         => 1,
            'destination_account_name'  => 'Some destination',
            'date'                      => '2016-01-01',
            'description'               => 'Test descr',
        ];
        $response = $this->post(route('transactions.store', ['withdrawal']), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
        $response->assertSessionHas('error');
        $response->assertSessionHas('info');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Transaction\SingleController::update
     * @throws \Exception
     */
    public function testUpdate()
    {
        // mock
        $this->expectsEvents(UpdatedTransactionJournal::class);

        $repository = $this->mock(JournalRepositoryInterface::class);
        $journal    = new TransactionJournal();

        $type                 = TransactionType::find(1);
        $journal->id          = 1000;
        $journal->description = 'New journal';
        $journal->transactionType()->associate($type);

        $repository->shouldReceive('update')->andReturn($journal);
        $repository->shouldReceive('first')->times(2)->andReturn(new TransactionJournal);

        $this->session(['transactions.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $data = [
            'id'                        => 123,
            'what'                      => 'withdrawal',
            'description'               => 'Updated groceries',
            'source_account_id'         => 1,
            'destination_account_name'  => 'PLUS',
            'amount'                    => '123',
            'amount_currency_id_amount' => 1,
            'budget_id'                 => 1,
            'category'                  => 'Daily groceries',
            'tags'                      => '',
            'date'                      => '2016-01-01',
        ];

        $response = $this->post(route('transactions.update', [123]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');

        $response = $this->get(route('transactions.show', [123]));
        $response->assertStatus(200);
        $response->assertSee('Updated groceries');
        // has bread crumb
        $response->assertSee('<ol class="breadcrumb">');
    }
}
