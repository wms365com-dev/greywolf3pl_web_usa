# Grey Wolf 3PL Website

Website and PHP backend for Grey Wolf 3PL & Logistics Inc.

The public site promotes warehousing, fulfillment, drayage, delivery appointments, retailer compliance, and logistics services in Mississauga and the Greater Toronto Area. The backend captures form submissions, sends email notifications, and stores data in PostgreSQL on Railway.

## Stack

- Static HTML, CSS, and vanilla JavaScript frontend.
- PHP 8.2 backend.
- Apache in Docker.
- PostgreSQL database on Railway.
- No Node.js package install is currently required.

## Do Not Commit Secrets

Never commit `.env`, passwords, API keys, SMTP credentials, database URLs, Google Apps Script webhook URLs, exported customer data, logs, or form submissions.

Use `.env.example` as the template and keep real values in `.env`, Railway Variables, or another secure secret store.

## Run Locally With Docker

```powershell
git clone https://github.com/wms365com-dev/greywolf3pl_web_usa.git C:\GreyWolfWebsite
cd C:\GreyWolfWebsite
copy .env.example .env
docker build -t greywolf3pl-local .
docker run --rm -p 8080:8080 --env-file .env greywolf3pl-local
```

Open:

```text
http://localhost:8080/
http://localhost:8080/health.php
```

If the database is configured correctly, `/health.php` should show `database_configured:true` and `database_ready:true`.

## Railway Deployment

Railway uses `Dockerfile` and `railway.toml`.

Basic deployment flow:

```powershell
git add .
git commit -m "Update Grey Wolf site"
git push origin main
```

Railway should redeploy automatically from the `main` branch.

## Required Environment Variables

Copy `.env.example` to `.env` locally and fill values manually. On Railway, add the same values in the service Variables tab.

Important values include:

- Site/API configuration: `GW_SITE_URL`, `GW_API_URL`, `GW_ALLOWED_ORIGINS`
- Email routing: `GW_TO_EMAIL`, `GW_FROM_DOMAIN`
- SMTP: `GW_SMTP_HOST`, `GW_SMTP_PORT`, `GW_SMTP_USERNAME`, `GW_SMTP_PASSWORD`
- Database: `DATABASE_URL`
- Optional Google Sheet sync: `GW_GOOGLE_SHEET_WEBHOOK_URL`

## Main Workflows To Test

- Homepage loads.
- Navigation links work.
- Quote form submits.
- Drayage form submits and emails `info@greywolf3pl.com`.
- Delivery appointment booking works and prevents overbooking beyond 3 dock doors.
- Inbound tracker opens.
- `/health.php` reports database ready on Railway.
- Private tools pages open from `/tools/`.

## More Setup Detail

See:

- `HANDOFF.md`
- `SETUP_NEW_COMPUTER.md`
- `RAILWAY.md`
- `BLUEHOST_RAILWAY_SPLIT.md`

