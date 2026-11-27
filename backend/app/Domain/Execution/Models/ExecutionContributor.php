<?php

namespace App\Domain\Execution\Models;

use App\Domain\Identity\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee credited on a recorded service, with their share in basis
 * points (FR-ATTR-001 to 003). The shares on one record total 10000.
 * Scoped through its execution.
 *
 * @property int $id
 * @property int $execution_id
 * @property int $employee_id
 * @property bool $is_primary
 * @property int $share_bp
 * @property JoinedVia $joined_via
 * @property-read EmployeeProfile $employee
 */
class ExecutionContributor extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['execution_id', 'employee_id', 'is_primary', 'share_bp', 'joined_via'];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'employee_id' => 'integer',
            'is_primary' => 'boolean',
            'share_bp' => 'integer',
            'joined_via' => JoinedVia::class,
        ];
    }

    /**
     * @return BelongsTo<EmployeeProfile, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }
}
