<?php

namespace App\Http\Requests\V1\Identity;

use App\Domain\Identity\Permissions\PermissionCatalogue;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create or edit a role. Caps are in basis points (1,000 = 10%) and kobo;
 * null means unlimited.
 */
class SaveRoleRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('post');
        $roleId = $creating ? null : (int) $this->route('role');

        return [
            'name' => [$creating ? 'required' : 'sometimes', 'string', 'max:60',
                Rule::unique('roles', 'name')->where('business_id', app(CurrentBusiness::class)->require())->where('guard_name', 'web')->ignore($roleId)],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'discount_cap_bp' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10000'],
            'discount_cap_kobo' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100000000000'],
            'permissions' => [$creating ? 'required' : 'sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::in(PermissionCatalogue::names())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['name.unique' => 'Another role already has this name.'];
    }
}
