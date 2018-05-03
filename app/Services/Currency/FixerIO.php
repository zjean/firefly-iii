<?php
/**
 * FixerIO.php
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

namespace FireflyIII\Services\Currency;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Log;
use Requests;

/**
 * Class FixerIO.
 */
class FixerIO implements ExchangeRateInterface
{
    /** @var User */
    protected $user;

    /**
     * @param TransactionCurrency $fromCurrency
     * @param TransactionCurrency $toCurrency
     * @param Carbon              $date
     *
     * @return CurrencyExchangeRate
     */
    public function getRate(TransactionCurrency $fromCurrency, TransactionCurrency $toCurrency, Carbon $date): CurrencyExchangeRate
    {
        $uri        = sprintf('https://api.fixer.io/%s?base=%s&symbols=%s', $date->format('Y-m-d'), $fromCurrency->code, $toCurrency->code);
        $statusCode = -1;
        try {
            $result     = Requests::get($uri);
            $statusCode = $result->status_code;
            $body       = $result->body;
        } catch (Exception $e) {
            // don't care about error
            $body = sprintf('Requests_Exception: %s', $e->getMessage());
        }
        // Requests_Exception
        $rate    = 1.0;
        $content = null;
        if (200 !== $statusCode) {
            Log::error(sprintf('Something went wrong. Received error code %d and body "%s" from FixerIO.', $statusCode, $body));
        }
        // get rate from body:
        if (200 === $statusCode) {
            $content = json_decode($body, true);
        }
        if (null !== $content) {
            $code = $toCurrency->code;
            $rate = $content['rates'][$code] ?? '1';
        }

        // create new currency exchange rate object:
        $exchangeRate = new CurrencyExchangeRate;
        $exchangeRate->user()->associate($this->user);
        $exchangeRate->fromCurrency()->associate($fromCurrency);
        $exchangeRate->toCurrency()->associate($toCurrency);
        $exchangeRate->date = $date;
        $exchangeRate->rate = $rate;
        $exchangeRate->save();

        return $exchangeRate;
    }

    /**
     * @param User $user
     *
     * @return mixed|void
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
