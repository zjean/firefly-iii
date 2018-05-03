<?php
declare(strict_types=1);
/**
 * CsvExporter.php
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

namespace FireflyIII\Export\Exporter;

use FireflyIII\Export\Entry\Entry;
use League\Csv\Writer;
use Storage;

/**
 * Class CsvExporter.
 */
class CsvExporter extends BasicExporter implements ExporterInterface
{
    /** @var string */
    private $fileName;

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @return bool
     *
     */
    public function run(): bool
    {
        // create temporary file:
        $this->tempFile();

        // necessary for CSV writer:
        $fullPath = storage_path('export') . DIRECTORY_SEPARATOR . $this->fileName;


        //we create the CSV into memory
        $writer = Writer::createFromPath($fullPath);
        $rows   = [];

        // get field names for header row:
        $first   = $this->getEntries()->first();
        $headers = [];
        if (null !== $first) {
            $headers = array_keys(get_object_vars($first));
        }

        $rows[] = $headers;

        /** @var Entry $entry */
        foreach ($this->getEntries() as $entry) {
            $line = [];
            foreach ($headers as $header) {
                $line[] = $entry->$header;
            }
            $rows[] = $line;
        }
        $writer->insertAll($rows);

        return true;
    }

    private function tempFile()
    {
        $this->fileName = $this->job->key . '-records.csv';
        // touch file in export directory:
        $disk = Storage::disk('export');
        $disk->put($this->fileName, '');
    }
}
