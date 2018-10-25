<?php
/**
 * BudgetFactoryTest.php
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


use FireflyIII\Factory\BudgetFactory;
use Log;
use Tests\TestCase;

/**
 * Class BudgetFactoryTest
 */
class BudgetFactoryTest extends TestCase
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
     * Put in ID, return it.
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindById(): void
    {
        $existing = $this->user()->budgets()->first();
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());

        $budget = $factory->find($existing->id, null);
        $this->assertEquals($existing->id, $budget->id);

    }

    /**
     * Put in name, return it.
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindByName(): void
    {
        $existing = $this->user()->budgets()->first();
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());

        $budget = $factory->find(null, $existing->name);
        $this->assertEquals($existing->id, $budget->id);

    }

    /**
     * Put in NULL, will find NULL.
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindNull(): void
    {
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());

        $this->assertNull($factory->find(null, null));

    }

    /**
     * Put in unknown, get NULL
     *
     * @covers \FireflyIII\Factory\BudgetFactory
     */
    public function testFindUnknown(): void
    {
        /** @var BudgetFactory $factory */
        $factory = app(BudgetFactory::class);
        $factory->setUser($this->user());
        $this->assertNull($factory->find(null, 'I dont exist.' . random_int(1, 10000)));
    }

}
