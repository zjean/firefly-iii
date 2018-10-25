<?php
/**
 * FinTS.php
 * Copyright (c) 2018 https://github.com/bnw
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

namespace FireflyIII\Support\FinTS;

use Fhp\Model\SEPAAccount;
use FireflyIII\Exceptions\FireflyException;
use Illuminate\Support\Facades\Crypt;

/**
 *
 * Class FinTS
 */
class FinTS
{
    /** @var \Fhp\FinTs */
    private $finTS;

    /**
     * @param array $config
     *
     * @throws FireflyException
     */
    public function __construct(array $config)
    {
        if (!isset($config['fints_url'], $config['fints_port'], $config['fints_bank_code'], $config['fints_username'], $config['fints_password'])) {
            throw new FireflyException('Constructed FinTS with incomplete config.');
        }
        $this->finTS = new \Fhp\FinTs(
            $config['fints_url'],
            $config['fints_port'],
            $config['fints_bank_code'],
            $config['fints_username'],
            Crypt::decrypt($config['fints_password'])
        );
    }

    /**
     * @return bool|string
     */
    public function checkConnection()
    {
        try {
            $this->finTS->getSEPAAccounts();

            return true;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @param string $accountNumber
     *
     * @return SEPAAccount
     * @throws FireflyException
     */
    public function getAccount(string $accountNumber)
    {
        $accounts         = $this->getAccounts();
        $filteredAccounts = array_filter(
            $accounts, function (SEPAAccount $account) use ($accountNumber) {
            return $account->getAccountNumber() === $accountNumber;
        }
        );
        if (count($filteredAccounts) != 1) {
            throw new FireflyException("Cannot find account with number " . $accountNumber);
        }

        return reset($filteredAccounts);
    }

    /**
     * @return SEPAAccount[]
     * @throws FireflyException
     */
    public function getAccounts()
    {
        try {
            return $this->finTS->getSEPAAccounts();
        } catch (\Exception $exception) {
            throw new FireflyException($exception->getMessage());
        }
    }

    /**
     * @param SEPAAccount $account
     * @param \DateTime   $from
     * @param \DateTIme   $to
     *
     * @return \Fhp\Model\StatementOfAccount\StatementOfAccount|null
     * @throws FireflyException
     */
    public function getStatementOfAccount(SEPAAccount $account, \DateTime $from, \DateTIme $to)
    {
        try {
            return $this->finTS->getStatementOfAccount($account, $from, $to);
        } catch (\Exception $exception) {
            throw new FireflyException($exception->getMessage());
        }
    }
}