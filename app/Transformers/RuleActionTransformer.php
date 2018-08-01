<?php
/**
 * RuleActionTransformer.php
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


use FireflyIII\Models\RuleAction;
use League\Fractal\TransformerAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Class RuleActionTransformer
 */
class RuleActionTransformer extends TransformerAbstract
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
     * Transform the rule action.
     *
     * @param RuleAction $ruleAction
     *
     * @return array
     */
    public function transform(RuleAction $ruleAction): array
    {
        $data = [
            'id'              => (int)$ruleAction->id,
            'updated_at'      => $ruleAction->updated_at->toAtomString(),
            'created_at'      => $ruleAction->created_at->toAtomString(),
            'action_type'     => $ruleAction->action_type,
            'action_value'    => $ruleAction->action_value,
            'order'           => $ruleAction->order,
            'active'          => $ruleAction->active,
            'stop_processing' => $ruleAction->stop_processing,
            'links'           => [
                [
                    'rel' => 'self',
                    'uri' => '/rule_triggers/' . $ruleAction->id,
                ],
            ],
        ];

        return $data;
    }
}
