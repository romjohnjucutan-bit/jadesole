# Deployment Guide — Supabase + GitHub + Cloudflare Pages

This deploys Jade Sole as a React SPA on **Cloudflare Pages**, backed by **Supabase**
(Postgres + Auth + Storage), with **GitHub** as the source of truth and CI/CD trigger.

Everything here is free-tier friendly. Follow the steps in order.

```
GitHub repo ──push──► Cloudflare Pages (builds Vite, serves dist/)
       React app  ──HTTPS──► Supabase (DB + Auth + Storage + Edge Function)
```

---

## 0) Prerequisites
- A [GitHub](https://github.com) account
- A [Supabase](https://supabase.com) account
- A [Cloudflare](https://dash.cloudflare.com/sign-up) account
- Node.js 18+ locally (to test the build)

---

## 1) Push the code to GitHub
From the project root:

```bash
git add .
git commit -m "Migrate to React + Supabase"
git branch -M main
git remote add origin https://github.com/<you>/jade-sole.git   # if not already set
git push -u origin main
```

> `node_modules/`, `dist/`, and `.env` are gitignored — don't commit secrets.

---

## 2) Create the Supabase project + database
1. Go to https://supabase.com/dashboard → **New project**. Pick a name, a strong
   database password, and a region near you. Wait for it to provision.
2. Open **SQL Editor** → **New query**, then run the migrations **in order**:
   - paste the contents of [`supabase/migrations/0001_schema.sql`](supabase/migrations/0001_schema.sql) → **Run**
   - paste the contents of [`supabase/migrations/0002_security.sql`](supabase/migrations/0002_security.sql) → **Run**

   This creates the tables, seed data (5 categories + 17 products), Row Level
   Security policies, the `place_order` / `track_order` / `cancel_order` RPCs,
   the new-user trigger, and the public `product-images` storage bucket.

   > Prefer the CLI? `supabase link --project-ref <ref>` then `supabase db push`.

3. Grab your API keys: **Project Settings → API**. You'll need:
   - **Project URL** (e.g. `https://abcd.supabase.co`)
   - **anon public** key (safe for the browser)
   - **service_role** key (secret — used only by the Edge Function)

---

## 3) Create the first admin user
Supabase Auth owns the login accounts. Create the admin once:

1. **Authentication → Users → Add user** → enter an email + password, and tick
   **Auto Confirm User**. (Use a real email format, e.g. `admin@jadesole.com`.)
2. The `on_auth_user_created` trigger auto-creates a `staff_profiles` row with role
   `staff`. Promote it to admin — **SQL Editor**:

   ```sql
   update public.staff_profiles
   set role = 'admin', name = 'Admin'
   where email = 'admin@jadesole.com';
   ```

After this, you (the admin) can create all other staff from **Admin → Staff** in the app.

---

## 4) Deploy the admin-staff Edge Function
Creating/deleting staff logins needs the service-role key, so it runs in an Edge
Function (never in the browser). Using the [Supabase CLI](https://supabase.com/docs/guides/cli):

```bash
npm i -g supabase            # or: npx supabase ...
supabase login
supabase link --project-ref <your-project-ref>
supabase functions deploy admin-staff
```

`SUPABASE_URL`, `SUPABASE_ANON_KEY`, and `SUPABASE_SERVICE_ROLE_KEY` are injected
automatically on Supabase — no manual secrets needed for this function.

> Skipping this step only disables **adding/deleting staff** from the UI; the rest of
> the app works. You can always add staff from the Supabase dashboard instead.

---

## 5) Deploy the frontend to Cloudflare Pages
1. Cloudflare dashboard → **Workers & Pages → Create → Pages → Connect to Git**.
2. Pick your GitHub repo and the `main` branch.
3. Build settings:
   - **Framework preset:** `Vite` (or "None")
   - **Build command:** `npm run build`
   - **Build output directory:** `dist`
4. **Environment variables** (Settings → Environment variables) — add for *Production*
   (and *Preview* if you use PR previews):
   - `VITE_SUPABASE_URL` = your Project URL
   - `VITE_SUPABASE_ANON_KEY` = your anon public key
5. **Save and Deploy.** Cloudflare builds and publishes to `https://<project>.pages.dev`.

The included [`public/_redirects`](public/_redirects) (`/* /index.html 200`) makes
client-side routes like `/menu` and `/admin/orders` resolve correctly on refresh.

Every `git push` to `main` now redeploys automatically.

---

## 6) Point Supabase at your domain (CORS / Auth URLs)
In Supabase → **Authentication → URL Configuration**, set:
- **Site URL:** your Cloudflare URL (`https://<project>.pages.dev` or your custom domain)
- add it to **Redirect URLs** too.

Supabase's data API already allows browser calls with the anon key, so no extra CORS
config is needed for queries/RPCs.

---

## 7) Product images
The `product-images` bucket is public-read; only admins can upload (enforced by RLS).
Admins upload via **Admin → Products → Add/Edit → Product Image**. Existing legacy
images (if any) live in `public/images/` and are served statically by Cloudflare.

---

## 8) Custom domain (optional)
Cloudflare Pages → your project → **Custom domains → Set up a domain**. If the domain
is on Cloudflare DNS it's a couple of clicks. Then update the Supabase **Site URL** (step 6).

---

## 9) Post-deploy checklist
- [ ] Storefront loads, products show (seed data visible)
- [ ] Add to cart → checkout → get an Order ID
- [ ] Track that Order ID; cancel while `Received`
- [ ] Log in as admin at `/login`
- [ ] Dashboard stats + recent orders render
- [ ] Update an order's status in Admin → Orders
- [ ] Add/edit a product with an image upload
- [ ] Add a staff member (requires step 4)

---

## Environment variables recap
| Where               | Variable                  | Value                         |
|---------------------|---------------------------|-------------------------------|
| Cloudflare Pages    | `VITE_SUPABASE_URL`       | Supabase Project URL          |
| Cloudflare Pages    | `VITE_SUPABASE_ANON_KEY`  | Supabase anon public key      |
| Local `.env`        | both of the above         | same values                   |
| Edge Function       | (auto-provided)           | service role, url, anon key   |

---

## Troubleshooting
- **Blank page / "Missing Supabase env vars":** the Cloudflare env vars aren't set, or
  you didn't redeploy after adding them. Re-deploy.
- **Login fails:** confirm the user exists and is **confirmed** in Auth → Users, and that
  you ran the promote-to-admin SQL (step 3).
- **403 on admin actions:** the logged-in user's `staff_profiles.role` isn't `admin`.
- **Routes 404 on refresh:** make sure `public/_redirects` shipped in `dist/` (it should
  copy automatically).
- **Can't add staff:** deploy the Edge Function (step 4).
