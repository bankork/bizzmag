<?php

namespace App\Http\Controllers\Api\V1\Business;

use App\Domain\Business\Actions\UpdateLogo;
use App\Domain\Business\Models\Business;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves the logo printed on receipts and shown on the sign-in page. It is
 * public on purpose: it carries no private data.
 */
class LogoController extends Controller
{
    public function __invoke(int $business): Response
    {
        $path = Business::query()->whereKey($business)->value('logo_path');

        if (! is_string($path) || ! Storage::disk(UpdateLogo::DISK)->exists($path)) {
            throw new NotFoundHttpException;
        }

        return Storage::disk(UpdateLogo::DISK)->response($path, null, [
            'Cache-Control' => 'public, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
