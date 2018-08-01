<?php
/**
 * FixerIOv2.php
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

namespace FireflyIII\Services\Currency;

use Carbon\Carbon;
use Exception;
use FireflyIII\Models\CurrencyExchangeRate;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;

/**
 * Class FixerIOv2.
 */
class FixerIOv2 implements ExchangeRateInterface
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
        // create new exchange rate with default values.
        $rate         = 0;
        $exchangeRate = new CurrencyExchangeRate;
        $exchangeRate->user()->associate($this->user);
        $exchangeRate->fromCurrency()->associate($fromCurrency);
        $exchangeRate->toCurrency()->associate($toCurrency);
        $exchangeRate->date = $date;
        $exchangeRate->rate = $rate;

        // get API key
        $apiKey = env('FIXER_API_KEY', '');

        // if no API key, return unsaved exchange rate.
        if ('' === $apiKey) {
            Log::warning('No fixer.IO API key, will not do conversion.');

            return $exchangeRate;
        }

        // build URI
        $uri        = sprintf(
            'http://data.fixer.io/api/%s?access_key=%s&base=%s&symbols=%s',
            $date->format('Y-m-d'), $apiKey, $fromCurrency->code, $toCurrency->code
        );
        Log::debug(sprintf('Going to request exchange rate using URI %s', str_replace($apiKey, 'xxxx', $uri)));
        $client = new Client;
        try {

            $res        = $client->request('GET', $uri);
            $statusCode = $res->getStatusCode();
            $body       = $res->getBody()->getContents();
        } catch (GuzzleException|Exception $e) {
            // don't care about error
            $body       = sprintf('Guzzle exception: %s', $e->getMessage());
            $statusCode = 500;
        }
        Log::debug(sprintf('Result status code is %d', $statusCode));
        Log::debug(sprintf('Result body is: %s', $body));

        $content = null;
        if (200 !== $statusCode) {
            Log::error(sprintf('Something went wrong. Received error code %d and body "%s" from FixerIO.', $statusCode, $body));
        }
        $success = false;
        // get rate from body:
        if (200 === $statusCode) {
            $content = json_decode($body, true);
            $success = $content['success'] ?? false;
        }
        if (null !== $content && true === $success) {
            $code = $toCurrency->code;
            $rate = (float)($content['rates'][$code] ?? 0);
            Log::debug('Got the following rates from Fixer: ', $content['rates'] ?? []);
        }

        $exchangeRate->rate = $rate;
        if (0.0 !== $rate) {
            Log::debug('Rate is not zero, save it!');
            $exchangeRate->save();
        }

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
