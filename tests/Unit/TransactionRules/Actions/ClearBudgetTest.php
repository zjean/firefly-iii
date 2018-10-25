<?php
/**
 * ClearBudgetTest.php
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
use FireflyIII\TransactionRules\Actions\ClearBudget;
use Tests\TestCase;
use Log;

/**
 * Class ClearBudgetTest
 */
class ClearBudgetTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Actions\ClearBudget
     */
    public function testAct(): void
    {
        // associate budget with journal:
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $budget  = $journal->user->budgets()->first();
        $journal->budgets()->save($budget);
        $this->assertGreaterThan(0, $journal->budgets()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new ClearBudget($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        // assert result
        $this->assertEquals(0, $journal->budgets()->count());

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $this->assertEquals(0, $transaction->budgets()->count());
        }


    }
}
