<?php
/**
 * Bill.php
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

use Carbon\Carbon;
use Crypt;
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Bill.
 *
 * @property bool                $active
 * @property int                 $transaction_currency_id
 * @property string              $amount_min
 * @property string              $amount_max
 * @property int                 $id
 * @property string              $name
 * @property Collection          $notes
 * @property TransactionCurrency $transactionCurrency
 * @property Carbon              $created_at
 * @property Carbon              $updated_at
 * @property Carbon              $date
 * @property string              $repeat_freq
 * @property int                 $skip
 * @property bool                $automatch
 * @property User                $user
 * @property string              $match
 * @property bool                match_encrypted
 * @property bool                name_encrypted
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Bill extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'      => 'datetime',
            'updated_at'      => 'datetime',
            'deleted_at'      => 'datetime',
            'date'            => 'date',
            'skip'            => 'int',
            'automatch'       => 'boolean',
            'active'          => 'boolean',
            'name_encrypted'  => 'boolean',
            'match_encrypted' => 'boolean',
        ];

    /** @var array Fields that can be filled */
    protected $fillable
        = ['name', 'match', 'amount_min', 'match_encrypted', 'name_encrypted', 'user_id', 'amount_max', 'date', 'repeat_freq', 'skip',
           'automatch', 'active', 'transaction_currency_id'];
    /** @var array Hidden from view */
    protected $hidden = ['amount_min_encrypted', 'amount_max_encrypted', 'name_encrypted', 'match_encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Bill
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Bill
    {
        if (auth()->check()) {
            $billId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Bill $bill */
            $bill = $user->bills()->find($billId);
            if (null !== $bill) {
                return $bill;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return MorphMany
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getMatchAttribute($value): string
    {
        if (1 === (int)$this->match_encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string|null
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getNameAttribute($value): ?string
    {
        if (1 === (int)$this->name_encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * @codeCoverageIgnore
     * Get all of the notes.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     */
    public function setAmountMaxAttribute($value): void
    {
        $this->attributes['amount_max'] = (string)$value;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setAmountMinAttribute($value): void
    {
        $this->attributes['amount_min'] = (string)$value;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setMatchAttribute($value): void
    {
        $encrypt                             = config('firefly.encryption');
        $this->attributes['match']           = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['match_encrypted'] = $encrypt;
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setNameAttribute($value): void
    {
        $encrypt                            = config('firefly.encryption');
        $this->attributes['name']           = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['name_encrypted'] = $encrypt;
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function transactionCurrency(): BelongsTo
    {
        return $this->belongsTo(TransactionCurrency::class);
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournals(): HasMany
    {
        return $this->hasMany(TransactionJournal::class);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
