<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Domain\Identity\Models\EmployeeProfile;
use App\Domain\Identity\Support\SalonStaff;
use App\Http\Controllers\Api\V1\Concerns\FindsVisits;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The people who can be assigned to or credited for work: names and job
 * titles only, never contact details.
 */
class StaffDirectoryController extends Controller
{
    use FindsVisits;

    public function __invoke(Request $request, SalonStaff $staff): JsonResponse
    {
        $me = $staff->profileFor($this->viewer($request))?->id;

        return ApiResponse::success($staff->eligibleQuery()->orderBy('display_name')->get()->map(fn (EmployeeProfile $profile) => [
            'id' => $profile->id,
            'name' => $profile->display_name,
            'job_title' => $profile->job_title,
            'is_me' => $profile->id === $me,
        ])->values()->all());
    }
}
