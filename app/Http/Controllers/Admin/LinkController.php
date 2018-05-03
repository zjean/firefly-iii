<?php
/**
 * LinkController.php
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

namespace FireflyIII\Http\Controllers\Admin;

use FireflyIII\Http\Controllers\Controller;
use FireflyIII\Http\Middleware\IsDemoUser;
use FireflyIII\Http\Requests\LinkTypeFormRequest;
use FireflyIII\Models\LinkType;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use Illuminate\Http\Request;
use Preferences;
use View;

/**
 * Class LinkController.
 */
class LinkController extends Controller
{
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware(
            function ($request, $next) {
                app('view')->share('title', (string)trans('firefly.administration'));
                app('view')->share('mainTitleIcon', 'fa-hand-spock-o');

                return $next($request);
            }
        );
        $this->middleware(IsDemoUser::class)->except(['index', 'show']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        $subTitle     = trans('firefly.create_new_link_type');
        $subTitleIcon = 'fa-link';

        // put previous url in session if not redirect from store (not "create another").
        if (true !== session('link_types.create.fromStore')) {
            $this->rememberPreviousUri('link_types.create.uri');
        }

        return view('admin.link.create', compact('subTitle', 'subTitleIcon'));
    }

    /**
     * @param Request                     $request
     * @param LinkTypeRepositoryInterface $repository
     * @param LinkType                    $linkType
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|View
     */
    public function delete(Request $request, LinkTypeRepositoryInterface $repository, LinkType $linkType)
    {
        if (!$linkType->editable) {
            $request->session()->flash('error', (string)trans('firefly.cannot_edit_link_type', ['name' => $linkType->name]));

            return redirect(route('admin.links.index'));
        }

        $subTitle   = trans('firefly.delete_link_type', ['name' => $linkType->name]);
        $otherTypes = $repository->get();
        $count      = $repository->countJournals($linkType);
        $moveTo     = [];
        $moveTo[0]  = trans('firefly.do_not_save_connection');
        /** @var LinkType $otherType */
        foreach ($otherTypes as $otherType) {
            if ($otherType->id !== $linkType->id) {
                $moveTo[$otherType->id] = sprintf('%s (%s / %s)', $otherType->name, $otherType->inward, $otherType->outward);
            }
        }
        // put previous url in session
        $this->rememberPreviousUri('link_types.delete.uri');

        return view('admin.link.delete', compact('linkType', 'subTitle', 'moveTo', 'count'));
    }

    /**
     * @param Request                     $request
     * @param LinkTypeRepositoryInterface $repository
     * @param LinkType                    $linkType
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(Request $request, LinkTypeRepositoryInterface $repository, LinkType $linkType)
    {
        $name   = $linkType->name;
        $moveTo = $repository->find((int)$request->get('move_link_type_before_delete'));
        $repository->destroy($linkType, $moveTo);

        $request->session()->flash('success', (string)trans('firefly.deleted_link_type', ['name' => $name]));
        Preferences::mark();

        return redirect($this->getPreviousUri('link_types.delete.uri'));
    }

    /**
     * @param Request  $request
     * @param LinkType $linkType
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */
    public function edit(Request $request, LinkType $linkType)
    {
        if (!$linkType->editable) {
            $request->session()->flash('error', (string)trans('firefly.cannot_edit_link_type', ['name' => $linkType->name]));

            return redirect(route('admin.links.index'));
        }
        $subTitle     = trans('firefly.edit_link_type', ['name' => $linkType->name]);
        $subTitleIcon = 'fa-link';

        // put previous url in session if not redirect from store (not "return_to_edit").
        if (true !== session('link_types.edit.fromUpdate')) {
            $this->rememberPreviousUri('link_types.edit.uri'); // @codeCoverageIgnore
        }
        $request->session()->forget('link_types.edit.fromUpdate');

        return view('admin.link.edit', compact('subTitle', 'subTitleIcon', 'linkType'));
    }

    /**
     * @param LinkTypeRepositoryInterface $repository
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(LinkTypeRepositoryInterface $repository)
    {
        $subTitle     = trans('firefly.journal_link_configuration');
        $subTitleIcon = 'fa-link';
        $linkTypes    = $repository->get();
        $linkTypes->each(
            function (LinkType $linkType) use ($repository) {
                $linkType->journalCount = $repository->countJournals($linkType);
            }
        );

        return view('admin.link.index', compact('subTitle', 'subTitleIcon', 'linkTypes'));
    }

    /**
     * @param LinkType $linkType
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(LinkType $linkType)
    {
        $subTitle     = trans('firefly.overview_for_link', ['name' => $linkType->name]);
        $subTitleIcon = 'fa-link';
        $links        = $linkType->transactionJournalLinks()->get();

        return view('admin.link.show', compact('subTitle', 'subTitleIcon', 'linkType', 'links'));
    }

    /**
     * @param LinkTypeFormRequest         $request
     * @param LinkTypeRepositoryInterface $repository
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(LinkTypeFormRequest $request, LinkTypeRepositoryInterface $repository)
    {
        $data     = [
            'name'    => $request->string('name'),
            'inward'  => $request->string('inward'),
            'outward' => $request->string('outward'),
        ];
        $linkType = $repository->store($data);
        $request->session()->flash('success', (string)trans('firefly.stored_new_link_type', ['name' => $linkType->name]));

        if (1 === (int)$request->get('create_another')) {
            // set value so create routine will not overwrite URL:
            $request->session()->put('link_types.create.fromStore', true);

            return redirect(route('admin.links.create'))->withInput();
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('link_types.create.uri'));
    }

    /**
     * @param LinkTypeFormRequest         $request
     * @param LinkTypeRepositoryInterface $repository
     * @param LinkType                    $linkType
     *
     * @return $this|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(LinkTypeFormRequest $request, LinkTypeRepositoryInterface $repository, LinkType $linkType)
    {
        if (!$linkType->editable) {
            $request->session()->flash('error', (string)trans('firefly.cannot_edit_link_type', ['name' => $linkType->name]));

            return redirect(route('admin.links.index'));
        }

        $data = [
            'name'    => $request->string('name'),
            'inward'  => $request->string('inward'),
            'outward' => $request->string('outward'),
        ];
        $repository->update($linkType, $data);

        $request->session()->flash('success', (string)trans('firefly.updated_link_type', ['name' => $linkType->name]));
        Preferences::mark();

        if (1 === (int)$request->get('return_to_edit')) {
            // set value so edit routine will not overwrite URL:
            $request->session()->put('link_types.edit.fromUpdate', true);

            return redirect(route('admin.links.edit', [$linkType->id]))->withInput(['return_to_edit' => 1]);
        }

        // redirect to previous URL.
        return redirect($this->getPreviousUri('link_types.edit.uri'));
    }
}
