<?php
/**
 * Category.php
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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Category.
 *
 * @property string      $name
 * @property int         $id
 * @property float       $spent // used in category reports
 * @property Carbon|null lastActivity
 * @property bool        encrypted
 */
class Category extends Model
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
            'encrypted'  => 'boolean',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'name'];
    /** @var array Hidden from view */
    protected $hidden = ['encrypted'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Category
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Category
    {
        if (auth()->check()) {
            $categoryId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Category $category */
            $category = $user->categories()->find($categoryId);
            if (null !== $category) {
                return $category;
            }
        }
        throw new NotFoundHttpException;
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
        if ($this->encrypted) {
            return Crypt::decrypt($value);
        }

        return $value;
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
     * @return BelongsToMany
     */
    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class, 'category_transaction_journal', 'category_id');
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'category_transaction', 'category_id');
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
