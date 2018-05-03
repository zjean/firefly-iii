<?php
/**
 * ConvertController.php
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

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Models\Account;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use Illuminate\Http\Request;
use Session;
use View;

/**
 * Class ConvertController.
 */
class ConvertController extends Controller
{
    /** @var JournalRepositoryInterface */
    private $repository;

    /**
     * ConvertController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // some useful repositories:
        $this->middleware(
            function ($request, $next) {
                $this->repository = app(JournalRepositoryInterface::class);

                app('view')->share('title', trans('firefly.transactions'));
                app('view')->share('mainTitleIcon', 'fa-exchange');

                return $next($request);
            }
        );
    }

    /**
     * @param TransactionType    $destinationType
     * @param TransactionJournal $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function index(TransactionType $destinationType, TransactionJournal $journal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd
        $positiveAmount = $this->repository->getJournalTotal($journal);
        $sourceType     = $journal->transactionType;
        $subTitle       = trans('firefly.convert_to_' . $destinationType->type, ['description' => $journal->description]);
        $subTitleIcon   = 'fa-exchange';

        // cannot convert to its own type.
        if ($sourceType->type === $destinationType->type) {
            Session::flash('info', trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$journal->id]));
        }

        // cannot convert split.
        if ($journal->transactions()->count() > 2) {
            Session::flash('error', trans('firefly.cannot_convert_split_journal'));

            return redirect(route('transactions.show', [$journal->id]));
        }

        // get source and destination account:
        $sourceAccount      = $this->repository->getJournalSourceAccounts($journal)->first();
        $destinationAccount = $this->repository->getJournalDestinationAccounts($journal)->first();

        return view(
            'transactions.convert',
            compact(
                'sourceType',
                'destinationType',
                'journal',
                'positiveAmount',
                'sourceAccount',
                'destinationAccount',
                'sourceType',
                'subTitle',
                'subTitleIcon'
            )
        );

        // convert withdrawal to deposit requires a new source account ()
        //  or to transfer requires
    }

    /**
     * @param Request                    $request
     * @param JournalRepositoryInterface $repository
     * @param TransactionType            $destinationType
     * @param TransactionJournal         $journal
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     *
     * @throws FireflyException
     * @throws FireflyException
     */
    public function postIndex(Request $request, JournalRepositoryInterface $repository, TransactionType $destinationType, TransactionJournal $journal)
    {
        // @codeCoverageIgnoreStart
        if ($this->isOpeningBalance($journal)) {
            return $this->redirectToAccount($journal);
        }
        // @codeCoverageIgnoreEnd

        $data = $request->all();

        if ($journal->transactionType->type === $destinationType->type) {
            Session::flash('error', trans('firefly.convert_is_already_type_' . $destinationType->type));

            return redirect(route('transactions.show', [$journal->id]));
        }

        if ($journal->transactions()->count() > 2) {
            Session::flash('error', trans('firefly.cannot_convert_split_journal'));

            return redirect(route('transactions.show', [$journal->id]));
        }

        // get the new source and destination account:
        $source      = $this->getSourceAccount($journal, $destinationType, $data);
        $destination = $this->getDestinationAccount($journal, $destinationType, $data);

        // update the journal:
        $errors = $repository->convert($journal, $destinationType, $source, $destination);

        if ($errors->count() > 0) {
            return redirect(route('transactions.convert.index', [strtolower($destinationType->type), $journal->id]))->withErrors($errors)->withInput();
        }

        Session::flash('success', trans('firefly.converted_to_' . $destinationType->type));

        return redirect(route('transactions.show', [$journal->id]));
    }

    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $destinationType
     * @param array              $data
     *
     * @return Account
     *
     * @throws FireflyException
     */
    private function getDestinationAccount(TransactionJournal $journal, TransactionType $destinationType, array $data): Account
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository  = app(AccountRepositoryInterface::class);
        $sourceAccount      = $this->repository->getJournalSourceAccounts($journal)->first();
        $destinationAccount = $this->repository->getJournalDestinationAccounts($journal)->first();
        $sourceType         = $journal->transactionType;
        $joined             = $sourceType->type . '-' . $destinationType->type;
        switch ($joined) {
            default:
                throw new FireflyException('Cannot handle ' . $joined); // @codeCoverageIgnore
            case TransactionType::WITHDRAWAL . '-' . TransactionType::DEPOSIT:
                // one
                $destination = $sourceAccount;
                break;
            case TransactionType::WITHDRAWAL . '-' . TransactionType::TRANSFER:
                // two
                $destination = $accountRepository->findNull((int)$data['destination_account_asset']);
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::WITHDRAWAL:
            case TransactionType::TRANSFER . '-' . TransactionType::WITHDRAWAL:
                // three and five
                if ('' === $data['destination_account_expense'] || null === $data['destination_account_expense']) {
                    // destination is a cash account.
                    return $accountRepository->getCashAccount();
                }
                $data        = [
                    'name'            => $data['destination_account_expense'],
                    'accountType'     => 'expense',
                    'account_type_id' => null,
                    'virtualBalance'  => 0,
                    'active'          => true,
                    'iban'            => null,
                ];
                $destination = $accountRepository->store($data);
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::TRANSFER:
            case TransactionType::TRANSFER . '-' . TransactionType::DEPOSIT:
                // four and six
                $destination = $destinationAccount;
                break;
        }

        return $destination;
    }

    /**
     * @param TransactionJournal $journal
     * @param TransactionType    $destinationType
     * @param array              $data
     *
     * @return Account
     *
     * @throws FireflyException
     */
    private function getSourceAccount(TransactionJournal $journal, TransactionType $destinationType, array $data): Account
    {
        /** @var AccountRepositoryInterface $accountRepository */
        $accountRepository  = app(AccountRepositoryInterface::class);
        $sourceAccount      = $this->repository->getJournalSourceAccounts($journal)->first();
        $destinationAccount = $this->repository->getJournalDestinationAccounts($journal)->first();
        $sourceType         = $journal->transactionType;
        $joined             = $sourceType->type . '-' . $destinationType->type;
        switch ($joined) {
            default:
                throw new FireflyException('Cannot handle ' . $joined); // @codeCoverageIgnore
            case TransactionType::WITHDRAWAL . '-' . TransactionType::DEPOSIT:
            case TransactionType::TRANSFER . '-' . TransactionType::DEPOSIT:

                if ('' === $data['source_account_revenue'] || null === $data['source_account_revenue']) {
                    // destination is a cash account.
                    return $accountRepository->getCashAccount();
                }

                $data   = [
                    'name'            => $data['source_account_revenue'],
                    'accountType'     => 'revenue',
                    'virtualBalance'  => 0,
                    'active'          => true,
                    'account_type_id' => null,
                    'iban'            => null,
                ];
                $source = $accountRepository->store($data);
                break;
            case TransactionType::WITHDRAWAL . '-' . TransactionType::TRANSFER:
            case TransactionType::TRANSFER . '-' . TransactionType::WITHDRAWAL:
                $source = $sourceAccount;
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::WITHDRAWAL:
                $source = $destinationAccount;
                break;
            case TransactionType::DEPOSIT . '-' . TransactionType::TRANSFER:
                $source = $accountRepository->findNull((int)$data['source_account_asset']);
                break;
        }

        return $source;
    }
}
