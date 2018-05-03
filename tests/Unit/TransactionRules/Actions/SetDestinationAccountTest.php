<?php
/**
 * SetDestinationAccountTest.php
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

namespace Tests\Unit\TransactionRules\Actions;

use DB;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\TransactionRules\Actions\SetDestinationAccount;
use Tests\TestCase;

/**
 * Try split journal
 *
 * Class SetDestinationAccountTest
 */
class SetDestinationAccountTest extends TestCase
{
    /**
     * Give deposit existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActDepositExisting()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::DEPOSIT)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        $destinationTr = $journal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;
        $user          = $journal->user;
        $accountType   = AccountType::whereType(AccountType::ASSET)->first();
        $account       = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $account->id);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal        = TransactionJournal::find($journal->id);
        $destinationTr  = $journal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
        $this->assertEquals($newDestination->id, $account->id);
    }

    /**
     * Give deposit not existing asset account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActDepositNotExisting()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::DEPOSIT)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Not existing asset account #' . random_int(1, 1000);
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }

    /**
     * Give withdrawal not existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActWithDrawalNotExisting()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();
        $account      = $this->user()->accounts()->inRandomOrder()->where('account_type_id', 4)->first();


        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn(null);
        $accountRepos->shouldReceive('store')->once()->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Not existing expense account #' . random_int(1, 1000);
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);

        $this->assertTrue($result);
    }

    /**
     * Give withdrawal existing expense account.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testActWithdrawalExisting()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $type         = TransactionType::whereType(TransactionType::WITHDRAWAL)->first();

        do {
            /** @var TransactionJournal $journal */
            $journal = $this->user()->transactionJournals()->where('transaction_type_id', $type->id)->inRandomOrder()->first();
            $count   = $journal->transactions()->count();
        } while ($count !== 2);


        $destinationTr = $journal->transactions()->where('amount', '>', 0)->first();
        $destination   = $destinationTr->account;
        $user          = $journal->user;
        $accountType   = AccountType::whereType(AccountType::EXPENSE)->first();
        $account       = $user->accounts()->where('account_type_id', $accountType->id)->where('id', '!=', $destination->id)->first();
        $this->assertNotEquals($destination->id, $account->id);

        // find account? Return account:
        $accountRepos->shouldReceive('setUser');
        $accountRepos->shouldReceive('findByName')->andReturn($account);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $account->name;
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // test journal for new account
        $journal        = TransactionJournal::find($journal->id);
        $destinationTr  = $journal->transactions()->where('amount', '>', 0)->first();
        $newDestination = $destinationTr->account;
        $this->assertNotEquals($destination->id, $newDestination->id);
        $this->assertEquals($newDestination->id, $account->id);
    }

    /**
     * Test this on a split journal.
     *
     * @covers \FireflyIII\TransactionRules\Actions\SetDestinationAccount
     */
    public function testSplitJournal()
    {
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $transaction  = Transaction::orderBy('count', 'DESC')->groupBy('transaction_journal_id')
                                   ->get(['transaction_journal_id', DB::raw('COUNT(transaction_journal_id) as count')])
                                   ->first();
        $journal      = TransactionJournal::find($transaction->transaction_journal_id);

        // mock
        $accountRepos->shouldReceive('setUser');

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = 'Some new asset ' . random_int(1, 1000);
        $action                   = new SetDestinationAccount($ruleAction);
        $result                   = $action->act($journal);
        $this->assertFalse($result);
    }
}
