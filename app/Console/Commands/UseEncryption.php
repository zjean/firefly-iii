<?php
declare(strict_types=1);

/**
 * UseEncryption.php
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

namespace FireflyIII\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Class UseEncryption.
 */
class UseEncryption extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will make sure that entries in the database will be encrypted (or not) according to the settings in .env';
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firefly:use-encryption';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('firefly.encryption') === true) {
            $this->info('Firefly III configuration calls for encrypted data.');
        }
        if (config('firefly.encryption') === false) {
            $this->info('Firefly III configuration calls for unencrypted data.');
        }
        $this->handleObjects('Account', 'name', 'encrypted');
        $this->handleObjects('Bill', 'name', 'name_encrypted');
        $this->handleObjects('Bill', 'match', 'match_encrypted');
        $this->handleObjects('Budget', 'name', 'encrypted');
        $this->handleObjects('Category', 'name', 'encrypted');
        $this->handleObjects('PiggyBank', 'name', 'encrypted');
        $this->handleObjects('TransactionJournal', 'description', 'encrypted');
    }

    /**
     * Run each object and encrypt them (or not).
     *
     * @param string $class
     * @param string $field
     * @param string $indicator
     */
    public function handleObjects(string $class, string $field, string $indicator)
    {
        $fqn     = sprintf('FireflyIII\Models\%s', $class);
        $encrypt = config('firefly.encryption') === true ? 0 : 1;
        $set     = $fqn::where($indicator, $encrypt)->get();

        foreach ($set as $entry) {
            $newName       = $entry->$field;
            $entry->$field = $newName;
            $entry->save();
        }

        $this->line(sprintf('Updated %d %s.', $set->count(), strtolower(Str::plural($class))));
    }
}
