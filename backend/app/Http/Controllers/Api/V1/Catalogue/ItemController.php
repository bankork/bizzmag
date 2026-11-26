<?php

namespace App\Http\Controllers\Api\V1\Catalogue;

use App\Domain\Audit\AuditEvent;
use App\Domain\Catalogue\Actions\SaveItem;
use App\Domain\Catalogue\Actions\SetItemActive;
use App\Domain\Catalogue\Models\CatalogueItem;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CataloguePresenter;
use App\Http\Requests\V1\Catalogue\StoreItemRequest;
use App\Http\Requests\V1\Catalogue\UpdateItemRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Services, products and variants (FR-SVC-002 to 009).
 *
 * Everyone signed in can list the catalogue, because employees choose the
 * service they worked on (P3). Prices are left out for anyone without
 * order.view_all or catalogue.manage (BR-041), and people who cannot manage
 * the catalogue only see what can be chosen for a visit.
 */
class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'filter.type' => ['nullable', 'in:service,product'],
            'filter.category_id' => ['nullable', 'integer'],
            'filter.active' => ['nullable', 'boolean'],
            'filter.q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $this->user($request);
        $manager = $user->checkPermissionTo('catalogue.manage');
        $type = $filters['filter']['type'] ?? null;
        $categoryId = $filters['filter']['category_id'] ?? null;
        $search = $filters['filter']['q'] ?? null;
        $activeOnly = ! $manager || filter_var($filters['filter']['active'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $items = CatalogueItem::query()
            ->topLevel()
            ->with(['category', 'variants' => fn ($query) => $query->when($activeOnly, fn ($query) => $query->where('is_active', true))])
            ->when($type, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($categoryId, fn (Builder $query, int|string $id) => $query->where('category_id', (int) $id))
            ->when($activeOnly, fn (Builder $query) => $query->where('is_active', true)->whereHas('category', fn (Builder $category) => $category->where('is_active', true)))
            ->when($search, function (Builder $query, string $term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where(fn (Builder $query) => $query->where('name', 'like', $like)
                    ->orWhereHas('variants', fn (Builder $variants) => $variants->where('name', 'like', $like)));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $prices = CataloguePresenter::canSeePrices($user);

        return ApiResponse::success($items
            ->reject(fn (CatalogueItem $item) => $activeOnly && $item->has_variants && $item->variants->isEmpty())
            ->map(fn (CatalogueItem $item) => CataloguePresenter::item($item, $prices))
            ->values()->all());
    }

    public function show(Request $request, int $item): JsonResponse
    {
        $user = $this->user($request);
        $record = $this->find($item);

        if (! $user->checkPermissionTo('catalogue.manage') && ! $record->isOrderable() && ! $record->has_variants) {
            throw new RecordNotFound;
        }

        return $this->respond($record, $user);
    }

    public function store(StoreItemRequest $request, SaveItem $save): JsonResponse
    {
        $item = $save->create($request->validated());

        return $this->respond($this->find($item->id), $this->user($request), 201);
    }

    public function update(UpdateItemRequest $request, int $item, SaveItem $save): JsonResponse
    {
        $data = $request->safe()->except('version');
        $record = $save->update($this->find($item), (int) $request->validated('version'), $data);

        return $this->respond($this->find($record->id), $this->user($request));
    }

    public function deactivate(Request $request, int $item, SetItemActive $set): JsonResponse
    {
        $set($this->find($item), false);

        return $this->respond($this->find($item), $this->user($request));
    }

    public function activate(Request $request, int $item, SetItemActive $set): JsonResponse
    {
        $set($this->find($item), true);

        return $this->respond($this->find($item), $this->user($request));
    }

    /**
     * Every recorded change to the item and its variants, newest first.
     * Price changes are the entries where price_kobo differs.
     */
    public function history(Request $request, int $item, CurrentBusiness $business): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $record = $this->find($item);
        $ids = [$record->id, ...CatalogueItem::query()->where('parent_id', $record->id)->pluck('id')->all()];
        $names = CatalogueItem::query()->whereKey($ids)->pluck('name', 'id');

        $events = AuditEvent::query()
            ->where('business_id', $business->require())
            ->where('subject_type', 'catalogue_item')
            ->whereIn('subject_id', $ids)
            ->with('actor:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        return ApiResponse::success(
            $events->getCollection()->map(fn (AuditEvent $event) => [
                'id' => $event->id,
                'action' => $event->action,
                'item' => ['id' => $event->subject_id, 'name' => $names[$event->subject_id] ?? null, 'is_variant' => $event->subject_id !== $record->id],
                'actor' => $event->actor === null ? null : ['id' => $event->actor->id, 'name' => $event->actor->name],
                'before' => $event->before,
                'after' => $event->after,
                'at' => $event->created_at?->toIso8601String(),
            ])->values()->all(),
            ['pagination' => ['page' => $events->currentPage(), 'per_page' => $events->perPage(), 'total' => $events->total(), 'last_page' => $events->lastPage()]],
        );
    }

    private function find(int $id): CatalogueItem
    {
        return CatalogueItem::query()->with(['category', 'parent', 'variants'])->whereKey($id)->first()
            ?? throw new RecordNotFound;
    }

    private function respond(CatalogueItem $item, User $user, int $status = 200): JsonResponse
    {
        if ($item->isVariant()) {
            $item->unsetRelation('variants');
        } elseif (! $user->checkPermissionTo('catalogue.manage')) {
            $item->setRelation('variants', $item->variants->where('is_active', true)->values());
        }

        return ApiResponse::success(CataloguePresenter::item($item, CataloguePresenter::canSeePrices($user)), status: $status);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
