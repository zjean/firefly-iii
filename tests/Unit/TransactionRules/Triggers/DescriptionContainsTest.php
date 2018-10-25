<?php
/**
 * DescriptionContainsTest.php
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
use FireflyIII\TransactionRules\Triggers\DescriptionContains;
use Tests\TestCase;

/**
 * Class DescriptionContains
 */
class DescriptionContainsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testTriggeredCase(): void
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUM bla bla ';
        $trigger              = DescriptionContains::makeFromStrings('ipsum', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testTriggeredDefault(): void
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Should contain test string';
        $trigger              = DescriptionContains::makeFromStrings('cont', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testTriggeredEnd(): void
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something is going to happen';
        $trigger              = DescriptionContains::makeFromStrings('pen', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testTriggeredNot(): void
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Lorem IPSUM bla bla ';
        $trigger              = DescriptionContains::makeFromStrings('blurb', false);
        $result               = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testTriggeredStart(): void
    {
        $journal              = new TransactionJournal;
        $journal->description = 'Something is going to happen';
        $trigger              = DescriptionContains::makeFromStrings('somet', false);
        $result               = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testWillMatchEverythingEmpty(): void
    {
        $value  = '';
        $result = DescriptionContains::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testWillMatchEverythingNotNull(): void
    {
        $value  = 'x';
        $result = DescriptionContains::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\DescriptionContains
     */
    public function testWillMatchEverythingNull(): void
    {
        $value  = null;
        $result = DescriptionContains::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
