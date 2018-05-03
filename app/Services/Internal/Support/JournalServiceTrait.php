<?php
/**
 * JournalServiceTrait.php
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

namespace FireflyIII\Services\Internal\Support;

use FireflyIII\Factory\BillFactory;
use FireflyIII\Factory\TagFactory;
use FireflyIII\Factory\TransactionJournalMetaFactory;
use FireflyIII\Models\Note;
use FireflyIII\Models\TransactionJournal;

/**
 * Trait JournalServiceTrait
 *
 * @package FireflyIII\Services\Internal\Support
 */
trait JournalServiceTrait
{

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     */
    public function connectTags(TransactionJournal $journal, array $data): void
    {
        /** @var TagFactory $factory */
        $factory = app(TagFactory::class);
        $factory->setUser($journal->user);
        $set = [];
        if (!is_array($data['tags'])) {
            return; // @codeCoverageIgnore
        }
        foreach ($data['tags'] as $string) {
            if (\strlen($string) > 0) {
                $tag   = $factory->findOrCreate($string);
                $set[] = $tag->id;
            }
        }
        $journal->tags()->sync($set);
    }

    /**
     * Connect bill if present.
     *
     * @param TransactionJournal $journal
     * @param array              $data
     */
    protected function connectBill(TransactionJournal $journal, array $data): void
    {
        /** @var BillFactory $factory */
        $factory = app(BillFactory::class);
        $factory->setUser($journal->user);
        $bill = $factory->find($data['bill_id'], $data['bill_name']);

        if (null !== $bill) {
            $journal->bill_id = $bill->id;
            $journal->save();

            return;
        }
        $journal->bill_id = null;
        $journal->save();

        return;
    }

    /**
     * @param TransactionJournal $journal
     * @param array              $data
     * @param string             $field
     */
    protected function storeMeta(TransactionJournal $journal, array $data, string $field): void
    {
        if (!isset($data[$field])) {
            return;
        }
        $set = [
            'journal' => $journal,
            'name'    => $field,
            'data'    => $data[$field],
        ];
        /** @var TransactionJournalMetaFactory $factory */
        $factory = app(TransactionJournalMetaFactory::class);
        $factory->updateOrCreate($set);
    }

    /**
     * @param TransactionJournal $journal
     * @param string             $notes
     */
    protected function storeNote(TransactionJournal $journal, ?string $notes): void
    {
        $notes = (string)$notes;
        if (strlen($notes) > 0) {
            $note = $journal->notes()->first();
            if (null === $note) {
                $note = new Note;
                $note->noteable()->associate($journal);
            }
            $note->text = $notes;
            $note->save();

            return;
        }
        $note = $journal->notes()->first();
        if (null !== $note) {
            $note->delete();
        }

        return;

    }
}
