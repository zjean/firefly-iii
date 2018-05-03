<?php
/**
 * FromAccountEndsTest.php
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
use FireflyIII\TransactionRules\Triggers\FromAccountEnds;
use Tests\TestCase;

/**
 * Class FromAccountEndsTest
 */
class FromAccountEndsTest extends TestCase
{
    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::triggered
     */
    public function testTriggered()
    {
        $count = 0;
        while ($count === 0) {
            $journal     = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count       = $journal->transactions()->where('amount', '<', 0)->count();
            $transaction = $journal->transactions()->where('amount', '<', 0)->first();
        }
        $account     = $transaction->account;

        $trigger = FromAccountEnds::makeFromStrings(substr($account->name, -3), false);
        $result  = $trigger->triggered($journal);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::triggered
     */
    public function testTriggeredLonger()
    {
        $count = 0;
        while ($count === 0) {
            $journal     = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();
            $count       = $journal->transactions()->where('amount', '<', 0)->count();
            $transaction = $journal->transactions()->where('amount', '<', 0)->first();
        }
        $account     = $transaction->account;

        $trigger = FromAccountEnds::makeFromStrings('bla-bla-bla' . $account->name, false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::triggered
     */
    public function testTriggeredNot()
    {
        $journal = TransactionJournal::inRandomOrder()->whereNull('deleted_at')->first();

        $trigger = FromAccountEnds::makeFromStrings('some name' . random_int(1, 234), false);
        $result  = $trigger->triggered($journal);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingEmpty()
    {
        $value  = '';
        $result = FromAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNotNull()
    {
        $value  = 'x';
        $result = FromAccountEnds::willMatchEverything($value);
        $this->assertFalse($result);
    }

    /**
     * @covers \FireflyIII\TransactionRules\Triggers\FromAccountEnds::willMatchEverything
     */
    public function testWillMatchEverythingNull()
    {
        $value  = null;
        $result = FromAccountEnds::willMatchEverything($value);
        $this->assertTrue($result);
    }
}
