<?php
/**
 * INGDebitCredit.php
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

namespace FireflyIII\Import\Converter;

use Log;

/**
 * Class INGDebitCredit.
 */
class INGDebitCredit implements ConverterInterface
{
    /**
     * Convert Af or Bij to correct integer values.
     *
     * @param $value
     *
     * @return int
     */
    public function convert($value): int
    {
        Log::debug('Going to convert ing debit credit', ['value' => $value]);

        if ('Af' === $value) {
            Log::debug('Return -1');

            return -1;
        }

        Log::debug('Return 1');

        return 1;
    }
}
