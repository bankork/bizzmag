<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\TaxSetting;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Exceptions\StaleVersion;
use App\Domain\Shared\Locking\RowLock;
use App\Domain\Shared\Tax\TaxMode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Switches tax on or off and sets its name, rate and mode (FR-TAX-001).
 * Applies to invoices finalised afterwards; invoices already final keep the
 * tax they were finalised with (FR-TAX-005). Audited with before and after
 * values (FR-BIZ-006).
 */
final class UpdateTaxSettings
{
    public function __construct(
        private readonly RowLock $locks,
        private readonly AuditRecorder $audit,
    ) {}

    public function __invoke(User $actor, int $expectedVersion, bool $enabled, string $name, int $rateBasisPoints, TaxMode $mode): TaxSetting
    {
        return DB::transaction(function () use ($actor, $expectedVersion, $enabled, $name, $rateBasisPoints, $mode): TaxSetting {
            $tax = TaxSetting::query()->firstOrCreate([]);
            $this->locks->forUpdate('tax_settings', $tax->id);
            $tax->refresh();

            if ($tax->version !== $expectedVersion) {
                throw new StaleVersion(context: ['current_version' => $tax->version]);
            }

            $before = $tax->snapshot();

            $tax->fill(['enabled' => $enabled, 'name' => $name, 'rate_bp' => $rateBasisPoints, 'mode' => $mode]);

            if ($tax->snapshot() === $before) {
                return $tax;
            }

            $tax->version++;
            $tax->updated_by = $actor->id;
            $tax->save();

            $this->audit->record(new AuditEntry('tax.updated', 'tax_setting', $tax->id, before: $before, after: $tax->snapshot()));

            return $tax;
        });
    }
}
