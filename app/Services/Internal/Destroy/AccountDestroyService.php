<?php
/**
 * AccountDestroyService.php
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

namespace FireflyIII\Services\Internal\Destroy;

use DB;
use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\PiggyBank;
use FireflyIII\Models\RecurrenceTransaction;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournal;
use Illuminate\Database\Eloquent\Builder;
use Log;

/**
 * Class AccountDestroyService
 */
class AccountDestroyService
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * @param Account      $account
     * @param Account|null $moveTo
     *
     * @return void
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function destroy(Account $account, ?Account $moveTo): void
    {

        if (null !== $moveTo) {
            DB::table('transactions')->where('account_id', $account->id)->update(['account_id' => $moveTo->id]);

            // also update recurring transactions:
            DB::table('recurrences_transactions')->where('source_id', $account->id)->update(['source_id' =>  $moveTo->id]);
            DB::table('recurrences_transactions')->where('destination_id', $account->id)->update(['destination_id' => $moveTo->id]);
        }
        $service = app(JournalDestroyService::class);

        Log::debug('Now trigger account delete response #' . $account->id);
        /** @var Transaction $transaction */
        foreach ($account->transactions()->get() as $transaction) {
            Log::debug('Now at transaction #' . $transaction->id);
            /** @var TransactionJournal $journal */
            $journal = $transaction->transactionJournal()->first();
            if (null !== $journal) {
                Log::debug('Call for deletion of journal #' . $journal->id);
                /** @var JournalDestroyService $service */

                $service->destroy($journal);
            }
        }

        // delete recurring transactions with this account:
        if (null === $moveTo) {
            $recurrences = RecurrenceTransaction::
            where(
                function (Builder $q) use ($account) {
                    $q->where('source_id', $account->id);
                    $q->orWhere('destination_id', $account->id);
                }
            )->get(['recurrence_id'])->pluck('recurrence_id')->toArray();


            $destroyService = new RecurrenceDestroyService();
            foreach ($recurrences as $recurrenceId) {
                $destroyService->destroyById((int)$recurrenceId);
            }
        }

        // delete piggy banks:
        PiggyBank::where('account_id', $account->id)->delete();

        try {
            $account->delete();
        } catch (Exception $e) { // @codeCoverageIgnore
            Log::error(sprintf('Could not delete account: %s', $e->getMessage())); // @codeCoverageIgnore
        }
    }

}
