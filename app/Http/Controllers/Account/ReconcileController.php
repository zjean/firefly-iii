<?php
/**
 * ReconcileController.php
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

namespace FireflyIII\Http\Controllers\Account;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Requests\ReconciliationStoreRequest;
use FireflyIII\Http\Requests\ReconciliationUpdateRequest;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Currency\CurrencyRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Services\Internal\Update\CurrencyUpdateService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Session;

/**
 * Class ReconcileController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReconcileController extends Controller
{
    /** @var CurrencyUpdateService */
    private $accountRepos;
    /** @var AccountRepositoryInterface */
    private $currencyRepos;
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        // translations:
        $this->middleware(
            function ($request, $next) {
                app('view')->share('mainTitleIcon', 'fa-credit-card');
                app('view')->share('title', trans('firefly.accounts'));
                $this->repository    = app(JournalRepositoryInterface::class);
                $this->accountRepos  = app(AccountRepositoryInterface::class);
                $this->currencyRepos = app(CurrencyRepositoryInterface::class);

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function edit(TransactionJournal $journal)
    {
        if (TransactionType::RECONCILIATION !== $journal->transactionType->type) {
            return redirect(route('transactions.edit', [$journal->id]));
        }
        // view related code
        $subTitle = trans('breadcrumbs.edit_journal', ['description' => $journal->description]);

        // journal related code
        $pTransaction = $this->repository->getFirstPosTransaction($journal);
        $preFilled    = [
            'date'     => $this->repository->getJournalDate($journal, null),
            'category' => $this->repository->getJournalCategoryName($journal),
            'tags'     => implode(',', $journal->tags->pluck('tag')->toArray()),
            'amount'   => $pTransaction->amount,
        ];

        Session::flash('preFilled', $preFilled);

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('reconcile.edit.fromUpdate')) {
            $this->rememberPreviousUri('reconcile.edit.uri');
        }
        Session::forget('reconcile.edit.fromUpdate');

        return view(
            'accounts.reconcile.edit',
            compact('journal', 'subTitle')
        )->with('data', $preFilled);
    }

    /**
     * @param Request $request
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws FireflyException
     */
    public function overview(Request $request, Account $account, Carbon $start, Carbon $end)
    {
        if (AccountType::ASSET !== $account->accountType->type) {
            throw new FireflyException(sprintf('Account %s is not an asset account.', $account->name));
        }
        $startBalance   = $request->get('startBalance');
        $endBalance     = $request->get('endBalance');
        $transactionIds = $request->get('transactions') ?? [];
        $clearedIds     = $request->get('cleared') ?? [];
        $amount         = '0';
        $clearedAmount  = '0';
        $route          = route('accounts.reconcile.submit', [$account->id, $start->format('Ymd'), $end->format('Ymd')]);
        // get sum of transaction amounts:
        $transactions = $this->repository->getTransactionsById($transactionIds);
        $cleared      = $this->repository->getTransactionsById($clearedIds);
        $countCleared = 0;

        /** @var Transaction $transaction */
        foreach ($transactions as $transaction) {
            $amount = bcadd($amount, $transaction->amount);
        }

        /** @var Transaction $transaction */
        foreach ($cleared as $transaction) {
            if ($transaction->transactionJournal->date <= $end) {
                $clearedAmount = bcadd($clearedAmount, $transaction->amount);
                ++$countCleared;
            }
        }
        $difference  = bcadd(bcadd(bcsub($startBalance, $endBalance), $clearedAmount), $amount);
        $diffCompare = bccomp($difference, '0');
        $return      = [
            'post_uri' => $route,
            'html'     => view(
                'accounts.reconcile.overview', compact(
                                                 'account', 'start', 'diffCompare', 'difference', 'end', 'clearedIds', 'transactionIds', 'clearedAmount',
                                                 'startBalance', 'endBalance', 'amount',
                                                 'route', 'countCleared'
                                             )
            )->render(),
        ];

        return response()->json($return);
    }

    /**
     * @param Account     $account
     * @param Carbon|null $start
     * @param Carbon|null $end
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     *
     * @throws FireflyException
     */
    public function reconcile(Account $account, Carbon $start = null, Carbon $end = null)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }
        if (AccountType::ASSET !== $account->accountType->type) {
            Session::flash('error', trans('firefly.must_be_asset_account'));

            return redirect(route('accounts.index', [config('firefly.shortNamesByFullName.' . $account->accountType->type)]));
        }
        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $currency   = $this->currencyRepos->findNull($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency(); // @codeCoverageIgnore
        }

        // no start or end:
        $range = Preferences::get('viewRange', '1M')->data;

        // get start and end
        if (null === $start && null === $end) {
            $start = clone session('start', app('navigation')->startOfPeriod(new Carbon, $range));
            $end   = clone session('end', app('navigation')->endOfPeriod(new Carbon, $range));
        }
        if (null === $end) {
            $end = app('navigation')->endOfPeriod($start, $range);
        }

        $startDate = clone $start;
        $startDate->subDays(1);
        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);
        $subTitleIcon = config('firefly.subIconsByIdentifier.' . $account->accountType->type);
        $subTitle     = trans('firefly.reconcile_account', ['account' => $account->name]);

        // various links
        $transactionsUri = route('accounts.reconcile.transactions', [$account->id, '%start%', '%end%']);
        $overviewUri     = route('accounts.reconcile.overview', [$account->id, '%start%', '%end%']);
        $indexUri        = route('accounts.reconcile', [$account->id, '%start%', '%end%']);

        return view(
            'accounts.reconcile.index', compact(
                                          'account', 'currency', 'subTitleIcon', 'start', 'end', 'subTitle', 'startBalance', 'endBalance', 'transactionsUri',
                                          'overviewUri', 'indexUri'
                                      )
        );
    }

    /**
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function show(TransactionJournal $journal)
    {

        if (TransactionType::RECONCILIATION !== $journal->transactionType->type) {
            return redirect(route('transactions.show', [$journal->id]));
        }
        $subTitle = trans('firefly.reconciliation') . ' "' . $journal->description . '"';

        // get main transaction:
        $transaction = $this->repository->getAssetTransaction($journal);
        $account     = $transaction->account;

        return view('accounts.reconcile.show', compact('journal', 'subTitle', 'transaction', 'account'));
    }

    /**
     * @param ReconciliationStoreRequest $request
     * @param JournalRepositoryInterface $repository
     * @param Account                    $account
     * @param Carbon                     $start
     * @param Carbon                     $end
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submit(ReconciliationStoreRequest $request, JournalRepositoryInterface $repository, Account $account, Carbon $start, Carbon $end)
    {
        Log::debug('In ReconcileController::submit()');
        $data = $request->getAll();

        /** @var Transaction $transaction */
        foreach ($data['transactions'] as $transactionId) {
            $repository->reconcileById((int)$transactionId);
        }
        Log::debug('Reconciled all transactions.');

        // create reconciliation transaction (if necessary):
        if ('create' === $data['reconcile']) {
            // get "opposing" account.
            $reconciliation = $this->accountRepos->getReconciliation($account);


            $difference  = $data['difference'];
            $source      = $reconciliation;
            $destination = $account;
            if (bccomp($difference, '0') === 1) {
                // amount is positive. Add it to reconciliation?
                $source      = $account;
                $destination = $reconciliation;

            }

            // data for journal
            $description = trans(
                'firefly.reconcilliation_transaction_title',
                ['from' => $start->formatLocalized($this->monthAndDayFormat), 'to' => $end->formatLocalized($this->monthAndDayFormat)]
            );
            $journalData = [
                'type'            => 'Reconciliation',
                'description'     => $description,
                'user'            => auth()->user()->id,
                'date'            => $data['end'],
                'bill_id'         => null,
                'bill_name'       => null,
                'piggy_bank_id'   => null,
                'piggy_bank_name' => null,
                'tags'            => null,
                'interest_date'   => null,
                'transactions'    => [[
                                          'currency_id'           => (int)$this->accountRepos->getMetaValue($account, 'currency_id'),
                                          'currency_code'         => null,
                                          'description'           => null,
                                          'amount'                => app('steam')->positive($difference),
                                          'source_id'             => $source->id,
                                          'source_name'           => null,
                                          'destination_id'        => $destination->id,
                                          'destination_name'      => null,
                                          'reconciled'            => true,
                                          'identifier'            => 0,
                                          'foreign_currency_id'   => null,
                                          'foreign_currency_code' => null,
                                          'foreign_amount'        => null,
                                          'budget_id'             => null,
                                          'budget_name'           => null,
                                          'category_id'           => null,
                                          'category_name'         => null,
                                      ],
                ],
                'notes'           => implode(', ', $data['transactions']),
            ];

            $journal = $repository->store($journalData);
        }
        Log::debug('End of routine.');

        Session::flash('success', trans('firefly.reconciliation_stored'));

        return redirect(route('accounts.show', [$account->id]));
    }

    /**
     * @param Account $account
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return mixed
     *
     * @throws FireflyException
     */
    public function transactions(Account $account, Carbon $start, Carbon $end)
    {
        if (AccountType::INITIAL_BALANCE === $account->accountType->type) {
            return $this->redirectToOriginalAccount($account);
        }

        $startDate = clone $start;
        $startDate->subDays(1);

        $currencyId = (int)$this->accountRepos->getMetaValue($account, 'currency_id');
        $currency   = $this->currencyRepos->findNull($currencyId);
        if (0 === $currencyId) {
            $currency = app('amount')->getDefaultCurrency(); // @codeCoverageIgnore
        }

        $startBalance = round(app('steam')->balance($account, $startDate), $currency->decimal_places);
        $endBalance   = round(app('steam')->balance($account, $end), $currency->decimal_places);

        // get the transactions
        $selectionStart = clone $start;
        $selectionStart->subDays(3);
        $selectionEnd = clone $end;
        $selectionEnd->addDays(3);

        // grab transactions:
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAccounts(new Collection([$account]))
                  ->setRange($selectionStart, $selectionEnd)->withBudgetInformation()->withOpposingAccount()->withCategoryInformation();
        $transactions = $collector->getJournals();
        $html         = view('accounts.reconcile.transactions', compact('account', 'transactions', 'start', 'end', 'selectionStart', 'selectionEnd'))->render();

        return response()->json(['html' => $html, 'startBalance' => $startBalance, 'endBalance' => $endBalance]);
    }

    /**
     * @param ReconciliationUpdateRequest $request
     * @param TransactionJournal          $journal
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(ReconciliationUpdateRequest $request, TransactionJournal $journal)
    {
        if (TransactionType::RECONCILIATION !== $journal->transactionType->type) {
            return redirect(route('transactions.show', [$journal->id]));
        }
        if (0 === bccomp('0', $request->get('amount'))) {
            Session::flash('error', trans('firefly.amount_cannot_be_zero'));

            return redirect(route('accounts.reconcile.edit', [$journal->id]))->withInput();
        }
        // update journal using account repository. Keep it consistent.
        $submitted = $request->getJournalData();

        // amount pos neg influences the accounts:
        $source      = $this->repository->getJournalSourceAccounts($journal)->first();
        $destination = $this->repository->getJournalDestinationAccounts($journal)->first();
        if (bccomp($submitted['amount'], '0') === 1) {
            // amount is positive, switch accounts:
            [$source, $destination] = [$destination, $source];

        }
        // expand data with journal data:
        $data = [
            'type'            => $journal->transactionType->type,
            'description'     => $journal->description,
            'user'            => $journal->user_id,
            'date'            => $journal->date,
            'bill_id'         => null,
            'bill_name'       => null,
            'piggy_bank_id'   => null,
            'piggy_bank_name' => null,
            'tags'            => $submitted['tags'],
            'interest_date'   => null,
            'book_date'       => null,
            'transactions'    => [[
                                      'currency_id'           => (int)$journal->transaction_currency_id,
                                      'currency_code'         => null,
                                      'description'           => null,
                                      'amount'                => app('steam')->positive($submitted['amount']),
                                      'source_id'             => $source->id,
                                      'source_name'           => null,
                                      'destination_id'        => $destination->id,
                                      'destination_name'      => null,
                                      'reconciled'            => true,
                                      'identifier'            => 0,
                                      'foreign_currency_id'   => null,
                                      'foreign_currency_code' => null,
                                      'foreign_amount'        => null,
                                      'budget_id'             => null,
                                      'budget_name'           => null,
                                      'category_id'           => null,
                                      'category_name'         => $submitted['category'],
                                  ],
            ],
            'notes'           => $this->repository->getNoteText($journal),
        ];

        $this->repository->update($journal, $data);

        // @codeCoverageIgnoreStart
        if (1 === (int)$request->get('return_to_edit')) {
            Session::put('reconcile.edit.fromUpdate', true);

            return redirect(route('accounts.reconcile.edit', [$journal->id]))->withInput(['return_to_edit' => 1]);
        }
        // @codeCoverageIgnoreEnd

        // redirect to previous URL.
        return redirect($this->getPreviousUri('reconcile.edit.uri'));
    }

    /**
     * @param Account $account
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     */
    private function redirectToOriginalAccount(Account $account)
    {
        /** @var Transaction $transaction */
        $transaction = $account->transactions()->first();
        if (null === $transaction) {
            throw new FireflyException(sprintf('Expected a transaction. Account #%d has none. BEEP, error.', $account->id)); // @codeCoverageIgnore
        }

        $journal = $transaction->transactionJournal;
        /** @var Transaction $opposingTransaction */
        $opposingTransaction = $journal->transactions()->where('transactions.id', '!=', $transaction->id)->first();

        if (null === $opposingTransaction) {
            throw new FireflyException('Expected an opposing transaction. This account has none. BEEP, error.'); // @codeCoverageIgnore
        }

        return redirect(route('accounts.show', [$opposingTransaction->account_id]));
    }
}
