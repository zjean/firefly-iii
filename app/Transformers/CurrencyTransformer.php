<?php
/**
 * CurrencyTransformer.php
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

namespace FireflyIII\Transformers;

use FireflyIII\Models\TransactionCurrency;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class CurrencyTransformer
 */
class CurrencyTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /** @var ParameterBag */
    protected $parameters;

    /**
     * CurrencyTransformer constructor.
     *
     * @codeCoverageIgnore
     *
     * @param ParameterBag $parameters
     */
    public function __construct(ParameterBag $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * Transform the currency.
     *
     * @param TransactionCurrency $currency
     *
     * @return array
     */
    public function transform(TransactionCurrency $currency): array
    {
        $isDefault       = false;
        $defaultCurrency = $this->parameters->get('defaultCurrency');
        if (null !== $defaultCurrency) {
            $isDefault = $defaultCurrency->id === $currency->id;
        }
        $data = [
            'id'             => (int)$currency->id,
            'updated_at'     => $currency->updated_at->toAtomString(),
            'created_at'     => $currency->created_at->toAtomString(),
            'name'           => $currency->name,
            'code'           => $currency->code,
            'symbol'         => $currency->symbol,
            'decimal_places' => (int)$currency->decimal_places,
            'default'        => $isDefault,
            'links'          => [
                [
                    'rel' => 'self',
                    'uri' => '/currencies/' . $currency->id,
                ],
            ],
        ];

        return $data;
    }
}
