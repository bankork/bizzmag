<?php

namespace App\Domain\Orders\Models;

use App\Domain\Customers\Models\Customer;
use App\Domain\Shared\Tenancy\BelongsToBusiness;
use App\Models\User;
use Database\Factories\ServiceOrderFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One customer visit, with one reference (SRS §1.3, FR-ORD-008). The order
 * row is what every command locks before it checks anything (§7.7.2).
 *
 * @property int $id
 * @property int $business_id
 * @property string $reference
 * @property ?int $customer_id
 * @property ?string $guest_name
 * @property ?string $guest_phone_e164
 * @property OrderStatus $status
 * @property Carbon $opened_at
 * @property string $opened_business_day
 * @property ?Carbon $finalised_at
 * @property ?Carbon $late_window_ends_at
 * @property ?Carbon $cancelled_at
 * @property ?int $cancelled_by
 * @property ?string $cancel_reason
 * @property int $created_by
 * @property ?Carbon $created_at
 * @property-read ?Customer $customer
 * @property-read User $creator
 * @property-read Collection<int, OrderItem> $items
 */
#[UseFactory(ServiceOrderFactory::class)]
class ServiceOrder extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<ServiceOrderFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['status' => 'open'];

    protected $fillable = [
        'business_id', 'reference', 'customer_id', 'guest_name', 'guest_phone_e164', 'status', 'opened_at',
        'opened_business_day', 'finalised_at', 'finalised_business_day', 'late_window_ends_at', 'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'opened_at' => 'datetime',
            'finalised_at' => 'datetime',
            'late_window_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return $this->status === OrderStatus::Open;
    }

    /**
     * What staff call the visit: the customer's or guest's name, or "Guest".
     */
    public function displayName(): string
    {
        return $this->customer->name ?? ($this->guest_name ?? 'Guest');
    }
}
