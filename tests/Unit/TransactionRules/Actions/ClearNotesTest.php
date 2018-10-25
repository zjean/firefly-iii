<?php
/**
 * ClearNotesTest.php
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

use Exception;
use FireflyIII\Models\Note;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Actions\ClearNotes;
use Tests\TestCase;
use Log;


/**
 * Class ClearNotesTest
 */
class ClearNotesTest extends TestCase
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
     * @covers \FireflyIII\TransactionRules\Actions\ClearNotes
     */
    public function testAct(): void
    {
        // give journal a note:
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $note    = $journal->notes()->first();
        if (null === $note) {
            $note = new Note;
            $note->noteable()->associate($journal);
        }
        $note->text = 'Hello test note';
        $note->save();
        $this->assertEquals(1, $journal->notes()->count());

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = null;
        $action                   = new ClearNotes($ruleAction);
        try {
            $result = $action->act($journal);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // assert result
        $this->assertEquals(0, $journal->notes()->count());
    }
}
