<?php
/**
 * Attachment.php
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
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Attachment.
 */
class Attachment extends Model
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
            'uploaded'   => 'boolean',
        ];
    /** @var array */
    protected $fillable = ['attachable_id', 'attachable_type', 'user_id', 'md5', 'filename', 'mime', 'title', 'notes', 'description', 'size', 'uploaded'];

    /**
     * @param string $value
     *
     * @return Attachment
     */
    public static function routeBinder(string $value): Attachment
    {
        if (auth()->check()) {
            $attachmentId = intval($value);
            $attachment   = auth()->user()->attachments()->find($attachmentId);
            if (!is_null($attachment)) {
                return $attachment;
            }
        }
        throw new NotFoundHttpException;
    }

    /**
     * Get all of the owning attachable models.
     *
     * @codeCoverageIgnore
     *
     * @return MorphTo
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Returns the expected filename for this attachment.
     *
     * @codeCoverageIgnore
     * @return string
     */
    public function fileName(): string
    {
        return sprintf('at-%s.data', strval($this->id));
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getDescriptionAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getFilenameAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getMimeAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getNotesAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @param $value
     *
     * @codeCoverageIgnore
     * @return null|string
     */
    public function getTitleAttribute($value)
    {
        if (null === $value || 0 === strlen($value)) {
            return null;
        }

        return Crypt::decrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     */
    public function setDescriptionAttribute(string $value)
    {
        $this->attributes['description'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     */
    public function setFilenameAttribute(string $value)
    {
        $this->attributes['filename'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     */
    public function setMimeAttribute(string $value)
    {
        $this->attributes['mime'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     */
    public function setNotesAttribute(string $value)
    {
        $this->attributes['notes'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param string $value
     */
    public function setTitleAttribute(string $value)
    {
        $this->attributes['title'] = Crypt::encrypt($value);
    }

    /**
     * @codeCoverageIgnore
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo('FireflyIII\User');
    }
}
