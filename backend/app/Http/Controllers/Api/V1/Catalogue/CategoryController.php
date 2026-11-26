<?php

namespace App\Http\Controllers\Api\V1\Catalogue;

use App\Domain\Catalogue\Actions\ReorderCategories;
use App\Domain\Catalogue\Actions\SaveCategory;
use App\Domain\Catalogue\Actions\SetCategoryActive;
use App\Domain\Catalogue\Models\CatalogueCategory;
use App\Domain\Catalogue\Models\ItemType;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CataloguePresenter;
use App\Http\Requests\V1\Catalogue\ReorderCategoriesRequest;
use App\Http\Requests\V1\Catalogue\StoreCategoryRequest;
use App\Http\Requests\V1\Catalogue\UpdateCategoryRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catalogue categories (FR-SVC-001). Everyone signed in can list them;
 * only catalogue.manage changes them. Categories are never deleted.
 */
class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'filter.type' => ['nullable', 'in:service,product'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $type = $filters['filter']['type'] ?? null;

        $categories = CatalogueCategory::query()
            ->when($type, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when(! $user->checkPermissionTo('catalogue.manage'), fn (Builder $query) => $query->where('is_active', true))
            ->withCount(['items' => fn (Builder $query) => $query->where('is_active', true)])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::success($categories->map(fn (CatalogueCategory $category) => CataloguePresenter::category($category))->values()->all());
    }

    public function store(StoreCategoryRequest $request, SaveCategory $save): JsonResponse
    {
        $category = $save->create(ItemType::from((string) $request->validated('type')), (string) $request->validated('name'));

        return ApiResponse::success(CataloguePresenter::category($category), status: 201);
    }

    public function update(UpdateCategoryRequest $request, int $category, SaveCategory $save): JsonResponse
    {
        $record = $save->rename($this->find($category), (int) $request->validated('version'), (string) $request->validated('name'));

        return ApiResponse::success(CataloguePresenter::category($record));
    }

    public function deactivate(int $category, SetCategoryActive $set): JsonResponse
    {
        return ApiResponse::success(CataloguePresenter::category($set($this->find($category), false)));
    }

    public function activate(int $category, SetCategoryActive $set): JsonResponse
    {
        return ApiResponse::success(CataloguePresenter::category($set($this->find($category), true)));
    }

    public function reorder(ReorderCategoriesRequest $request, ReorderCategories $reorder): JsonResponse
    {
        $categories = $reorder(
            ItemType::from((string) $request->validated('type')),
            array_map('intval', (array) $request->validated('ids')),
        );

        return ApiResponse::success(array_map(fn (CatalogueCategory $category) => CataloguePresenter::category($category), $categories));
    }

    /**
     * Inside the current business only; another salon's category is not found.
     */
    private function find(int $id): CatalogueCategory
    {
        return CatalogueCategory::query()->whereKey($id)->first() ?? throw new RecordNotFound;
    }
}
