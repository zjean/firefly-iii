<?php
/**
 * Account.php
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
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Account.
 *
 * @property int         $id
 * @property string      $name
 * @property string      $iban
 * @property AccountType $accountType
 * @property bool        $active
 * @property string      $virtual_balance
 * @property User        $user
 * @property string      startBalance
 * @property string      endBalance
 * @property string      difference
 * @property Carbon      lastActivityDate
 * @property Collection  accountMeta
 * @property bool        encrypted
 * @property int         account_type_id
 * @property Collection  piggyBanks
 * @property string      $interest
 * @property string      $interestPeriod
 * @property string      accountTypeString
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Account extends Model
{
    use SoftDeletes;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts
        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'active'     => 'boolean',
            'encrypted'  => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'account_type_id', 'name', 'active', 'virtual_balance', 'iban'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];
    /** @var bool */
    private $joinedAccountTypes;

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Account
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Account
    {
        if (auth()->check()) {
            $accountId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Account $account */
            $account = $user->accounts()->find($accountId);
            if (null !== $account) {
                return $account;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function accountMeta(): HasMany
    {
        return $this->hasMany(AccountMeta::class);
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function accountType(): BelongsTo
    {
        return $this->belongsTo(AccountType::class);
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getEditNameAttribute(): string
    {
        $name = $this->name;

        if (AccountType::CASH === $this->accountType->type) {
            return '';
        }

        return $name;
    }

    /**
     * @param $value
     *
     * @return string
     *
     * @throws FireflyException
     */
    public function getIbanAttribute($value): string
    {
        if ('' === (string)$value) {
            return '';
        }
        try {
            $result = Crypt::decrypt($value);
        } catch (DecryptException $e) {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
            throw new FireflyException('Cannot decrypt value "' . $value . '" for account #' . $this->id);
        }
        if (null === $result) {
            return '';
        }

        return $result;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getNameAttribute($value): ?string
    {
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
    }

    /**
     * Returns the opening balance.
     *
     * @return TransactionJournal
     */
    public function getOpeningBalance(): TransactionJournal
    {
        $journal = TransactionJournal::leftJoin('transactions', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                     ->where('transactions.account_id', $this->id)
                                     ->transactionTypes([TransactionType::OPENING_BALANCE])
                                     ->first(['transaction_journals.*']);
        if (null === $journal) {
            return new TransactionJournal;
        }

        return $journal;
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
    public function piggyBanks(): HasMany
    {
        return $this->hasMany(PiggyBank::class);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param EloquentBuilder $query
     * @param array           $types
     */
    public function scopeAccountTypeIn(EloquentBuilder $query, array $types): void
    {
        if (null === $this->joinedAccountTypes) {
            $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $this->joinedAccountTypes = true;
        }
        $query->whereIn('account_types.type', $types);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setIbanAttribute($value): void
    {
        $this->attributes['iban'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setNameAttribute($value): void
    {
        $encrypt                       = config('firefly.encryption');
        $this->attributes['name']      = $encrypt ? Crypt::encrypt($value) : $value;
        $this->attributes['encrypted'] = $encrypt;
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @codeCoverageIgnore
     */
    public function setVirtualBalanceAttribute($value): void
    {
        $this->attributes['virtual_balance'] = (string)$value;
    }

    /**
     * @return HasMany
     * @codeCoverageIgnore
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
