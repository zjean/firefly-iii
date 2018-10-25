<?php
/**
 * DecryptAttachment.php
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

/** @noinspection MultipleReturnStatementsInspection */

declare(strict_types=1);

namespace FireflyIII\Console\Commands;

use FireflyIII\Repositories\Attachment\AttachmentRepositoryInterface;
use Illuminate\Console\Command;
use Log;

/**
 * Class DecryptAttachment.
 *
 * @codeCoverageIgnore
 */
class DecryptAttachment extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Decrypts an attachment and dumps the content in a file in the given directory.';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature
        = 'firefly:decrypt-attachment {id:The ID of the attachment.} {name:The file name of the attachment.} 
    {directory:Where the file must be stored.}';

    /**
     * Execute the console command.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return int
     */
    public function handle(): int
    {
        /** @var AttachmentRepositoryInterface $repository */
        $repository     = app(AttachmentRepositoryInterface::class);
        $attachmentId   = (int)$this->argument('id');
        $attachment     = $repository->findWithoutUser($attachmentId);
        $attachmentName = trim((string)$this->argument('name'));
        $storagePath    = realpath(trim((string)$this->argument('directory')));
        if (null === $attachment) {
            $this->error(sprintf('No attachment with id #%d', $attachmentId));
            Log::error(sprintf('DecryptAttachment: No attachment with id #%d', $attachmentId));

            return 1;
        }

        if ($attachmentName !== $attachment->filename) {
            $this->error('File name does not match.');
            Log::error('DecryptAttachment: File name does not match.');

            return 1;
        }

        if (!is_dir($storagePath)) {
            $this->error(sprintf('Path "%s" is not a directory.', $storagePath));
            Log::error(sprintf('DecryptAttachment: Path "%s" is not a directory.', $storagePath));

            return 1;
        }

        if (!is_writable($storagePath)) {
            $this->error(sprintf('Path "%s" is not writable.', $storagePath));
            Log::error(sprintf('DecryptAttachment: Path "%s" is not writable.', $storagePath));

            return 1;
        }

        $fullPath = $storagePath . DIRECTORY_SEPARATOR . $attachment->filename;
        $content  = $repository->getContent($attachment);
        $this->line(sprintf('Going to write content for attachment #%d into file "%s"', $attachment->id, $fullPath));
        $result = file_put_contents($fullPath, $content);
        if (false === $result) {
            $this->error('Could not write to file.');

            return 1;
        }
        $this->info(sprintf('%d bytes written. Exiting now..', $result));

        return 0;
    }
}
