<?php
/**
 * RecurrenceMeta.php
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


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class RecurrenceTransactionMeta
 *
 * @property string $name
 * @property string $value
 */
class RecurrenceTransactionMeta extends Model
{
    use SoftDeletes;
    /** @var array */
    protected $casts
                        = [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
            'name'       => 'string',
            'value'      => 'string',
        ];
    protected $fillable = ['rt_id', 'name', 'value'];
    /** @var string */
    protected $table = 'rt_meta';

    /**
     * @return BelongsTo
     * @codeCoverageIgnore
     */
    public function recurrenceTransaction(): BelongsTo
    {
        return $this->belongsTo(RecurrenceTransaction::class, 'rt_id');
    }

}