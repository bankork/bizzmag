<?php

namespace App\Http\Presenters;

use App\Domain\Customers\Models\Customer;
use App\Models\User;

/**
 * API view of a customer. Phone and email are left out entirely for users
 * without customer.view_contact (SEC-021); has_phone and has_email still
 * tell the desk whether they exist.
 */
final class CustomerPresenter
{
    public static function canSeeContact(?User $user): bool
    {
        return $user !== null && $user->checkPermissionTo('customer.view_contact');
    }

    /**
     * @return array<string, mixed>
     */
    public static function customer(Customer $customer, bool $withContact): array
    {
        $view = [
            'id' => $customer->id,
            'name' => $customer->name,
            'has_phone' => $customer->phone_e164 !== null,
            'has_email' => $customer->email !== null,
        ];

        if ($withContact) {
            $view['phone'] = $customer->phone_e164;
            $view['phone_display'] = self::localPhone($customer->phone_e164);
            $view['email'] = $customer->email;
        }

        return $view + [
            'notes' => $customer->notes,
            'is_removed' => $customer->isRemoved(),
            'removed_at' => $customer->anonymised_at?->toIso8601String(),
            'version' => $customer->version,
            'created_at' => $customer->created_at?->toIso8601String(),
        ];
    }

    /**
     * Nigerian numbers the way people write them: 0803 000 4821.
     */
    public static function localPhone(?string $e164): ?string
    {
        if ($e164 === null || ! str_starts_with($e164, '+234') || strlen($e164) !== 14) {
            return $e164;
        }

        $local = '0'.substr($e164, 4);

        return substr($local, 0, 4).' '.substr($local, 4, 3).' '.substr($local, 7);
    }
}
