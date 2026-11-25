<?php

namespace App\Console\Preflight\Checks;

use App\Console\Preflight\CheckResult;
use App\Console\Preflight\PreflightCheck;

/**
 * Cookie login (Sanctum SPA, S2-D02) only works when the web app and the
 * API agree on domains. This check catches the mistakes that make login
 * silently fail: a missing CORS origin, a missing stateful domain, a session
 * cookie domain that does not cover both hosts, or a *.vercel.app address.
 *
 * Problems are failures in production and warnings elsewhere.
 */
final class DomainConsistencyCheck implements PreflightCheck
{
    public function run(): array
    {
        $production = app()->isProduction();
        $frontend = parse_url((string) config('bizzmag.frontend_url')) ?: [];
        $api = parse_url((string) config('app.url')) ?: [];
        $frontendHost = strtolower($frontend['host'] ?? '');
        $apiHost = strtolower($api['host'] ?? '');

        if ($frontendHost === '' || $apiHost === '') {
            return [$this->problem($production, 'Frontend and API URLs', 'FRONTEND_URL or APP_URL is missing or not a full URL.', 'Set FRONTEND_URL=https://app.your-domain and APP_URL=https://api.your-domain.')];
        }

        $frontendOrigin = $this->origin($frontend);

        return array_values(array_filter([
            $this->vercelPreviewDomain($frontendHost),
            $this->corsOrigin($production, $frontendOrigin),
            $this->statefulDomain($production, $frontend),
            $this->sessionDomain($production, $frontendHost, $apiHost),
            $this->secureCookies($production),
        ]));
    }

    private function vercelPreviewDomain(string $frontendHost): ?CheckResult
    {
        if (! str_ends_with($frontendHost, '.vercel.app')) {
            return null;
        }

        return CheckResult::fail(
            'Vercel domain',
            "{$frontendHost} is a vercel.app address. Browsers will not send the login cookie from it to the API.",
            'Add a custom domain in Vercel that shares the API\'s parent domain (for example app.your-domain with api.your-domain) and use it as FRONTEND_URL.',
        );
    }

    private function corsOrigin(bool $production, string $frontendOrigin): CheckResult
    {
        $allowed = array_map(fn ($origin) => rtrim((string) $origin, '/'), (array) config('cors.allowed_origins'));

        if (in_array('*', $allowed, true)) {
            return $this->problem($production, 'CORS origin', 'CORS allows every origin. Credentials cannot be used with a wildcard.', 'Remove the wildcard. FRONTEND_URL drives the allowed origin.');
        }

        return in_array($frontendOrigin, $allowed, true)
            ? CheckResult::pass('CORS origin', "{$frontendOrigin} is allowed")
            : $this->problem($production, 'CORS origin', "{$frontendOrigin} is not an allowed CORS origin.", 'Set FRONTEND_URL to the exact web app origin, then run php artisan config:cache.');
    }

    /**
     * @param  array<string, mixed>  $frontend
     */
    private function statefulDomain(bool $production, array $frontend): CheckResult
    {
        $hostWithPort = strtolower($frontend['host']).(isset($frontend['port']) ? ':'.$frontend['port'] : '');
        $stateful = array_map(fn ($domain) => strtolower(trim((string) $domain)), (array) config('sanctum.stateful'));

        return in_array($hostWithPort, $stateful, true)
            ? CheckResult::pass('Sanctum stateful domain', "{$hostWithPort} is stateful")
            : $this->problem($production, 'Sanctum stateful domain', "{$hostWithPort} is not in SANCTUM_STATEFUL_DOMAINS.", "Set SANCTUM_STATEFUL_DOMAINS={$hostWithPort}");
    }

    private function sessionDomain(bool $production, string $frontendHost, string $apiHost): CheckResult
    {
        if ($frontendHost === $apiHost) {
            return CheckResult::pass('Session cookie domain', 'Web app and API share one host');
        }

        $domain = ltrim(strtolower((string) config('session.domain')), '.');

        if ($domain === '') {
            return $this->problem($production, 'Session cookie domain', "The web app ({$frontendHost}) and API ({$apiHost}) are on different hosts but SESSION_DOMAIN is empty.", 'Set SESSION_DOMAIN=.your-domain so both hosts share the login cookie.');
        }

        $covers = fn (string $host) => $host === $domain || str_ends_with($host, '.'.$domain);

        return $covers($frontendHost) && $covers($apiHost)
            ? CheckResult::pass('Session cookie domain', ".{$domain} covers both hosts")
            : $this->problem($production, 'Session cookie domain', ".{$domain} does not cover both {$frontendHost} and {$apiHost}.", 'Put the web app and API on subdomains of one domain and set SESSION_DOMAIN to that domain.');
    }

    private function secureCookies(bool $production): ?CheckResult
    {
        if (! $production) {
            return null;
        }

        if (! config('session.secure')) {
            return CheckResult::fail('Secure cookies', 'SESSION_SECURE_COOKIE is not true.', 'Set SESSION_SECURE_COOKIE=true.');
        }

        $sameSite = strtolower((string) config('session.same_site'));

        return in_array($sameSite, ['lax', 'strict'], true)
            ? CheckResult::pass('Secure cookies', "Secure, SameSite={$sameSite}")
            : CheckResult::fail('Secure cookies', "SameSite is \"{$sameSite}\".", 'Set SESSION_SAME_SITE=lax.');
    }

    /**
     * @param  array<string, mixed>  $url
     */
    private function origin(array $url): string
    {
        return strtolower(($url['scheme'] ?? 'https').'://'.$url['host'].(isset($url['port']) ? ':'.$url['port'] : ''));
    }

    private function problem(bool $production, string $name, string $detail, string $fix): CheckResult
    {
        return $production ? CheckResult::fail($name, $detail, $fix) : CheckResult::warn($name, $detail, $fix);
    }
}
