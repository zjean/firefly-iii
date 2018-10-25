<?php
/**
 * IndexControllerTest.php
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

namespace Tests\Feature\Controllers\Rule;

use FireflyIII\Models\RuleGroup;
use FireflyIII\Models\TransactionJournal;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\Rule\RuleRepositoryInterface;
use FireflyIII\Repositories\RuleGroup\RuleGroupRepositoryInterface;
use FireflyIII\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\Collection;
use Log;
use Mockery;
use Tests\TestCase;

/**
 * Class IndexControllerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class IndexControllerTest extends TestCase
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
     * @covers \FireflyIII\Http\Controllers\Rule\IndexController
     */
    public function testDown(): void
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('moveDown');


        $this->be($this->user());
        $response = $this->get(route('rules.down', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Rule\IndexController
     */
    public function testIndex(): void
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $ruleGroupRepos->shouldReceive('count')->andReturn(0);
        $ruleGroupRepos->shouldReceive('store');
        $repository->shouldReceive('getFirstRuleGroup')->andReturn(new RuleGroup);
        $ruleGroupRepos->shouldReceive('getRuleGroupsWithRules')->andReturn(new Collection);
        $repository->shouldReceive('count')->andReturn(0);
        $repository->shouldReceive('store');

        $this->be($this->user());
        $response = $this->get(route('rules.index'));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Rule\IndexController
     */
    public function testReorderRuleActions(): void
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);

        $data = ['actions' => [1, 2, 3]];
        $repository->shouldReceive('reorderRuleActions')->once();

        $this->be($this->user());
        $response = $this->post(route('rules.reorder-actions', [1]), $data);
        $response->assertStatus(200);
    }

    /**
     * @covers \FireflyIII\Http\Controllers\Rule\IndexController
     */
    public function testReorderRuleTriggers(): void
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $data = ['triggers' => [1, 2, 3]];
        $repository->shouldReceive('reorderRuleTriggers')->once();

        $this->be($this->user());
        $response = $this->post(route('rules.reorder-triggers', [1]), $data);
        $response->assertStatus(200);
    }


    /**
     * @covers \FireflyIII\Http\Controllers\Rule\IndexController
     */
    public function testUp(): void
    {
        // mock stuff
        $repository     = $this->mock(RuleRepositoryInterface::class);
        $journalRepos   = $this->mock(JournalRepositoryInterface::class);
        $ruleGroupRepos = $this->mock(RuleGroupRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);


        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('moveUp');

        $this->be($this->user());
        $response = $this->get(route('rules.up', [1]));
        $response->assertStatus(302);
        $response->assertRedirect(route('rules.index'));
    }

}
