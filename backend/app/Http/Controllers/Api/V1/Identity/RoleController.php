<?php

namespace App\Http\Controllers\Api\V1\Identity;

use App\Domain\Identity\Actions\DeleteRole;
use App\Domain\Identity\Actions\SaveRole;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Controllers\Api\V1\Concerns\FindsInBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Identity\SaveRoleRequest;
use App\Http\Resources\V1\RoleResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Roles, their permissions and discount caps (FR-IAM-003, FR-IAM-009).
 */
class RoleController extends Controller
{
    use FindsInBusiness;

    public function index(CurrentBusiness $business): JsonResponse
    {
        $roles = Role::query()
            ->forBusiness($business->require())
            ->with('permissions')
            ->withCount('users')
            ->orderByRaw('system_key IS NULL')
            ->orderBy('id')
            ->get();

        return ApiResponse::success(RoleResource::collection($roles)->resolve());
    }

    public function permissions(): JsonResponse
    {
        return ApiResponse::success(PermissionCatalogue::describe());
    }

    public function store(SaveRoleRequest $request, SaveRole $save): JsonResponse
    {
        /** @var list<string> $permissions */
        $permissions = $request->validated('permissions');

        $role = $save(null, $request->safe()->only(['name', 'description', 'discount_cap_bp', 'discount_cap_kobo']), $permissions);

        return $this->respond($role, 201);
    }

    public function update(SaveRoleRequest $request, int $role, SaveRole $save): JsonResponse
    {
        /** @var list<string>|null $permissions */
        $permissions = $request->has('permissions') ? $request->validated('permissions') : null;

        $saved = $save(
            $this->roleInBusiness($role),
            $request->safe()->only(['name', 'description', 'discount_cap_bp', 'discount_cap_kobo']),
            $permissions,
        );

        return $this->respond($saved);
    }

    public function destroy(int $role, DeleteRole $delete): JsonResponse
    {
        $delete($this->roleInBusiness($role));

        return ApiResponse::success(null);
    }

    private function respond(Role $role, int $status = 200): JsonResponse
    {
        $role->load('permissions')->loadCount('users');

        return ApiResponse::success((new RoleResource($role))->resolve(), status: $status);
    }
}
