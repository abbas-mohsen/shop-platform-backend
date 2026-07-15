# Deploying XTREMEFIT

The system is two deployable parts plus a database:

| Part          | What it is            | Suggested free/cheap host          |
| ------------- | --------------------- | ---------------------------------- |
| Frontend      | React (CRA) static build | Vercel or Netlify (free subdomain) |
| Backend       | Laravel 8 API (PHP 8.0/8.1) | Shared cPanel host / Railway / Render |
| Database      | MySQL                 | The host's MySQL, or a managed MySQL |
| Uploaded files| Product images, hero video | Host with a **persistent disk**, or S3/R2 |

> **Note on PHP:** developed locally on PHP 7.4 (end-of-life). Laravel 8 runs
> fine on **PHP 8.0 / 8.1** — deploy on 8.x, which every host offers.

> **The one gotcha — file storage:** uploads save to local disk
> (`storage/app/public`). On ephemeral hosts (Railway/Render) they vanish on
> redeploy. Two supported options, switched by the `MEDIA_DISK` env var:
>
> - **Persistent-disk host (cPanel/Hostinger):** do nothing — `MEDIA_DISK=public`.
> - **Cloudflare R2 (recommended for PaaS):** `MEDIA_DISK=s3` — see §2b below.
>
> The upload code reads `config('filesystems.media_disk')`, so no code change is
> needed to switch; it's purely env.

---

## 1. Backend (Laravel)

1. Upload / connect the `shop-platform` repo to the host.
2. `composer install --no-dev --optimize-autoloader`
3. Copy `.env.production.example` → `.env` and fill it in.
4. `php artisan key:generate`
5. Point the web server's document root at the **`public/`** folder.
6. `php artisan migrate --force --seed`  ← seeds demo store + admin
7. `php artisan storage:link`
8. `php artisan config:cache && php artisan route:cache`
9. Make sure PHP upload limits are raised (host php.ini):
   `post_max_size = 115M`, `upload_max_filesize = 110M` (for hero video).

**Key `.env` values:** `APP_URL` (this API's https URL), `APP_DEBUG=false`,
`CORS_ALLOWED_ORIGINS` (the frontend URL), DB creds, `OPENAI_API_KEY`,
mail creds, `SUPER_ADMIN_EMAIL` (the permanent owner account).

## 2b. Cloudflare R2 for uploads (recommended, free)

Do this only if you chose the R2 option. R2 keeps your images safe even when the
backend host redeploys or is swapped.

1. Cloudflare dashboard → **R2** → **Create bucket** (e.g. `xtremefit-media`).
2. Bucket → **Settings** → **Public access** → enable the **r2.dev public URL**.
   Copy it — it looks like `https://pub-xxxxxxxx.r2.dev`.
3. R2 → **Manage API Tokens** → create a token with **Object Read & Write** for
   that bucket. Copy the Access Key ID + Secret Access Key.
4. On the backend host (PHP 8.x), install the S3 adapter once:
   ```
   composer require league/flysystem-aws-s3-v3
   ```
   (Not committed to the repo because the PHP-7.4-compatible AWS SDK versions
   carry security advisories; on PHP 8.x this pulls the current, secure release.)
5. In the backend `.env`, set `MEDIA_DISK=s3` and the `AWS_*` block (see
   `.env.production.example`): `AWS_DEFAULT_REGION=auto`,
   `AWS_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com`,
   `AWS_USE_PATH_STYLE_ENDPOINT=true`, `AWS_URL=<the r2.dev public URL>`.
6. `php artisan config:cache`.
7. **Frontend:** set `REACT_APP_STORAGE_URL` to the **r2.dev public URL** (not the
   backend `/storage` path), then redeploy the frontend.

Existing local images won't move automatically — re-upload them from the admin,
or re-run the seeders after switching, so their files land in R2.

## 2. Frontend (React)

On Vercel/Netlify, import the `shop-frontend` repo and set these **build-time**
Environment Variables (see `.env.production.example`):

- `REACT_APP_API_BASE_URL` = the backend https URL
- `REACT_APP_STORAGE_URL`  = backend URL + `/storage`
- `REACT_APP_APP_NAME`     = `XtremeFit`
- `REACT_APP_GOOGLE_MAPS_KEY` = your Maps key

Build command `npm run build`, output dir `build`. SPA deep-link rewrites are
already configured (`vercel.json` + `public/_redirects`).

## 3. Wire the two together

1. Deploy the backend first; note its final URL.
2. Set the frontend env vars to that URL; deploy the frontend; note its URL.
3. Back on the backend, set `CORS_ALLOWED_ORIGINS` + `FRONTEND_URL` to the
   frontend URL, then `php artisan config:cache` again.

## 4. Smoke test after going live

- [ ] Homepage loads, products + images show
- [ ] Register / login / logout
- [ ] Add to cart → checkout (map picker loads) → place order
- [ ] Semantic search returns results (needs `OPENAI_API_KEY`)
- [ ] Virtual try-on runs
- [ ] Admin login lands in admin mode; dashboard has data
- [ ] Download invoice PDF from an order
- [ ] Order + password-reset emails arrive (check spam)

## 5. Custom domain (later)

Both hosts let you attach a domain for free later; point the frontend at
`www.` and the backend at `api.`, then update the env URLs + `CORS_ALLOWED_ORIGINS`.
