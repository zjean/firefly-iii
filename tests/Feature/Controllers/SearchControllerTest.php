<?php
/**
 * SearchControllerTest.php
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

namespace Tests\Feature\Controllers;

use FireflyIII\Support\Search\SearchInterface;
use Illuminate\Support\Collection;
use Log;
use Tests\TestCase;

/**
 * Class SearchControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SearchControllerTest extends TestCase
{
    /**
     *
     */
    public function setUp()
    {
        parent::setUp();
        Log::debug(sprintf('Now in %s.', get_class($this)));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\SearchController::index
     * @covers \FireflyIII\Http\Controllers\SearchController::__construct
     */
    public function testIndex()
    {
        $search = $this->mock(SearchInterface::class);
        $search->shouldReceive('parseQuery')->once();
        $search->shouldReceive('getWordsAsString')->once()->andReturn('test');
        $this->be($this->user());
        $response = $this->get(route('search.index') . '?q=test');
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\SearchController::search
     * @covers \FireflyIII\Http\Controllers\SearchController::__construct
     */
    public function testSearch()
    {
        $search = $this->mock(SearchInterface::class);
        $search->shouldReceive('parseQuery')->once();
        $search->shouldReceive('setLimit')->withArgs([50])->once();
        $search->shouldReceive('searchTransactions')->once()->andReturn(new Collection);

        $this->be($this->user());

        $response = $this->get(route('search.search') . '?query=test');
        $response->assertStatus(200);
    }
}
