<?php
/**
 * SelectController.php
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


use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Http\Requests\TestRuleFormRequest;
use FireflyIII\Jobs\ExecuteRuleOnExistingTransactions;
use FireflyIII\Models\Rule;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Support\Http\Controllers\RuleManagement;
use FireflyIII\TransactionRules\TransactionMatcher;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Log;
use Throwable;

/**
 * Class SelectController
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SelectController extends Controller
{
    use RuleManagement;
    /** @var AccountRepositoryInterface */
    private $accountRepos;

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

                $this->accountRepos = app(AccountRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * Execute the given rule on a set of existing transactions.
     *
     * @param SelectTransactionsRequest $request
     * @param Rule                      $rule
     *
     * @return RedirectResponse
     */
    public function execute(SelectTransactionsRequest $request, Rule $rule): RedirectResponse
    {
        // Get parameters specified by the user
        /** @var User $user */
        $user      = auth()->user();
        $accounts  = $this->accountRepos->getAccountsById($request->get('accounts'));
        $startDate = new Carbon($request->get('start_date'));
        $endDate   = new Carbon($request->get('end_date'));

        // Create a job to do the work asynchronously
        $job = new ExecuteRuleOnExistingTransactions($rule);

        // Apply parameters to the job
        $job->setUser($user);
        $job->setAccounts($accounts);
        $job->setStartDate($startDate);
        $job->setEndDate($endDate);

        // Dispatch a new job to execute it in a queue
        $this->dispatch($job);

        // Tell the user that the job is queued
        session()->flash('success', (string)trans('firefly.applied_rule_selection', ['title' => $rule->title]));

        return redirect()->route('rules.index');
    }


    /**
     * @param Rule $rule
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function selectTransactions(Rule $rule)
    {
        // does the user have shared accounts?
        $first    = session('first')->format('Y-m-d');
        $today    = Carbon::create()->format('Y-m-d');
        $subTitle = (string)trans('firefly.apply_rule_selection', ['title' => $rule->title]);

        return view('rules.rule.select-transactions', compact('first', 'today', 'rule', 'subTitle'));
    }


    /**
     * This method allows the user to test a certain set of rule triggers. The rule triggers are passed along
     * using the URL parameters (GET), and are usually put there using a Javascript thing.
     *
     * This method will parse and validate those rules and create a "TransactionMatcher" which will attempt
     * to find transaction journals matching the users input. A maximum range of transactions to try (range) and
     * a maximum number of transactions to return (limit) are set as well.
     *
     * @param TestRuleFormRequest $request
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testTriggers(TestRuleFormRequest $request): JsonResponse
    {
        // build trigger array from response
        $triggers = $this->getValidTriggerList($request);

        if (0 === \count($triggers)) {
            return response()->json(['html' => '', 'warning' => (string)trans('firefly.warning_no_valid_triggers')]); // @codeCoverageIgnore
        }

        $limit                = (int)config('firefly.test-triggers.limit');
        $range                = (int)config('firefly.test-triggers.range');
        $matchingTransactions = new Collection;
        /** @var TransactionMatcher $matcher */
        $matcher = app(TransactionMatcher::class);
        $matcher->setLimit($limit);
        $matcher->setRange($range);
        $matcher->setTriggers($triggers);
        try {
            $matchingTransactions = $matcher->findTransactionsByTriggers();
            // @codeCoverageIgnoreStart
        } catch (FireflyException $exception) {
            Log::error(sprintf('Could not grab transactions in testTriggers(): %s', $exception->getMessage()));
            Log::error($exception->getTraceAsString());
        }
        // @codeCoverageIgnoreStart


        // Warn the user if only a subset of transactions is returned
        $warning = '';
        if ($matchingTransactions->count() === $limit) {
            $warning = (string)trans('firefly.warning_transaction_subset', ['max_num_transactions' => $limit]); // @codeCoverageIgnore
        }
        if (0 === $matchingTransactions->count()) {
            $warning = (string)trans('firefly.warning_no_matching_transactions', ['num_transactions' => $range]); // @codeCoverageIgnore
        }

        // Return json response
        $view = 'ERROR, see logs.';
        try {
            $view = view('list.journals-tiny', ['transactions' => $matchingTransactions])->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            Log::error(sprintf('Could not render view in testTriggers(): %s', $exception->getMessage()));
            Log::error($exception->getTraceAsString());
        }

        // @codeCoverageIgnoreEnd

        return response()->json(['html' => $view, 'warning' => $warning]);
    }

    /**
     * This method allows the user to test a certain set of rule triggers. The rule triggers are grabbed from
     * the rule itself.
     *
     * This method will parse and validate those rules and create a "TransactionMatcher" which will attempt
     * to find transaction journals matching the users input. A maximum range of transactions to try (range) and
     * a maximum number of transactions to return (limit) are set as well.
     *
     * @param Rule $rule
     *
     * @return JsonResponse
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testTriggersByRule(Rule $rule): JsonResponse
    {
        $triggers = $rule->ruleTriggers;

        if (0 === \count($triggers)) {
            return response()->json(['html' => '', 'warning' => (string)trans('firefly.warning_no_valid_triggers')]); // @codeCoverageIgnore
        }

        $limit                = (int)config('firefly.test-triggers.limit');
        $range                = (int)config('firefly.test-triggers.range');
        $matchingTransactions = new Collection;

        /** @var TransactionMatcher $matcher */
        $matcher = app(TransactionMatcher::class);
        $matcher->setLimit($limit);
        $matcher->setRange($range);
        $matcher->setRule($rule);
        try {
            $matchingTransactions = $matcher->findTransactionsByRule();
            // @codeCoverageIgnoreStart
        } catch (FireflyException $exception) {
            Log::error(sprintf('Could not grab transactions in testTriggersByRule(): %s', $exception->getMessage()));
            Log::error($exception->getTraceAsString());
        }
        // @codeCoverageIgnoreEnd

        // Warn the user if only a subset of transactions is returned
        $warning = '';
        if ($matchingTransactions->count() === $limit) {
            $warning = (string)trans('firefly.warning_transaction_subset', ['max_num_transactions' => $limit]); // @codeCoverageIgnore
        }
        if (0 === $matchingTransactions->count()) {
            $warning = (string)trans('firefly.warning_no_matching_transactions', ['num_transactions' => $range]); // @codeCoverageIgnore
        }

        // Return json response
        $view = 'ERROR, see logs.';
        try {
            $view = view('list.journals-tiny', ['transactions' => $matchingTransactions])->render();
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            Log::error(sprintf('Could not render view in testTriggersByRule(): %s', $exception->getMessage()));
            Log::error($exception->getTraceAsString());
        }

        // @codeCoverageIgnoreEnd

        return response()->json(['html' => $view, 'warning' => $warning]);
    }


    /**
     * @param TestRuleFormRequest $request
     *
     * @return array
     */
    private function getValidTriggerList(TestRuleFormRequest $request): array
    {
        $triggers = [];
        $data     = [
            'rule-triggers'       => $request->get('rule-trigger'),
            'rule-trigger-values' => $request->get('rule-trigger-value'),
            'rule-trigger-stop'   => $request->get('rule-trigger-stop'),
        ];
        if (\is_array($data['rule-triggers'])) {
            foreach ($data['rule-triggers'] as $index => $triggerType) {
                $data['rule-trigger-stop'][$index] = (int)($data['rule-trigger-stop'][$index] ?? 0.0);
                $triggers[]                        = [
                    'type'           => $triggerType,
                    'value'          => $data['rule-trigger-values'][$index],
                    'stopProcessing' => 1 === (int)$data['rule-trigger-stop'][$index],
                ];
            }
        }

        return $triggers;
    }
}