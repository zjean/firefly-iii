<?php
/**
 * PiggyBankEventFactoryTest.php
 * Copyright (c) 2018 thegrumpydictator@gmail.com
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

namespace Tests\Unit\Factory;


use FireflyIII\Factory\PiggyBankEventFactory;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\PiggyBank\PiggyBankRepositoryInterface;
use Tests\TestCase;

/**
 * Class PiggyBankEventFactoryTest
 */
class PiggyBankEventFactoryTest extends TestCase
{
    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateAmountZero()
    {
        /** @var TransactionJournal $transfer */
        $transfer   = $this->user()->transactionJournals()->where('transaction_type_id', 3)->first();
        $piggy      = $this->user()->piggyBanks()->first();
        $repetition = PiggyBankRepetition::first();
        $repos      = $this->mock(PiggyBankRepositoryInterface::class);
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        // mock:
        $repos->shouldReceive('setUser');
        $repos->shouldReceive('getRepetition')->andReturn($repetition);
        $repos->shouldReceive('getExactAmount')->andReturn('0');

        $this->assertNull($factory->create($transfer, $piggy));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateNoPiggy()
    {
        /** @var TransactionJournal $transfer */
        $transfer = $this->user()->transactionJournals()->where('transaction_type_id', 3)->first();

        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        $this->assertNull($factory->create($transfer, null));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateNoRep()
    {
        /** @var TransactionJournal $transfer */
        $transfer   = $this->user()->transactionJournals()->where('transaction_type_id', 3)->first();
        $piggy      = $this->user()->piggyBanks()->first();
        $repetition = new PiggyBankRepetition;
        $repos      = $this->mock(PiggyBankRepositoryInterface::class);
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        // mock:
        $repos->shouldReceive('setUser');
        $repos->shouldReceive('getRepetition')->andReturn($repetition);
        $repos->shouldReceive('getExactAmount')->andReturn('0');

        $this->assertNull($factory->create($transfer, $piggy));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateNotTransfer()
    {
        /** @var TransactionJournal $deposit */
        $deposit = $this->user()->transactionJournals()->where('transaction_type_id', 2)->first();
        $piggy   = $this->user()->piggyBanks()->first();
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        $this->assertNull($factory->create($deposit, $piggy));
    }

    /**
     * @covers \FireflyIII\Factory\PiggyBankEventFactory
     */
    public function testCreateSuccess()
    {
        /** @var TransactionJournal $transfer */
        $transfer   = $this->user()->transactionJournals()->where('transaction_type_id', 3)->first();
        $piggy      = $this->user()->piggyBanks()->first();
        $repetition = PiggyBankRepetition::first();
        $event      = PiggyBankEvent::first();
        $repos      = $this->mock(PiggyBankRepositoryInterface::class);
        /** @var PiggyBankEventFactory $factory */
        $factory = app(PiggyBankEventFactory::class);

        // mock:
        $repos->shouldReceive('setUser');
        $repos->shouldReceive('getRepetition')->andReturn($repetition);
        $repos->shouldReceive('getExactAmount')->andReturn('5');
        $repos->shouldReceive('addAmountToRepetition')->once();
        $repos->shouldReceive('createEventWithJournal')->once()->andReturn($event);

        $result = $factory->create($transfer, $piggy);
        $this->assertEquals($result->id, $event->id);

    }

}
