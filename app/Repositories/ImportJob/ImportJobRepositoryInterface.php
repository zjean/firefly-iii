<?php
/**
 * ImportJobRepositoryInterface.php
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

namespace FireflyIII\Repositories\ImportJob;

use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\User;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Interface ImportJobRepositoryInterface.
 */
interface ImportJobRepositoryInterface
{
    /**
     * Add message to job.
     *
     * @param ImportJob $job
     * @param string    $error
     *
     * @return ImportJob
     */
    public function addErrorMessage(ImportJob $job, string $error): ImportJob;

    /**
     * Append transactions to array instead of replacing them.
     *
     * @param ImportJob $job
     * @param array     $transactions
     *
     * @return ImportJob
     */
    public function appendTransactions(ImportJob $job, array $transactions): ImportJob;

    /**
     * @param string $importProvider
     *
     * @return ImportJob
     */
    public function create(string $importProvider): ImportJob;

    /**
     * @param string $key
     *
     * @return ImportJob|null
     */
    public function findByKey(string $key): ?ImportJob;

    /**
     * Return all attachments for job.
     *
     * @param ImportJob $job
     *
     * @return Collection
     */
    public function getAttachments(ImportJob $job): Collection;

    /**
     * Return configuration of job.
     *
     * @param ImportJob $job
     *
     * @return array
     */
    public function getConfiguration(ImportJob $job): array;

    /**
     * Return extended status of job.
     *
     * @param ImportJob $job
     *
     * @return array
     */
    public function getExtendedStatus(ImportJob $job): array;

    /**
     * @param ImportJob $job
     * @param array     $configuration
     *
     * @return ImportJob
     */
    public function setConfiguration(ImportJob $job, array $configuration): ImportJob;

    /**
     * @param ImportJob $job
     * @param string    $stage
     *
     * @return ImportJob
     */
    public function setStage(ImportJob $job, string $stage): ImportJob;

    /**
     * @param ImportJob $job
     * @param string    $status
     *
     * @return ImportJob
     */
    public function setStatus(ImportJob $job, string $status): ImportJob;

    /**
     * @param ImportJob $job
     * @param Tag       $tag
     *
     * @return ImportJob
     */
    public function setTag(ImportJob $job, Tag $tag): ImportJob;

    /**
     * @param ImportJob $job
     * @param array     $transactions
     *
     * @return ImportJob
     */
    public function setTransactions(ImportJob $job, array $transactions): ImportJob;

    /**
     * @param User $user
     */
    public function setUser(User $user);

    /**
     * Store file.
     *
     * @param ImportJob $job
     * @param string    $name
     * @param string    $fileName
     *
     * @return MessageBag
     */
    public function storeCLIUpload(ImportJob $job, string $name, string $fileName): MessageBag;

    /**
     * Handle upload for job.
     *
     * @param ImportJob    $job
     * @param string       $name
     * @param UploadedFile $file
     *
     * @return MessageBag
     * @throws FireflyException
     */
    public function storeFileUpload(ImportJob $job, string $name, UploadedFile $file): MessageBag;


}
