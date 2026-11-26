<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Domain\Audit\AuditEvent;
use App\Domain\Business\Actions\UpdateBusinessSettings;
use App\Domain\Business\Actions\UpdateLogo;
use App\Domain\Business\Actions\UpdateTaxSettings;
use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\TaxSetting;
use App\Domain\Business\Settings\BusinessSettings;
use App\Domain\Shared\Tax\TaxMode;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Controllers\Controller;
use App\Http\Presenters\BusinessPresenter;
use App\Http\Requests\V1\Business\UpdateSettingsRequest;
use App\Http\Requests\V1\Business\UpdateTaxRequest;
use App\Http\Requests\V1\Business\UploadLogoRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Business profile, numbering, windows, idle limits and tax
 * (FR-BIZ-001 to FR-BIZ-006, FR-TAX-001).
 */
class SettingsController extends Controller
{
    /**
     * Audit actions shown in the settings change history.
     */
    private const HISTORY_ACTIONS = ['settings.updated', 'settings.logo_updated', 'tax.updated', 'business.installed'];

    public function __construct(
        private readonly CurrentBusiness $business,
        private readonly BusinessSettings $settings,
    ) {}

    public function show(): JsonResponse
    {
        return $this->respondSettings($this->currentBusiness());
    }

    public function update(UpdateSettingsRequest $request, UpdateBusinessSettings $update): JsonResponse
    {
        $business = $update(
            $this->currentBusiness(),
            (int) $request->validated('version'),
            $request->profileValues(),
            $request->settingValues(),
        );

        return $this->respondSettings($business);
    }

    public function uploadLogo(UploadLogoRequest $request, UpdateLogo $update): JsonResponse
    {
        return $this->respondSettings($update($this->currentBusiness(), $request->file('logo')));
    }

    public function showTax(): JsonResponse
    {
        return ApiResponse::success(BusinessPresenter::tax(TaxSetting::query()->firstOrCreate([])));
    }

    public function updateTax(UpdateTaxRequest $request, UpdateTaxSettings $update): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $tax = $update(
            $actor,
            (int) $request->validated('version'),
            $request->boolean('enabled'),
            (string) $request->validated('name'),
            (int) $request->validated('rate_bp'),
            TaxMode::from((string) $request->validated('mode')),
        );

        return ApiResponse::success(BusinessPresenter::tax($tax));
    }

    public function history(Request $request): JsonResponse
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'filter.area' => ['nullable', 'in:tax,settings']]);
        $area = $request->input('filter.area');

        $actions = match ($area) {
            'tax' => ['tax.updated'],
            'settings' => ['settings.updated', 'settings.logo_updated'],
            default => self::HISTORY_ACTIONS,
        };

        $events = AuditEvent::query()
            ->where('business_id', $this->business->require())
            ->whereIn('action', $actions)
            ->with('actor:id,name')
            ->orderByDesc('id')
            ->paginate(20);

        return ApiResponse::success(
            $events->getCollection()->map(fn (AuditEvent $event) => [
                'id' => $event->id,
                'action' => $event->action,
                'actor' => $event->actor === null ? null : ['id' => $event->actor->id, 'name' => $event->actor->name],
                'before' => $event->before,
                'after' => $event->after,
                'at' => $event->created_at?->toIso8601String(),
            ])->values()->all(),
            ['pagination' => ['page' => $events->currentPage(), 'per_page' => $events->perPage(), 'total' => $events->total(), 'last_page' => $events->lastPage()]],
        );
    }

    private function currentBusiness(): Business
    {
        return Business::query()->findOrFail($this->business->require());
    }

    private function respondSettings(Business $business): JsonResponse
    {
        return ApiResponse::success(BusinessPresenter::settings($business, $this->settings->all()));
    }
}
