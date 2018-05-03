<?php
/**
 * TransactionController.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\CountAttachmentsFilter;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Helpers\Filter\SplitIndicatorFilter;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Transformers\TransactionTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Log;
use Preferences;
use Symfony\Component\HttpFoundation\ParameterBag;
use View;

/**
 * Class TransactionController.
 */
class TransactionController extends Controller
{
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     * TransactionController constructor.
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
     * Index for a range of transactions.
     *
     * @param Request $request
     * @param string  $what
     * @param Carbon  $start
     * @param Carbon  $end
     *
     * @return View
     *
     */
    public function index(Request $request, string $what, Carbon $start = null, Carbon $end = null)
    {
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $page         = (int)$request->get('page');
        $pageSize     = (int)Preferences::get('listPageSize', 50)->data;
        $path         = route('transactions.index', [$what]);
        if (null === $start) {
            $start = session('start');
            $end   = session('end');
        }
        if (null === $end) {
            $end = session('end');
        }

        if ($end < $start) {
            [$start, $end] = [$end, $start];
        }
        $startStr = $start->formatLocalized($this->monthAndDayFormat);
        $endStr   = $end->formatLocalized($this->monthAndDayFormat);
        $subTitle = trans('firefly.title_' . $what . '_between', ['start' => $startStr, 'end' => $endStr]);
        $periods  = $this->getPeriodOverview($what, $end);

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes($types)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->addFilter(SplitIndicatorFilter::class);
        $collector->addFilter(CountAttachmentsFilter::class);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'transactions', 'periods', 'start', 'end'));
    }

    /**
     * @param Request $request
     * @param string  $what
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function indexAll(Request $request, string $what)
    {
        $subTitleIcon = config('firefly.transactionIconsByWhat.' . $what);
        $types        = config('firefly.transactionTypesByWhat.' . $what);
        $page         = (int)$request->get('page');
        $pageSize     = (int)Preferences::get('listPageSize', 50)->data;
        $path         = route('transactions.index.all', [$what]);
        $first        = $this->repository->firstNull();
        $start        = null === $first ? new Carbon : $first->date;
        $end          = new Carbon;
        $subTitle     = trans('firefly.all_' . $what);

        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()->setRange($start, $end)
                  ->setTypes($types)->setLimit($pageSize)->setPage($page)->withOpposingAccount()
                  ->withBudgetInformation()->withCategoryInformation();
        $collector->removeFilter(InternalTransferFilter::class);
        $collector->addFilter(SplitIndicatorFilter::class);
        $collector->addFilter(CountAttachmentsFilter::class);
        $transactions = $collector->getPaginatedJournals();
        $transactions->setPath($path);

        return view('transactions.index', compact('subTitle', 'what', 'subTitleIcon', 'transactions', 'start', 'end'));
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reconcile(Request $request)
    {
        $transactionIds = $request->get('transactions');
        foreach ($transactionIds as $transactionId) {
            $transactionId = (int)$transactionId;
            $transaction   = $this->repository->findTransaction($transactionId);
            Log::debug(sprintf('Transaction ID is %d', $transaction->id));

            $this->repository->reconcile($transaction);
        }

        return response()->json(['ok' => 'reconciled']);
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $ids  = $request->get('items');
        $date = new Carbon($request->get('date'));
        if (\count($ids) > 0) {
            $order = 0;
            $ids   = array_unique($ids);
            foreach ($ids as $id) {
                $journal = $this->repository->find((int)$id);
                if ($journal && $journal->date->isSameDay($date)) {
                    $this->repository->setOrder($journal, $order);
                    ++$order;
                }
            }
        }
        Preferences::mark();

        return response()->json([true]);
    }

    /**
     * @param TransactionJournal          $journal
     * @param LinkTypeRepositoryInterface $linkTypeRepository
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     * @throws FireflyException
     */
    public function show(TransactionJournal $journal, LinkTypeRepositoryInterface $linkTypeRepository)
    {
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        $transactionType = $journal->transactionType->type;
        if (TransactionType::RECONCILIATION === $transactionType) {
            return redirect(route('accounts.reconcile.show', [$journal->id])); // @codeCoverageIgnore
        }
        $linkTypes = $linkTypeRepository->get();
        $links     = $linkTypeRepository->getLinks($journal);

        // get transactions using the collector:
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
            $transactions[] = $transformer->transform($transaction);
        }

        $events   = $this->repository->getPiggyBankEvents($journal);
        $what     = strtolower($transactionType);
        $subTitle = trans('firefly.' . $what) . ' "' . $journal->description . '"';

        return view('transactions.show', compact('journal', 'events', 'subTitle', 'what', 'transactions', 'linkTypes', 'links'));
    }

    /**
     * @param string $what
     *
     * @param Carbon $date
     *
     * @return Collection
     */
    private function getPeriodOverview(string $what, Carbon $date): Collection
    {
        $range = Preferences::get('viewRange', '1M')->data;
        $first = $this->repository->firstNull();
        $start = new Carbon;
        $start->subYear();
        $types   = config('firefly.transactionTypesByWhat.' . $what);
        $entries = new Collection;
        if (null !== $first) {
            $start = $first->date;
        }
        if ($date < $start) {
            [$start, $date] = [$date, $start]; // @codeCoverageIgnore
        }

        /** @var array $dates */
        $dates = app('navigation')->blockPeriods($start, $date, $range);

        foreach ($dates as $currentDate) {
            /** @var JournalCollectorInterface $collector */
            $collector = app(JournalCollectorInterface::class);
            $collector->setAllAssetAccounts()->setRange($currentDate['start'], $currentDate['end'])->withOpposingAccount()->setTypes($types);
            $collector->removeFilter(InternalTransferFilter::class);
            $journals = $collector->getJournals();

            if ($journals->count() > 0) {
                $sums     = $this->sumPerCurrency($journals);
                $dateName = app('navigation')->periodShow($currentDate['start'], $currentDate['period']);
                $sum      = $journals->sum('transaction_amount');
                $entries->push(
                    [
                        'name'  => $dateName,
                        'sums'  => $sums,
                        'sum'   => $sum,
                        'start' => $currentDate['start']->format('Y-m-d'),
                        'end'   => $currentDate['end']->format('Y-m-d'),
                    ]
                );
            }
        }

        return $entries;
    }

    /**
     * @param Collection $collection
     *
     * @return array
     */
    private function sumPerCurrency(Collection $collection): array
    {
        $return = [];
        /** @var Transaction $transaction */
        foreach ($collection as $transaction) {
            $currencyId = (int)$transaction->transaction_currency_id;

            // save currency information:
            if (!isset($return[$currencyId])) {
                $currencySymbol      = $transaction->transaction_currency_symbol;
                $decimalPlaces       = $transaction->transaction_currency_dp;
                $currencyCode        = $transaction->transaction_currency_code;
                $return[$currencyId] = [
                    'currency' => [
                        'id'     => $currencyId,
                        'code'   => $currencyCode,
                        'symbol' => $currencySymbol,
                        'dp'     => $decimalPlaces,
                    ],
                    'sum'      => '0',
                    'count'    => 0,
                ];
            }
            // save amount:
            $return[$currencyId]['sum'] = bcadd($return[$currencyId]['sum'], $transaction->transaction_amount);
            ++$return[$currencyId]['count'];
        }
        asort($return);

        return $return;
    }
}
