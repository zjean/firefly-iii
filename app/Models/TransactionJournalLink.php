<?php
/**
 * TransactionJournalLink.php
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

namespace FireflyIII\Models;

use Crypt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionJournalLink.
 */
class TransactionJournalLink extends Model
{
    /**
     * @var string
     */
    protected $table = 'journal_links';

    /**
     * @param string $value
     *
     * @return mixed
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): TransactionJournalLink
    {
        if (auth()->check()) {
            $linkId = (int)$value;
            $link   = self::where('journal_links.id', $linkId)
                          ->leftJoin('transaction_journals as t_a', 't_a.id', '=', 'source_id')
                          ->leftJoin('transaction_journals as t_b', 't_b.id', '=', 'destination_id')
                          ->where('t_a.user_id', auth()->user()->id)
                          ->where('t_b.user_id', auth()->user()->id)
                          ->first(['journal_links.*']);
            if (null !== $link) {
                return $link;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function destination()
    {
        return $this->belongsTo(TransactionJournal::class, 'destination_id');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return null|string
     */
    public function getCommentAttribute($value): ?string
    {
        if (null !== $value) {
            return app('steam')->tryDecrypt($value);
        }

        return null;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function linkType(): BelongsTo
    {
        return $this->belongsTo(LinkType::class);
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setCommentAttribute($value): void
    {
        if (null !== $value && strlen($value) > 0) {
            $this->attributes['comment'] = Crypt::encrypt($value);

            return;
        }
        $this->attributes['comment'] = null;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function source()
    {
        return $this->belongsTo(TransactionJournal::class, 'source_id');
    }
}
