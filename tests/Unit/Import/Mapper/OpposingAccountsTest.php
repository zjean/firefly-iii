<?php
/**
 * OpposingAccountsTest.php
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

use FireflyIII\Import\Mapper\OpposingAccounts;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Class OpposingAccountsTest
 */
class OpposingAccountsTest extends TestCase
{
    /**
     * @covers \FireflyIII\Import\Mapper\OpposingAccounts::getMap()
     */
    public function testGetMapBasic()
    {
        $one        = new Account;
        $one->id    = 13;
        $one->name  = 'Something';
        $one->iban  = 'IBAN';
        $two        = new Account;
        $two->id    = 9;
        $two->name  = 'Else';
        $collection = new Collection([$one, $two]);

        $repository = $this->mock(AccountRepositoryInterface::class);
        $repository->shouldReceive('getAccountsByType')->withArgs(
            [[AccountType::DEFAULT, AccountType::ASSET, AccountType::EXPENSE, AccountType::BENEFICIARY, AccountType::REVENUE,]]
        )->andReturn($collection)->once();

        $mapper  = new OpposingAccounts();
        $mapping = $mapper->getMap();
        $this->assertCount(3, $mapping);
        // assert this is what the result looks like:
        $result = [
            0  => (string)trans('import.map_do_not_map'),
            9  => 'Else',
            13 => 'Something (IBAN)',
        ];
        $this->assertEquals($result, $mapping);
    }

}
