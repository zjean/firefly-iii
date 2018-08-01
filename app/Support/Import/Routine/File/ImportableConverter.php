<?php
/**
 * ImportableConverter.php
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

namespace FireflyIII\Support\Import\Routine\File;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidDateException;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\TransactionCurrency;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Support\Import\Placeholder\ImportTransaction;
use InvalidArgumentException;
use Log;

/**
 * Class ImportableConverter
 */
class ImportableConverter
{
    /** @var AccountRepositoryInterface */
    private $accountRepository;
    /** @var AssetAccountMapper */
    private $assetMapper;
    /** @var array */
    private $config;
    /** @var CurrencyMapper */
    private $currencyMapper;
    /** @var TransactionCurrency */
    private $defaultCurrency;
    /** @var ImportJob */
    private $importJob;
    /** @var array */
    private $mappedValues;
    /** @var OpposingAccountMapper */
    private $opposingMapper;
    /** @var ImportJobRepositoryInterface */
    private $repository;

    /**
     * Convert ImportTransaction to factory-compatible array.
     *
     * @param array $importables
     *
     * @return array
     */
    public function convert(array $importables): array
    {
        $total = \count($importables);
        Log::debug(sprintf('Going to convert %d import transactions', $total));
        $result = [];
        /** @var ImportTransaction $importable */
        foreach ($importables as $index => $importable) {
            Log::debug(sprintf('Now going to parse importable %d of %d', $index + 1, $total));
            try {
                $entry = $this->convertSingle($importable);
            } catch (FireflyException $e) {
                $this->repository->addErrorMessage($this->importJob, sprintf('Row #%d: %s', $index + 1, $e->getMessage()));
                continue;
            }
            if (null !== $entry) {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * @param ImportJob $importJob
     */
    public function setImportJob(ImportJob $importJob): void
    {
        $this->importJob = $importJob;
        $this->config    = $importJob->configuration;

        // repository is used for error messages
        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);

        // asset account mapper can map asset accounts (makes sense right?)
        $this->assetMapper = app(AssetAccountMapper::class);
        $this->assetMapper->setUser($importJob->user);
        $this->assetMapper->setDefaultAccount($this->config['import-account'] ?? 0);

        // asset account repository is used for currency information
        $this->accountRepository = app(AccountRepositoryInterface::class);
        $this->accountRepository->setUser($importJob->user);

        // opposing account mapper:
        $this->opposingMapper = app(OpposingAccountMapper::class);
        $this->opposingMapper->setUser($importJob->user);

        // currency mapper:
        $this->currencyMapper = app(CurrencyMapper::class);
        $this->currencyMapper->setUser($importJob->user);
        $this->defaultCurrency = app('amount')->getDefaultCurrencyByUser($importJob->user);
    }

    /**
     * @codeCoverageIgnore
     *
     * @param array $mappedValues
     */
    public function setMappedValues(array $mappedValues): void
    {
        $this->mappedValues = $mappedValues;
    }

    /**
     * @param string|null $date
     *
     * @return string
     */
    private function convertDateValue(string $date = null): string
    {
        $result = null;
        if (null !== $date) {
            try {
                $object = Carbon::createFromFormat($this->config['date-format'] ?? 'Ymd', $date);
                $result = $object->format('Y-m-d');
            } catch (InvalidDateException|InvalidArgumentException $e) {
                Log::error($e->getMessage());
                Log::error($e->getTraceAsString());
            }
        }
        if (null === $result) {
            $object = new Carbon;
            $result = $object->format('Y-m-d');
        }

        return $result;
    }

    /**
     * @param ImportTransaction $importable
     *
     * @throws FireflyException
     * @return array
     */
    private function convertSingle(ImportTransaction $importable): array
    {
        Log::debug(sprintf('Description is: "%s"', $importable->description));
        $foreignAmount = $importable->calculateForeignAmount();
        $amount        = $importable->calculateAmount();

        if ('' === $amount) {
            $amount = $foreignAmount;
        }
        if ('' === $amount) {
            throw new FireflyException('No transaction amount information.');
        }

        $source          = $this->assetMapper->map($importable->accountId, $importable->getAccountData());
        $destination     = $this->opposingMapper->map($importable->opposingId, $amount, $importable->getOpposingAccountData());
        $currency        = $this->currencyMapper->map($importable->currencyId, $importable->getCurrencyData());
        $foreignCurrency = $this->currencyMapper->map($importable->foreignCurrencyId, $importable->getForeignCurrencyData());

        Log::debug(sprintf('"%s" (#%d) is source and "%s" (#%d) is destination.', $source->name, $source->id, $destination->name, $destination->id));


        // amount is positive? Then switch:
        if (1 === bccomp($amount, '0')) {

            [$destination, $source] = [$source, $destination];
            Log::debug(
                sprintf(
                    '%s is positive, so "%s" (#%d) is now source and "%s" (#%d) is now destination.',
                    $amount, $source->name, $source->id, $destination->name, $destination->id
                )
            );
        }

        if ($destination->id === $source->id) {
            throw new FireflyException(
                sprintf(
                    'Source ("%s", #%d) and destination ("%s", #%d) are the same account.', $source->name, $source->id, $destination->name, $destination->id
                )
            );
        }

        $transactionType = $this->getTransactionType($source->accountType->type, $destination->accountType->type);
        $currency        = $currency ?? $this->getCurrency($source, $destination);

        if ('unknown' === $transactionType) {
            $message = sprintf(
                'Cannot determine transaction type. Source account is a %s, destination is a %s', $source->accountType->type, $destination->accountType->type
            );
            Log::error($message, ['source' => $source->toArray(), 'dest' => $destination->toArray()]);
            throw new FireflyException($message);
        }

        return [
            'type'               => $transactionType,
            'date'               => $this->convertDateValue($importable->date),
            'tags'               => $importable->tags,
            'user'               => $this->importJob->user_id,
            'notes'              => $importable->note,

            // all custom fields:
            'internal_reference' => $importable->meta['internal-reference'] ?? null,
            'sepa-cc'            => $importable->meta['sepa-cc'] ?? null,
            'sepa-ct-op'         => $importable->meta['sepa-ct-op'] ?? null,
            'sepa-ct-id'         => $importable->meta['sepa-ct-id'] ?? null,
            'sepa-db'            => $importable->meta['sepa-db'] ?? null,
            'sepa-country'       => $importable->meta['sepa-country'] ?? null,
            'sepa-ep'            => $importable->meta['sepa-ep'] ?? null,
            'sepa-ci'            => $importable->meta['sepa-ci'] ?? null,
            'sepa-batch-id'      => $importable->meta['sepa-batch-id'] ?? null,
            'interest_date'      => $this->convertDateValue($importable->meta['date-interest'] ?? null),
            'book_date'          => $this->convertDateValue($importable->meta['date-book'] ?? null),
            'process_date'       => $this->convertDateValue($importable->meta['date-process'] ?? null),
            'due_date'           => $this->convertDateValue($importable->meta['date-due'] ?? null),
            'payment_date'       => $this->convertDateValue($importable->meta['date-payment'] ?? null),
            'invoice_date'       => $this->convertDateValue($importable->meta['date-invoice'] ?? null),
            'external_id'        => $importable->externalId,

            // journal data:
            'description'        => $importable->description,
            'piggy_bank_id'      => null,
            'piggy_bank_name'    => null,
            'bill_id'            => $importable->billId,
            'bill_name'          => $importable->billName,

            // transaction data:
            'transactions'       => [
                [
                    'currency_id'           => $currency->id,
                    'currency_code'         => null,
                    'description'           => null,
                    'amount'                => $amount,
                    'budget_id'             => $importable->budgetId,
                    'budget_name'           => $importable->budgetName,
                    'category_id'           => $importable->categoryId,
                    'category_name'         => $importable->categoryName,
                    'source_id'             => $source->id,
                    'source_name'           => null,
                    'destination_id'        => $destination->id,
                    'destination_name'      => null,
                    'foreign_currency_id'   => $importable->foreignCurrencyId,
                    'foreign_currency_code' => null === $foreignCurrency ? null : $foreignCurrency->code,
                    'foreign_amount'        => $foreignAmount,
                    'reconciled'            => false,
                    'identifier'            => 0,
                ],
            ],
        ];
    }

    /**
     * @param Account $source
     * @param Account $destination
     *
     * @return TransactionCurrency
     */
    private function getCurrency(Account $source, Account $destination): TransactionCurrency
    {
        $currency = null;
        if ($destination->accountType->type === AccountType::ASSET) {
            // destination is asset, might have currency preference:
            $destinationCurrencyId = (int)$this->accountRepository->getMetaValue($destination, 'currency_id');
            $currency              = 0 === $destinationCurrencyId ? $this->defaultCurrency : $this->currencyMapper->map($destinationCurrencyId, []);
            Log::debug(sprintf('Destination is an asset account, and has currency preference %s', $currency->code));
        }

        if ($source->accountType->type === AccountType::ASSET) {
            // source is asset, might have currency preference:
            $sourceCurrencyId = (int)$this->accountRepository->getMetaValue($source, 'currency_id');
            $currency         = 0 === $sourceCurrencyId ? $this->defaultCurrency : $this->currencyMapper->map($sourceCurrencyId, []);
            Log::debug(sprintf('Source is an asset account, and has currency preference %s', $currency->code));
        }
        if (null === $currency) {
            Log::debug(sprintf('Could not map currency, use default (%s)', $this->defaultCurrency->code));
            $currency = $this->defaultCurrency;
        }

        return $currency;
    }

    /**
     * @param string $source
     * @param string $destination
     *
     * @return string
     */
    private function getTransactionType(string $source, string $destination): string
    {
        $type = 'unknown';

        if ($source === AccountType::ASSET && $destination === AccountType::ASSET) {
            Log::debug('Source and destination are asset accounts. This is a transfer.');
            $type = 'transfer';
        }
        if ($source === AccountType::REVENUE) {
            Log::debug('Source is a revenue account. This is a deposit.');
            $type = 'deposit';
        }
        if ($destination === AccountType::EXPENSE) {
            Log::debug('Destination is an expense account. This is a withdrawal.');
            $type = 'withdrawal';
        }

        return $type;
    }
}
