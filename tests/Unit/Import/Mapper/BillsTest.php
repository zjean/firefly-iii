<?php
/**
 * BillsTest.php
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

namespace Tests\Unit\Import\Mapper;

use FireflyIII\Import\Mapper\Bills;
use FireflyIII\Models\Account;
use FireflyIII\Models\Bill;
use FireflyIII\Repositories\Bill\BillRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class BillsTest
 */
class BillsTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Mapper\Bills::getMap()
     */
    public function testGetMapBasic()
    {
        $one        = new Bill();
        $one->id    = 5;
        $one->name  = 'Something';
        $one->match = 'hi,bye';
        $two        = new Account;
        $two->id    = 9;
        $two->name  = 'Else';
        $two->match = 'match';
        $collection = new Collection([$one, $two]);

        $repository = $this->mock(BillRepositoryInterface::class);
        $repository->shouldReceive('getBills')->andReturn($collection)->once();

        $mapper  = new Bills();
        $mapping = $mapper->getMap();
        $this->assertCount(3, $mapping);
        // assert this is what the result looks like:
        $result = [
            0 => (string)trans('import.map_do_not_map'),
            9 => 'Else [match]',
            5 => 'Something [hi,bye]',
        ];
        $this->assertEquals($result, $mapping);
    }

}
