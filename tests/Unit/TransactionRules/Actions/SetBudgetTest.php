<?php
/**
 * SetBudgetTest.php
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

use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\TransactionRules\Actions\SetBudget;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class SetBudgetTest
 */
class SetBudgetTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetBudget
     */
    public function testAct(): void
    {
        // get journal, remove all budgets
        $journal     = TransactionJournal::inRandomOrder()->where('transaction_type_id', 1)->whereNull('deleted_at')->first();
        $budget      = $journal->user->budgets()->first();
        $budgetRepos = $this->mock(BudgetRepositoryInterface::class);
        $budgetRepos->shouldReceive('setUser');
        $budgetRepos->shouldReceive('getActiveBudgets')->andReturn(new Collection([$budget]));

        $journal->budgets()->detach();
        $this->assertEquals(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $budget->name;
        $action                   = new SetBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $this->assertEquals(1, $transaction->budgets()->count());
            $this->assertEquals($budget->name, $transaction->budgets()->first()->name);
        }
    }
}
