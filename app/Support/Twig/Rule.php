<?php
/**
 * Rule.php
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

namespace FireflyIII\Support\Twig;

use Config;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class Rule.
 */
class Rule extends Twig_Extension
{
    /**
     * @return Twig_SimpleFunction
     */
    public function allActionTriggers(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'allRuleActions',
            function () {
                // array of valid values for actions
                $ruleActions     = array_keys(Config::get('firefly.rule-actions'));
                $possibleActions = [];
                foreach ($ruleActions as $key) {
                    $possibleActions[$key] = (string)trans('firefly.rule_action_' . $key . '_choice');
                }
                unset($ruleActions);
                asort($possibleActions);

                return $possibleActions;
            }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function allJournalTriggers(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'allJournalTriggers',
            function () {
                return [
                    'store-journal'  => (string)trans('firefly.rule_trigger_store_journal'),
                    'update-journal' => (string)trans('firefly.rule_trigger_update_journal'),
                ];
            }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function allRuleTriggers(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'allRuleTriggers',
            function () {
                $ruleTriggers     = array_keys(Config::get('firefly.rule-triggers'));
                $possibleTriggers = [];
                foreach ($ruleTriggers as $key) {
                    if ('user_action' !== $key) {
                        $possibleTriggers[$key] = (string)trans('firefly.rule_trigger_' . $key . '_choice');
                    }
                }
                unset($ruleTriggers);
                asort($possibleTriggers);

                return $possibleTriggers;
            }
        );
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return [
            $this->allJournalTriggers(),
            $this->allRuleTriggers(),
            $this->allActionTriggers(),
        ];
    }
}
