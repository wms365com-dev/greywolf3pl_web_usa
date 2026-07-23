# Grey Wolf 3PL Website Handoff

## Project Name And Purpose

Grey Wolf 3PL & Logistics Inc website and form backend.

The site promotes Grey Wolf warehouse, fulfillment, drayage, retailer compliance, delivery appointment, and logistics services in Mississauga and the Greater Toronto Area. Public pages are mostly static HTML/CSS/JavaScript. Dynamic form handling, lead capture, appointment booking, email notification, and database storage are handled by PHP endpoints designed for Railway.

## Current Status

The project is functional but split across two local folders:

- `E:\GreyWolfWebsite` is the newest working copy and should be treated as the current source of truth.
- `C:\GreyWolfWebsite` is the Git repository connected to GitHub.

Railway deployment is intended to run the PHP backend and optional public PHP pages in a Docker container with PostgreSQL. Bluehost can continue hosting the public website if desired, while forms can post to Railway in the background.

The preferred production customer-facing domain is:

- `https://greywolf3pl.com`
- `https://www.greywolf3pl.com`

The current Railway backend URL used for health checks is:

- `https://greywolf3plwebusa-production.up.railway.app/health.php`

The preferred future API subdomain is:

- `https://api.greywolf3pl.com`

At the time of this handoff, `api.greywolf3pl.com` still needs DNS verification/provisioning before forms should be switched to it.

## Main Features Already Built

- Main marketing website for Grey Wolf 3PL services.
- Service pages for warehousing, fulfillment, cross-docking, returns, rework, international shipping, Canada/US support, drayage, and delivery appointments.
- Retailer compliance pages for Costco, Walmart, Canadian Tire, Staples, and TJX.
- Drayage quote/request page with multi-step form flow.
- Geoapify address autocomplete support for Canadian addresses.
- Google Sheet draft lead sync support for drayage visitors.
- PHP form endpoints for quotes, leads, drayage, new customer requests, and delivery appointments.
- Railway/PostgreSQL support for form submissions.
- Delivery appointment booking with 3 dock door capacity logic.
- Inbound tracker page for appointment visibility.
- Private tools folder with tools index, LPN/license plate label generator, and pallet builder.
- Sitemap and robots files for search engines.
- Docker/Railway deployment files.

## Features Still Pending Or Needing Follow-Up

- Finish DNS for `api.greywolf3pl.com` and then change frontend form endpoints from the Railway app URL to the branded API URL.
- Confirm live Bluehost pages are the same version as the newest local project.
- Confirm all production forms send email from/to `info@greywolf3pl.com`.
- Add authentication to any private admin/submissions pages before exposing them publicly.
- Review and reduce old WordPress files if Bluehost no longer needs them.
- Optimize large images and raw warehouse media for performance.
- Refresh sitemap `lastmod` dates after the final live upload.
- Add automated tests if the backend grows.
- Confirm SMTP credentials and Google Apps Script webhook are stored only in Railway/hosting environment variables.

## Folder And File Structure

- `/index.html`: Main homepage.
- `/*.html`: Public marketing, service, compliance, location, privacy, sitemap, and tool pages.
- `/assets/`: Site images, logos, warehouse media, retailer assets, and carrier images.
- `/tools/`: Private/internal tools, including LPN label generator and pallet builder.
- `/app-config.php`: Central configuration and environment variable handling.
- `/app-db.php`: PostgreSQL connection and table creation helpers.
- `/app-http.php`: Shared HTTP/CORS helpers.
- `/app-mail.php`: Shared email helpers.
- `/quote-submit.php`: Quote form endpoint.
- `/lead-submit.php`: Lead form endpoint.
- `/drayage-submit.php`: Drayage form endpoint.
- `/drayage-draft-sync.php`: Background drayage draft capture endpoint.
- `/delivery-appointment-submit.php`: Delivery appointment submission endpoint.
- `/appointment-lib.php`: Delivery appointment scheduling logic.
- `/appointment-availability.php`: Appointment availability endpoint.
- `/inbound-tracker.php`: Appointment tracker/admin-style page.
- `/new-customer-submit.php`: New customer form endpoint.
- `/health.php`: Railway health check endpoint.
- `/Dockerfile`: PHP 8.2 Apache container for Railway.
- `/railway.toml`: Railway build/deploy configuration.
- `/docker/`: Apache virtual host and startup script.
- `/form_submissions/`: Local fallback storage location. Do not commit live submissions.
- `/wp-*` and other WordPress files: Legacy Bluehost/WordPress footprint still present.

## Tech Stack

- Frontend: static HTML, CSS, and vanilla JavaScript.
- Backend: PHP 8.2 on Apache.
- Database: PostgreSQL on Railway.
- Container: Docker using `php:8.2-apache`.
- Hosting: Bluehost for public site is acceptable; Railway for backend/API and PostgreSQL.
- Package manager: none currently required.
- Node.js: not currently required by the app.
- Python: not currently required by the app.

## Required Programs On A New Computer

- Git.
- Docker Desktop.
- VS Code or another editor.
- Codex.
- Browser for testing.
- Optional: PHP 8.2 with `pdo_pgsql` and `pgsql` extensions if running without Docker.
- Optional: PostgreSQL client such as `psql` or pgAdmin.
- Optional: FileZilla, WinSCP, or Bluehost cPanel File Manager for uploads.

## Environment Variables

Do not commit secrets. Put local values in `.env`, Railway values in Railway Variables, and hosting-only values in the hosting control panel if needed.

Required or commonly used variable names:

- `GW_TO_EMAIL`
- `GW_FROM_DOMAIN`
- `GW_SITE_URL`
- `GW_API_URL`
- `GW_ALLOWED_ORIGINS`
- `GW_GOOGLE_SHEET_WEBHOOK_URL`
- `GW_DISABLE_CANONICAL_REDIRECT`
- `DATABASE_URL`
- `PGSSLMODE`
- `GW_DB_SSLMODE`
- `GW_STORAGE_DIR`
- `GW_SMTP_HOST`
- `GW_SMTP_PORT`
- `GW_SMTP_USERNAME`
- `GW_SMTP_PASSWORD`
- `GW_SMTP_SECURE`
- `GW_SMTP_AUTH`
- `GW_SMTP_DEBUG`
- `GW_SMTP_FROM_EMAIL`
- `GW_SMTP_FROM_NAME`
- `GW_SMTP_DISABLE_NATIVE_FALLBACK`
- `RAILWAY_PUBLIC_DOMAIN`
- `PORT`

## Where Secrets Are Expected

- Local development: `.env` file copied from `.env.example`.
- Railway: service variables on the Railway web service.
- Railway PostgreSQL: `DATABASE_URL` should come from the attached PostgreSQL service.
- GitHub: only use GitHub secrets if future GitHub Actions are added.
- Google Apps Script: webhook URL should be treated like a secret and stored as an environment variable.
- SMTP: username/password must stay in Railway variables or hosting environment variables.

## Local Setup Steps

1. Clone the GitHub repo.
2. Copy `.env.example` to `.env`.
3. Manually fill `.env` with non-public values from Railway or the secure password source.
4. Build the Docker image.
5. Run the container with the `.env` file.
6. Open the local health check.
7. Test the main forms and pages.

Commands:

```powershell
git clone https://github.com/wms365com-dev/greywolf3pl_web_usa.git C:\GreyWolfWebsite
cd C:\GreyWolfWebsite
copy .env.example .env
docker build -t greywolf3pl-local .
docker run --rm -p 8080:8080 --env-file .env greywolf3pl-local
```

Then open:

```text
http://localhost:8080/health.php
```

## GitHub Setup Steps

```powershell
cd C:\GreyWolfWebsite
git status
git pull origin main
git add .
git commit -m "Describe the change"
git push origin main
```

Do not commit `.env`, logs, local database files, generated submissions, or downloaded private documents.

## Railway Deployment Steps

1. Create or open the Railway project, preferably named `GreyWolf3plUSA`.
2. Add a service from GitHub using `wms365com-dev/greywolf3pl_web_usa`.
3. Confirm the branch is `main`.
4. Add a PostgreSQL service.
5. In the web service variables, set `DATABASE_URL` from the PostgreSQL service.
6. Add SMTP variables and Grey Wolf configuration variables.
7. Confirm `railway.toml` is detected.
8. Deploy the service.
9. Test `/health.php`.
10. Test a form submission and confirm it writes to PostgreSQL and sends email.

Health check expected when database is configured:

```json
{"ok":true,"service":"greywolf3pl","database_configured":true,"database_ready":true,"storage_ready":true}
```

## Database Setup And Migrations

The app currently creates required PostgreSQL tables automatically from PHP helpers when the database is available.

Tables created by the app include:

- `quotes`
- `leads`
- `drayage_requests`
- `drayage_draft_events`
- `delivery_appointments`

There is no separate migration command yet. After Railway deploys, visit `/health.php`, then submit test forms to confirm tables can be created and written.

## Build And Deploy Commands

Local Docker build:

```powershell
docker build -t greywolf3pl-local .
```

Local Docker run:

```powershell
docker run --rm -p 8080:8080 --env-file .env greywolf3pl-local
```

Railway build:

```text
Railway uses Dockerfile automatically through railway.toml.
```

## Known Bugs Or Issues

- `E:\GreyWolfWebsite` is the newest working copy but is not currently a Git repository.
- `C:\GreyWolfWebsite` is the GitHub-connected repo and may be behind the newest working copy if files are not synced.
- `api.greywolf3pl.com` DNS was not fully working during recent setup, so forms may still point to the Railway app URL.
- If `DATABASE_URL` is missing on Railway, `/health.php` will show `database_configured:false`.
- If SMTP variables are missing, form submissions may save but emails may not send.
- Some pages may still have older styling from previous iterations.
- Some large image/media files should be compressed before final production rollout.
- Old WordPress files remain in the project and should be handled carefully.

## Testing Checklist

- Open homepage on desktop and mobile widths.
- Test Services dropdown hover/click behavior.
- Test all navigation links.
- Test `/sitemap.xml` and `robots.txt`.
- Test `/health.php` locally or on Railway.
- Submit quote form.
- Submit drayage form.
- Confirm drayage request email goes to `info@greywolf3pl.com`.
- Confirm drayage data writes to PostgreSQL.
- Confirm drayage draft capture writes to Google Sheet only if enabled.
- Submit delivery appointment in standard hours.
- Submit delivery appointment outside standard hours.
- Confirm appointment capacity prevents more than 3 overlapping dock bookings.
- Open inbound tracker.
- Open private tools index.
- Test LPN label generator print preview.
- Test pallet builder.
- Confirm no `.ca` email addresses remain where Grey Wolf email should be `.com`.

## Next Recommended Tasks

1. Decide whether GitHub should be synced from `E:\GreyWolfWebsite` before the move.
2. Finish `api.greywolf3pl.com` DNS and switch forms to the branded API URL.
3. Add simple password protection to tracker/admin/private tools pages.
4. Run a full live form submission test after Railway variables are confirmed.
5. Compress large assets and remove unused legacy files only after backup.
6. Refresh sitemap dates and submit sitemap in Google Search Console.
7. Add a small backend test script or smoke test checklist to CI later.

