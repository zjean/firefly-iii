<?php
/**
 * Amount.php
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

namespace FireflyIII\Support;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Preferences as Prefs;

/**
 * Class Amount.
 */
class Amount
{
    /** @noinspection MoreThanThreeArgumentsInspection */
    /**
     * bool $sepBySpace is $localeconv['n_sep_by_space']
     * int $signPosn = $localeconv['n_sign_posn']
     * string $sign = $localeconv['negative_sign']
     * bool $csPrecedes = $localeconv['n_cs_precedes'].
     *
     * @param bool   $sepBySpace
     * @param int    $signPosn
     * @param string $sign
     * @param bool   $csPrecedes
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getAmountJsConfig(bool $sepBySpace, int $signPosn, string $sign, bool $csPrecedes): string
    {
        // negative first:
        $space = ' ';

        // require space between symbol and amount?
        if (false === $sepBySpace) {
            $space = ''; // no
        }

        // there are five possible positions for the "+" or "-" sign (if it is even used)
        // pos_a and pos_e could be the ( and ) symbol.
        $posA = ''; // before everything
        $posB = ''; // before currency symbol
        $posC = ''; // after currency symbol
        $posD = ''; // before amount
        $posE = ''; // after everything

        // format would be (currency before amount)
        // AB%sC_D%vE
        // or:
        // AD%v_B%sCE (amount before currency)
        // the _ is the optional space

        // switch on how to display amount:
        switch ($signPosn) {
            default:
            case 0:
                // ( and ) around the whole thing
                $posA = '(';
                $posE = ')';
                break;
            case 1:
                // The sign string precedes the quantity and currency_symbol
                $posA = $sign;
                break;
            case 2:
                // The sign string succeeds the quantity and currency_symbol
                $posE = $sign;
                break;
            case 3:
                // The sign string immediately precedes the currency_symbol
                $posB = $sign;
                break;
            case 4:
                // The sign string immediately succeeds the currency_symbol
                $posC = $sign;
        }

        // default is amount before currency
        $format = $posA . $posD . '%v' . $space . $posB . '%s' . $posC . $posE;

        if ($csPrecedes) {
            // alternative is currency before amount
            $format = $posA . $posB . '%s' . $posC . $space . $posD . '%v' . $posE;
        }

        return $format;
    }

    /**
     * This method will properly format the given number, in color or "black and white",
     * as a currency, given two things: the currency required and the current locale.
     *
     * @param \FireflyIII\Models\TransactionCurrency $format
     * @param string                                 $amount
     * @param bool                                   $coloured
     *
     * @return string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function formatAnything(TransactionCurrency $format, string $amount, bool $coloured = null): string
    {
        $coloured = $coloured ?? true;
        $locale   = explode(',', (string)trans('config.locale'));
        $locale   = array_map('trim', $locale);
        setlocale(LC_MONETARY, $locale);
        $float     = round($amount, 12);
        $info      = localeconv();
        $formatted = number_format($float, (int)$format->decimal_places, $info['mon_decimal_point'], $info['mon_thousands_sep']);

        // some complicated switches to format the amount correctly:
        $precedes  = $amount < 0 ? $info['n_cs_precedes'] : $info['p_cs_precedes'];
        $separated = $amount < 0 ? $info['n_sep_by_space'] : $info['p_sep_by_space'];
        $space     = true === $separated ? ' ' : '';
        $result    = false === $precedes ? $formatted . $space . $format->symbol : $format->symbol . $space . $formatted;

        if (true === $coloured) {
            if ($amount > 0) {
                return sprintf('<span class="text-success">%s</span>', $result);
            }
            if ($amount < 0) {
                return sprintf('<span class="text-danger">%s</span>', $result);
            }

            return sprintf('<span style="color:#999">%s</span>', $result);
        }

        return $result;
    }

    /**
     * @return Collection
     */
    public function getAllCurrencies(): Collection
    {
        return TransactionCurrency::orderBy('code', 'ASC')->get();
    }

    /**
     * @return string
     */
    public function getCurrencyCode(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty('getCurrencyCode');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));

        $currency = TransactionCurrency::where('code', $currencyPreference->data)->first();
        if ($currency) {
            $cache->store($currency->code);

            return $currency->code;
        }
        $cache->store(config('firefly.default_currency', 'EUR'));

        return (string)config('firefly.default_currency', 'EUR');
    }

    /**
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        $cache = new CacheProperties;
        $cache->addProperty('getCurrencySymbol');
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = Prefs::get('currencyPreference', config('firefly.default_currency', 'EUR'));
        $currency           = TransactionCurrency::where('code', $currencyPreference->data)->first();

        $cache->store($currency->symbol);

        return $currency->symbol;
    }

    /**
     * @return \FireflyIII\Models\TransactionCurrency
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function getDefaultCurrency(): TransactionCurrency
    {
        /** @var User $user */
        $user = auth()->user();

        return $this->getDefaultCurrencyByUser($user);
    }

    /**
     * @param User $user
     *
     * @return \FireflyIII\Models\TransactionCurrency
     *
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function getDefaultCurrencyByUser(User $user): TransactionCurrency
    {
        $cache = new CacheProperties;
        $cache->addProperty('getDefaultCurrency');
        $cache->addProperty($user->id);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        $currencyPreference = Prefs::getForUser($user, 'currencyPreference', config('firefly.default_currency', 'EUR'));
        $currency           = TransactionCurrency::where('code', $currencyPreference->data)->first();
        if (null === $currency) {
            throw new FireflyException(sprintf('No currency found with code "%s"', $currencyPreference->data));
        }
        $cache->store($currency);

        return $currency;
    }

    /**
     * This method returns the correct format rules required by accounting.js,
     * the library used to format amounts in charts.
     *
     * @param array $config
     *
     * @return array
     */
    public function getJsConfig(array $config): array
    {
        $negative = self::getAmountJsConfig(1 === $config['n_sep_by_space'], $config['n_sign_posn'], $config['negative_sign'], 1 === $config['n_cs_precedes']);
        $positive = self::getAmountJsConfig(1 === $config['p_sep_by_space'], $config['p_sign_posn'], $config['positive_sign'], 1 === $config['p_cs_precedes']);

        return [
            'pos'  => $positive,
            'neg'  => $negative,
            'zero' => $positive,
        ];
    }
}
