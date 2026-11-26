<?php

namespace App\Http\Requests\V1\Customers;

use App\Http\Requests\V1\Customers\Concerns\CustomerRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
{
    use CustomerRules;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return $this->customerRules('required');
    }
}
