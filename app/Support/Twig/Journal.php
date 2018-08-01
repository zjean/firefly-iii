<?php
/**
 * Journal.php
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

namespace FireflyIII\Support\Twig;

use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\Category;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Support\CacheProperties;
use FireflyIII\Support\Twig\Extension\TransactionJournal as TransactionJournalExtension;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

/**
 * Class Journal.
 */
class Journal extends Twig_Extension
{
    /**
     * @return Twig_SimpleFunction
     */
    public function getDestinationAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'destinationAccount',
            function (TransactionJournal $journal) {
                $cache = new CacheProperties;
                $cache->addProperty($journal->id);
                $cache->addProperty('transaction-journal');
                $cache->addProperty('destination-account-string');
                if ($cache->has()) {
                    return $cache->get(); // @codeCoverageIgnore
                }

                /** @var JournalRepositoryInterface $repository */
                $repository = app(JournalRepositoryInterface::class);
                $list       = $repository->getJournalDestinationAccounts($journal);
                $array      = [];
                /** @var Account $entry */
                foreach ($list as $entry) {
                    if (AccountType::CASH === $entry->accountType->type) {
                        $array[] = '<span class="text-success">(cash)</span>';
                        continue;
                    }
                    $array[] = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($entry->name), route('accounts.show', $entry->id));
                }
                $array  = array_unique($array);
                $result = implode(', ', $array);
                $cache->store($result);

                return $result;
            }
        );
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        $filters = [
            new Twig_SimpleFilter('journalTotalAmount', [TransactionJournalExtension::class, 'totalAmount'], ['is_safe' => ['html']]),
            new Twig_SimpleFilter('journalTotalAmountPlain', [TransactionJournalExtension::class, 'totalAmountPlain'], ['is_safe' => ['html']]),
        ];

        return $filters;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        $functions = [
            $this->getSourceAccount(),
            $this->getDestinationAccount(),
            $this->journalBudgets(),
            $this->journalCategories(),
            new Twig_SimpleFunction('journalGetMetaField', [TransactionJournalExtension::class, 'getMetaField']),
            new Twig_SimpleFunction('journalHasMeta', [TransactionJournalExtension::class, 'hasMetaField']),
            new Twig_SimpleFunction('journalGetMetaDate', [TransactionJournalExtension::class, 'getMetaDate']),
        ];

        return $functions;
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function getSourceAccount(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'sourceAccount',
            function (TransactionJournal $journal): string {
                $cache = new CacheProperties;
                $cache->addProperty($journal->id);
                $cache->addProperty('transaction-journal');
                $cache->addProperty('source-account-string');
                if ($cache->has()) {
                    return $cache->get(); // @codeCoverageIgnore
                }

                /** @var JournalRepositoryInterface $repository */
                $repository = app(JournalRepositoryInterface::class);

                $list  = $repository->getJournalSourceAccounts($journal);
                $array = [];
                /** @var Account $entry */
                foreach ($list as $entry) {
                    if (AccountType::CASH === $entry->accountType->type) {
                        $array[] = '<span class="text-success">(cash)</span>';
                        continue;
                    }
                    $array[] = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($entry->name), route('accounts.show', $entry->id));
                }
                $array  = array_unique($array);
                $result = implode(', ', $array);
                $cache->store($result);

                return $result;
            }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalBudgets(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalBudgets',
            function (TransactionJournal $journal): string {
                $cache = new CacheProperties;
                $cache->addProperty($journal->id);
                $cache->addProperty('transaction-journal');
                $cache->addProperty('budget-string');
                if ($cache->has()) {
                    return $cache->get(); // @codeCoverageIgnore
                }

                $budgets = [];
                // get all budgets:
                foreach ($journal->budgets as $budget) {
                    $budgets[] = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($budget->name), route('budgets.show', $budget->id));
                }
                // and more!
                foreach ($journal->transactions as $transaction) {
                    foreach ($transaction->budgets as $budget) {
                        $budgets[] = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($budget->name), route('budgets.show', $budget->id));
                    }
                }
                $string = implode(', ', array_unique($budgets));
                $cache->store($string);

                return $string;
            }
        );
    }

    /**
     * @return Twig_SimpleFunction
     */
    public function journalCategories(): Twig_SimpleFunction
    {
        return new Twig_SimpleFunction(
            'journalCategories',
            function (TransactionJournal $journal): string {
                $cache = new CacheProperties;
                $cache->addProperty($journal->id);
                $cache->addProperty('transaction-journal');
                $cache->addProperty('category-string');
                if ($cache->has()) {
                    return $cache->get(); // @codeCoverageIgnore
                }
                $categories = [];
                // get all categories for the journal itself (easy):
                foreach ($journal->categories as $category) {
                    $categories[] = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($category->name), route('categories.show', $category->id));
                }
                if (0 === \count($categories)) {
                    $set = Category::distinct()->leftJoin('category_transaction', 'categories.id', '=', 'category_transaction.category_id')
                                   ->leftJoin('transactions', 'category_transaction.transaction_id', '=', 'transactions.id')
                                   ->leftJoin('transaction_journals', 'transactions.transaction_journal_id', '=', 'transaction_journals.id')
                                   ->where('categories.user_id', $journal->user_id)
                                   ->where('transaction_journals.id', $journal->id)
                                   ->whereNull('transactions.deleted_at')
                                   ->get(['categories.*']);
                    /** @var Category $category */
                    foreach ($set as $category) {
                        $categories[] = sprintf('<a title="%1$s" href="%2$s">%1$s</a>', e($category->name), route('categories.show', $category->id));
                    }
                }

                $string = implode(', ', array_unique($categories));
                $cache->store($string);

                return $string;
            }
        );
    }
}
