# Setup On A New Computer

Follow these steps to continue Grey Wolf 3PL website development on a new Windows computer.

## 1. Install Required Programs

Install Git:

```powershell
winget install --id Git.Git -e
```

Install VS Code:

```powershell
winget install --id Microsoft.VisualStudioCode -e
```

Install Docker Desktop:

```powershell
winget install --id Docker.DockerDesktop -e
```

Restart the computer if Docker or Git asks for it.

Optional tools:

- Codex desktop app.
- FileZilla or WinSCP for Bluehost uploads.
- PostgreSQL client such as pgAdmin.
- PHP 8.2 if you want to run without Docker.

## 2. Clone The GitHub Repo

```powershell
cd C:\
git clone https://github.com/wms365com-dev/greywolf3pl_web_usa.git C:\GreyWolfWebsite
cd C:\GreyWolfWebsite
```

Confirm the branch:

```powershell
git status
```

You should be on `main`.

## 3. Create Local Environment File

```powershell
copy .env.example .env
```

Open `.env` in VS Code and manually add the real values from Railway or the secure password source.

Do not commit `.env`.

## 4. Build And Run Locally

```powershell
docker build -t greywolf3pl-local .
docker run --rm -p 8080:8080 --env-file .env greywolf3pl-local
```

Open:

```text
http://localhost:8080/
http://localhost:8080/health.php
```

The health page should return JSON. If `DATABASE_URL` is present and correct, it should show:

```json
"database_configured":true
```

and:

```json
"database_ready":true
```

## 5. Test Main Workflows

- Homepage loads on desktop.
- Homepage works at mobile width.
- Services menu can be opened and clicked.
- Quote form submits.
- Drayage form submits.
- Delivery appointment form submits.
- Standard-hours appointments confirm.
- After-hours appointments show pending review.
- Inbound tracker opens.
- Tools index opens.
- LPN label generator opens and print preview looks correct.
- Pallet builder opens.
- Form emails route to `info@greywolf3pl.com`.

## 6. Push Changes To GitHub

```powershell
cd C:\GreyWolfWebsite
git status
git add .
git commit -m "Update Grey Wolf site"
git push origin main
```

If Git asks for your name/email:

```powershell
git config --global user.name "Your Name"
git config --global user.email "your-email@example.com"
```

## 7. Confirm Railway Redeploys

1. Open Railway.
2. Open the Grey Wolf project.
3. Confirm the GitHub service is connected to `wms365com-dev/greywolf3pl_web_usa`.
4. Confirm the branch is `main`.
5. Confirm PostgreSQL is attached and online.
6. Confirm the web service has `DATABASE_URL`.
7. Watch the deployment after `git push`.
8. Open the Railway health URL:

```text
https://greywolf3plwebusa-production.up.railway.app/health.php
```

Expected database-ready result:

```json
{"ok":true,"service":"greywolf3pl","database_configured":true,"database_ready":true}
```

## 8. Bluehost And Railway Split

If Bluehost hosts the public website, upload the static/PHP public files to Bluehost and keep form endpoints pointed to Railway.

Recommended long-term setup:

- `greywolf3pl.com` and `www.greywolf3pl.com` point to Bluehost.
- `api.greywolf3pl.com` points to Railway.
- Forms on Bluehost post to `https://api.greywolf3pl.com/...`.

Until `api.greywolf3pl.com` DNS is working, use the Railway app URL for backend form posts.

## 9. Important Warnings

- Do not commit `.env`.
- Do not commit SMTP passwords, database URLs, API keys, Google Apps Script webhook URLs, or customer data.
- Do not delete old WordPress files unless a full backup exists and Bluehost no longer uses them.
- `E:\GreyWolfWebsite` was the newest working folder on the old computer. Confirm it was synced to GitHub before relying only on the clone.

