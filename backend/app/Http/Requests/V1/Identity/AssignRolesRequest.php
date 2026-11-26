<?php

namespace App\Http\Requests\V1\Identity;

use App\Domain\Shared\Tenancy\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRolesRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'role_ids' => ['present', 'array'],
            'role_ids.*' => ['integer', 'distinct', Rule::exists('roles', 'id')->where('business_id', app(CurrentBusiness::class)->require())],
        ];
    }
}
