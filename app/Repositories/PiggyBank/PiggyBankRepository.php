<?php
/**
 * PiggyBankRepository.php
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

namespace FireflyIII\Repositories\PiggyBank;

use Carbon\Carbon;
use FireflyIII\Models\Note;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\PiggyBankEvent;
use FireflyIII\Models\PiggyBankRepetition;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Log;

/**
 * Class PiggyBankRepository.
 */
class PiggyBankRepository implements PiggyBankRepositoryInterface
{
    /** @var User */
    private $user;

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function addAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $repetition                = $piggyBank->currentRelevantRep();
        $currentAmount             = $repetition->currentamount ?? '0';
        $repetition->currentamount = bcadd($currentAmount, $amount);
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, $amount);

        return true;
    }

    /**
     * @param PiggyBankRepetition $repetition
     * @param string              $amount
     *
     * @return string
     */
    public function addAmountToRepetition(PiggyBankRepetition $repetition, string $amount): string
    {
        $newAmount                 = bcadd($repetition->currentamount, $amount);
        $repetition->currentamount = $newAmount;
        $repetition->save();

        return $newAmount;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function canAddAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $leftOnAccount = $piggyBank->leftOnAccount(new Carbon);
        $savedSoFar    = (string)$piggyBank->currentRelevantRep()->currentamount;
        $leftToSave    = bcsub($piggyBank->targetamount, $savedSoFar);
        $maxAmount     = (string)min(round($leftOnAccount, 12), round($leftToSave, 12));

        return bccomp($amount, $maxAmount) <= 0;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function canRemoveAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $savedSoFar = $piggyBank->currentRelevantRep()->currentamount;

        return bccomp($amount, $savedSoFar) <= 0;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return PiggyBankEvent
     */
    public function createEvent(PiggyBank $piggyBank, string $amount): PiggyBankEvent
    {
        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::create(['date' => Carbon::now(), 'amount' => $amount, 'piggy_bank_id' => $piggyBank->id]);

        return $event;
    }

    /**
     * @param PiggyBank          $piggyBank
     * @param string             $amount
     * @param TransactionJournal $journal
     *
     * @return PiggyBankEvent
     */
    public function createEventWithJournal(PiggyBank $piggyBank, string $amount, TransactionJournal $journal): PiggyBankEvent
    {
        /** @var PiggyBankEvent $event */
        $event = PiggyBankEvent::create(
            [
                'piggy_bank_id'          => $piggyBank->id,
                'transaction_journal_id' => $journal->id,
                'date'                   => $journal->date->format('Y-m-d'),
                'amount'                 => $amount]
        );

        return $event;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return bool
     *

     */
    public function destroy(PiggyBank $piggyBank): bool
    {
        $piggyBank->delete();

        return true;
    }

    /**
     * @param int $piggyBankid
     *
     * @return PiggyBank
     */
    public function find(int $piggyBankid): PiggyBank
    {
        $piggyBank = $this->user->piggyBanks()->where('piggy_banks.id', $piggyBankid)->first(['piggy_banks.*']);
        if (null !== $piggyBank) {
            return $piggyBank;
        }

        return new PiggyBank();
    }

    /**
     * Find by name or return NULL.
     *
     * @param string $name
     *
     * @return PiggyBank|null
     */
    public function findByName(string $name): ?PiggyBank
    {
        $set = $this->user->piggyBanks()->get(['piggy_banks.*']);
        /** @var PiggyBank $piggy */
        foreach ($set as $piggy) {
            if ($piggy->name === $name) {
                return $piggy;
            }
        }

        return null;
    }

    /**
     * Get current amount saved in piggy bank.
     *
     * @param PiggyBank $piggyBank
     *
     * @return string
     */
    public function getCurrentAmount(PiggyBank $piggyBank): string
    {
        /** @var PiggyBankRepetition $rep */
        $rep = $piggyBank->piggyBankRepetitions()->first(['piggy_bank_repetitions.*']);
        if (null === $rep) {
            return '0';
        }

        return (string)$rep->currentamount;
    }

    /**
     * @param PiggyBank $piggyBank
     *
     * @return Collection
     */
    public function getEvents(PiggyBank $piggyBank): Collection
    {
        return $piggyBank->piggyBankEvents()->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get();
    }

    /**
     * Used for connecting to a piggy bank.
     *
     * @param PiggyBank           $piggyBank
     * @param PiggyBankRepetition $repetition
     * @param TransactionJournal  $journal
     *
     * @return string
     */
    public function getExactAmount(PiggyBank $piggyBank, PiggyBankRepetition $repetition, TransactionJournal $journal): string
    {
        /** @var JournalRepositoryInterface $repos */
        $repos = app(JournalRepositoryInterface::class);
        $repos->setUser($this->user);

        $amount  = $repos->getJournalTotal($journal);
        $sources = $repos->getJournalSourceAccounts($journal)->pluck('id')->toArray();
        $room    = bcsub((string)$piggyBank->targetamount, (string)$repetition->currentamount);
        $compare = bcmul($repetition->currentamount, '-1');

        Log::debug(sprintf('Will add/remove %f to piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));

        // if piggy account matches source account, the amount is positive
        if (in_array($piggyBank->account_id, $sources)) {
            $amount = bcmul($amount, '-1');
            Log::debug(sprintf('Account #%d is the source, so will remove amount from piggy bank.', $piggyBank->account_id));
        }

        // if the amount is positive, make sure it fits in piggy bank:
        if (1 === bccomp($amount, '0') && bccomp($room, $amount) === -1) {
            // amount is positive and $room is smaller than $amount
            Log::debug(sprintf('Room in piggy bank for extra money is %f', $room));
            Log::debug(sprintf('There is NO room to add %f to piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
            Log::debug(sprintf('New amount is %f', $room));

            return $room;
        }

        // amount is negative and $currentamount is smaller than $amount
        if (bccomp($amount, '0') === -1 && 1 === bccomp($compare, $amount)) {
            Log::debug(sprintf('Max amount to remove is %f', $repetition->currentamount));
            Log::debug(sprintf('Cannot remove %f from piggy bank #%d ("%s")', $amount, $piggyBank->id, $piggyBank->name));
            Log::debug(sprintf('New amount is %f', $compare));

            return $compare;
        }

        return $amount;
    }

    /**
     * @return int
     */
    public function getMaxOrder(): int
    {
        return (int)$this->user->piggyBanks()->max('order');
    }

    /**
     * @return Collection
     */
    public function getPiggyBanks(): Collection
    {
        return $this->user->piggyBanks()->orderBy('order', 'ASC')->get();
    }

    /**
     * Also add amount in name.
     *
     * @return Collection
     */
    public function getPiggyBanksWithAmount(): Collection
    {
        $currency = app('amount')->getDefaultCurrency();
        $set      = $this->getPiggyBanks();
        foreach ($set as $piggy) {
            $currentAmount = $piggy->currentRelevantRep()->currentamount ?? '0';

            $piggy->name = $piggy->name . ' (' . app('amount')->formatAnything($currency, $currentAmount, false) . ')';
        }

        return $set;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param Carbon    $date
     *
     * @return PiggyBankRepetition
     */
    public function getRepetition(PiggyBank $piggyBank, Carbon $date): PiggyBankRepetition
    {
        $repetition = $piggyBank->piggyBankRepetitions()->relevantOnDate($date)->first();
        if (null === $repetition) {
            return new PiggyBankRepetition;
        }

        return $repetition;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $amount
     *
     * @return bool
     */
    public function removeAmount(PiggyBank $piggyBank, string $amount): bool
    {
        $repetition                = $piggyBank->currentRelevantRep();
        $repetition->currentamount = bcsub($repetition->currentamount, $amount);
        $repetition->save();

        // create event
        $this->createEvent($piggyBank, bcmul($amount, '-1'));

        return true;
    }

    /**
     * Set all piggy banks to order 0.
     *
     * @return bool
     */
    public function reset(): bool
    {
        // split query to make it work in sqlite:
        $set = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.id')
                        ->where('accounts.user_id', $this->user->id)->get(['piggy_banks.*']);
        foreach ($set as $e) {
            $e->order = 0;
            $e->save();
        }

        return true;
    }

    /**
     * set id of piggy bank.
     *
     * @param int $piggyBankId
     * @param int $order
     *
     * @return bool
     */
    public function setOrder(int $piggyBankId, int $order): bool
    {
        $piggyBank = PiggyBank::leftJoin('accounts', 'accounts.id', '=', 'piggy_banks.account_id')->where('accounts.user_id', $this->user->id)
                              ->where('piggy_banks.id', $piggyBankId)->first(['piggy_banks.*']);
        if ($piggyBank) {
            $piggyBank->order = $order;
            $piggyBank->save();
        }

        return true;
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
     * @return PiggyBank
     */
    public function store(array $data): PiggyBank
    {
        $data['order'] = $this->getMaxOrder() + 1;
        /** @var PiggyBank $piggyBank */
        $piggyBank = PiggyBank::create($data);

        $this->updateNote($piggyBank, $data['note']);

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param array     $data
     *
     * @return PiggyBank
     */
    public function update(PiggyBank $piggyBank, array $data): PiggyBank
    {
        $piggyBank->name         = $data['name'];
        $piggyBank->account_id   = (int)$data['account_id'];
        $piggyBank->targetamount = round($data['targetamount'], 2);
        $piggyBank->targetdate   = $data['targetdate'];
        $piggyBank->startdate    = $data['startdate'];

        $piggyBank->save();

        $this->updateNote($piggyBank, $data['note']);

        // if the piggy bank is now smaller than the current relevant rep,
        // remove money from the rep.
        $repetition = $piggyBank->currentRelevantRep();
        if ($repetition->currentamount > $piggyBank->targetamount) {
            $diff = bcsub($piggyBank->targetamount, $repetition->currentamount);
            $this->createEvent($piggyBank, $diff);

            $repetition->currentamount = $piggyBank->targetamount;
            $repetition->save();
        }

        return $piggyBank;
    }

    /**
     * @param PiggyBank $piggyBank
     * @param string    $note
     *
     * @return bool
     */
    private function updateNote(PiggyBank $piggyBank, string $note): bool
    {
        if (0 === strlen($note)) {
            $dbNote = $piggyBank->notes()->first();
            if (null !== $dbNote) {
                $dbNote->delete();
            }

            return true;
        }
        $dbNote = $piggyBank->notes()->first();
        if (null === $dbNote) {
            $dbNote = new Note();
            $dbNote->noteable()->associate($piggyBank);
        }
        $dbNote->text = trim($note);
        $dbNote->save();

        return true;
    }
}
