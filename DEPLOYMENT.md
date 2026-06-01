# Deployment Guide — Vercel (frontend) + Railway (PHP backend + MySQL)

This document walks through deploying the project with a free-friendly stack: Vercel for the static React frontend and Railway for the PHP backend container + managed MySQL.

---

## 1) Prepare the repo
- Commit all files to a GitHub repository.
- Ensure `assets/js/react/main.js` exists after building (see below) or let Docker build it.

## 2) Build frontend locally (recommended)
On your dev machine:

```bash
cd /path/to/JADE SOLE
npm install
npm run build
```

- Confirm `dist/` created and the app's built files are at `dist/`.
- Optionally copy `dist` into `assets/js/react/` so PHP pages can load `assets/js/react/main.js`:

```bash
mkdir -p assets/js/react
cp -r dist/* assets/js/react/
```

Commit the built assets (or add to CI build).

## 3) Update DB config (already applied)
`config.php` now reads DB credentials from environment variables: `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`. Railway provides connection credentials — set these as env variables in Railway.

## 4) Docker (Railway) — optional local test
You can test the container locally:

```bash
# build the image
docker build -t jadesole:local .
# run with local mysql (example)
docker run -p 8080:80 --env DB_HOST=host.docker.internal --env DB_USER=root --env DB_PASS=yourpass --env DB_NAME=jade_sole jadesole:local
```

Visit http://localhost:8080 to verify.

## 5) Deploy backend to Railway
1. Create an account at https://railway.app and connect your GitHub repo.
2. Create a new Project → Add a Service → Deploy from GitHub. Select this repo.
3. Railway will detect the `Dockerfile` and build the container.
4. Add the Railway MySQL plugin to the project to create a managed database. Railway provides `MYSQL_HOST`, `MYSQL_USER`, `MYSQL_PASSWORD`, `MYSQL_DB`.
5. In your Railway service, add environment variables mapping to names used by `config.php`:
   - `DB_HOST` => `MYSQL_HOST`
   - `DB_USER` => `MYSQL_USER`
   - `DB_PASS` => `MYSQL_PASSWORD`
   - `DB_NAME` => `MYSQL_DB`
6. Deploy. Railway will build and publish a public URL.
7. Import your SQL schema/data into the Railway database (you can use the Railway database UI or `mysql` client using provided credentials):

```bash
mysql -h <MYSQL_HOST> -u <MYSQL_USER> -p<MYSQL_PASSWORD> <MYSQL_DB> < database/jade_sole.sql
```

## 6) Deploy frontend to Vercel
1. Create a Vercel account and connect to GitHub.
2. New Project → import the repo (or a front-end-only repo/branch).
3. Set Build Command: `npm install && npm run build` and Output Directory: `dist`.
4. If your frontend needs to call the backend API, add an Environment Variable on Vercel `VITE_API_URL` with the Railway service URL.
5. Deploy — Vercel will serve the static site with HTTPS.

## 7) CORS / API calls
- If your React app will call the PHP backend, ensure the PHP backend allows CORS or proxy calls from Vercel to the Railway URL.
- Alternatively use Vercel rewrites to proxy certain paths to Railway.

## 8) Environment variables recap
- Backend (Railway service env): `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` (mapped from Railway MySQL plugin)
- Frontend (Vercel env): `VITE_API_URL` (if needed)

## 9) Post-deploy checks
- Visit the Railway URL (PHP) and Vercel URL (frontend).
- Verify DB connectivity and that orders/pages work.

## 10) Notes & caveats
- Railway free tier has usage limits; switch to paid if production traffic grows.
- If you prefer Aiven for MySQL, set env vars accordingly and ensure SSL connection parameters if Aiven requires TLS.
- For local development, continue using XAMPP and `npm run dev` (Vite) as before.

---

If you want, I can:
- Create a GitHub Actions workflow that builds the frontend and pushes the built assets to the repo or deploys to Vercel automatically.
- Create a sample `railway.json` or instructions to automate Railway deployment.

Tell me which automation you'd like next.
