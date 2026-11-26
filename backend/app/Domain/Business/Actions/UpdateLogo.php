<?php

namespace App\Domain\Business\Actions;

use App\Domain\Business\Models\Business;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stores the business logo on the private disk. It is served through the
 * API, so no public storage symlink is needed on shared hosting.
 */
final class UpdateLogo
{
    public const DISK = 'local';

    public function __construct(private readonly AuditRecorder $audit) {}

    public function __invoke(Business $business, ?UploadedFile $file): Business
    {
        $previous = $business->logo_path;
        $path = $file?->storeAs('logos', $business->id.'-'.now()->format('YmdHis').'.'.$file->extension(), self::DISK);

        if ($path === false) {
            throw new \RuntimeException('The logo could not be stored.');
        }

        DB::transaction(function () use ($business, $path, $previous): void {
            $business->logo_path = $path;
            $business->save();

            $this->audit->record(new AuditEntry('settings.logo_updated', 'business', $business->id, before: ['logo' => $previous], after: ['logo' => $path]));
        });

        if ($previous !== null && $previous !== $path) {
            Storage::disk(self::DISK)->delete($previous);
        }

        return $business;
    }
}
