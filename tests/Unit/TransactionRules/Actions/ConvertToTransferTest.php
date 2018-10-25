<?php
/**
 * ConvertToTransferTest.php
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

namespace Tests\Unit\TransactionRules\Actions;


use Exception;
use FireflyIII\Models\Account;
use FireflyIII\Models\AccountType;
use FireflyIII\Models\RuleAction;
use FireflyIII\Models\TransactionType;
use FireflyIII\Repositories\Account\AccountRepositoryInterface;
use FireflyIII\TransactionRules\Actions\ConvertToTransfer;
use Log;
use Tests\TestCase;

/**
 *
 * Class ConvertToTransferTest
 */
class ConvertToTransferTest extends TestCase
{
    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();
        Log::info(sprintf('Now in %s.', \get_class($this)));
    }

    /**
     * Convert a deposit to a transfer.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToTransfer
     */
    public function testActDeposit(): void
    {
        $deposit = $this->getRandomDeposit();
        /** @var Account $asset */
        $asset = $this->user()->accounts()->where('name', 'Bitcoin Account')->first();
        // journal is a withdrawal:
        $this->assertEquals(TransactionType::DEPOSIT, $deposit->transactionType->type);

        // mock used stuff:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->withArgs([$asset->name, [AccountType::ASSET, AccountType::DEFAULT]])->andReturn($asset);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $asset->name;
        $action                   = new ConvertToTransfer($ruleAction);

        try {
            $result = $action->act($deposit);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a transfer.
        $deposit->refresh();
        $this->assertEquals(TransactionType::TRANSFER, $deposit->transactionType->type);
    }

    /**
     * Convert a withdrawal to a transfer.
     *
     * @covers \FireflyIII\TransactionRules\Actions\ConvertToTransfer
     */
    public function testActWithdrawal(): void
    {
        $withdrawal = $this->getRandomWithdrawal();
        /** @var Account $asset */
        $asset = $this->user()->accounts()->where('name', 'Bitcoin Account')->first();
        // journal is a withdrawal:
        $this->assertEquals(TransactionType::WITHDRAWAL, $withdrawal->transactionType->type);

        // mock used stuff:
        $accountRepos = $this->mock(AccountRepositoryInterface::class);
        $accountRepos->shouldReceive('setUser')->once();
        $accountRepos->shouldReceive('findByName')->withArgs([$asset->name, [AccountType::ASSET, AccountType::DEFAULT]])->andReturn($asset);

        // fire the action:
        $ruleAction               = new RuleAction;
        $ruleAction->action_value = $asset->name;
        $action                   = new ConvertToTransfer($ruleAction);

        try {
            $result = $action->act($withdrawal);
        } catch (Exception $e) {
            $this->assertTrue(false, $e->getMessage());
        }
        $this->assertTrue($result);

        // journal is now a transfer.
        $withdrawal->refresh();
        $this->assertEquals(TransactionType::TRANSFER, $withdrawal->transactionType->type);
    }


}