<?php

namespace App\Domain\Customers\Models;

use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Database\Factories\CustomerFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A registered customer (FR-CUS-001, FR-CUS-002). Never deleted. A removal
 * request clears the personal data and keeps the record, so visits and
 * payments stay complete (SEC-021).
 *
 * @property int $id
 * @property int $business_id
 * @property string $name
 * @property ?string $phone_e164
 * @property ?string $email
 * @property ?string $notes
 * @property ?Carbon $anonymised_at
 * @property ?int $anonymised_by
 * @property ?int $created_by
 * @property ?int $updated_by
 * @property int $version
 * @property ?Carbon $created_at
 */
#[UseFactory(CustomerFactory::class)]
class Customer extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<CustomerFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = ['version' => 1];

    public const REMOVED_NAME = 'Removed customer';

    /**
     * Personal data under SEC-021. The audit log records only which of
     * these changed, never their values.
     */
    public const PERSONAL_FIELDS = ['name', 'phone_e164', 'email', 'notes'];

    protected $fillable = ['business_id', 'name', 'phone_e164', 'email', 'notes', 'created_by', 'updated_by', 'version'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'anonymised_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function isRemoved(): bool
    {
        return $this->anonymised_at !== null;
    }
}
