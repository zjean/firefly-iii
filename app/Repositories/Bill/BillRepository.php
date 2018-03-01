<?php
/**
 * BillRepository.php
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

namespace FireflyIII\Repositories\Bill;

use Carbon\Carbon;
use DB;
use FireflyIII\Models\Bill;
use FireflyIII\Models\Note;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Models\TransactionType;
use FireflyIII\Support\CacheProperties;
use FireflyIII\User;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Log;
use Navigation;

/**
 * Class BillRepository.
 */
class BillRepository implements BillRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param Bill $bill
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function destroy(Bill $bill): bool
    {
        $bill->delete();

        return true;
    }

    /**
     * Find a bill by ID.
     *
     * @param int $billId
     *
     * @return Bill
     */
    public function find(int $billId): ?Bill
    {
        /** @var Bill $res */
        $res = $this->user->bills()->find($billId);

        return $res;
    }

    /**
     * Find a bill by name.
     *
     * @param string $name
     *
     * @return Bill
     */
    public function findByName(string $name): ?Bill
    {
        $bills = $this->user->bills()->get(['bills.*']);

        /** @var Bill $bill */
        foreach ($bills as $bill) {
            if ($bill->name === $name) {
                return $bill;
            }
        }

        return null;
    }

    /**
     * @return Collection
     */
    public function getActiveBills(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->bills()
                          ->where('active', 1)
                          ->get(
                              [
                                  'bills.*',
                                  DB::raw('((bills.amount_min + bills.amount_max) / 2) AS expectedAmount'),
                              ]
                          )->sortBy('name');

        return $set;
    }

    /**
     * @return Collection
     */
    public function getBills(): Collection
    {
        /** @var Collection $set */
        $set = $this->user->bills()->orderBy('name', 'ASC')->get();

        $set = $set->sortBy(
            function (Bill $bill) {
                $int = $bill->active ? 0 : 1;

                return $int . strtolower($bill->name);
            }
        );

        return $set;
    }

    /**
     * @param Collection $accounts
     *
     * @return Collection
     */
    public function getBillsForAccounts(Collection $accounts): Collection
    {
        $fields = ['bills.id',
                   'bills.created_at',
                   'bills.updated_at',
                   'bills.deleted_at',
                   'bills.user_id',
                   'bills.name',
                   'bills.match',
                   'bills.amount_min',
                   'bills.amount_max',
                   'bills.date',
                   'bills.repeat_freq',
                   'bills.skip',
                   'bills.automatch',
                   'bills.active',
                   'bills.name_encrypted',
                   'bills.match_encrypted',];
        $ids    = $accounts->pluck('id')->toArray();
        $set    = $this->user->bills()
                             ->leftJoin(
                                 'transaction_journals',
                                 function (JoinClause $join) {
                                     $join->on('transaction_journals.bill_id', '=', 'bills.id')->whereNull('transaction_journals.deleted_at');
                                 }
                             )
                             ->leftJoin(
                                 'transactions',
                                 function (JoinClause $join) {
                                     $join->on('transaction_journals.id', '=', 'transactions.transaction_journal_id')->where('transactions.amount', '<', 0);
                                 }
                             )
                             ->whereIn('transactions.account_id', $ids)
                             ->whereNull('transaction_journals.deleted_at')
                             ->groupBy($fields)
                             ->get($fields);

        $set = $set->sortBy(
            function (Bill $bill) {
                $int = 1 === $bill->active ? 0 : 1;

                return $int . strtolower($bill->name);
            }
        );

        return $set;
    }

    /**
     * Get the total amount of money paid for the users active bills in the date range given.
     * This amount will be negative (they're expenses). This method is equal to
     * getBillsUnpaidInRange. So the debug comments are gone.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsPaidInRange(Carbon $start, Carbon $end): string
    {
        $bills = $this->getActiveBills();
        $sum   = '0';
        /** @var Bill $bill */
        foreach ($bills as $bill) {
            /** @var Collection $set */
            $set = $bill->transactionJournals()->after($start)->before($end)->get(['transaction_journals.*']);
            if ($set->count() > 0) {
                $journalIds = $set->pluck('id')->toArray();
                $amount     = strval(Transaction::whereIn('transaction_journal_id', $journalIds)->where('amount', '<', 0)->sum('amount'));
                $sum        = bcadd($sum, $amount);
                Log::debug(sprintf('Total > 0, so add to sum %f, which becomes %f', $amount, $sum));
            }
        }

        return $sum;
    }

    /**
     * Get the total amount of money due for the users active bills in the date range given. This amount will be positive.
     *
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return string
     */
    public function getBillsUnpaidInRange(Carbon $start, Carbon $end): string
    {
        $bills = $this->getActiveBills();
        $sum   = '0';
        /** @var Bill $bill */
        foreach ($bills as $bill) {
            Log::debug(sprintf('Now at bill #%d (%s)', $bill->id, $bill->name));
            $dates = $this->getPayDatesInRange($bill, $start, $end);
            $count = $bill->transactionJournals()->after($start)->before($end)->count();
            $total = $dates->count() - $count;

            Log::debug(sprintf('Dates = %d, journalCount = %d, total = %d', $dates->count(), $count, $total));

            if ($total > 0) {
                $average = bcdiv(bcadd($bill->amount_max, $bill->amount_min), '2');
                $multi   = bcmul($average, strval($total));
                $sum     = bcadd($sum, $multi);
                Log::debug(sprintf('Total > 0, so add to sum %f, which becomes %f', $multi, $sum));
            }
        }

        return $sum;
    }

    /**
     * @param Bill $bill
     *
     * @return string
     */
    public function getOverallAverage(Bill $bill): string
    {
        $journals = $bill->transactionJournals()->get();
        $sum      = '0';
        $count    = strval($journals->count());
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $sum = bcadd($sum, $journal->amountPositive());
        }
        $avg = '0';
        if ($journals->count() > 0) {
            $avg = bcdiv($sum, $count);
        }

        return $avg;
    }

    /**
     * @param int $size
     *
     * @return LengthAwarePaginator
     */
    public function getPaginator(int $size): LengthAwarePaginator
    {
        return $this->user->bills()->paginate($size);
    }

    /**
     * The "paid dates" list is a list of dates of transaction journals that are linked to this bill.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPaidDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        $dates = $bill->transactionJournals()->before($end)->after($start)->get(['transaction_journals.date'])->pluck('date');

        return $dates;
    }

    /**
     * Between start and end, tells you on which date(s) the bill is expected to hit.
     *
     * @param Bill   $bill
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return Collection
     */
    public function getPayDatesInRange(Bill $bill, Carbon $start, Carbon $end): Collection
    {
        $set = new Collection;
        Log::debug(sprintf('Now at bill "%s" (%s)', $bill->name, $bill->repeat_freq));

        // Start at 2016-10-01, see when we expect the bill to hit:
        $currentStart = clone $start;
        Log::debug(sprintf('First currentstart is %s', $currentStart->format('Y-m-d')));

        while ($currentStart <= $end) {
            Log::debug(sprintf('Currentstart is now %s.', $currentStart->format('Y-m-d')));
            $nextExpectedMatch = $this->nextDateMatch($bill, $currentStart);
            Log::debug(sprintf('Next Date match after %s is %s', $currentStart->format('Y-m-d'), $nextExpectedMatch->format('Y-m-d')));
            // If nextExpectedMatch is after end, we continue:
            if ($nextExpectedMatch > $end) {
                Log::debug(
                    sprintf('nextExpectedMatch %s is after %s, so we skip this bill now.', $nextExpectedMatch->format('Y-m-d'), $end->format('Y-m-d'))
                );
                break;
            }
            // add to set
            $set->push(clone $nextExpectedMatch);
            Log::debug(sprintf('Now %d dates in set.', $set->count()));

            // add day if necessary.
            $nextExpectedMatch->addDay();

            Log::debug(sprintf('Currentstart (%s) has become %s.', $currentStart->format('Y-m-d'), $nextExpectedMatch->format('Y-m-d')));

            $currentStart = clone $nextExpectedMatch;
        }
        $simple = $set->each(
            function (Carbon $date) {
                return $date->format('Y-m-d');
            }
        );
        Log::debug(sprintf('Found dates between %s and %s:', $start->format('Y-m-d'), $end->format('Y-m-d')), $simple->toArray());

        return $set;
    }

    /**
     * @param Bill $bill
     *
     * @return Collection
     */
    public function getPossiblyRelatedJournals(Bill $bill): Collection
    {
        $set = new Collection(
            DB::table('transactions')->where('amount', '>', 0)->where('amount', '>=', $bill->amount_min)->where('amount', '<=', $bill->amount_max)
              ->get(['transaction_journal_id'])
        );
        $ids = $set->pluck('transaction_journal_id')->toArray();

        $journals = new Collection;
        if (count($ids) > 0) {
            $journals = $this->user->transactionJournals()->transactionTypes([TransactionType::WITHDRAWAL])->whereIn('transaction_journals.id', $ids)->get(
                ['transaction_journals.*']
            );
        }

        return $journals;
    }

    /**
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return string
     */
    public function getYearAverage(Bill $bill, Carbon $date): string
    {
        $journals = $bill->transactionJournals()
                         ->where('date', '>=', $date->year . '-01-01 00:00:00')
                         ->where('date', '<=', $date->year . '-12-31 00:00:00')
                         ->get();
        $sum      = '0';
        $count    = strval($journals->count());
        /** @var TransactionJournal $journal */
        foreach ($journals as $journal) {
            $sum = bcadd($sum, $journal->amountPositive());
        }
        $avg = '0';
        if ($journals->count() > 0) {
            $avg = bcdiv($sum, $count);
        }

        return $avg;
    }

    /**
     * Given a bill and a date, this method will tell you at which moment this bill expects its next
     * transaction. Whether or not it is there already, is not relevant.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return \Carbon\Carbon
     * @throws \FireflyIII\Exceptions\FireflyException
     */
    public function nextDateMatch(Bill $bill, Carbon $date): Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty($bill->id);
        $cache->addProperty('nextDateMatch');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        // find the most recent date for this bill NOT in the future. Cache this date:
        $start = clone $bill->date;
        Log::debug('nextDateMatch: Start is ' . $start->format('Y-m-d'));

        while ($start < $date) {
            Log::debug(sprintf('$start (%s) < $date (%s)', $start->format('Y-m-d'), $date->format('Y-m-d')));
            $start = Navigation::addPeriod($start, $bill->repeat_freq, $bill->skip);
            Log::debug('Start is now ' . $start->format('Y-m-d'));
        }

        $end = Navigation::addPeriod($start, $bill->repeat_freq, $bill->skip);

        Log::debug('nextDateMatch: Final start is ' . $start->format('Y-m-d'));
        Log::debug('nextDateMatch: Matching end is ' . $end->format('Y-m-d'));

        $cache->store($start);

        return $start;
    }

    /**
     * Given the date in $date, this method will return a moment in the future where the bill is expected to be paid.
     *
     * @param Bill   $bill
     * @param Carbon $date
     *
     * @return Carbon
     */
    public function nextExpectedMatch(Bill $bill, Carbon $date): Carbon
    {
        $cache = new CacheProperties;
        $cache->addProperty($bill->id);
        $cache->addProperty('nextExpectedMatch');
        $cache->addProperty($date);
        if ($cache->has()) {
            return $cache->get(); // @codeCoverageIgnore
        }
        // find the most recent date for this bill NOT in the future. Cache this date:
        $start = clone $bill->date;
        Log::debug('nextExpectedMatch: Start is ' . $start->format('Y-m-d'));

        while ($start < $date) {
            Log::debug(sprintf('$start (%s) < $date (%s)', $start->format('Y-m-d'), $date->format('Y-m-d')));
            $start = Navigation::addPeriod($start, $bill->repeat_freq, $bill->skip);
            Log::debug('Start is now ' . $start->format('Y-m-d'));
        }

        $end = Navigation::addPeriod($start, $bill->repeat_freq, $bill->skip);

        // see if the bill was paid in this period.
        $journalCount = $bill->transactionJournals()->before($end)->after($start)->count();

        if ($journalCount > 0) {
            // this period had in fact a bill. The new start is the current end, and we create a new end.
            Log::debug(sprintf('Journal count is %d, so start becomes %s', $journalCount, $end->format('Y-m-d')));
            $start = clone $end;
            $end   = Navigation::addPeriod($start, $bill->repeat_freq, $bill->skip);
        }
        Log::debug('nextExpectedMatch: Final start is ' . $start->format('Y-m-d'));
        Log::debug('nextExpectedMatch: Matching end is ' . $end->format('Y-m-d'));

        $cache->store($start);

        return $start;
    }

    /**
     * @param Bill               $bill
     * @param TransactionJournal $journal
     *
     * @return bool
     */
    public function scan(Bill $bill, TransactionJournal $journal): bool
    {
        // Can only support withdrawals.
        if (false === $journal->isWithdrawal()) {
            return false;
        }
        $destinationAccounts = $journal->destinationAccountList();
        $sourceAccounts      = $journal->sourceAccountList();
        $matches             = explode(',', $bill->match);
        $description         = strtolower($journal->description) . ' ';
        $description         .= strtolower(join(' ', $destinationAccounts->pluck('name')->toArray()));
        $description         .= strtolower(join(' ', $sourceAccounts->pluck('name')->toArray()));

        $wordMatch   = $this->doWordMatch($matches, $description);
        $amountMatch = $this->doAmountMatch($journal->amountPositive(), $bill->amount_min, $bill->amount_max);

        // when both, update!
        if ($wordMatch && $amountMatch) {
            $journal->bill()->associate($bill);
            $journal->save();

            return true;
        }
        if ($bill->id === $journal->bill_id) {
            // if no match, but bill used to match, remove it:
            $journal->bill_id = null;
            $journal->save();

            return true;
        }

        return false;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $data
     *
     * @return Bill
     */
    public function store(array $data): Bill
    {
        $matchArray = explode(',', $data['match']);
        $matchArray = array_unique($matchArray);
        $match      = join(',', $matchArray);

        /** @var Bill $bill */
        $bill = Bill::create(
            [
                'name'        => $data['name'],
                'match'       => $match,
                'amount_min'  => $data['amount_min'],
                'user_id'     => $this->user->id,
                'amount_max'  => $data['amount_max'],
                'date'        => $data['date'],
                'repeat_freq' => $data['repeat_freq'],
                'skip'        => $data['skip'],
                'automatch'   => $data['automatch'],
                'active'      => $data['active'],
            ]
        );

        // update note:
        if (isset($data['notes'])) {
            $this->updateNote($bill, $data['notes']);
        }

        return $bill;
    }

    /**
     * @param Bill  $bill
     * @param array $data
     *
     * @return Bill
     */
    public function update(Bill $bill, array $data): Bill
    {
        $matchArray = explode(',', $data['match']);
        $matchArray = array_unique($matchArray);
        $match      = join(',', $matchArray);

        $bill->name        = $data['name'];
        $bill->match       = $match;
        $bill->amount_min  = $data['amount_min'];
        $bill->amount_max  = $data['amount_max'];
        $bill->date        = $data['date'];
        $bill->repeat_freq = $data['repeat_freq'];
        $bill->skip        = $data['skip'];
        $bill->automatch   = $data['automatch'];
        $bill->active      = $data['active'];
        $bill->save();

        // update note:
        if (isset($data['notes']) && null !== $data['notes']) {
            $this->updateNote($bill, strval($data['notes']));
        }

        return $bill;
    }

    /**
     * @param float $amount
     * @param float $min
     * @param float $max
     *
     * @return bool
     */
    protected function doAmountMatch($amount, $min, $max): bool
    {
        if ($amount >= $min && $amount <= $max) {
            return true;
        }

        return false;
    }

    /**
     * @param array $matches
     * @param       $description
     *
     * @return bool
     */
    protected function doWordMatch(array $matches, $description): bool
    {
        $wordMatch = false;
        $count     = 0;
        foreach ($matches as $word) {
            if (!(false === strpos($description, strtolower($word)))) {
                ++$count;
            }
        }
        if ($count >= count($matches)) {
            $wordMatch = true;
        }

        return $wordMatch;
    }

    /**
     * @param Bill   $bill
     * @param string $note
     *
     * @return bool
     */
    protected function updateNote(Bill $bill, string $note): bool
    {
        if (0 === strlen($note)) {
            $dbNote = $bill->notes()->first();
            if (null !== $dbNote) {
                $dbNote->delete();
            }

            return true;
        }
        $dbNote = $bill->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($bill);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
