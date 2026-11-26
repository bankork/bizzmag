<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Exceptions\CustomerRemoved;
use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\StaleVersion;
use App\Domain\Shared\Locking\RowLock;
use App\Domain\Shared\Phone\PhoneNumber;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Edits a customer. Only the fields given change. A new phone number that
 * another customer has needs confirmation (FR-CUS-009). The audit event
 * names the changed fields, never their old or new values (SEC-021).
 */
final class UpdateCustomer
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly DuplicatePhoneCheck $duplicates,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{name?: string, phone?: ?string, email?: ?string, notes?: ?string}  $details
     */
    public function __invoke(User $actor, Customer $customer, int $expectedVersion, array $details, bool $confirmDuplicate = false): Customer
    {
        return DB::transaction(function () use ($actor, $customer, $expectedVersion, $details, $confirmDuplicate): Customer {
            $this->locks->forUpdate('customers', $customer->id);
            $customer->refresh();

            if ($customer->isRemoved()) {
                throw new CustomerRemoved;
            }

            if ($customer->version !== $expectedVersion) {
                throw new StaleVersion(context: ['current_version' => $customer->version]);
            }

            if (array_key_exists('name', $details)) {
                $customer->name = trim((string) $details['name']);
            }

            if (array_key_exists('phone', $details)) {
                $customer->phone_e164 = PhoneNumber::normalise($details['phone']);
            }

            if (array_key_exists('email', $details)) {
                $customer->email = $details['email'] === null ? null : mb_strtolower(trim($details['email']));
            }

            if (array_key_exists('notes', $details)) {
                $customer->notes = $details['notes'];
            }

            $changed = array_values(array_intersect(Customer::PERSONAL_FIELDS, array_keys($customer->getDirty())));

            if ($changed === []) {
                return $customer;
            }

            if (in_array('phone_e164', $changed, true)) {
                ($this->duplicates)($customer->phone_e164, $confirmDuplicate, $customer->id);
            }

            $customer->updated_by = $actor->id;
            $customer->version++;
            $customer->save();

            $this->audit->record(new AuditEntry('customer.updated', 'customer', $customer->id, after: ['fields' => $changed]));

            return $customer;
        });
    }
}
