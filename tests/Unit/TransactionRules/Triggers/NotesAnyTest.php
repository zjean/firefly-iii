<?php
/**
 * NotesAnyTest.php
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

namespace Tests\Unit\TransactionRules\Triggers;

use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\NotesAny;
use Tests\TestCase;

/**
 * Class NotesAnyTest
 */
class NotesAnyTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny
     */
    public function testTriggered(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = 'Bla bla bla';
        $note->save();
        $trigger = NotesAny::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny
     */
    public function testTriggeredEmpty(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $journal->notes()->delete();
        $note = new Note();
        $note->noteable()->associate($journal);
        $note->text = '';
        $note->save();
        $trigger = NotesAny::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny
     */
    public function testTriggeredNone(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $journal->notes()->delete();
        $trigger = NotesAny::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\NotesAny
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = NotesAny::willMatchEverything($value);
        $this->assertFalse($result);
    }
}
