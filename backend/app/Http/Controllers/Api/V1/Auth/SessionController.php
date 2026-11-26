<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Business\Models\Business;
use App\Domain\Identity\Actions\SignIn;
use App\Domain\Shared\Audit\AuditEntry;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Controllers\Controller;
use App\Http\Middleware\EnforceIdleTimeout;
use App\Http\Presenters\MePresenter;
use App\Http\Requests\V1\Auth\LoginRequest;
use App\Http\Responses\ApiResponse;
use App\Http\Support\SessionEnder;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Cookie session sign-in and sign-out (SEC-023). The web app first calls
 * GET /sanctum/csrf-cookie, then posts here with the X-XSRF-TOKEN header.
 */
class SessionController extends Controller
{
    public function store(LoginRequest $request, SignIn $signIn, CurrentBusiness $business, MePresenter $me): JsonResponse
    {
        ['user' => $user, 'business_id' => $businessId] = $signIn(
            (string) $request->validated('identifier'),
            (string) $request->validated('password'),
            (string) $request->ip(),
        );

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->put(EnforceIdleTimeout::SESSION_KEY, now()->getTimestamp());

        $business->set($businessId);

        return ApiResponse::success($me->present($user, Business::query()->findOrFail($businessId)));
    }

    public function destroy(Request $request, AuditRecorder $audit): JsonResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            $audit->record(new AuditEntry('auth.logout', 'user', $user->id));
        }

        SessionEnder::end($request);

        return ApiResponse::success(null);
    }
}
