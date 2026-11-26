<?php

namespace App\Domain\Business\Models;

use App\Models\User;
use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * The salon: the root of the business scope (FR-BIZ-001).
 *
 * @property int $id
 * @property string $name
 * @property string $currency
 * @property string $timezone
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $address
 * @property ?string $receipt_footer
 * @property ?string $invoice_footer
 * @property ?string $logo_path
 * @property int $settings_version
 */
#[UseFactory(BusinessFactory::class)]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    protected $fillable = [
        'name', 'currency', 'timezone', 'phone', 'email', 'address',
        'receipt_footer', 'invoice_footer', 'logo_path', 'settings_version',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['settings_version' => 'integer'];
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_active')->withTimestamps();
    }

    /**
     * @return HasMany<BusinessSetting, $this>
     */
    public function settings(): HasMany
    {
        return $this->hasMany(BusinessSetting::class);
    }

    /**
     * @return HasOne<TaxSetting, $this>
     */
    public function taxSetting(): HasOne
    {
        return $this->hasOne(TaxSetting::class);
    }
}
