<?php

namespace App\Http\Controllers\Api\V1\Identity;

use App\Domain\Identity\Actions\AssignRoles;
use App\Domain\Identity\Actions\CreateUser;
use App\Domain\Identity\Actions\DisableUser;
use App\Domain\Identity\Actions\EnableUser;
use App\Domain\Identity\Actions\ResetPassword;
use App\Domain\Identity\Actions\UpdateUser;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Controllers\Api\V1\Concerns\FindsInBusiness;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\Identity\AssignRolesRequest;
use App\Http\Requests\V1\Identity\DisableUserRequest;
use App\Http\Requests\V1\Identity\ResetPasswordRequest;
use App\Http\Requests\V1\Identity\StoreUserRequest;
use App\Http\Requests\V1\Identity\UpdateUserRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Staff accounts (FR-AUTH-003 to FR-AUTH-005). Users are never deleted.
 */
class UserController extends Controller
{
    use FindsInBusiness;

    private const PER_PAGE = 25;

    public function index(Request $request, CurrentBusiness $business): JsonResponse
    {
        $filters = $request->validate([
            'filter.q' => ['nullable', 'string', 'max:100'],
            'filter.status' => ['nullable', 'in:active,disabled'],
            'filter.role_id' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = $filters['filter']['q'] ?? null;
        $status = $filters['filter']['status'] ?? null;
        $roleId = $filters['filter']['role_id'] ?? null;

        $users = User::query()
            ->inBusiness($business->require())
            ->with(['roles', 'employeeProfile'])
            ->when($search, fn (Builder $query, string $term) => $query->where(function (Builder $query) use ($term): void {
                $like = '%'.addcslashes($term, '%_\\').'%';
                $query->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('phone', 'like', $like);
            }))
            ->when($status, fn (Builder $query, string $status) => $query->where('is_active', $status === 'active'))
            ->when($roleId, fn (Builder $query, int|string $roleId) => $query->whereHas('roles', fn (Builder $roles) => $roles->whereKey((int) $roleId)))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(self::PER_PAGE);

        return ApiResponse::success(
            UserResource::collection($users->getCollection())->resolve(),
            ['pagination' => ['page' => $users->currentPage(), 'per_page' => $users->perPage(), 'total' => $users->total(), 'last_page' => $users->lastPage()]],
        );
    }

    public function show(int $user): JsonResponse
    {
        return $this->respond($this->userInBusiness($user));
    }

    public function store(StoreUserRequest $request, CreateUser $create): JsonResponse
    {
        /** @var array{display_name: string, job_title?: ?string}|null $profile */
        $profile = $request->validated('employee_profile');

        $user = $create(
            (string) $request->validated('name'),
            $request->validated('email'),
            $request->validated('phone'),
            (string) $request->validated('password'),
            array_map('intval', (array) $request->validated('role_ids')),
            $profile,
        );

        return $this->respond($user, 201);
    }

    public function update(UpdateUserRequest $request, int $user, UpdateUser $update): JsonResponse
    {
        $record = $this->userInBusiness($user);
        $details = $request->safe()->only(['name', 'email', 'phone']);

        $email = array_key_exists('email', $details) ? $details['email'] : $record->email;
        $phone = array_key_exists('phone', $details) ? $details['phone'] : $record->phone;

        if ($email === null && $phone === null) {
            throw ValidationException::withMessages(['email' => 'Keep an email or a phone number so they can sign in.']);
        }

        /** @var array{display_name?: string, job_title?: ?string, is_active?: bool}|null $profile */
        $profile = $request->validated('employee_profile');

        return $this->respond($update($record, $details, $profile));
    }

    public function disable(DisableUserRequest $request, int $user, DisableUser $disable): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return $this->respond($disable($actor, $this->userInBusiness($user), $request->validated('reason')));
    }

    public function enable(int $user, EnableUser $enable): JsonResponse
    {
        return $this->respond($enable($this->userInBusiness($user)));
    }

    public function resetPassword(ResetPasswordRequest $request, int $user, ResetPassword $reset): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $record = $this->userInBusiness($user);

        $reset($actor, $record, (string) $request->validated('password'));

        return $this->respond($record->refresh());
    }

    public function roles(AssignRolesRequest $request, int $user, AssignRoles $assign): JsonResponse
    {
        $record = $this->userInBusiness($user);

        return $this->respond($assign($record, array_map('intval', (array) $request->validated('role_ids'))));
    }

    private function respond(User $user, int $status = 200): JsonResponse
    {
        $user->unsetRelation('roles')->unsetRelation('employeeProfile')->load(['roles', 'employeeProfile']);

        return ApiResponse::success((new UserResource($user))->resolve(), status: $status);
    }
}
