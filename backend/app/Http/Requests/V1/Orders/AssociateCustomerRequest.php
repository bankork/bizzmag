<?php

namespace App\Http\Requests\V1\Orders;

use Illuminate\Foundation\Http\FormRequest;

class AssociateCustomerRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return ['customer_id' => ['required', 'integer']];
    }
}
