<?php
/**
 * Tag.php
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
use FireflyIII\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Tag.
 *
 * @property Collection     $transactionJournals
 * @property string         $tag
 * @property int            $id
 * @property \Carbon\Carbon $date
 * @property int            zoomLevel
 * @property float          longitude
 * @property float          latitude
 * @property string         description
 * @property string         amount_sum
 * @property string         tagMode
 */
class Tag extends Model
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
            'date'       => 'date',
            'zoomLevel'  => 'int',
        ];
    /** @var array Fields that can be filled */
    protected $fillable = ['user_id', 'tag', 'date', 'description', 'longitude', 'latitude', 'zoomLevel', 'tagMode'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param string $value
     *
     * @return Tag
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): Tag
    {
        if (auth()->check()) {
            $tagId = (int)$value;
            /** @var User $user */
            $user = auth()->user();
            /** @var Tag $tag */
            $tag = $user->tags()->find($tagId);
            if (null !== $tag) {
                return $tag;
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
    public function getDescriptionAttribute($value): ?string
    {
        if (null === $value) {
            return $value;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @return string|null
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function getTagAttribute($value): ?string
    {
        if (null === $value) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setDescriptionAttribute($value): void
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param $value
     *
     * @throws \Illuminate\Contracts\Encryption\EncryptException
     */
    public function setTagAttribute($value): void
    {
        $this->attributes['tag'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsToMany
     */
    public function transactionJournals(): BelongsToMany
    {
        return $this->belongsToMany(TransactionJournal::class);
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
