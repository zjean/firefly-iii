<?php
/**
 * HasAnyTagTest.php
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

use FireflyIII\Models\TransactionJournal;
use FireflyIII\TransactionRules\Triggers\HasAnyTag;
use Tests\TestCase;

/**
 * Class HasAnyTagTest
 */
class HasAnyTagTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyTag
     */
    public function testTriggered(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $tag     = $journal->user->tags()->first();
        $journal->tags()->detach();
        $journal->tags()->save($tag);

        $this->assertEquals(1, $journal->tags()->count());
        $trigger = HasAnyTag::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyTag
     */
    public function testTriggeredNot(): void
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
        $journal->tags()->detach();
        $this->assertEquals(0, $journal->tags()->count());
        $trigger = HasAnyTag::makeFromStrings('', false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\HasAnyTag
     */
    public function testWillMatchEverything(): void
    {
        $value  = '';
        $result = HasAnyTag::willMatchEverything($value);
        $this->assertFalse($result);
    }
}
