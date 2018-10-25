<?php
/**
 * Help.php
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

namespace FireflyIII\Helpers\Help;

use Cache;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use League\CommonMark\CommonMarkConverter;
use Log;
use Route;

/**
 * Class Help.
 */
class Help implements HelpInterface
{
    /** @var string The cache key */
    public const CACHEKEY = 'help_%s_%s';
    /** @var string The user agent. */
    protected $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36';

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ('testing' === env('APP_ENV')) {
            Log::warning(sprintf('%s should not be instantiated in the TEST environment!', \get_class($this)));
        }
    }

    /**
     * Get from cache.
     *
     * @codeCoverageIgnore
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromCache(string $route, string $language): string
    {
        $line = sprintf(self::CACHEKEY, $route, $language);

        return Cache::get($line);
    }

    /**
     * Get text from GitHub.
     *
     * @param string $route
     * @param string $language
     *
     * @return string
     */
    public function getFromGitHub(string $route, string $language): string
    {
        $uri = sprintf('https://raw.githubusercontent.com/firefly-iii/help/master/%s/%s.md', $language, $route);
        Log::debug(sprintf('Trying to get %s...', $uri));
        $opt        = ['headers' => ['User-Agent' => $this->userAgent]];
        $content    = '';
        $statusCode = 500;
        $client     = app(Client::class);
        try {
            $res        = $client->request('GET', $uri, $opt);
            $statusCode = $res->getStatusCode();
            $content    = trim($res->getBody()->getContents());
        } catch (GuzzleException|Exception $e) {
            Log::info($e->getMessage());
            Log::info($e->getTraceAsString());
        }

        Log::debug(sprintf('Status code is %d', $statusCode));

        if (\strlen($content) > 0) {
            Log::debug('Content is longer than zero. Expect something.');
            $converter = new CommonMarkConverter();
            $content   = $converter->convertToHtml($content);
        }

        return $content;
    }

    /**
     * Do we have the route?
     *
     * @codeCoverageIgnore
     *
     * @param string $route
     *
     * @return bool
     */
    public function hasRoute(string $route): bool
    {
        return Route::has($route);
    }

    /**
     * Is in cache?
     *
     * @codeCoverageIgnore
     *
     * @param string $route
     * @param string $language
     *
     * @return bool
     */
    public function inCache(string $route, string $language): bool
    {
        $line   = sprintf(self::CACHEKEY, $route, $language);
        $result = Cache::has($line);
        if ($result) {
            Log::debug(sprintf('Cache has this entry: %s', 'help.' . $route . '.' . $language));
        }
        if (!$result) {
            Log::debug(sprintf('Cache does not have this entry: %s', 'help.' . $route . '.' . $language));
        }

        return $result;
    }

    /**
     * Put help text in cache.
     *
     * @codeCoverageIgnore
     *
     * @param string $route
     * @param string $language
     * @param string $content
     */
    public function putInCache(string $route, string $language, string $content): void
    {
        $key = sprintf(self::CACHEKEY, $route, $language);
        if (\strlen($content) > 0) {
            Log::debug(sprintf('Will store entry in cache: %s', $key));
            Cache::put($key, $content, 10080); // a week.

            return;
        }
        Log::info(sprintf('Will not cache %s because content is empty.', $key));
    }
}
