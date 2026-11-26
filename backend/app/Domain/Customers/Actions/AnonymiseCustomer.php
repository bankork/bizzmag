<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Exceptions\CustomerRemoved;
use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Locking\RowLock;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Removes a customer's personal data on request (SEC-021, Nigeria Data
 * Protection Act 2023). Owner only, enforced on the route. The record stays,
 * so visits and payments remain complete, but the name becomes "Removed
 * customer" and the phone, email and notes are cleared. Cannot be undone.
 */
final class AnonymiseCustomer
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, Customer $customer): Customer
    {
        return DB::transaction(function () use ($actor, $customer): Customer {
            $this->locks->forUpdate('customers', $customer->id);
            $customer->refresh();

            if ($customer->isRemoved()) {
                throw new CustomerRemoved('This customer\'s personal data was already removed.');
            }

            $customer->forceFill([
                'name' => Customer::REMOVED_NAME,
                'phone_e164' => null,
                'email' => null,
                'notes' => null,
                'anonymised_at' => now(),
                'anonymised_by' => $actor->id,
                'updated_by' => $actor->id,
                'version' => $customer->version + 1,
            ])->save();

            $this->audit->record(new AuditEntry('customer.anonymised', 'customer', $customer->id, after: ['fields' => Customer::PERSONAL_FIELDS]));

            return $customer;
        });
    }
}
