<?php

/**
 * StoredTransactionJournal.php
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

namespace FireflyIII\Events;

use FireflyIII\Models\TransactionJournal;
use Illuminate\Queue\SerializesModels;

/**
 * Class StoredTransactionJournal.
 *
 * @codeCoverageIgnore
 */
class StoredTransactionJournal extends Event
{
    use SerializesModels;

    /** @var TransactionJournal The journal that was stored. */
    public $journal;
    /** @var int The piggy bank ID. */
    public $piggyBankId;

    /**
     * Create a new event instance.
     *
     * @param TransactionJournal $journal
     * @param int                $piggyBankId
     */
    public function __construct(TransactionJournal $journal, int $piggyBankId)
    {
        $this->journal     = $journal;
        $this->piggyBankId = $piggyBankId;
    }
}
