<?php
/**
 * SetCategoryTest.php
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
use FireflyIII\TransactionRules\Actions\SetCategory;
use Tests\TestCase;

/**
 * Class SetCategoryTest
 */
class SetCategoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\SetCategory
     */
    public function testAct(): void
    {
        // get journal, remove all budgets
        $journal  = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $category = $journal->user->categories()->first();
        $journal->categories()->detach();
        $this->assertEquals(0, $journal->categories()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $category->name;
        $action                   = new SetCategory($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $this->assertEquals(1, $transaction->categories()->count());
            $this->assertEquals($category->name, $transaction->categories()->first()->name);
        }


    }
}
