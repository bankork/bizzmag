<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Domain\Business\Models\Business;
use App\Domain\Identity\Actions\ChangeOwnPassword;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Http\Controllers\Controller;
use App\Http\Presenters\MePresenter;
use App\Http\Requests\V1\Auth\ChangePasswordRequest;
use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request, CurrentBusiness $business, MePresenter $me): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return ApiResponse::success($me->present($user, Business::query()->findOrFail($business->require())));
    }

    public function updatePassword(ChangePasswordRequest $request, ChangeOwnPassword $change, CurrentBusiness $business, MePresenter $me): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $change($user, (string) $request->validated('current_password'), (string) $request->validated('password'), $request->session()->getId());

        $request->session()->migrate(true);

        return ApiResponse::success($me->present($user->refresh(), Business::query()->findOrFail($business->require())));
    }
}
