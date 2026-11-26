<?php

namespace App\Domain\Business\Models;

use App\Domain\Shared\Tenancy\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

/**
 * One configurable value (FR-BIZ-005). Keys and defaults are defined in
 * App\Domain\Business\Settings\SettingsCatalogue.
 *
 * @property int $id
 * @property int $business_id
 * @property string $key
 * @property mixed $value
 * @property int $version
 */
class BusinessSetting extends Model
{
    use BelongsToBusiness;

    protected $fillable = ['business_id', 'key', 'value', 'version'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json', 'version' => 'integer'];
    }
}
