# 👟 Jade Sole — Shoe Store System (React + Supabase)

A full-stack shoe store ordering system. The frontend is a **React (Vite) single-page app**
hosted on **Cloudflare Pages**; the backend is **Supabase** (Postgres + Auth + Storage).
Source lives on **GitHub**, which auto-deploys to Cloudflare on every push.

> The original PHP/MySQL version is preserved under [`legacy-php/`](legacy-php/) for reference.

---

## 🧱 Architecture

```
Browser ──► Cloudflare Pages (React SPA)
                  │
                  ├─ Supabase Postgres        (products, orders, …)  via RLS + RPCs
                  ├─ Supabase Auth            (staff / admin login)
                  ├─ Supabase Storage         (product images)
                  └─ Supabase Edge Function   (admin-staff: create/delete staff)
```

| Concern              | Old (PHP)                    | New                                   |
|----------------------|------------------------------|---------------------------------------|
| UI                   | PHP-rendered pages           | React SPA (Vite, react-router)        |
| Database             | MySQL                        | Supabase Postgres                     |
| Auth                 | PHP sessions + bcrypt        | Supabase Auth (email/password)        |
| Cart                 | PHP `$_SESSION`              | React context + `localStorage`        |
| Order placement      | inline SQL                   | `place_order` Postgres RPC (atomic)   |
| Images               | `assets/images/` uploads     | Supabase Storage bucket               |
| Hosting              | Apache                       | Cloudflare Pages                      |

---

## 📁 Project Structure

```
.
├── index.html                 ← Vite entry
├── src/
│   ├── main.jsx               ← app bootstrap (Router + providers)
│   ├── App.jsx                ← routes
│   ├── supabaseClient.js
│   ├── lib/                   ← config, helpers, notifications, image URLs
│   ├── context/              ← AuthContext, CartContext
│   ├── components/           ← Navbar, Footer, layouts, ProtectedRoute, SizeModal
│   ├── pages/                ← Home, Menu, Order, Track, Login
│   │   └── admin/            ← Dashboard, Orders, Products, Staff, Notifications
│   └── styles/legacy.css     ← ported stylesheet
├── public/
│   ├── _redirects            ← SPA fallback for Cloudflare Pages
│   └── images/               ← static product images (optional)
├── supabase/
│   ├── migrations/           ← 0001_schema.sql, 0002_security.sql
│   ├── functions/admin-staff ← Edge Function (service-role)
│   └── config.toml
├── legacy-php/                ← original PHP/MySQL app (reference only)
└── DEPLOYMENT.md             ← full Supabase + GitHub + Cloudflare guide
```

---

## 🚀 Quick start (local dev)

```bash
npm install
cp .env.example .env          # fill in your Supabase URL + anon key
npm run dev                   # http://localhost:5173
```

You need a Supabase project with the migrations in `supabase/migrations/` applied.
See **[DEPLOYMENT.md](DEPLOYMENT.md)** for the full, click-by-click setup and hosting guide.

---

## 🔐 Login

Staff/admin sign in with **email + password** (Supabase Auth) at `/login`.
The first admin account is created during setup — see DEPLOYMENT.md step 3.
There is no public sign-up; admins create staff from **Admin → Staff**.

---

## 🛍️ Routes

| Page              | Path                  | Access        |
|-------------------|-----------------------|---------------|
| Home              | `/`                   | public        |
| Collection        | `/menu`               | public        |
| Order / cart      | `/order`              | public        |
| Track order       | `/track?id=JS-…`      | public        |
| Login             | `/login`              | public        |
| Admin dashboard   | `/admin`              | staff/admin   |
| Orders            | `/admin/orders`       | staff/admin   |
| Notifications     | `/admin/notifications`| staff/admin   |
| Products          | `/admin/products`     | admin         |
| Staff             | `/admin/staff`        | admin         |

---

## 📦 Order flow

1. Customer adds items (with size) to the cart on **Collection** or **Order**.
2. Checkout → name, contact, pickup/delivery, payment.
3. The `place_order` RPC validates stock, applies the discount, decrements stock,
   and returns an **Order ID** (e.g. `JS-4A2F8C1D`) — all server-side.
4. Customer tracks it at **/track**; can cancel while status is `Received`.
5. Staff/admin move it through statuses from **Admin → Orders**.

Statuses: `Received → Preparing → Ready for Pickup / Out for Delivery → Completed`, or `Cancelled`.
Promotions: 10% off over ₱500, free delivery over ₱300 (configurable in `src/lib/config.js`
and the `place_order` function).
