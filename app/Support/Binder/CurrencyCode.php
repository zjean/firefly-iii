<?php
/**
 * CurrencyCode.php
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

use FireflyIII\Models\TransactionCurrency;
use Illuminate\Routing\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class CurrencyCode.
 */
class CurrencyCode implements BinderInterface
{
    /**
     * @param string $value
     * @param Route  $route
     *
     * @return TransactionCurrency
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $value, Route $route): TransactionCurrency
    {
        if (auth()->check()) {
            $currency = TransactionCurrency::where('code', trim($value))->first();
            if (null !== $currency) {
                return $currency;
            }
        }
        throw new NotFoundHttpException;
    }
}
