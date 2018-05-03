<?php

namespace FireflyIII\Import\Storage;

use Carbon\Carbon;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Factory\TransactionJournalFactory;
use FireflyIII\Helpers\Collector\JournalCollectorInterface;
use FireflyIII\Helpers\Filter\InternalTransferFilter;
use FireflyIII\Models\ImportJob;
use FireflyIII\Models\Tag;
use FireflyIII\Models\Transaction;
use FireflyIII\Models\TransactionJournalMeta;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\ImportJob\ImportJobRepositoryInterface;
use FireflyIII\Repositories\Tag\TagRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;
use Log;
use DB;

/**
 * Creates new transactions based upon arrays. Will first check the array for duplicates.
 *
 * Class ImportArrayStorage
 *
 * @package FireflyIII\Import\Storage
 */
class ImportArrayStorage
{
    /** @var bool */
    private $checkForTransfers = false;
    /** @var ImportJob */
    private $importJob;

    /** @var ImportJobRepositoryInterface */
    private $repository;

    /** @var Collection */
    private $transfers;

    /**
     * ImportArrayStorage constructor.
     *
     * @param ImportJob $importJob
     */
    public function __construct(ImportJob $importJob)
    {
        $this->importJob = $importJob;
        $this->countTransfers();

        $this->repository = app(ImportJobRepositoryInterface::class);
        $this->repository->setUser($importJob->user);

        Log::debug('Constructed ImportArrayStorage()');
    }

    /**
     * Actually does the storing.
     *
     * @return Collection
     * @throws FireflyException
     */
    public function store(): Collection
    {
        $count = count($this->importJob->transactions);
        Log::debug(sprintf('Now in store(). Count of items is %d', $count));
        $toStore = [];
        foreach ($this->importJob->transactions as $index => $transaction) {
            Log::debug(sprintf('Now at item %d out of %d', ($index + 1), $count));
            $existingId = $this->hashExists($transaction);
            if (null !== $existingId) {
                $this->logDuplicateObject($transaction, $existingId);
                $this->repository->addErrorMessage(
                    $this->importJob, sprintf(
                                        'Entry #%d ("%s") could not be imported. It already exists.',
                                        $index, $transaction['description']
                                    )
                );
                continue;
            }
            if ($this->checkForTransfers) {
                if ($this->transferExists($transaction)) {
                    $this->logDuplicateTransfer($transaction);
                    $this->repository->addErrorMessage(
                        $this->importJob, sprintf(
                                            'Entry #%d ("%s") could not be imported. Such a transfer already exists.',
                                            $index,
                                            $transaction['description']
                                        )
                    );
                    continue;
                }
            }
            $toStore[] = $transaction;
        }

        if (count($toStore) === 0) {
            Log::info('No transactions to store left!');

            return new Collection;
        }
        Log::debug('Going to store...');
        // now actually store them:
        $collection = new Collection;
        /** @var TransactionJournalFactory $factory */
        $factory = app(TransactionJournalFactory::class);
        $factory->setUser($this->importJob->user);
        foreach ($toStore as $store) {
            // convert the date to an object:
            $store['date'] = Carbon::createFromFormat('Y-m-d', $store['date']);

            // store the journal.
            $collection->push($factory->create($store));
        }
        Log::debug('DONE storing!');


        // create tag and append journals:
        $this->createTag($collection);

        return $collection;
    }

    /**
     * @param Collection $collection
     */
    private function createTag(Collection $collection): void
    {

        /** @var TagRepositoryInterface $repository */
        $repository = app(TagRepositoryInterface::class);
        $repository->setUser($this->importJob->user);
        $data = [
            'tag'         => trans('import.import_with_key', ['key' => $this->importJob->key]),
            'date'        => new Carbon,
            'description' => null,
            'latitude'    => null,
            'longitude'   => null,
            'zoomLevel'   => null,
            'tagMode'     => 'nothing',
        ];
        $tag  = $repository->store($data);

        Log::debug(sprintf('Created tag #%d ("%s")', $tag->id, $tag->tag));
        Log::debug('Looping journals...');
        $journalIds = $collection->pluck('id')->toArray();
        $tagId      = $tag->id;
        foreach ($journalIds as $journalId) {
            Log::debug(sprintf('Linking journal #%d to tag #%d...', $journalId, $tagId));
            DB::table('tag_transaction_journal')->insert(['transaction_journal_id' => $journalId, 'tag_id' => $tagId]);
        }
        Log::info(sprintf('Linked %d journals to tag #%d ("%s")', $collection->count(), $tag->id, $tag->tag));

        $this->repository->setTag($this->importJob, $tag);

    }

    /**
     * Count the number of transfers in the array. If this is zero, don't bother checking for double transfers.
     */
    private function countTransfers(): void
    {
        $count = 0;
        foreach ($this->importJob->transactions as $transaction) {
            if (strtolower(TransactionType::TRANSFER) === $transaction['type']) {
                $count++;
            }
        }
        $count = 1;
        if ($count > 0) {
            $this->checkForTransfers = true;

            // get users transfers. Needed for comparison.
            $this->getTransfers();
        }

    }

    /**
     * Get the users transfers, so they can be compared to whatever the user is trying to import.
     */
    private function getTransfers(): void
    {
        /** @var JournalCollectorInterface $collector */
        $collector = app(JournalCollectorInterface::class);
        $collector->setAllAssetAccounts()
                  ->setTypes([TransactionType::TRANSFER])
                  ->withOpposingAccount();
        $collector->removeFilter(InternalTransferFilter::class);
        $this->transfers = $collector->getJournals();

    }


    /**
     * @param array $transaction
     *
     * @return int|null
     * @throws FireflyException
     */
    private function hashExists(array $transaction): ?int
    {
        $json = json_encode($transaction);
        if ($json === false) {
            throw new FireflyException('Could not encode import array. Please see the logs.', $transaction);
        }
        $hash = hash('sha256', $json, false);

        // find it!
        /** @var TransactionJournalMeta $entry */
        $entry = TransactionJournalMeta
            ::leftJoin('transaction_journals', 'transaction_journals.id', '=', 'journal_meta.transaction_journal_id')
            ->where('data', $hash)
            ->where('name', 'importHashV2')
            ->first(['journal_meta.*']);
        if (null === $entry) {
            return null;
        }
        Log::info(sprintf('Found a transaction journal with an existing hash: %s', $hash));

        return (int)$entry->transaction_journal_id;
    }

    /**
     * @param array $transaction
     * @param int   $existingId
     */
    private function logDuplicateObject(array $transaction, int $existingId): void
    {
        Log::info(
            'Transaction is a duplicate, and will not be imported (the hash exists).',
            [
                'existing'    => $existingId,
                'description' => $transaction['description'] ?? '',
                'amount'      => $transaction['transactions'][0]['amount'] ?? 0,
                'date'        => isset($transaction['date']) ? $transaction['date'] : '',
            ]
        );

    }

    /**
     * @param array $transaction
     */
    private function logDuplicateTransfer(array $transaction): void
    {
        Log::info(
            'Transaction is a duplicate transfer, and will not be imported (such a transfer exists already).',
            [
                'description' => $transaction['description'] ?? '',
                'amount'      => $transaction['transactions'][0]['amount'] ?? 0,
                'date'        => isset($transaction['date']) ? $transaction['date'] : '',
            ]
        );
    }

    /**
     * Check if a transfer exists.
     *
     * @param $transaction
     *
     * @return bool
     */
    private function transferExists(array $transaction): bool
    {
        Log::debug('Check if is a double transfer.');
        if (strtolower(TransactionType::TRANSFER) !== $transaction['type']) {
            Log::debug(sprintf('Is a %s, not a transfer so no.', $transaction['type']));

            return false;
        }

        // how many hits do we need?
        $requiredHits = count($transaction['transactions']) * 4;
        $totalHits    = 0;
        Log::debug(sprintf('Required hits for transfer comparison is %d', $requiredHits));

        // loop over each split:
        foreach ($transaction['transactions'] as $current) {

            // get the amount:
            $amount = (string)($current['amount'] ?? '0');
            if (bccomp($amount, '0') === -1) {
                $amount = bcmul($amount, '-1');
            }

            // get the description:
            $description = strlen((string)$current['description']) === 0 ? $transaction['description'] : $current['description'];

            // get the source and destination ID's:
            $currentSourceIDs = [(int)$current['source_id'], (int)$current['destination_id']];
            sort($currentSourceIDs);

            // get the source and destination names:
            $currentSourceNames = [(string)$current['source_name'], (string)$current['destination_name']];
            sort($currentSourceNames);

            // then loop all transfers:
            /** @var Transaction $transfer */
            foreach ($this->transfers as $transfer) {
                // number of hits for this split-transfer combination:
                $hits = 0;
                Log::debug(sprintf('Now looking at transaction journal #%d', $transfer->journal_id));
                // compare amount:
                Log::debug(sprintf('Amount %s compared to %s', $amount, $transfer->transaction_amount));
                if (0 !== bccomp($amount, $transfer->transaction_amount)) {
                    continue;
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare description:
                Log::debug(sprintf('Comparing "%s" to "%s"', $description, $transfer->description));
                if ($description !== $transfer->description) {
                    continue;
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare date:
                $transferDate = $transfer->date->format('Y-m-d');
                Log::debug(sprintf('Comparing dates "%s" to "%s"', $transaction['date'], $transferDate));
                if ($transaction['date'] !== $transferDate) {
                    continue;
                }
                ++$hits;
                Log::debug(sprintf('Comparison is a hit! (%s)', $hits));

                // compare source and destination id's
                $transferSourceIDs = [(int)$transfer->account_id, (int)$transfer->opposing_account_id];
                sort($transferSourceIDs);
                Log::debug('Comparing current transaction source+dest IDs', $currentSourceIDs);
                Log::debug('.. with current transfer source+dest IDs', $transferSourceIDs);
                if ($currentSourceIDs === $transferSourceIDs) {
                    ++$hits;
                    Log::debug(sprintf('Source IDs are the same! (%d)', $hits));
                }
                unset($transferSourceIDs);

                // compare source and destination names
                $transferSource = [(string)$transfer->account_name, (int)$transfer->opposing_account_name];
                sort($transferSource);
                Log::debug('Comparing current transaction source+dest names', $currentSourceNames);
                Log::debug('.. with current transfer source+dest names', $transferSource);
                if ($currentSourceNames === $transferSource) {
                    Log::debug(sprintf('Source names are the same! (%d)', $hits));
                    ++$hits;
                }
                $totalHits += $hits;
                if ($totalHits >= $requiredHits) {
                    return true;
                }
            }
        }
        Log::debug(sprintf('Total hits: %d, required: %d', $totalHits, $requiredHits));

        return $totalHits >= $requiredHits;
    }

}