# Hosting the web app on Vercel

The API always runs on cPanel or a VPS ([deploy-cpanel.md](deploy-cpanel.md) or [deploy-vps.md](deploy-vps.md)). Only the web app moves to Vercel. Vercel builds it from GitHub, so you do not need the `bizzmag-app` package.

## The one rule: use a custom subdomain

Login uses a secure cookie that must be shared between the web app and the API. That only works when both are on **subdomains of the same domain**:

| Web app | API | Login works? |
|---|---|---|
| `app.your-domain` (on Vercel) | `api.your-domain` (cPanel or VPS) | **Yes** |
| `bizzmag.vercel.app` | `api.your-domain` | **No.** Browsers treat it as a different site. |
| Vercel preview links (`...-git-branch.vercel.app`) | `api.your-domain` | **No.** Use them only to look at screens. |

`app:preflight` on the API fails with a clear message if `FRONTEND_URL` is a `vercel.app` address.

## Steps

1. **Create the project.** In Vercel, choose **Add New > Project** and import the GitHub repository.
2. **Set the root directory** to `frontend`. Everything else comes from [`frontend/vercel.json`](../../frontend/vercel.json):
   - build command `pnpm build`, which also generates the service worker;
   - output directory `out`;
   - security and cache headers.
3. **Add environment variables** (Production):
   - `NEXT_PUBLIC_API_URL` = `https://api.your-domain`
   - `NEXT_PUBLIC_APP_ENV` = `production`
4. **Deploy**, then open **Settings > Domains** and add `app.your-domain`. Create the DNS record Vercel shows you (usually a CNAME to `cname.vercel-dns.com`) in cPanel > Zone Editor, or wherever your DNS is hosted.
5. **Point the API at the Vercel address.** In the API's `shared/.env`:

   ```dotenv
   FRONTEND_URL=https://app.your-domain
   SANCTUM_STATEFUL_DOMAINS=app.your-domain
   SESSION_DOMAIN=.your-domain
   ```

   Then run `php artisan config:cache`, then `php artisan app:preflight`, in `current/`.
6. **Check.** Open `https://app.your-domain`. The start page should show **"Connected to ... API"**.

## Notes

- Changing `NEXT_PUBLIC_API_URL` needs a **redeploy**, because the value is built into the app.
- Vercel deploys every push to the production branch automatically. Release only from the branch you trust, normally `main`.
- **Moving between Vercel and cPanel** needs no code change. Build the `bizzmag-app` package and follow Part B of the cPanel runbook, or connect Vercel. Either way, keep the same `app.your-domain` address.
