<?php
/**
 * Navigation.php
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

namespace FireflyIII\Support\Facades;

use Carbon\Carbon;
use Illuminate\Support\Facades\Facade;

/**
 * @codeCoverageIgnore
 * Class Navigation.
 *
 * @method Carbon addPeriod(Carbon $theDate, string $repeatFreq, int $skip)
 * @method array blockPeriods(Carbon $start, Carbon $end, string $range)
 * @method Carbon endOfPeriod(Carbon $end, string $repeatFreq)
 * @method Carbon endOfX(Carbon $theCurrentEnd, string $repeatFreq, Carbon $maxDate = null)
 * @method array listOfPeriods(Carbon $start, Carbon $end)
 * @method string periodShow(Carbon $theDate, string $repeatFrequency)
 * @method string preferredCarbonFormat(Carbon $start, Carbon $end)
 * @method string preferredCarbonLocalizedFormat(Carbon $start, Carbon $end)
 * @method string preferredEndOfPeriod(Carbon $start, Carbon $end)
 * @method string preferredRangeFormat(Carbon $start, Carbon $end)
 * @method string preferredSqlFormat(Carbon $start, Carbon $end)
 * @method Carbon startOfPeriod(Carbon $theDate, string $repeatFreq)
 * @method Carbon subtractPeriod(Carbon $theDate, string $repeatFreq, int $subtract = 1)
 * @method Carbon updateEndDate(string $range, Carbon $start)
 * @method Carbon updateStartDate(string $range, Carbon $start)
 */
class Navigation extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'navigation';
    }
}
