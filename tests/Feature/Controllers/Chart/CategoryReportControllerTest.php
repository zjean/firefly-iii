<?php
/**
 * CategoryReportControllerTest.php
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

namespace Tests\Feature\Controllers\Chart;

use FireflyIII\Generator\Chart\Basic\GeneratorInterface;
use FireflyIII\Helpers\Chart\MetaPieChartInterface;
use FireflyIII\Helpers\Collector\TransactionCollectorInterface;
use FireflyIII\Helpers\Filter\NegativeAmountFilter;
use FireflyIII\Helpers\Filter\OpposingAccountFilter;
use FireflyIII\Helpers\Filter\PositiveAmountFilter;
use FireflyIII\Helpers\Filter\TransferFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionType;
use Log;
use Tests\TestCase;

/**
 * Class CategoryReportControllerTest
 */
class CategoryReportControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testAccountExpense(): void
    {
        $generator = $this->mock(GeneratorInterface::class);
        $pieChart  = $this->mock(MetaPieChartInterface::class);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCategories')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'account'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.account-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testAccountIncome(): void
    {
        $generator = $this->mock(GeneratorInterface::class);
        $pieChart  = $this->mock(MetaPieChartInterface::class);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCategories')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['income', 'account'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.account-income', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testCategoryExpense(): void
    {
        $generator = $this->mock(GeneratorInterface::class);
        $pieChart  = $this->mock(MetaPieChartInterface::class);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCategories')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['expense', 'category'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.category-expense', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testCategoryIncome(): void
    {
        $generator = $this->mock(GeneratorInterface::class);
        $pieChart  = $this->mock(MetaPieChartInterface::class);

        $pieChart->shouldReceive('setAccounts')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCategories')->once()->andReturnSelf();
        $pieChart->shouldReceive('setStart')->once()->andReturnSelf();
        $pieChart->shouldReceive('setEnd')->once()->andReturnSelf();
        $pieChart->shouldReceive('setCollectOtherObjects')->once()->andReturnSelf()->withArgs([false]);
        $pieChart->shouldReceive('generate')->withArgs(['income', 'category'])->andReturn([])->once();
        $generator->shouldReceive('pieChart')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.category-income', ['1', '1', '20120101', '20120131', 0]));
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Chart\CategoryReportController
     */
    public function testMainChart(): void
    {
        $generator    = $this->mock(GeneratorInterface::class);
        $collector    = $this->mock(TransactionCollectorInterface::class);
        $transactions = factory(Transaction::class, 10)->make();

        $collector->shouldReceive('setAccounts')->andReturnSelf();
        $collector->shouldReceive('setRange')->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::WITHDRAWAL, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('setTypes')->withArgs([[TransactionType::DEPOSIT, TransactionType::TRANSFER]])->andReturnSelf();
        $collector->shouldReceive('removeFilter')->withArgs([TransferFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([OpposingAccountFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([PositiveAmountFilter::class])->andReturnSelf();
        $collector->shouldReceive('addFilter')->withArgs([NegativeAmountFilter::class])->andReturnSelf();
        $collector->shouldReceive('setCategories')->andReturnSelf();
        $collector->shouldReceive('withOpposingAccount')->andReturnSelf();
        $collector->shouldReceive('getTransactions')->andReturn($transactions);
        $generator->shouldReceive('multiSet')->andReturn([])->once();

        $this->be($this->user());
        $response = $this->get(route('chart.category.main', ['1', '1', '20120101', '20120131']));
        $response->assertStatus(200);
    }
}
