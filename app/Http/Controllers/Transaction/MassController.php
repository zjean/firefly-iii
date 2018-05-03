<?php
/**
 * MassController.php
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

namespace FireflyIII\Http\Controllers\Transaction;

use Carbon\Carbon;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\MassDeleteJournalRequest;
use FireflyIII\Http\Requests\MassEditBulkJournalRequest;
use FireflyIII\Http\Requests\MassEditJournalRequest;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Support\Collection;
use Preferences;
use Session;
use View;

/**
 * Class MassController.
 */
class MassController extends Controller
{
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-repeat');
                $this->repository = app(JournalRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function delete(Collection $journals)
    {
        $subTitle = trans('firefly.mass_delete_journals');

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-delete.uri');

        return view('transactions.mass.delete', compact('journals', 'subTitle'));
    }

    /**
     * @param MassDeleteJournalRequest $request
     *
     * @return mixed
     */
    public function destroy(MassDeleteJournalRequest $request)
    {
        $ids = $request->get('confirm_mass_delete');
        $set = new Collection;
        if (is_array($ids)) {
            /** @var int $journalId */
            foreach ($ids as $journalId) {
                /** @var TransactionJournal $journal */
                $journal = $this->repository->find((int)$journalId);
                if (null !== $journal->id && (int)$journalId === $journal->id) {
                    $set->push($journal);
                }
            }
        }
        unset($journal);
        $count = 0;

        /** @var TransactionJournal $journal */
        foreach ($set as $journal) {
            $this->repository->destroy($journal);
            ++$count;
        }

        Preferences::mark();
        Session::flash('success', trans('firefly.mass_deleted_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-delete.uri'));
    }

    /**
     * @param Collection $journals
     *
     * @return View
     */
    public function edit(Collection $journals)
    {
        $subTitle = trans('firefly.mass_edit_journals');

        /** @var AccountRepositoryInterface $repository */
        $repository = app(AccountRepositoryInterface::class);
        $accounts   = $repository->getAccountsByType([AccountType::DEFAULT, AccountType::ASSET]);

        // get budgets
        /** @var BudgetRepositoryInterface $budgetRepository */
        $budgetRepository = app(BudgetRepositoryInterface::class);
        $budgets          = $budgetRepository->getBudgets();

        // skip transactions that have multiple destinations, multiple sources or are an opening balance.
        $filtered = new Collection;
        $messages = [];
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $sources      = $this->repository->getJournalSourceAccounts($journal);
            $destinations = $this->repository->getJournalDestinationAccounts($journal);
            if ($sources->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_source', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }

            if ($destinations->count() > 1) {
                $messages[] = trans('firefly.cannot_edit_multiple_dest', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }
            if (TransactionType::OPENING_BALANCE === $this->repository->getTransactionType($journal)) {
                $messages[] = trans('firefly.cannot_edit_opening_balance');
                continue;
            }

            // cannot edit reconciled transactions / journals:
            if ($this->repository->isJournalReconciled($journal)) {
                $messages[] = trans('firefly.cannot_edit_reconciled', ['description' => $journal->description, 'id' => $journal->id]);
                continue;
            }

            $filtered->push($journal);
        }

        if (count($messages) > 0) {
            Session::flash('info', $messages);
        }

        // put previous url in session
        $this->rememberPreviousUri('transactions.mass-edit.uri');

        // collect some useful meta data for the mass edit:
        $filtered->each(
            function (TransactionJournal $journal) {
                $transaction                    = $this->repository->getFirstPosTransaction($journal);
                $currency                       = $transaction->transactionCurrency;
                $journal->amount                = (float)$transaction->amount;
                $sources                        = $this->repository->getJournalSourceAccounts($journal);
                $destinations                   = $this->repository->getJournalDestinationAccounts($journal);
                $journal->transaction_count     = $journal->transactions()->count();
                $journal->currency_symbol       = $currency->symbol;
                $journal->transaction_type_type = $journal->transactionType->type;

                $journal->foreign_amount   = (float)$transaction->foreign_amount;
                $journal->foreign_currency = $transaction->foreignCurrency;

                if (null !== $sources->first()) {
                    $journal->source_account_id   = $sources->first()->id;
                    $journal->source_account_name = $sources->first()->editname;
                }
                if (null !== $destinations->first()) {
                    $journal->destination_account_id   = $destinations->first()->id;
                    $journal->destination_account_name = $destinations->first()->editname;
                }
            }
        );

        if (0 === $filtered->count()) {
            Session::flash('error', trans('firefly.no_edit_multiple_left'));
        }

        $journals = $filtered;

        return view('transactions.mass.edit', compact('journals', 'subTitle', 'accounts', 'budgets'));
    }

    /**
     * @param MassEditJournalRequest     $request
     * @param JournalRepositoryInterface $repository
     *
     * @return mixed
     */
    public function update(MassEditJournalRequest $request, JournalRepositoryInterface $repository)
    {
        $journalIds = $request->get('journals');
        $count      = 0;
        if (is_array($journalIds)) {
            foreach ($journalIds as $journalId) {
                $journal = $repository->find((int)$journalId);
                if (null !== $journal) {
                    // get optional fields:
                    $what              = strtolower($this->repository->getTransactionType($journal));
                    $sourceAccountId   = $request->get('source_account_id')[$journal->id] ?? null;
                    $currencyId        = $request->get('transaction_currency_id')[$journal->id] ?? 1;
                    $sourceAccountName = $request->get('source_account_name')[$journal->id] ?? null;
                    $destAccountId     = $request->get('destination_account_id')[$journal->id] ?? null;
                    $destAccountName   = $request->get('destination_account_name')[$journal->id] ?? null;
                    $budgetId          = (int)($request->get('budget_id')[$journal->id] ?? 0.0);
                    $category          = $request->get('category')[$journal->id];
                    $tags              = $journal->tags->pluck('tag')->toArray();
                    $amount            = round($request->get('amount')[$journal->id], 12);
                    $foreignAmount     = isset($request->get('foreign_amount')[$journal->id]) ? round($request->get('foreign_amount')[$journal->id], 12) : null;
                    $foreignCurrencyId = isset($request->get('foreign_currency_id')[$journal->id]) ?
                        (int)$request->get('foreign_currency_id')[$journal->id] : null;
                    // build data array
                    $data = [
                        'id'            => $journal->id,
                        'what'          => $what,
                        'description'   => $request->get('description')[$journal->id],
                        'date'          => new Carbon($request->get('date')[$journal->id]),
                        'bill_id'       => null,
                        'bill_name'     => null,
                        'notes'         => $repository->getNoteText($journal),
                        'transactions'  => [[

                                                'category_id'           => null,
                                                'category_name'         => $category,
                                                'budget_id'             => (int)$budgetId,
                                                'budget_name'           => null,
                                                'source_id'             => (int)$sourceAccountId,
                                                'source_name'           => $sourceAccountName,
                                                'destination_id'        => (int)$destAccountId,
                                                'destination_name'      => $destAccountName,
                                                'amount'                => $amount,
                                                'identifier'            => 0,
                                                'reconciled'            => false,
                                                'currency_id'           => (int)$currencyId,
                                                'currency_code'         => null,
                                                'description'           => null,
                                                'foreign_amount'        => $foreignAmount,
                                                'foreign_currency_id'   => $foreignCurrencyId,
                                                'foreign_currency_code' => null,
                                                //'native_amount'            => $amount,
                                                //'source_amount'            => $amount,
                                                //'foreign_amount'           => $foreignAmount,
                                                //'destination_amount'       => $foreignAmount,
                                                //'amount'                   => $foreignAmount,
                                            ]],
                        'currency_id'   => $foreignCurrencyId,
                        'tags'          => $tags,
                        'interest_date' => $journal->interest_date,
                        'book_date'     => $journal->book_date,
                        'process_date'  => $journal->process_date,

                    ];
                    // call repository update function.
                    $repository->update($journal, $data);

                    ++$count;
                }
            }
        }
        Preferences::mark();
        Session::flash('success', trans('firefly.mass_edited_transactions_success', ['amount' => $count]));

        // redirect to previous URL:
        return redirect($this->getPreviousUri('transactions.mass-edit.uri'));
    }

}
