<?php

namespace App\Http\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Signs the current browser out and throws away its session.
 */
final class SessionEnder
{
    public static function end(Request $request): void
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
