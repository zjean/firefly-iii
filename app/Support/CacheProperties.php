<?php
/**
 * CacheProperties.php
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

namespace FireflyIII\Support;

use Cache;
use Illuminate\Support\Collection;

/**
 * Class CacheProperties.
 */
class CacheProperties
{
    /** @var string */
    protected $hash = '';
    /** @var Collection */
    protected $properties;

    /**
     *
     */
    public function __construct()
    {
        $this->properties = new Collection;
        if (auth()->check()) {
            $this->addProperty(auth()->user()->id);
            $this->addProperty(app('preferences')->lastActivity());
        }
    }

    /**
     * @param $property
     */
    public function addProperty($property): void
    {
        $this->properties->push($property);
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return Cache::get($this->hash);
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        return $this->hash;
    }

    /**
     * @return bool
     */
    public function has(): bool
    {
        if ('testing' === getenv('APP_ENV')) {
            return false;
        }
        $this->hash();

        return Cache::has($this->hash);
    }

    /**
     * @param $data
     */
    public function store($data): void
    {
        Cache::forever($this->hash, $data);
    }

    /**
     */
    private function hash(): void
    {
        $content = '';
        foreach ($this->properties as $property) {
            $content .= json_encode($property);
        }
        $this->hash = substr(sha1($content), 0, 16);
    }
}
