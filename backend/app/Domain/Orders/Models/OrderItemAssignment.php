<?php

namespace App\Domain\Orders\Models;

use App\Domain\Identity\Models\EmployeeProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An employee expected to do a service (FR-ORD-005). Scoped through its item.
 *
 * @property int $id
 * @property int $order_item_id
 * @property int $employee_id
 * @property int $assigned_by
 * @property-read EmployeeProfile $employee
 */
class OrderItemAssignment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['order_item_id', 'employee_id', 'assigned_by'];

    /**
     * @return BelongsTo<EmployeeProfile, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_id');
    }
}
