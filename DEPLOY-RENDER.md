# Deploying XTREMEFIT (free tier) — Vercel + Render + Aiven + R2

Domain: **xtremefitlb.com** (frontend) and **api.xtremefitlb.com** (backend).

| Piece            | Service                     | Notes |
| ---------------- | --------------------------- | ----- |
| Frontend (React) | **Vercel** (free)           | Already configured (`vercel.json`). |
| Backend (Laravel)| **Render** free web service | Uses the `Dockerfile` in this repo. Sleeps after ~15 min idle (first hit ~50s). |
| Database (MySQL) | **Aiven** free MySQL        | Keeps the app on MySQL — no code changes. |
| Product images   | **Cloudflare R2** (free)    | Render's disk is ephemeral, so uploads must go to R2. |

Do them in this order.

---

## 1. Database — Aiven free MySQL
1. Create an Aiven account → **Create service → MySQL → Free plan**.
2. When it's running, copy the **Host, Port, Database (defaultdb), User (avnadmin), Password**.
3. **Download the CA certificate** (service Overview → Connection information → *CA certificate* → download, or the "Secure connection" step). Aiven MySQL requires SSL, so this is needed.
4. In Render, add that file as a **Secret File** (Render service → Environment → Secret Files) named `aiven-ca.pem`; Render mounts it at `/etc/secrets/aiven-ca.pem`. Then set `MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem` (already in the env list below). If you ever get a certificate-verification error, add `DB_SSL_VERIFY=false`.

## 2. Image storage — Cloudflare R2
1. Cloudflare → **R2 → Create bucket** (e.g. `xtremefit-media`).
2. Bucket → **Settings → Public access** → enable the **r2.dev public URL** (looks like `https://pub-xxxx.r2.dev`). Copy it.
3. R2 → **Manage API Tokens** → create a token with **Object Read & Write**; copy the Access Key ID + Secret.

## 3. Backend — Render
1. Push both repos to GitHub (already done).
2. Render → **New → Web Service** → connect the **shop-platform** repo → **Runtime: Docker** (it finds the `Dockerfile`).
3. Instance type: **Free**.
4. Add **Environment Variables** (see the list at the bottom), then **Create Web Service**. First build takes a few minutes.
5. After it's live, note the `…onrender.com` URL, then **Settings → Custom Domain** → add `api.xtremefitlb.com` and follow the DNS instructions.

## 4. Frontend — Vercel
1. Vercel → **Add New → Project** → import the **shop-frontend** repo.
2. Framework preset: **Create React App**. Build command `npm run build`, output `build`.
3. **Environment Variables** (build-time):
   - `REACT_APP_API_BASE_URL` = `https://api.xtremefitlb.com`
   - `REACT_APP_STORAGE_URL` = your R2 public URL (`https://pub-xxxx.r2.dev`)
   - `REACT_APP_APP_NAME` = `XTREMEFIT`
   - `REACT_APP_GOOGLE_MAPS_KEY` = your Google Maps key
4. Deploy. Then **Settings → Domains** → add `xtremefitlb.com` (and `www`) and follow the DNS instructions.

## 5. DNS (at your domain registrar)
- `xtremefitlb.com` + `www` → the records Vercel shows (usually an A record `76.76.21.21` and a CNAME for `www`).
- `api.xtremefitlb.com` → a CNAME to the Render service's `…onrender.com` host.
- SSL is issued automatically by both once DNS resolves.

## 6. Third-party config for the new domain (easy to forget)
- **Google Maps key** → add `xtremefitlb.com` and `www.xtremefitlb.com` to the key's allowed HTTP referrers (it currently only allows localhost).
- **Google Sign-In** (Google Cloud console → Credentials):
  - Authorized redirect URI: `https://api.xtremefitlb.com/auth/google/callback`
  - Authorized JavaScript origin: `https://xtremefitlb.com`
- **OpenAI / FASHN** keys work anywhere — set a **small monthly spend cap** on each.

## 7. Before it's public (important)
- Set `APP_DEBUG=false` and a strong `SUPER_ADMIN_PASSWORD` (the default is `Admin@123!`).
- Do **NOT** seed the demo data on production. Run only `php artisan migrate --force` (the container does this automatically); create your real admin via the `SuperAdminSeeder` or by setting `SUPER_ADMIN_*` and running `php artisan db:seed --class=SuperAdminSeeder` once.
- Upload real product images through the admin (they land in R2).

---

## Render environment variables
```
APP_NAME=XTREMEFIT
APP_ENV=production
APP_KEY=            # run `php artisan key:generate --show` locally and paste the value
APP_DEBUG=false
APP_URL=https://api.xtremefitlb.com
APP_FRONTEND_URL=https://xtremefitlb.com
FRONTEND_URL=https://xtremefitlb.com
CORS_ALLOWED_ORIGINS=https://xtremefitlb.com,https://www.xtremefitlb.com

DB_CONNECTION=mysql
DB_HOST=            # from Aiven (e.g. mysql-xxxx.l.aivencloud.com)
DB_PORT=            # from Aiven (e.g. 18280)
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=        # from Aiven
# Aiven requires SSL — upload the CA as a Render Secret File and point to it:
MYSQL_ATTR_SSL_CA=/etc/secrets/aiven-ca.pem
# DB_SSL_VERIFY=false   # only if you hit a cert-verification error

# Image storage on Cloudflare R2
MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=          # R2 token
AWS_SECRET_ACCESS_KEY=      # R2 token
AWS_DEFAULT_REGION=auto
AWS_BUCKET=xtremefit-media
AWS_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=https://pub-xxxx.r2.dev

# AI
OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
FASHN_API_KEY=

# Google
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=https://api.xtremefitlb.com/auth/google/callback
REACT_APP_GOOGLE_MAPS_KEY=

# Mail (order + reset emails)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=465
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=
MAIL_FROM_NAME=XTREMEFIT
MAIL_ADMIN_ADDRESS=

# Permanent owner account
SUPER_ADMIN_EMAIL=
SUPER_ADMIN_PASSWORD=       # set a strong password
```
