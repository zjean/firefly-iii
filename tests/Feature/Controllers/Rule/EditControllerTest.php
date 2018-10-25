<?php
/**
 * EditControllerTest.php
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

namespace tests\Feature\Controllers\Rule;


use FireflyIII\Models\Rule;
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
 * Class EditControllerTest
 */
class EditControllerTest extends TestCase
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
     * @covers       \FireflyIII\Http\Controllers\Rule\EditController
     */
    public function testEdit(): void
    {
        // mock stuff
        $groupRepos   = $this->mock(RuleGroupRepositoryInterface::class);
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getPrimaryTrigger')->andReturn(new Rule);
        $groupRepos->shouldReceive('get')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('rules.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Rule\EditController
     */
    public function testEditPreviousInput(): void
    {
        $old = [
            'rule-trigger'       => ['description_is'],
            'rule-trigger-stop'  => ['1'],
            'rule-trigger-value' => ['X'],
            'rule-action'        => ['set_category'],
            'rule-action-stop'   => ['1'],
            'rule-action-value'  => ['x'],
        ];
        $this->session(['_old_input' => $old]);

        // mock stuff
        $groupRepos   = $this->mock(RuleGroupRepositoryInterface::class);
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);
        $userRepos->shouldReceive('hasRole')->withArgs([Mockery::any(), 'owner'])->atLeast()->once()->andReturn(true);

        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('getPrimaryTrigger')->andReturn(new Rule);
        $groupRepos->shouldReceive('get')->andReturn(new Collection);

        $this->be($this->user());
        $response = $this->get(route('rules.edit', [1]));
        $response->assertStatus(200);
        $response->assertSee('<ol class="breadcrumb">');
    }

    /**
     * @covers       \FireflyIII\Http\Controllers\Rule\EditController
     * @covers       \FireflyIII\Http\Requests\RuleFormRequest
     */
    public function testUpdate(): void
    {
        // mock stuff
        $repository   = $this->mock(RuleRepositoryInterface::class);
        $journalRepos = $this->mock(JournalRepositoryInterface::class);
        $userRepos      = $this->mock(UserRepositoryInterface::class);

        $rule         = Rule::find(1);
        $journalRepos->shouldReceive('firstNull')->once()->andReturn(new TransactionJournal);
        $repository->shouldReceive('update');

        $data = [
            'rule_group_id'      => 1,
            'id'                 => 1,
            'title'              => 'Your first default rule',
            'trigger'            => 'store-journal',
            'active'             => 1,
            'description'        => 'This rule is an example. You can safely delete it.',
            'rule-trigger'       => [
                1 => 'description_is',
            ],
            'rule-trigger-value' => [
                1 => 'something',
            ],
            'rule-action'        => [
                1 => 'prepend_description',
            ],
            'rule-action-value'  => [
                1 => 'Bla bla',
            ],
        ];
        $this->session(['rules.edit.uri' => 'http://localhost']);
        $this->be($this->user());
        $response = $this->post(route('rules.update', [1]), $data);
        $response->assertStatus(302);
        $response->assertSessionHas('success');
    }
}
