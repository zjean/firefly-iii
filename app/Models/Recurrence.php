<?php
/**
 * Recurrence.php
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

namespace FireflyIII\Models;


use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Recurrence
 *
 * @property int                                $id
 * @property \Carbon\Carbon                     $created_at
 * @property \Carbon\Carbon                     $updated_at
 * @property int                                $user_id
 * @property int                                $transaction_type_id
 * @property int                                $transaction_currency_id
 * @property string                             $title
 * @property string                             $description
 * @property \Carbon\Carbon                     $first_date
 * @property \Carbon\Carbon                     $repeat_until
 * @property \Carbon\Carbon                     $latest_date
 * @property string                             $repetition_type
 * @property string                             $repetition_moment
 * @property int                                $repetition_skip
 * @property int                                $repetitions
 * @property bool                               $active
 * @property bool                               $apply_rules
 * @property \FireflyIII\User                   $user
 * @property \Illuminate\Support\Collection     $recurrenceRepetitions
 * @property \Illuminate\Support\Collection     $recurrenceMeta
 * @property \Illuminate\Support\Collection     $recurrenceTransactions
 * @property \FireflyIII\Models\TransactionType $transactionType
 *
 */
class Recurrence extends Model
{
    use SoftDeletes;
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at'   => 'datetime',
            'updated_at'   => 'datetime',
            'deleted_at'   => 'datetime',
            'title'        => 'string',
            'id'           => 'int',
            'description'  => 'string',
            'first_date'   => 'date',
            'repeat_until' => 'date',
            'latest_date'  => 'date',
            'repetitions'  => 'int',
            'active'       => 'bool',
            'apply_rules'  => 'bool',
        ];
    /** @var array Fields that can be filled */
    protected $fillable
        = ['user_id', 'transaction_type_id', 'title', 'description', 'first_date', 'repeat_until', 'latest_date', 'repetitions', 'apply_rules', 'active'];
    /** @var string The table to store the data in */
    protected $table = 'recurrences';

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Recurrence
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Recurrence
    {
        if (auth()->check()) {
            $recurrenceId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Recurrence $recurrence */
            $recurrence = $user->recurrences()->find($recurrenceId);
            if (null !== $recurrence) {
                return $recurrence;
            }
        }
        throw new NotFoundHttpException;
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
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function recurrenceMeta(): HasMany
    {
        return $this->hasMany(RecurrenceMeta::class);
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function recurrenceRepetitions(): HasMany
    {
        return $this->hasMany(RecurrenceRepetition::class);
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function recurrenceTransactions(): HasMany
    {
        return $this->hasMany(RecurrenceTransaction::class);
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
     * @return BelongsTo
     */
    public function transactionType(): BelongsTo
    {
        return $this->belongsTo(TransactionType::class);
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
