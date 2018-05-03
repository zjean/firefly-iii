<?php
/**
 * SplitController.php
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

use ExpandedForm;
use FireflyIII\Events\UpdatedTransactionJournal;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Attachments\AttachmentHelperInterface;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\SplitJournalFormRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Budget\BudgetRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Preferences;
use Session;
use Steam;
use Symfony\Component\HttpFoundation\ParameterBag;
use View;

/**
 * Class SplitController.
 */
class SplitController extends Controller
{
    /** @var AccountRepositoryInterface */
    private $accounts;

    /** @var AttachmentHelperInterface */
    private $attachments;

    /** @var BudgetRepositoryInterface */
    private $budgets;

    /** @var CurrencyRepositoryInterface */
    private $currencies;
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->accounts    = app(AccountRepositoryInterface::class);
                $this->budgets     = app(BudgetRepositoryInterface::class);
                $this->attachments = app(AttachmentHelperInterface::class);
                $this->currencies  = app(CurrencyRepositoryInterface::class);
                $this->repository  = app(JournalRepositoryInterface::class);
                app('view')->share('mainTitleIcon', 'fa-share-alt');
                app('view')->share('title', trans('firefly.split-transactions'));

                return $next($request);
            }
        );
    }

    /**
     * @param Request            $request
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     * @throws FireflyException
     */
    public function edit(Request $request, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal); // @codeCoverageIgnore
        }

        $uploadSize     = min(Steam::phpBytes(ini_get('upload_max_filesize')), Steam::phpBytes(ini_get('post_max_size')));
        $currencies     = $this->currencies->get();
        $optionalFields = Preferences::get('transaction_journal_optional_fields', [])->data;
        $budgets        = ExpandedForm::makeSelectListWithEmpty($this->budgets->getActiveBudgets());
        $preFilled      = $this->arrayFromJournal($request, $journal);
        $subTitle       = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);
        $subTitleIcon   = 'fa-pencil';
        $accountList    = $this->accounts->getAccountsByType([AccountType::ASSET, AccountType::DEFAULT]);
        $accountArray   = [];
        // account array to display currency info:
        /** @var Account $account */
        foreach ($accountList as $account) {
            $accountArray[$account->id]                = $account;
            $accountArray[$account->id]['currency_id'] = (int)$this->accounts->getMetaValue($account, 'currency_id');
        }

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('transactions.edit-split.fromUpdate')) {
            $this->rememberPreviousUri('transactions.edit-split.uri');
        }
        Session::forget('transactions.edit-split.fromUpdate');

        return view(
            'transactions.split.edit', compact(
                                         'subTitleIcon', 'currencies', 'optionalFields', 'preFilled', 'subTitle', 'uploadSize', 'budgets',
                                         'journal', 'accountArray'
                                     )
        );
    }

    /**
     * @param SplitJournalFormRequest $request
     * @param TransactionJournal      $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(SplitJournalFormRequest $request, TransactionJournal $journal)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal); // @codeCoverageIgnore
        }
        $data    = $request->getAll();
        $journal = $this->repository->update($journal, $data);

        /** @var array $files */
        $files = $request->hasFile('attachments') ? $request->file('attachments') : null;
        // save attachments:
        $this->attachments->saveAttachmentsForModel($journal, $files);
        event(new UpdatedTransactionJournal($journal));

        // flash messages
        // @codeCoverageIgnoreStart
        if (count($this->attachments->getMessages()->get('attachments')) > 0) {
            Session::flash('info', $this->attachments->getMessages()->get('attachments'));
        }
        // @codeCoverageIgnoreEnd

        $type = strtolower($this->repository->getTransactionType($journal));
        Session::flash('success', (string)trans('firefly.updated_' . $type, ['description' => $journal->description]));
        Preferences::mark();

        // @codeCoverageIgnoreStart
        if (1 === (int)$request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            Session::put('transactions.edit-split.fromUpdate', true);

            return redirect(route('transactions.split.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('transactions.edit-split.uri'));
    }

    /**
     * @param SplitJournalFormRequest|Request $request
     * @param TransactionJournal              $journal
     *
     * @return array
     * @throws FireflyException
     */
    private function arrayFromJournal(Request $request, TransactionJournal $journal): array
    {
        $sourceAccounts      = $this->repository->getJournalSourceAccounts($journal);
        $destinationAccounts = $this->repository->getJournalDestinationAccounts($journal);
        $array               = [
            'journal_description'            => $request->old('journal_description', $journal->description),
            'journal_amount'                 => $this->repository->getJournalTotal($journal),
            'sourceAccounts'                 => $sourceAccounts,
            'journal_source_account_id'      => $request->old('journal_source_account_id', $sourceAccounts->first()->id),
            'journal_source_account_name'    => $request->old('journal_source_account_name', $sourceAccounts->first()->name),
            'journal_destination_account_id' => $request->old('journal_destination_account_id', $destinationAccounts->first()->id),
            'destinationAccounts'            => $destinationAccounts,
            'what'                           => strtolower($this->repository->getTransactionType($journal)),
            'date'                           => $request->old('date', $this->repository->getJournalDate($journal, null)),
            'tags'                           => implode(',', $journal->tags->pluck('tag')->toArray()),

            // all custom fields:
            'interest_date'                  => $request->old('interest_date', $this->repository->getMetaField($journal, 'interest_date')),
            'book_date'                      => $request->old('book_date', $this->repository->getMetaField($journal, 'book_date')),
            'process_date'                   => $request->old('process_date', $this->repository->getMetaField($journal, 'process_date')),
            'due_date'                       => $request->old('due_date', $this->repository->getMetaField($journal, 'due_date')),
            'payment_date'                   => $request->old('payment_date', $this->repository->getMetaField($journal, 'payment_date')),
            'invoice_date'                   => $request->old('invoice_date', $this->repository->getMetaField($journal, 'invoice_date')),
            'internal_reference'             => $request->old('internal_reference', $this->repository->getMetaField($journal, 'internal_reference')),
            'notes'                          => $request->old('notes', $this->repository->getNoteText($journal)),

            // transactions.
            'transactions'                   => $this->getTransactionDataFromJournal($journal),
        ];
        // update transactions array with old request data.

        $array['transactions'] = $this->updateWithPrevious($array['transactions'], $request->old());

        return $array;
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return array
     * @throws FireflyException
     */
    private function getTransactionDataFromJournal(TransactionJournal $journal): array
    {
        // use collector to collect transactions.
        $collector = app(JournalCollectorInterface::class);
        $collector->setUser(auth()->user());
        $collector->withOpposingAccount()->withCategoryInformation()->withBudgetInformation();
        // filter on specific journals.
        $collector->setJournals(new Collection([$journal]));
        $set          = $collector->getJournals();
        $transactions = [];
        $transformer  = new TransactionTransformer(new ParameterBag);
        /** @var Transaction $transaction */
        foreach ($set as $transaction) {
            $res = [];
            if ((float)$transaction->transaction_amount > 0 && $journal->transactionType->type === TransactionType::DEPOSIT) {
                $res = $transformer->transform($transaction);
            }
            if ((float)$transaction->transaction_amount < 0 && $journal->transactionType->type !== TransactionType::DEPOSIT) {
                $res = $transformer->transform($transaction);
            }

            if (count($res) > 0) {
                $res['amount']  = app('steam')->positive((string)$res['amount']);
                $transactions[] = $res;
            }
        }

        return $transactions;
    }

    /**
     * @param $array
     * @param $old
     *
     * @return array
     */
    private function updateWithPrevious($array, $old): array
    {
        if (0 === count($old) || !isset($old['transactions'])) {
            return $array;
        }
        $old = $old['transactions'];

        foreach ($old as $index => $row) {
            if (isset($array[$index])) {
                $array[$index] = array_merge($array[$index], $row);
                continue;
            }
            // take some info from first transaction, that should at least exist.
            $array[$index]                            = $row;
            $array[$index]['currency_id']             = $array[0]['transaction_currency_id'];
            $array[$index]['currency_code']           = $array[0]['transaction_currency_code'] ?? '';
            $array[$index]['currency_symbol']         = $array[0]['transaction_currency_symbol'] ?? '';
            $array[$index]['foreign_amount']          = round($array[0]['foreign_destination_amount'] ?? '0', 12);
            $array[$index]['foreign_currency_id']     = $array[0]['foreign_currency_id'];
            $array[$index]['foreign_currency_code']   = $array[0]['foreign_currency_code'];
            $array[$index]['foreign_currency_symbol'] = $array[0]['foreign_currency_symbol'];
        }

        return $array;
    }
}
