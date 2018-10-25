<?php
/**
 * LinkType.php
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
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @property int    $journalCount
 * @property string $inward
 * @property string $outward
 * @property string $name
 * @property bool   $editable
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property int    $id
 * Class LinkType
 *
 */
class LinkType extends Model
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
            'editable'   => 'boolean',
        ];

    /** @var array Fields that can be filled */
    protected $fillable = ['name', 'inward', 'outward', 'editable'];

    /**
     * Route binder. Converts the key in the URL to the specified object (or throw 404).
     *
     * @param $value
     *
     * @return LinkType
     *
     * @throws NotFoundHttpException
     */
    public static function routeBinder(string $value): LinkType
    {
        if (auth()->check()) {
            $linkTypeId = (int)$value;
            $linkType   = self::find($linkTypeId);
            if (null !== $linkType) {
                return $linkType;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * @codeCoverageIgnore
     * @return HasMany
     */
    public function transactionJournalLinks(): HasMany
    {
        return $this->hasMany(TransactionJournalLink::class);
    }
}
