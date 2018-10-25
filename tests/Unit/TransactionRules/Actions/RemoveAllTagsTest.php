<?php
/**
 * RemoveAllTagsTest.php
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
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\RemoveAllTags;
use Tests\TestCase;

/**
 * Class RemoveAllTagsTest
 */
class RemoveAllTagsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Actions\RemoveAllTags
     */
    public function testAct(): void
    {
        // find journal with at least one tag
        $journalIds = DB::table('tag_transaction_journal')->get(['transaction_journal_id'])->pluck('transaction_journal_id')->toArray();
        $journalId  = (int)$journalIds[0];
        /** @var TransactionJournal $journal */
        $journal = TransactionJournal::find($journalId);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new RemoveAllTags($ruleAction);
        $result                   = $action->act($journal);
        $this->assertTrue($result);

        $this->assertEquals(0, $journal->tags()->count());
    }
}
