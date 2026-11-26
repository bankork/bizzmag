<?php

namespace App\Domain\Identity\Models;

use App\Domain\Shared\Tenancy\BelongsToBusiness;
use App\Models\User;
use Database\Factories\EmployeeProfileFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A person who can be credited for salon work.
 *
 * @property int $id
 * @property int $business_id
 * @property int $user_id
 * @property string $display_name
 * @property ?string $job_title
 * @property bool $is_active
 */
#[UseFactory(EmployeeProfileFactory::class)]
class EmployeeProfile extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<EmployeeProfileFactory> */
    use HasFactory;

    protected $fillable = ['business_id', 'user_id', 'display_name', 'job_title', 'is_active'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
