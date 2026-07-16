# HAL Computer Spare Inventory — Deployment Guide

This is your original PHP/MySQL project, rebuilt into two independent
pieces so it can be deployed the way you asked:

- **`backend/`** — a PHP JSON API + MySQL, deployed on **Render**
- **`frontend/`** — plain HTML/CSS/JS, deployed on **Vercel**

No XAMPP, no local server needed anywhere. The two talk to each other
over the internet via the API.

## What was fixed from the original code
- `edit_part.php` was broken — it referenced a `db_connect.php` file, a
  `parts` table, and columns (`category`, `supplier`, `location`...)
  that don't exist anywhere in your schema. It could never have worked.
- `supplier_view.php` linked to `edit_supplier.php` and `stocks_view.php`
  linked to `edit_stock.php` — neither file existed in your project.
- All queries were built with raw string concatenation (SQL injection
  risk) — every query now uses prepared statements.
- Passwords were stored in plain text — now hashed with `password_hash`.
- The header logo was loaded from a Google image URL — that requires
  internet access to `gstatic.com`; it's now a local SVG so nothing
  external is depended on.
- Every "Edit" action now actually works (parts, suppliers, stocks).

## Step 1 — Create the MySQL database on Render
1. In the Render dashboard: **New → Private Service → Deploy an
   existing image** and search Render's template gallery for **MySQL**
   (or deploy `render-examples/mysql` from GitHub — it's a one-click
   template with a persistent disk already configured).
2. Once it's running, open its **Connect** tab and copy the internal
   hostname, port, username, and password. You'll paste these into the
   backend's environment variables in Step 3.
3. Use the **Shell** tab (or Adminer, which Render's docs describe how
   to add) to run the contents of `backend/schema.sql` once, to create
   the tables and seed data.

## Step 2 — Push this project to GitHub
Render and Vercel both deploy from a Git repo. Create one repo (or two
— your choice) containing the `backend/` and `frontend/` folders.

## Step 3 — Deploy the backend on Render
1. **New → Web Service**, connect your repo, point it at the
   `backend/` folder.
2. Render will detect the `Dockerfile` automatically — select **Docker**
   as the runtime.
3. Under **Environment**, add the variables listed in
   `backend/.env.example`:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS` — from Step 1
   - `APP_SECRET` — any long random string
   - `FRONTEND_ORIGIN` — leave as `*` for now, tighten later to your
     Vercel URL
4. Deploy. You'll get a URL like `https://hal-inventory-api.onrender.com`.
   Your API lives under `/api/...`, e.g.
   `https://hal-inventory-api.onrender.com/api/login.php`.

Note: on Render's free tier the service spins down after 15 minutes of
inactivity, so the first request after a break takes 30-60 seconds to
wake up — that's normal, not a bug.

## Step 4 — Point the frontend at the backend
Open `frontend/js/config.js` and set:
```js
const API_BASE = "https://hal-inventory-api.onrender.com/api";
```
(use the real URL Render gave you in Step 3).

## Step 5 — Deploy the frontend on Vercel
1. **New Project**, import the same repo, set the **Root Directory**
   to `frontend/`.
2. Framework preset: **Other** (it's static HTML, no build step).
3. Deploy. You'll get a URL like `https://your-app.vercel.app`.
4. Go back to Render and update `FRONTEND_ORIGIN` to that exact URL,
   then redeploy the backend, so only your frontend can call the API.

## Step 6 — Log in
The seed admin account from your original `.sql` file still works:
- Username: `admin`
- Password: `admin123`

(Its password gets automatically upgraded to a secure hash the first
time it's used to log in.)

## Why this avoids "page failure"
- Every page is a static file — no PHP page-rendering step that can
  502 on a cold path.
- Every button (Edit/Delete/Add) is now wired to a real, working API
  endpoint — nothing points at a missing file anymore.
- No external image/script dependency, so nothing breaks if a
  third-party CDN is slow or blocked.
- API errors show up as an on-page message instead of a blank/broken
  page.
