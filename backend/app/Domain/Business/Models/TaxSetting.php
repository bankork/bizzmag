<?php

namespace App\Domain\Business\Models;

use App\Domain\Shared\Tax\TaxMode;
use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

/**
 * The single tax of a business (FR-TAX-001, FR-TAX-008). Off on a new
 * installation. When on, it applies to every non-exempt line of invoices
 * finalised afterwards.
 *
 * @property int $id
 * @property int $business_id
 * @property bool $enabled
 * @property string $name
 * @property int $rate_bp
 * @property TaxMode $mode
 * @property int $version
 * @property ?int $updated_by
 */
class TaxSetting extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'enabled', 'name', 'rate_bp', 'mode', 'version', 'updated_by'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'rate_bp' => 'integer',
            'mode' => TaxMode::class,
            'version' => 'integer',
        ];
    }

    /**
     * @return array{enabled: bool, name: string, rate_bp: int, mode: string}
     */
    public function snapshot(): array
    {
        return [
            'enabled' => $this->enabled,
            'name' => $this->name,
            'rate_bp' => $this->rate_bp,
            'mode' => $this->mode->value,
        ];
    }
}
