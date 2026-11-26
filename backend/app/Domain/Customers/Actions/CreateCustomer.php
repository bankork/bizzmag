<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Phone\PhoneNumber;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Registers a customer (FR-CUS-001, FR-CUS-002, FR-CUS-009). The phone is
 * stored in E.164 form. The audit event names the fields given, not their
 * values (SEC-021).
 */
final class CreateCustomer
{
    public function __construct(
        private readonly DuplicatePhoneCheck $duplicates,
        private readonly AuditRecorder $audit,
    ) {}

    /**
     * @param  array{name: string, phone?: ?string, email?: ?string, notes?: ?string}  $details
     */
    public function __invoke(User $actor, array $details, bool $confirmDuplicate = false): Customer
    {
        return DB::transaction(function () use ($actor, $details, $confirmDuplicate): Customer {
            $phone = PhoneNumber::normalise($details['phone'] ?? null);
            ($this->duplicates)($phone, $confirmDuplicate);

            $customer = Customer::query()->create([
                'name' => trim($details['name']),
                'phone_e164' => $phone,
                'email' => isset($details['email']) ? mb_strtolower(trim($details['email'])) : null,
                'notes' => $details['notes'] ?? null,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $given = array_values(array_filter(Customer::PERSONAL_FIELDS, fn (string $field) => $customer->getAttribute($field) !== null));

            $this->audit->record(new AuditEntry('customer.created', 'customer', $customer->id, after: [
                'fields' => $given,
                'duplicate_confirmed' => $confirmDuplicate && $phone !== null && $this->sharesPhone($customer),
            ]));

            return $customer;
        });
    }

    private function sharesPhone(Customer $customer): bool
    {
        return Customer::query()->where('phone_e164', $customer->phone_e164)->whereKeyNot($customer->id)->exists();
    }
}
