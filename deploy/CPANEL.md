# BRON: GitHub Actions and cPanel deployment

**For the current shahjaha hosting account, use [CPANEL-GIT.md](CPANEL-GIT.md).** The live workflow now publishes the `cpanel` branch for cPanel Git pull deployment; the older optional SSH dispatch described below is superseded. The File Manager steps remain a manual alternative.

This repository supports PHP 8.3, Apache and MariaDB 11.4. The workflow tests with SQLite and MariaDB, installs production Composer dependencies, and creates a ready-to-upload package. Current frontend assets are plain CSS/JavaScript already in `backend/public`; no Node build is needed. No credentials are included in the package.

Normal pushes to `main` **build only**. They do not change your live server. Choose File Manager installation without SSH, or enable the optional SSH update job after the first installation. Your existing Sites website remains separate until you point your domain at cPanel.

Current target: **https://bron.serinstech.com**. SSH is unavailable, so follow steps 1–8 using File Manager and cPanel browser Terminal if enabled (otherwise hosting support). Skip the optional SSH section unless your hosting access changes. GitHub automatically builds the upload package; the no-SSH path does not automatically transfer it to cPanel.

## 1. Get the tested package from GitHub

1. Open https://github.com/nazrinaza/bron-highworks-website/actions.
2. Choose **BRON cPanel**, then the latest successful run for `main`.
3. Under **Artifacts**, download `bron-cpanel-COMMIT`. You must be signed in to GitHub with repository access.
4. Extract that download on your computer. Inside are `bron-cpanel.zip`, `bron-cpanel.tar.gz`, `SHA256SUMS`, and this guide (possibly in subfolders).
5. Use the inner `bron-cpanel.zip` for cPanel. It contains a single `bron/` folder including `vendor/` and hidden `.htaccess` / `.env.example` files.
6. Optional integrity check from the directory containing the archives: `sha256sum -c SHA256SUMS` on Linux or `shasum -a 256 -c SHA256SUMS` on macOS.

Artifacts expire after 30 days. Use **Run workflow** with `deploy` unchecked to rebuild. Tests or package failures stop the workflow before an upload or deployment.

## 2. Confirm hosting settings

In cPanel, verify:

- **MultiPHP Manager / Select PHP Version:** PHP 8.3 for the BRON domain. Web PHP and CLI PHP can differ.
- Extensions: PDO MySQL, mbstring, OpenSSL, DOM/XML, cURL, fileinfo, ctype, filter, hash, PCRE, session and tokenizer.
- **Domains:** ability to set the document root to `/home/ACCOUNT/bron/public`, outside `public_html`.
- **SSL/TLS Status:** valid HTTPS certificate for the chosen domain. Enable HTTPS redirection after the certificate is active.
- Outbound TCP 443 with DNS and certificate validation to `api.resend.com` and your eventual payment API host. This is a hosting-provider firewall setting; the application cannot grant it.

Ask support for the exact PHP 8.3 CLI executable. Common examples are `/usr/local/bin/ea-php83` or `/opt/cpanel/ea-php83/root/usr/bin/php`; neither is guaranteed. Confirm with `THE_CONFIRMED_PATH -v` and `THE_CONFIRMED_PATH -m` in cPanel Terminal or ask support to run those checks. Use that same path for setup and cron.

## 3. Create the database

1. Open **MySQL Database Wizard / Manage My Databases**.
2. Create a database, e.g. `ACCOUNT_bron`.
3. Create a dedicated database user with a strong generated password.
4. Add the user to that database with all privileges on that database only. Record the full cPanel-prefixed names.
5. Confirm the database host with support; it is commonly `localhost`.

Use a new empty database for first installation. Do not run migration commands against CuciNow's database.

## 4. Upload and configure the first installation

1. In **File Manager → Settings**, enable **Show Hidden Files**.
2. Navigate to `/home/ACCOUNT` and upload the inner `bron-cpanel.zip`.
3. Extract there. Check that `/home/ACCOUNT/bron/artisan`, `vendor/autoload.php`, and `public/index.php` exist. Avoid a nested `bron/bron` directory.
4. Set the domain document root to `/home/ACCOUNT/bron/public`. Never use `/home/ACCOUNT/bron` as the web root.
5. Copy `bron/.env.example` to `bron/.env` in File Manager.
6. Edit only the server's `.env`:

```dotenv
APP_NAME="BRON Highworks"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bron.serinstech.com
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ACCOUNT_bron
DB_USERNAME=ACCOUNT_bron
DB_PASSWORD="YOUR_DATABASE_PASSWORD"
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
CACHE_STORE=database
QUEUE_CONNECTION=sync
MAIL_MAILER=log
```

Keep the other template settings. Use the password's exact value with valid dotenv quoting; ask for help if it contains quotes or backslashes. Never commit `.env` to GitHub. Keep `APP_KEY` blank until the next step.

Set `.env` permissions to `600` if PHP runs as your cPanel user. Typical files are `644`, directories `755`; `storage/` and `bootstrap/cache/` must be writable by PHP. Ask the host to fix ownership instead of making everything `777`.

## 5. Run initial Laravel setup

Use **cPanel → Advanced → Terminal**. cPanel browser Terminal is sufficient; remote SSH is not required. Replace the PHP path and account below with verified values:

```sh
cd /home/ACCOUNT/bron
BRON_PHP=/CONFIRMED/PATH/TO/PHP83
"$BRON_PHP" -v
"$BRON_PHP" artisan key:generate --force
"$BRON_PHP" artisan migrate --force
"$BRON_PHP" artisan bron:admin YOUR_ADMIN_EMAIL
"$BRON_PHP" artisan config:cache
"$BRON_PHP" artisan route:cache
"$BRON_PHP" artisan view:cache
```

The admin command privately prompts twice for a password with at least 12 characters, letters and numbers. There are no default login credentials. Do not use `db:seed` or `migrate:fresh` in production. Generate the application key **once on initial installation**, preserve it on updates, and back it up securely.

**If Terminal and SSH are both unavailable:** upload and configure through File Manager, then ask hosting support to run these initial commands and arrange secure administrator provisioning. The interactive admin command cannot be run from an unattended cron job. This package intentionally has no public web installer. SSH is optional, but some way to execute the initial Laravel commands is required.

## 6. Add the scheduler

In **Cron Jobs**, choose once per minute (`* * * * *`). Replace placeholders with your verified PHP executable and account:

```cron
* * * * * /CONFIRMED/PATH/TO/PHP83 /home/ACCOUNT/bron/artisan schedule:run >> /home/ACCOUNT/bron/storage/logs/scheduler.log 2>&1
```

If cPanel shows separate time fields, enter only the command after the five stars in its **Command** box. This is future-ready; the current app has no scheduled business tasks. Rotate or periodically inspect the scheduler log. A scheduler is not a queue worker. The template uses `QUEUE_CONNECTION=sync`; revisit worker setup when background integrations are added.

## 7. Verify before launch

- Open `https://bron.serinstech.com/up` (Laravel health endpoint).
- Open `/` and `/site-assessment`; submit a sample request.
- Sign in at `/admin/login` and check that the request appears.
- Schedule a visit and create a draft quotation, both PO types, invoice and delivery order. Check printed output.
- Check that `.env` is inaccessible from the web and HTTP redirects to HTTPS.
- Back up the database, `.env` including `APP_KEY`, and any files under `storage/app` privately.

`MAIL_MAILER=log` does not deliver mail. Resend and payment processing are not integrated yet. Do not add payment keys or promise email notifications until those integrations are implemented.

## 8. Updating with File Manager (no SSH)

1. Download a new successful Actions package and take a cPanel database backup plus a private backup of `.env` and the application.
2. Put the app into maintenance mode using Terminal: `THE_PHP_PATH /home/ACCOUNT/bron/artisan down`. If Terminal is unavailable, coordinate with support for this and the final commands.
3. Extract the archive to a separate private staging directory, not over the live app.
4. Preserve the live `.env`, entire `storage/` directory, `public/storage` if present, and `public/.well-known` certificate files.
5. Replace the live code directories `app`, `bootstrap`, `config`, `database`, `resources`, `routes`, and `vendor` from staging. Remove each old code directory before copying its replacement so deleted files do not remain. Replace public application files while preserving the two paths above. Replace `artisan`, `composer.json` and `composer.lock`. Do not copy the archive's empty storage over live storage.
6. Run the following commands using the confirmed PHP path:

```sh
cd /home/ACCOUNT/bron
BRON_PHP=/CONFIRMED/PATH/TO/PHP83
"$BRON_PHP" artisan config:clear
"$BRON_PHP" artisan migrate --force
"$BRON_PHP" artisan config:cache
"$BRON_PHP" artisan route:cache
"$BRON_PHP" artisan view:cache
"$BRON_PHP" artisan up
```

7. Verify the site and login again. Delete staging directories and uploaded archives after success. Preserve private backups according to your retention policy.

## 9. Optional automated SSH updates

This is an alternative to step 8 and requires SSH, Bash, `rsync`, `tar`, `sha256sum`, and `flock` on the hosting server. Initial installation and admin creation must already be complete at `/home/CPANEL_USER/bron`. No Composer or Node installation is needed on hosting.

1. Create a dedicated deployment SSH key on your own machine; install/authorize its public key through **cPanel → SSH Access → Manage SSH Keys**. Verify SSH access and the server host-key fingerprint with the host.
2. In GitHub, open **Settings → Environments → New environment**, name it `production`. Restrict deployment branches to `main`; enable required reviewers if your GitHub plan supports them.
3. Under that environment, add these **secrets**:

| Secret | Value |
| --- | --- |
| `CPANEL_HOST` | Hostname used for SSH, without a URL prefix |
| `CPANEL_PORT` | SSH port, commonly `22` (optional; defaults to 22) |
| `CPANEL_USER` | Hosting account username |
| `CPANEL_SSH_KEY` | Complete dedicated private key; use an automation key without a passphrase |
| `CPANEL_KNOWN_HOSTS` | Verified OpenSSH known_hosts line(s), including `[host]:port` for nonstandard ports |

Obtain the known_hosts entry from your host or a trusted connection, and verify its fingerprint through the provider. Do not disable host-key checking. Do not paste any private key into a chat or repository file.

4. Add these environment **variables**:

| Variable | Value |
| --- | --- |
| `CPANEL_APP_DIR` | `/home/YOUR_CPANEL_USER/bron` |
| `CPANEL_PHP` | Verified absolute PHP 8.3 CLI executable path |
| `BRON_URL` | Final HTTPS URL, e.g. `https://bronhighworks.com` if that is your selected domain |

5. Take a database backup in cPanel before deploying. The script creates a private **code** backup, not a database backup.
6. Open **Actions → BRON cPanel → Run workflow**, select `main`, and check **deploy**. Approve the environment job if configured.
7. The workflow retests, builds, uploads over verified SSH, checks the package checksum and server PHP, locks concurrent deployments, enters maintenance mode, backs up code, synchronizes files, migrates, rebuilds caches, exits maintenance mode, and checks `/up` over HTTPS.

Server `.env`, runtime storage, and certificate validation files are preserved. The script removes stale application code. Code backups under `/home/ACCOUNT/bron-backups` include `.env`, are private, and need a retention policy. Failed updates after maintenance begins leave the app in maintenance mode; no automatic database rollback occurs. A failed post-deployment HTTPS check reports failure but does not undo an otherwise completed update.

## 10. Recovery and troubleshooting

- **Deployment failed in maintenance:** review the Actions log and `storage/logs/laravel.log` privately. Fix the error and rerun the update. Run `THE_PHP_PATH artisan up` only once code and database are consistent.
- **Rollback:** restore matching code from the private backup while preserving `.env` and storage. If migrations changed the schema, assess compatibility and restore the pre-update database backup when needed; restoring a database loses writes since that backup. Do not blindly run migration rollback.
- **500 error:** check PHP version/extensions, `APP_KEY`, writable directories and Laravel logs. Keep production debug disabled.
- **Database access denied:** verify the cPanel prefixes, password, host and user privileges.
- **419/session error:** verify HTTPS, `APP_URL`, session tables and cookie configuration.
- **Routes return 404:** ensure `public/.htaccess` exists and Apache rewrite support is enabled.
- **CLI uses another PHP version:** use the full provider-confirmed executable, including in cron.
- **SSH host-key mismatch:** confirm a legitimate host-key change with the provider before updating the secret.
- **Actions unavailable:** enable Actions in repository settings, check third-party action policies and private-repository billing/minute allowance.

## Git Version Control in cPanel

The cPanel Git feature alone does not install Composer dependencies. This workflow uses a tested artifact, so cloning the source into cPanel is optional. Do not mix cPanel's Git deployment with the artifact update workflow against the same live directory. If you keep a source mirror, put it in a separate private directory. There is intentionally no `.cpanel.yml` blindly copying an unbuilt checkout into production.

## Official references

- Laravel deployment: https://laravel.com/docs/13.x/deployment
- cPanel Git deployment: https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/
- GitHub workflow artifacts: https://docs.github.com/en/actions/concepts/workflows-and-actions/workflow-artifacts
