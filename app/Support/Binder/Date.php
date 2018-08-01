<?php
/**
 * Date.php
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

namespace FireflyIII\Support\Binder;

use Carbon\Carbon;
use Exception;
use FireflyIII\Helpers\FiscalHelperInterface;
use Illuminate\Routing\Route;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Date.
 */
class Date implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return Carbon
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): Carbon
    {
        /** @var FiscalHelperInterface $fiscalHelper */
        $fiscalHelper = app(FiscalHelperInterface::class);

        $magicWords = [
            'currentMonthStart'      => Carbon::now()->startOfMonth(),
            'currentMonthEnd'        => Carbon::now()->endOfMonth(),
            'currentYearStart'       => Carbon::now()->startOfYear(),
            'currentYearEnd'         => Carbon::now()->endOfYear(),
            'currentFiscalYearStart' => $fiscalHelper->startOfFiscalYear(Carbon::now()),
            'currentFiscalYearEnd'   => $fiscalHelper->endOfFiscalYear(Carbon::now()),
        ];
        if (isset($magicWords[$value])) {
            return $magicWords[$value];
        }

        try {
            $result = new Carbon($value);
        } catch (Exception $e) {
            Log::error(sprintf('Could not parse date "%s" for user #%d: %s', $value, auth()->user()->id, $e->getMessage()));
            throw new NotFoundHttpException;
        }

        return $result;
    }
}
