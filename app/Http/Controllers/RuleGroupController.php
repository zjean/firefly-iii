<?php
/**
 * RuleGroupController.php
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

namespace FireflyIII\Http\Controllers;

use Carbon\Carbon;
use ExpandedForm;
use FireflyIII\Http\Requests\RuleGroupFormRequest;
use FireflyIII\Http\Requests\SelectTransactionsRequest;
use FireflyIII\Jobs\ExecuteRuleGroupOnExistingTransactions;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleGroup;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use Illuminate\Http\Request;
use Preferences;
use Session;
use View;

/**
 * Class RuleGroupController.
 */
class RuleGroupController extends Controller
{
    /**
     * RuleGroupController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.rules'));
                app('view')->share('mainTitleIcon', 'fa-random');

                return $next($request);
            }
        );
    }

    /**
     * @return View
     */
    public function create()
    {
        $subTitleIcon = 'fa-clone';
        $subTitle     = trans('firefly.make_new_rule_group');

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('rule-groups.create.fromStore')) {
            $this->rememberPreviousUri('rule-groups.create.uri');
        }
        Session::forget('rule-groups.create.fromStore');

        return view('rules.rule-group.create', compact('subTitleIcon', 'subTitle'));
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return View
     */
    public function delete(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $subTitle = trans('firefly.delete_rule_group', ['title' => $ruleGroup->title]);

        $ruleGroupList = ExpandedForm::makeSelectListWithEmpty($repository->get());
        unset($ruleGroupList[$ruleGroup->id]);

        // put previous url in session
        $this->rememberPreviousUri('rule-groups.delete.uri');

        return view('rules.rule-group.delete', compact('ruleGroup', 'subTitle', 'ruleGroupList'));
    }

    /**
     * @param Request                      $request
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $title  = $ruleGroup->title;
        $moveTo = auth()->user()->ruleGroups()->find((int)$request->get('move_rules_before_delete'));

        $repository->destroy($ruleGroup, $moveTo);

        Session::flash('success', (string)trans('firefly.deleted_rule_group', ['title' => $title]));
        Preferences::mark();

        return redirect($this->getPreviousUri('rule-groups.delete.uri'));
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function down(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveDown($ruleGroup);

        return redirect(route('rules.index'));
    }

    /**
     * @param RuleGroup $ruleGroup
     *
     * @return View
     */
    public function edit(RuleGroup $ruleGroup)
    {
        $subTitle = trans('firefly.edit_rule_group', ['title' => $ruleGroup->title]);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('rule-groups.edit.fromUpdate')) {
            $this->rememberPreviousUri('rule-groups.edit.uri');
        }
        Session::forget('rule-groups.edit.fromUpdate');

        return view('rules.rule-group.edit', compact('ruleGroup', 'subTitle'));
    }

    /**
     * Execute the given rulegroup on a set of existing transactions.
     *
     * @param SelectTransactionsRequest  $request
     * @param AccountRepositoryInterface $repository
     * @param RuleGroup                  $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function execute(SelectTransactionsRequest $request, AccountRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        // Get parameters specified by the user
        $accounts  = $repository->getAccountsById($request->get('accounts'));
        $startDate = new Carbon($request->get('start_date'));
        $endDate   = new Carbon($request->get('end_date'));

        // Create a job to do the work asynchronously
        $job = new ExecuteRuleGroupOnExistingTransactions($ruleGroup);

        // Apply parameters to the job
        $job->setUser(auth()->user());
        $job->setAccounts($accounts);
        $job->setStartDate($startDate);
        $job->setEndDate($endDate);

        // Dispatch a new job to execute it in a queue
        $this->dispatch($job);

        // Tell the user that the job is queued
        Session::flash('success', (string)trans('firefly.applied_rule_group_selection', ['title' => $ruleGroup->title]));

        return redirect()->route('rules.index');
    }

    /**
     * @param AccountRepositoryInterface $repository
     * @param RuleGroup                  $ruleGroup
     *
     * @return View
     */
    public function selectTransactions(AccountRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        // does the user have shared accounts?
        $accounts        = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);
        $accountList     = ExpandedForm::makeSelectList($accounts);
        $checkedAccounts = array_keys($accountList);
        $first           = session('first')->format('Y-m-d');
        $today           = Carbon::create()->format('Y-m-d');
        $subTitle        = (string)trans('firefly.apply_rule_group_selection', ['title' => $ruleGroup->title]);

        return view('rules.rule-group.select-transactions', compact('checkedAccounts', 'accountList', 'first', 'today', 'ruleGroup', 'subTitle'));
    }

    /**
     * @param RuleGroupFormRequest         $request
     * @param RuleGroupRepositoryInterface $repository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(RuleGroupFormRequest $request, RuleGroupRepositoryInterface $repository)
    {
        $data      = $request->getRuleGroupData();
        $ruleGroup = $repository->store($data);

        Session::flash('success', (string)trans('firefly.created_new_rule_group', ['title' => $ruleGroup->title]));
        Preferences::mark();

        if (1 === (int)$request->get('create_another')) {
            // @codeCoverageIgnoreStart
            Session::put('rule-groups.create.fromStore', true);

            return redirect(route('rule-groups.create'))->withInput();
            // @codeCoverageIgnoreEnd
        }

        return redirect($this->getPreviousUri('rule-groups.create.uri'));
    }

    /**
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function up(RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $repository->moveUp($ruleGroup);

        return redirect(route('rules.index'));
    }

    /**
     * @param RuleGroupFormRequest         $request
     * @param RuleGroupRepositoryInterface $repository
     * @param RuleGroup                    $ruleGroup
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(RuleGroupFormRequest $request, RuleGroupRepositoryInterface $repository, RuleGroup $ruleGroup)
    {
        $data = [
            'title'       => $request->input('title'),
            'description' => $request->input('description'),
            'active'      => 1 === (int)$request->input('active'),
        ];

        $repository->update($ruleGroup, $data);

        Session::flash('success', (string)trans('firefly.updated_rule_group', ['title' => $ruleGroup->title]));
        Preferences::mark();

        if (1 === (int)$request->get('return_to_edit')) {
            // @codeCoverageIgnoreStart
            Session::put('rule-groups.edit.fromUpdate', true);

            return redirect(route('rule-groups.edit', [$ruleGroup->id]))->withInput(['return_to_edit' => 1]);
            // @codeCoverageIgnoreEnd
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('rule-groups.edit.uri'));
    }
}
