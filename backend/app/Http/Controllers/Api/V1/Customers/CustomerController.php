<?php

namespace App\Http\Controllers\Api\V1\Customers;

use App\Domain\Customers\Actions\AnonymiseCustomer;
use App\Domain\Customers\Actions\CreateCustomer;
use App\Domain\Customers\Actions\SearchCustomers;
use App\Domain\Customers\Actions\UpdateCustomer;
use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Exceptions\RecordNotFound;
use App\Http\Controllers\Controller;
use App\Http\Presenters\CustomerPresenter;
use App\Http\Requests\V1\Customers\AnonymiseCustomerRequest;
use App\Http\Requests\V1\Customers\StoreCustomerRequest;
use App\Http\Requests\V1\Customers\UpdateCustomerRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Registered customers (FR-CUS-001, 002, 008, 009) and removal of their
 * personal data (SEC-021).
 */
class CustomerController extends Controller
{
    public function index(Request $request, SearchCustomers $search): JsonResponse
    {
        $filters = $request->validate([
            'filter.q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $contact = CustomerPresenter::canSeeContact($this->user($request));
        $customers = $search($filters['filter']['q'] ?? null, byPhone: $contact, page: (int) ($filters['page'] ?? 1));

        return ApiResponse::success(
            $customers->getCollection()->map(fn (Customer $customer) => CustomerPresenter::customer($customer, $contact))->values()->all(),
            ['pagination' => ['page' => $customers->currentPage(), 'per_page' => $customers->perPage(), 'total' => $customers->total(), 'last_page' => $customers->lastPage()]],
        );
    }

    public function show(Request $request, int $customer): JsonResponse
    {
        return $this->respond($this->find($customer), $request);
    }

    public function store(StoreCustomerRequest $request, CreateCustomer $create): JsonResponse
    {
        /** @var array{name: string, phone?: ?string, email?: ?string, notes?: ?string} $details */
        $details = $request->safe()->only(['name', 'phone', 'email', 'notes']);

        return $this->respond($create($this->user($request), $details, $request->boolean('confirm_duplicate')), $request, 201);
    }

    public function update(UpdateCustomerRequest $request, int $customer, UpdateCustomer $update): JsonResponse
    {
        /** @var array{name?: string, phone?: ?string, email?: ?string, notes?: ?string} $details */
        $details = $request->safe()->only(['name', 'phone', 'email', 'notes']);

        $record = $update($this->user($request), $this->find($customer), (int) $request->validated('version'), $details, $request->boolean('confirm_duplicate'));

        return $this->respond($record, $request);
    }

    public function anonymise(AnonymiseCustomerRequest $request, int $customer, AnonymiseCustomer $anonymise): JsonResponse
    {
        return $this->respond($anonymise($this->user($request), $this->find($customer)), $request);
    }

    /**
     * Inside the current business only; another salon's customer is not found.
     */
    private function find(int $id): Customer
    {
        return Customer::query()->whereKey($id)->first() ?? throw new RecordNotFound;
    }

    private function respond(Customer $customer, Request $request, int $status = 200): JsonResponse
    {
        return ApiResponse::success(CustomerPresenter::customer($customer, CustomerPresenter::canSeeContact($this->user($request))), status: $status);
    }

    private function user(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }
}
