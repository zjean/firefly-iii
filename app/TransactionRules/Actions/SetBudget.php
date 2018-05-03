<?php
/**
 * SetBudget.php
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

namespace FireflyIII\TransactionRules\Actions;

use FireflyIII\Models\Budget;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use Log;

/**
 * Class SetBudget.
 */
class SetBudget implements ActionInterface
{
    /** @var RuleAction The rule action */
    private $action;

    /**
     * TriggerInterface constructor.
     *
     * @param RuleAction $action
     */
    public function __construct(RuleAction $action)
    {
        $this->action = $action;
    }

    /**
     * Set budget X
     *
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function act(TransactionJournal $journal): bool
    {
        /** @var BudgetRepositoryInterface $repository */
        $repository = app(BudgetRepositoryInterface::class);
        $repository->setUser($journal->user);
        $search  = $this->action->action_value;
        $budgets = $repository->getActiveBudgets();
        $budget  = $budgets->filter(
            function (Budget $current) use ($search) {
                return $current->name === $search;
            }
        )->first();
        if (null === $budget) {
            Log::debug(sprintf('RuleAction SetBudget could not set budget of journal #%d to "%s" because no such budget exists.', $journal->id, $search));

            return true;
        }

        if (TransactionType::WITHDRAWAL !== $journal->transactionType->type) {
            Log::debug(
                sprintf(
                    'RuleAction SetBudget could not set budget of journal #%d to "%s" because journal is a %s.',
                    $journal->id,
                    $search,
                    $journal->transactionType->type
                )
            );

            return true;
        }

        Log::debug(sprintf('RuleAction SetBudget set the budget of journal #%d to budget #%d ("%s").', $journal->id, $budget->id, $budget->name));

        $journal->budgets()->detach();
        // set budget on transactions:
        /** @var Transaction $transaction */
        foreach ($journal->transactions as $transaction) {
            $transaction->budgets()->sync([$budget->id]);
        }
        $journal->touch();

        return true;
    }
}
