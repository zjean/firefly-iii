<?php
/**
 * TransactionType.php
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

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class TransactionType.
 */
class TransactionType extends Model
{
    use SoftDeletes;

    /**
     *
     */
    public const WITHDRAWAL = 'Withdrawal';
    /**
     *
     */
    public const DEPOSIT = 'Deposit';
    /**
     *
     */
    public const TRANSFER = 'Transfer';
    /**
     *
     */
    public const OPENING_BALANCE = 'Opening balance';
    /**
     *
     */
    public const RECONCILIATION = 'Reconciliation';
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
        ];
    /** @var array */
    protected $fillable = ['type'];

    /**
     * @param string $type
     *
     * @return Model|null|static
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public static function routeBinder(string $type): TransactionType
    {
        if (!auth()->check()) {
            throw new NotFoundHttpException();
        }
        $transactionType = self::where('type', ucfirst($type))->first();
        if (null !== $transactionType) {
            return $transactionType;
        }
        throw new NotFoundHttpException();
    }

    /**
     * @codeCoverageIgnore
     * @return bool
     */
    public function isDeposit(): bool
    {
        return self::DEPOSIT === $this->type;
    }

    /**
     * @codeCoverageIgnore
     * @return bool
     */
    public function isOpeningBalance(): bool
    {
        return self::OPENING_BALANCE === $this->type;
    }

    /**
     * @codeCoverageIgnore
     * @return bool
     */
    public function isTransfer(): bool
    {
        return self::TRANSFER === $this->type;
    }

    /**
     * @codeCoverageIgnore
     * @return bool
     */
    public function isWithdrawal(): bool
    {
        return self::WITHDRAWAL === $this->type;
    }

    /**
     * @codeCoverageIgnore
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionJournals()
    {
        return $this->hasMany('FireflyIII\Models\TransactionJournal');
    }
}
