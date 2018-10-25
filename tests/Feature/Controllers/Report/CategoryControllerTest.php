<?php
/**
 * CategoryControllerTest.php
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

namespace Tests\Feature\Controllers\Report;

use FireflyIII\Models\Category;
use FireflyIII\Repositories\Category\CategoryRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class CategoryControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CategoryControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Report\CategoryController
     */
    public function testExpenses(): void
    {
        $first      = [1 => ['entries' => ['1', '1']]];
        $second     = ['entries' => ['1', '1']];
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('getCategories')->andReturn(new Collection);
        $repository->shouldReceive('periodExpenses')->andReturn($first);
        $repository->shouldReceive('periodExpensesNoCategory')->andReturn($second);

        $this->be($this->user());
        $response = $this->get(route('report-data.category.expenses', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\CategoryController
     */
    public function testIncome(): void
    {
        $first      = [1 => ['entries' => ['1', '1']]];
        $second     = ['entries' => ['1', '1']];
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('getCategories')->andReturn(new Collection);
        $repository->shouldReceive('periodIncome')->andReturn($first);
        $repository->shouldReceive('periodIncomeNoCategory')->andReturn($second);

        $this->be($this->user());
        $response = $this->get(route('report-data.category.income', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Report\CategoryController
     */
    public function testOperations(): void
    {
        $repository = $this->mock(CategoryRepositoryInterface::class);
        $category   = factory(Category::class)->make();
        $repository->shouldReceive('getCategories')->andReturn(new Collection([$category]));
        $repository->shouldReceive('spentInPeriod')->andReturn('-1');
        $repository->shouldReceive('earnedInPeriod')->andReturn('1');


        $this->be($this->user());
        $response = $this->get(route('report-data.category.operations', ['1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
