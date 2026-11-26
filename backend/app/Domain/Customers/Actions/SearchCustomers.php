<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Phone\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Customer search by name or phone (FR-CUS-008). A phone can be typed any
 * way (0803..., 234803..., +234 803...) or as its last digits. Phone search
 * is only offered to users who may see phone numbers, so nobody can confirm
 * a number by guessing it (SEC-021). Removed customers are left out.
 */
final class SearchCustomers
{
    public const PER_PAGE = 20;

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function __invoke(?string $term, bool $byPhone, int $page = 1): LengthAwarePaginator
    {
        $term = trim((string) $term);

        return Customer::query()
            ->whereNull('anonymised_at')
            ->when($term !== '', function (Builder $query) use ($term, $byPhone): void {
                $query->where(function (Builder $query) use ($term, $byPhone): void {
                    $query->where('name', 'like', '%'.addcslashes($term, '%_\\').'%');

                    $digits = $byPhone ? $this->phoneDigits($term) : null;

                    if ($digits !== null) {
                        $query->orWhere('phone_e164', 'like', '%'.$digits.'%');
                    }
                });
            })
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(self::PER_PAGE, page: $page);
    }

    /**
     * The digits to look for in an E.164 number, or null when the term is
     * not a phone number.
     */
    private function phoneDigits(string $term): ?string
    {
        if (preg_match('/^[\d\s+\-().]+$/', $term) !== 1) {
            return null;
        }

        $full = PhoneNumber::normalise($term);

        if ($full !== null) {
            return ltrim($full, '+');
        }

        $digits = preg_replace('/\D/', '', $term) ?? '';

        if (strlen($digits) < 3) {
            return null;
        }

        // A local number starts with 0, which is not in the E.164 form.
        return str_starts_with($digits, '0') ? substr($digits, 1) : $digits;
    }
}
