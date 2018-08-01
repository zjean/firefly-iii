<?php
/**
 * AttachmentHelperInterface.php
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

namespace FireflyIII\Helpers\Attachments;

use FireflyIII\Models\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

/**
 * Interface AttachmentHelperInterface.
 */
interface AttachmentHelperInterface
{
    /**
     * Get content of an attachment.
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentContent(Attachment $attachment): string;

    /**
     * Get the location of an attachment.
     *
     * @param Attachment $attachment
     *
     * @return string
     */
    public function getAttachmentLocation(Attachment $attachment): string;

    /**
     * Get all attachments.
     *
     * @return Collection
     */
    public function getAttachments(): Collection;

    /**
     * Get all errors.
     *
     * @return MessageBag
     */
    public function getErrors(): MessageBag;

    /**
     * Get all messages/
     *
     * @return MessageBag
     */
    public function getMessages(): MessageBag;

    /**
     * Uploads a file as a string.
     *
     * @param Attachment $attachment
     * @param string     $content
     *
     * @return bool
     */
    public function saveAttachmentFromApi(Attachment $attachment, string $content): bool;

    /**
     * Save attachments that got uploaded.
     *
     * @param Model      $model
     * @param null|array $files
     *
     * @return bool
     */
    public function saveAttachmentsForModel(Model $model, ?array $files): bool;
}
