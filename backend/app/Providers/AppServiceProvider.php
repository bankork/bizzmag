<?php

namespace App\Providers;

use App\Domain\Audit\DatabaseAuditRecorder;
use App\Domain\Notifications\Listeners\WorkNotifications;
use App\Domain\Orders\Support\EloquentOrderLock;
use App\Domain\Shared\Audit\AuditRecorder;
use App\Domain\Shared\Locking\OrderLock;
use App\Domain\Shared\Tenancy\CurrentBusiness;
use App\Rules\NotCommonPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AuditRecorder::class, DatabaseAuditRecorder::class);
        $this->app->bind(OrderLock::class, EloquentOrderLock::class);

        // One business scope per request (or per queued job / command).
        $this->app->scoped(CurrentBusiness::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        // In-app notifications for visit and work events (SRS §4.22).
        Event::subscribe(WorkNotifications::class);

        // SEC-019: at least 8 characters and not a commonly guessed password.
        Password::defaults(fn () => Password::min(8)->max(200)->rules([new NotCommonPassword]));

        // A coarse limit on the sign-in endpoint per address. The real
        // lockout (SEC-017) is LoginThrottle, which also audits.
        RateLimiter::for('login', fn (Request $request) => Limit::perMinute(30)->by((string) $request->ip()));
    }
}
