<?php
/**
 * EditController.php
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

namespace FireflyIII\Http\Controllers\Rule;


use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\RuleFormRequest;
use FireflyIII\Models\Rule;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\RuleTrigger;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Log;
use Throwable;

/**
 * Class EditController
 */
class EditController extends Controller
{
    use RuleManagement;

    /** @var RuleRepositoryInterface Rule repository */
    private $ruleRepos;

    /**
     * RuleController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                $this->ruleRepos = app(RuleRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Edit a rule.
     *
     * @param Request $request
     * @param Rule    $rule
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function edit(Request $request, Rule $rule)
    {
        $triggerCount = 0;
        $actionCount  = 0;
        $oldActions   = [];
        $oldTriggers  = [];
        // has old input?
        if (\count($request->old()) > 0) {
            $oldTriggers  = $this->getPreviousTriggers($request);
            $triggerCount = \count($oldTriggers);
            $oldActions   = $this->getPreviousActions($request);
            $actionCount  = \count($oldActions);
        }

        // overrule old input when it has no rule data:
        if (0 === $triggerCount && 0 === $actionCount) {
            $oldTriggers  = $this->getCurrentTriggers($rule);
            $triggerCount = \count($oldTriggers);
            $oldActions   = $this->getCurrentActions($rule);
            $actionCount  = \count($oldActions);
        }

        $hasOldInput = null !== $request->old('_token');
        $preFilled   = [
            'active'          => $hasOldInput ? (bool)$request->old('active') : $rule->active,
            'stop_processing' => $hasOldInput ? (bool)$request->old('stop_processing') : $rule->stop_processing,
            'strict'          => $hasOldInput ? (bool)$request->old('strict') : $rule->strict,

        ];

        // get rule trigger for update / store-journal:
        $primaryTrigger = $this->ruleRepos->getPrimaryTrigger($rule);
        $subTitle       = (string)trans('firefly.edit_rule', ['title' => $rule->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('rules.edit.fromUpdate')) {
            $this->rememberPreviousUri('rules.edit.uri');
        }
        session()->forget('rules.edit.fromUpdate');

        $request->session()->flash('preFilled', $preFilled);

        return view('rules.rule.edit', compact('rule', 'subTitle', 'primaryTrigger', 'oldTriggers', 'oldActions', 'triggerCount', 'actionCount'));
    }

    /**
     * Update the rule.
     *
     * @param RuleFormRequest $request
     * @param Rule            $rule
     *
     * @return RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleFormRequest $request, Rule $rule)
    {
        $data = $request->getRuleData();
        $this->ruleRepos->update($rule, $data);

        session()->flash('success', (string)trans('firefly.updated_rule', ['title' => $rule->title]));
        app('preferences')->mark();
        $redirect = redirect($this->getPreviousUri('rules.edit.uri'));
        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            session()->put('rules.edit.fromUpdate', true);

            $redirect = redirect(route('rules.edit', [$rule->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        return $redirect;
    }

    /**
     * Get current (from system) rule actions.
     *
     * @param Rule $rule
     *
     * @return array
     */
    protected function getCurrentActions(Rule $rule): array // get info from object and present.
    {
        $index   = 0;
        $actions = [];

        /** @var RuleAction $entry */
        foreach ($rule->ruleActions as $entry) {
            $count = ($index + 1);
            try {
                $actions[] = view(
                    'rules.partials.action',
                    [
                        'oldAction'  => $entry->action_type,
                        'oldValue'   => $entry->action_value,
                        'oldChecked' => $entry->stop_processing,
                        'count'      => $count,
                    ]
                )->render();
                // @codeCoverageIgnoreStart
            } catch (Throwable $e) {
                Log::debug(sprintf('Throwable was thrown in getCurrentActions(): %s', $e->getMessage()));
                Log::error($e->getTraceAsString());
            }
            // @codeCoverageIgnoreEnd
            ++$index;
        }

        return $actions;
    }

    /**
     * Get current (from DB) rule triggers.
     *
     * @param Rule $rule
     *
     * @return array
     *
     */
    protected function getCurrentTriggers(Rule $rule): array // get info from object and present.
    {
        $index    = 0;
        $triggers = [];

        /** @var RuleTrigger $entry */
        foreach ($rule->ruleTriggers as $entry) {
            if ('user_action' !== $entry->trigger_type) {
                $count = ($index + 1);
                try {
                    $triggers[] = view(
                        'rules.partials.trigger',
                        [
                            'oldTrigger' => $entry->trigger_type,
                            'oldValue'   => $entry->trigger_value,
                            'oldChecked' => $entry->stop_processing,
                            'count'      => $count,
                        ]
                    )->render();
                    // @codeCoverageIgnoreStart
                } catch (Throwable $e) {
                    Log::debug(sprintf('Throwable was thrown in getCurrentTriggers(): %s', $e->getMessage()));
                    Log::error($e->getTraceAsString());
                }
                // @codeCoverageIgnoreEnd
                ++$index;
            }
        }

        return $triggers;
    }
}
