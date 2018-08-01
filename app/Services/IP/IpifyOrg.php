<?php
/**
 * IpifyOrg.php
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

namespace FireflyIII\Services\IP;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Log;
use RunTimeException;

/**
 * Class IpifyOrg
 */
class IpifyOrg implements IPRetrievalInterface
{
    /**
     * Returns the user's IP address.
     *
     * @noinspection MultipleReturnStatementsInspection
     * @return null|string
     */
    public function getIP(): ?string
    {
        try {
            $client = new Client;
            $res    = $client->request('GET', 'https://api.ipify.org');
        } catch (GuzzleException|Exception $e) {
            Log::warning(sprintf('The ipify.org service could not retrieve external IP: %s', $e->getMessage()));
            Log::warning($e->getTraceAsString());

            return null;
        }
        if (200 !== $res->getStatusCode()) {
            Log::warning(sprintf('Could not retrieve external IP: %d', $res->getStatusCode()));

            return null;
        }
        try {
            $body = (string)$res->getBody()->getContents();
        } catch (RunTimeException $e) {
            Log::error(sprintf('Could not get body from ipify.org result: %s', $e->getMessage()));
            $body = null;
        }

        return $body;
    }
}
