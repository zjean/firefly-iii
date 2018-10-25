<?php
/**
 * BasicDataSupport.php
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

namespace FireflyIII\Support\Http\Controllers;

/**
 * Trait BasicDataSupport
 *
 */
trait BasicDataSupport
{

    /**
     * Filters empty results from getBudgetPeriodReport.
     *
     * @param array $data
     *
     * @return array
     */
    protected function filterPeriodReport(array $data): array // helper function for period overview.
    {
        /**
         * @var int $entryId
         * @var array $set
         */
        foreach ($data as $entryId => $set) {
            $sum = '0';
            foreach ($set['entries'] as $amount) {
                $sum = bcadd($amount, $sum);
            }
            $data[$entryId]['sum'] = $sum;
            if (0 === bccomp('0', $sum)) {
                unset($data[$entryId]);
            }
        }

        return $data;
    }
    /**
     * Sum up an array.
     *
     * @param array $array
     *
     * @return string
     */
    protected function arraySum(array $array): string // filter + group data
    {
        $sum = '0';
        foreach ($array as $entry) {
            $sum = bcadd($sum, $entry);
        }

        return $sum;
    }

    /**
     * Find the ID in a given array. Return '0' of not there (amount).
     *
     * @param array $array
     * @param int   $entryId
     *
     * @return null|mixed
     */
    protected function isInArray(array $array, int $entryId) // helper for data (math, calculations)
    {
        $result = '0';
        if (isset($array[$entryId])) {
            $result = $array[$entryId];
        }

        return $result;
    }
}