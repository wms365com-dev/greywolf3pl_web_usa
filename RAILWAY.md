# Grey Wolf Railway Deployment

This site is now prepared to run on Railway with Docker, Apache, PHP handlers, SMTP email, and Railway Postgres as the primary application data store.

Config-as-code is included in [railway.toml](/E:/GreyWolfWebsite/railway.toml) for Docker builds and the `/health.php` healthcheck.
An environment template is included in [.env.railway.example](/E:/GreyWolfWebsite/.env.railway.example).

## What Railway will run

- `Dockerfile` at the project root
- Apache + PHP 8.2
- Static HTML/CSS/JS pages from `/var/www/html`
- PHP form handlers for:
  - quotes
  - lead capture
  - drayage requests
  - delivery appointments
- PostgreSQL-backed storage for:
  - quotes
  - leads
  - drayage requests
  - drayage draft tracking events
  - dock appointments / inbound tracker

## Railway setup steps

1. Create a new Railway project.
2. Add a PostgreSQL service to that project.
3. Add the Grey Wolf site as a separate service from this repo/folder.
4. Let Railway build the site from the root `Dockerfile`.
5. Reference the Postgres connection into the site service with `DATABASE_URL`.
6. Generate a public Railway domain first so you can test.
7. Confirm `/health.php` returns JSON after deploy with `"database_ready": true`.

## Required environment variables

Set these in Railway:

- `DATABASE_URL=${{Postgres.DATABASE_URL}}`
- `GW_TO_EMAIL=info@greywolf3pl.com`
- `GW_FROM_DOMAIN=greywolf3pl.com`
- `GW_SITE_URL=https://www.greywolf3pl.com`
- `PGSSLMODE=require`
- `GW_DB_SSLMODE=require`
- `GW_GOOGLE_SHEET_WEBHOOK_URL=https://script.google.com/macros/s/AKfycbzKtrMBi3_Z5thT2MIU1ACdRlJwtuQ-CXIYkDJCxB7CtLoH-owo3fixF1ddR1e877gb/exec`

Optional fallback storage variable:

- `GW_STORAGE_DIR=/data/form_submissions`

## SMTP variables

Railway containers should not rely on plain `mail()` for dependable sending. Configure a real SMTP provider and set:

- `GW_SMTP_HOST=...`
- `GW_SMTP_PORT=587`
- `GW_SMTP_USERNAME=...`
- `GW_SMTP_PASSWORD=...`
- `GW_SMTP_SECURE=tls`
- `GW_SMTP_FROM_EMAIL=no-reply@greywolf3pl.com`
- `GW_SMTP_FROM_NAME=Grey Wolf 3PL`

Optional:

- `GW_SMTP_AUTH=true`
- `GW_SMTP_DEBUG=0`
- `GW_SMTP_DISABLE_NATIVE_FALLBACK=false`

If your SMTP provider does not use TLS or SSL, set:

- `GW_SMTP_SECURE=none`

## Domains and redirects

The site keeps the production canonical redirect to `https://www.greywolf3pl.com`, but it now skips that redirect on Railway preview domains ending in `.railway.app`.

If you want to disable the canonical redirect in a staging environment completely, set:

- `GW_DISABLE_CANONICAL_REDIRECT=1`

For production:

1. Add your custom domain in Railway Public Networking.
2. Point DNS to Railway as instructed in the Railway dashboard.
3. Keep `GW_SITE_URL=https://www.greywolf3pl.com`.

## PostgreSQL data

The app now auto-creates the required PostgreSQL tables on first successful connection.

Core tables:

- `quotes`
- `leads`
- `drayage_requests`
- `drayage_draft_events`
- `delivery_appointments`

## Optional fallback volume

The app service no longer needs a Railway volume for normal production use if PostgreSQL is configured correctly.

Only add an app volume if you want file-based fallback storage too:

- Mount path: `/data`
- App env var: `GW_STORAGE_DIR=/data/form_submissions`

Postgres remains the primary store.

## Quick test checklist

After deploy, test these pages on the Railway domain:

1. `/health.php`
2. `/drayage.html`
3. `/delivery-appointment.html`
4. homepage quote form

Then verify:

- `/health.php` shows `"database_ready": true`
- emails send through SMTP
- drayage requests sync to Google Sheets
- delivery appointments can be booked without double-booking
- inbound tracker shows booked appointments from PostgreSQL

## Notes

- Railway expects your app to listen on the provided `PORT`, which the startup script now handles.
- The Docker image now installs `pdo_pgsql` and `pgsql` so PHP can connect to Railway Postgres.
- If PostgreSQL is unavailable, the app can still fall back to file storage when `GW_STORAGE_DIR` is writable, but production should use PostgreSQL.
