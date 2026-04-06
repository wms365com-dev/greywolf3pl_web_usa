# Bluehost Front End + Railway Backend

Use this setup if you want the website pages to stay on Bluehost while Railway handles forms, PostgreSQL, and email.

## Architecture

- `www.greywolf3pl.com` and `greywolf3pl.com`
  - hosted on Bluehost
  - serves HTML, CSS, JS, images
- `api.greywolf3pl.com`
  - hosted on Railway
  - serves PHP form handlers, appointment endpoints, healthcheck, and Postgres-backed storage

## What already works in this codebase

- homepage quote form submits to the Railway backend once `js/site.js` loads
- service-page lead forms submit to the Railway backend once `js/site.js` loads
- drayage form submits to Railway with cross-origin `fetch`
- drayage draft autosave syncs to Railway with cross-origin `fetch`
- delivery appointment form submits to Railway
- delivery appointment availability checker calls Railway
- PHP success/error links redirect back to the main Grey Wolf site

## Railway variables

Set these on the Railway service:

- `DATABASE_URL=${{Postgres.DATABASE_URL}}`
- `PGSSLMODE=require`
- `GW_DB_SSLMODE=require`
- `GW_TO_EMAIL=info@greywolf3pl.com`
- `GW_FROM_DOMAIN=greywolf3pl.com`
- `GW_SITE_URL=https://www.greywolf3pl.com`
- `GW_API_URL=https://api.greywolf3pl.com`
- `GW_ALLOWED_ORIGINS=https://www.greywolf3pl.com,https://greywolf3pl.com`
- `GW_GOOGLE_SHEET_WEBHOOK_URL=https://script.google.com/macros/s/AKfycbzKtrMBi3_Z5thT2MIU1ACdRlJwtuQ-CXIYkDJCxB7CtLoH-owo3fixF1ddR1e877gb/exec`

Add your SMTP settings too:

- `GW_SMTP_HOST`
- `GW_SMTP_PORT`
- `GW_SMTP_USERNAME`
- `GW_SMTP_PASSWORD`
- `GW_SMTP_SECURE`
- `GW_SMTP_AUTH`
- `GW_SMTP_FROM_EMAIL`
- `GW_SMTP_FROM_NAME`

## DNS

### Bluehost website

Leave the website DNS pointed at Bluehost for:

- `greywolf3pl.com`
- `www.greywolf3pl.com`

### Railway API

In Railway, add a custom domain:

- `api.greywolf3pl.com`

Then create the DNS record in your DNS provider exactly as Railway instructs, usually:

- `CNAME`
- `Name`: `api`
- `Value`: Railway target

## Upload steps

1. Upload the updated front-end files from this project to Bluehost.
2. Make sure the updated `js/site.js` file is uploaded too.
3. Deploy the same codebase to Railway for the backend service.
4. Confirm `https://api.greywolf3pl.com/health.php` returns:
   - `"database_configured": true`
   - `"database_ready": true`

## Test checklist

Run these tests from the live website on Bluehost:

1. Homepage quote form
2. One service page lead form
3. Drayage form
4. Delivery appointment form
5. Appointment availability lookup
6. Inbound tracker on Railway if you use it internally

Confirm:

- emails arrive at `info@greywolf3pl.com`
- drayage rows appear in Google Sheets
- appointments are saved and visible in the tracker
- Postgres remains healthy via `/health.php`

## Notes

- The API service skips the website canonical redirect for `api.greywolf3pl.com`
- The drayage and appointment endpoints now send CORS headers for the main Grey Wolf site
- The shared front-end script version was bumped to `20260405-1`, so upload the updated HTML pages and `js/site.js` together
