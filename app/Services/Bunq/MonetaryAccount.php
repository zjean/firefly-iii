<?php
/**
 * MonetaryAccount.php
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

namespace FireflyIII\Services\Bunq;


use bunq\Model\Generated\Endpoint\BunqResponseMonetaryAccountList;
use bunq\Model\Generated\Endpoint\MonetaryAccount as BunqMonetaryAccount;
use Exception;
use FireflyIII\Exceptions\FireflyException;

/**
 * Class MonetaryAccount
 */
class MonetaryAccount
{
    /**
     * @param array $params
     * @param array $customHeaders
     *
     * @return BunqResponseMonetaryAccountList
     * @throws FireflyException
     */
    public function listing(array $params = null, array $customHeaders = null): BunqResponseMonetaryAccountList
    {
        $params        = $params ?? [];
        $customHeaders = $customHeaders ?? [];
        try {
            $result = BunqMonetaryAccount::listing($params, $customHeaders);
        } catch (Exception $e) {
            throw new FireflyException($e->getMessage());
        }

        return $result;
    }

}
