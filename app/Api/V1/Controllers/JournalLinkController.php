<?php
/**
 * JournalLinkController.php
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

namespace FireflyIII\Api\V1\Controllers;

use FireflyIII\Api\V1\Requests\JournalLinkRequest;
use FireflyIII\Exceptions\FireflyException;
use FireflyIII\Models\TransactionJournalLink;
use FireflyIII\Repositories\Journal\JournalRepositoryInterface;
use FireflyIII\Repositories\LinkType\LinkTypeRepositoryInterface;
use FireflyIII\Transformers\JournalLinkTransformer;
use FireflyIII\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use League\Fractal\Manager;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Resource\Collection as FractalCollection;
use League\Fractal\Resource\Item;
use League\Fractal\Serializer\JsonApiSerializer;

/**
 * Class JournalLinkController.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class JournalLinkController extends Controller
{
    /** @var JournalRepositoryInterface The journal repository */
    private $journalRepository;
    /** @var LinkTypeRepositoryInterface The link type repository */
    private $repository;

    /**
     * JournalLinkController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->middleware(
            function ($request, $next) {
                /** @var User $user */
                $user = auth()->user();

                $this->repository        = app(LinkTypeRepositoryInterface::class);
                $this->journalRepository = app(JournalRepositoryInterface::class);

                $this->repository->setUser($user);
                $this->journalRepository->setUser($user);

                return $next($request);
            }
        );
    }

    /**
     * Delete the resource.
     *
     * @param TransactionJournalLink $link
     *
     * @return JsonResponse
     */
    public function delete(TransactionJournalLink $link): JsonResponse
    {
        $this->repository->destroyLink($link);

        return response()->json([], 204);
    }

    /**
     * List all of them.
     *
     * @param Request $request
     *
     * @return JsonResponse]
     */
    public function index(Request $request): JsonResponse
    {
        // create some objects:
        $manager = new Manager;
        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';

        // read type from URI
        $name = $request->get('name') ?? null;

        // types to get, page size:
        $pageSize = (int)app('preferences')->getForUser(auth()->user(), 'listPageSize', 50)->data;

        $linkType = $this->repository->findByName($name);

        // get list of accounts. Count it and split it.
        $collection   = $this->repository->getJournalLinks($linkType);
        $count        = $collection->count();
        $journalLinks = $collection->slice(($this->parameters->get('page') - 1) * $pageSize, $pageSize);

        // make paginator:
        $paginator = new LengthAwarePaginator($journalLinks, $count, $pageSize, $this->parameters->get('page'));
        $paginator->setPath(route('api.v1.journal_links.index') . $this->buildParams());

        // present to user.
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new FractalCollection($journalLinks, new JournalLinkTransformer($this->parameters), 'journal_links');
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * List single resource.
     *
     * @param Request                $request
     * @param TransactionJournalLink $journalLink
     *
     * @return JsonResponse
     */
    public function show(Request $request, TransactionJournalLink $journalLink): JsonResponse
    {
        $manager = new Manager;

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $baseUrl = $request->getSchemeAndHttpHost() . '/api/v1';
        $manager->setSerializer(new JsonApiSerializer($baseUrl));
        $resource = new Item($journalLink, new JournalLinkTransformer($this->parameters), 'journal_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Store new object.
     *
     * @param JournalLinkRequest $request
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function store(JournalLinkRequest $request): JsonResponse
    {
        $manager = new Manager;

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);

        $data    = $request->getAll();
        $inward  = $this->journalRepository->findNull($data['inward_id'] ?? 0);
        $outward = $this->journalRepository->findNull($data['outward_id'] ?? 0);
        if (null === $inward || null === $outward) {
            throw new FireflyException('Source or destination is NULL.');
        }
        $data['direction'] = 'inward';

        $journalLink = $this->repository->storeLink($data, $inward, $outward);
        $resource    = new Item($journalLink, new JournalLinkTransformer($this->parameters), 'journal_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }

    /**
     * Update object.
     *
     * @param JournalLinkRequest     $request
     * @param TransactionJournalLink $journalLink
     *
     * @return JsonResponse
     * @throws FireflyException
     */
    public function update(JournalLinkRequest $request, TransactionJournalLink $journalLink): JsonResponse
    {
        $manager = new Manager;

        // add include parameter:
        $include = $request->get('include') ?? '';
        $manager->parseIncludes($include);


        $data            = $request->getAll();
        $data['inward']  = $this->journalRepository->findNull($data['inward_id'] ?? 0);
        $data['outward'] = $this->journalRepository->findNull($data['outward_id'] ?? 0);
        if (null === $data['inward'] || null === $data['outward']) {
            throw new FireflyException('Source or destination is NULL.');
        }
        $data['direction'] = 'inward';
        $journalLink       = $this->repository->updateLink($journalLink, $data);

        $resource = new Item($journalLink, new JournalLinkTransformer($this->parameters), 'journal_links');

        return response()->json($manager->createData($resource)->toArray())->header('Content-Type', 'application/vnd.api+json');

    }
}
