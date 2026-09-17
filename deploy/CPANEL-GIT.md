# BRON — Spaceship cPanel Git Version Control

Use this guide for **https://bronhighworks.com** on the Spaceship cPanel account `aneowclfol`, without remote SSH login. It supersedes the earlier Serinstech instructions.

## Directory layout

| Purpose | Exact path |
| --- | --- |
| Domain document root, public files only | `/home/aneowclfol/bronhighworks.com` |
| Private Laravel application, dependencies, `.env` and storage | `/home/aneowclfol/bron` |
| cPanel Git repository (choose this on creation) | `/home/aneowclfol/repositories/bron-highworks` |
| Optional web-root override (one line, outside Git) | `/home/aneowclfol/.bron-web-root` |
| Optional PHP executable override (one line, outside Git) | `/home/aneowclfol/.bron-php-path` |
| Optional one-time initial admin credentials | `/home/aneowclfol/bron-admin.json` |
| Private deployment backups | `/home/aneowclfol/bron-backups` |

The public `index.php` loads Laravel from the private app directory. Keep the current domain document root; do not put `.env`, `vendor`, the Git checkout, or the complete Laravel app under `public_html`. The path contains the literal `public_html`, with **no backslash** before `_`.

## Spaceship document root

Spaceship creates a document root folder named after the hosted domain. This account serves `bronhighworks.com` from `/home/aneowclfol/bronhighworks.com`, as confirmed in File Manager and by Spaceship's hosting documentation. The browser URL is `https://bronhighworks.com` — do not add `/public` to the URL or `APP_URL`.

Create `/home/aneowclfol/.bron-web-root` in File Manager with exactly `/home/aneowclfol/bronhighworks.com` on its first line. After deploying, check that `index.php`, `.htaccess`, `operations.css` and `brand/` are directly inside that domain folder, not in another nested `public` folder. Keep the private application and Git checkout at the paths shown above.

If you previously deployed using the old parent-directory destination, back up and review those old public files separately. The corrected deployment only synchronizes the new `/public` destination; it does not remove files from its parent. Do not move or delete the new `public` directory during cleanup.

## How updates flow

1. Source changes are pushed to `main` on GitHub.
2. GitHub Actions tests PHP 8.3 with SQLite and MariaDB 11.4, installs production dependencies, and builds the package.
3. After success, Actions publishes the ready-to-deploy package to the **`cpanel` branch** in the same public repository. Never edit this generated branch manually.
4. In cPanel, **Update from Remote** retrieves the new `cpanel` commit.
5. **Deploy HEAD Commit** runs `.cpanel.yml`, which installs the private app, publishes only public files, runs migrations, and refreshes caches.

No Composer, Node or remote shell login is required on hosting. cPanel must be allowed to execute deployment commands and provide Bash, tar, sha256sum and standard file utilities, and a PHP 8.3 CLI executable. Tests run in GitHub; the actual host is still to be verified. cPanel does not automatically pull GitHub just because a GitHub build succeeded.

**Hosting prerequisite:** cPanel documents that accounts without shell access can only create, clone, delete and view repositories. If your account has shell access entirely disabled, the provider must enable the required shell/deployment entitlement (often jailed shell) before Pull/Deploy can work. You can still operate through the cPanel UI without logging in over SSH. Simply seeing the Git icon does not prove deployment is enabled.

## 1. Public GitHub repository access

The BRON repository is now public. Use the **HTTPS clone URL**:

```text
https://github.com/nazrinaza/bron-highworks-website.git
```

Public HTTPS cloning does not require a GitHub login, deploy key, personal access token, or SSH authentication. The hosting server needs outbound HTTPS access to GitHub. GitHub Actions still uses its built-in token to publish the generated `cpanel` branch; no new hosting credential is needed for that.

If an earlier clone used `git@github.com:...`, making the repository public does **not** change the saved SSH URL. For an existing checkout, ask hosting support to change its `origin` to the HTTPS URL above. If the earlier clone failed and no usable checkout exists, create a new cPanel clone using HTTPS in an empty private repository directory. Do not delete a working checkout or create duplicate clones while a clone is still running.

The notice “successfully initiated the clone process” is normal background progress, not an authentication error. Wait for completion and refresh the Git repository list; inspect the actual error if cloning fails.

The public GitHub repository contains source and build packages only. Continue keeping the server `.env`, customer database, runtime files and initial-admin JSON outside Git. The server directories described below remain private even though the source repository is public.

## 2. Create the cPanel repository

Open **cPanel → Files → Git™ Version Control → Create**:

- **Clone a Repository:** enabled.
- **Clone URL:** `https://github.com/nazrinaza/bron-highworks-website.git` (public HTTPS; no credentials required).
- **Repository Path:** `/home/aneowclfol/repositories/bron-highworks`.
- **Repository Name:** `BRON Highworks`.

Create the repository. Open **Manage** and select **`cpanel`** as the checked-out branch. If it is not listed, wait for the GitHub Actions build to finish and refresh/update the remote branches. The default `main` branch is source-only and intentionally refuses deployment.

Do not create the Git repository in `/home/aneowclfol/bronhighworks.com`. If you already have a checkout there, back it up and ask support to relocate the repository before proceeding. Do not delete unrelated site files blindly.

## 3. Configure PHP, database, HTTPS and environment

1. Set `bronhighworks.com` to PHP 8.3 in **PHP Tweaks**. Enable PDO MySQL and Laravel's required extensions.
2. Confirm the domain points at Spaceship hosting and that HTTPS is active. Its document root stays `/home/aneowclfol/bronhighworks.com`.
3. The deployment automatically finds a compatible PHP 8.3+ CLI. If it reports that none is available, ask Spaceship support for the absolute PHP 8.3 CLI executable, then create `/home/aneowclfol/.bron-php-path` containing only that path.
4. In File Manager, enable **Show Hidden Files**. Create `/home/aneowclfol/.bron-web-root` containing only `/home/aneowclfol/bronhighworks.com`.
5. Create a dedicated MySQL/MariaDB database and user through cPanel. Grant that user privileges on the BRON database only. Record the complete cPanel-prefixed names. Do not reuse the CuciNow database.
6. Create the private folder `/home/aneowclfol/bron` and inside it a file named `.env`:

```dotenv
APP_NAME="BRON Highworks"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://bronhighworks.com
APP_KEY=
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=YOUR_FULL_CPANEL_DATABASE_NAME
DB_USERNAME=YOUR_FULL_CPANEL_DATABASE_USERNAME
DB_PASSWORD="YOUR_DATABASE_PASSWORD"
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_LIFETIME=120
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
APP_MAINTENANCE_DRIVER=file
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning
MAIL_MAILER=resend
MAIL_FROM_ADDRESS=hello@bronhighworks.com
MAIL_FROM_NAME="BRON Highworks"
RESEND_API_KEY="YOUR_RESEND_API_KEY"
BRON_NOTIFICATION_EMAIL=hello@bronhighworks.com
```

Before enabling email, add and verify `bronhighworks.com` in Resend, publish the DKIM and SPF records Resend provides in the domain's authoritative DNS, and create a sending-only API key. Store the key only in the private server `.env`; never put it in GitHub or this guide. After editing `.env`, deploy again so Laravel rebuilds its configuration cache. A new assessment queues a confirmation to the customer and an alert to `BRON_NOTIFICATION_EMAIL`. Each admin document page can queue its quotation, customer or supplier PO, invoice, or delivery order to an editable recipient.

Use the host-confirmed database hostname if it is not `localhost`. Preserve valid dotenv quoting, especially if a password includes quotes or backslashes. Set `.env` permissions to `600` (PHP must run as the cPanel user). Leave `APP_KEY` blank only for a brand-new installation; the deployment generates it automatically. Never reset an existing application key on updates. Never commit this server `.env` to GitHub.

## 4. Create the initial administrator without Terminal

For a **new installation with no admin yet**, use File Manager to create `/home/aneowclfol/bron-admin.json`, outside `public_html` and outside the Git checkout:

```json
{
  "name": "BRON Admin",
  "email": "YOUR_ADMIN_EMAIL",
  "password": "REPLACE_WITH_YOUR_UNIQUE_PASSWORD"
}
```

Replace both placeholders. Use a unique password with at least 12 characters including letters and numbers. This is JSON: quotes and backslashes inside values must be escaped. Set file permissions to `600` before deploying. Do not put credentials in GitHub, `.cpanel.yml`, URLs, cron commands, or chat.

The CLI-only provisioning helper validates this file, creates the first admin, hashes the password, and deletes the file after success. It refuses to reset existing users or create another admin if one already exists. It has no public web route. If deployment reports that provisioning failed after the admin was saved, remove the leftover file manually before retrying. Additional staff accounts/password resets use the existing interactive `bron:admin` command through support or Terminal.

## 5. First deployment

1. Ensure the latest **BRON cPanel** Actions run on GitHub is green and includes a successful **publish-cpanel** job.
2. Take a backup of any existing content at the public path. Deployment replaces BRON's public files and removes stale application files, while preserving `.well-known` certificate files and `public/storage` if present.
3. In cPanel → Git Version Control → **Manage**, confirm branch **cpanel**.
4. Open **Pull or Deploy → Update from Remote**.
5. Click **Deploy HEAD Commit**.
6. Wait for the deployment task to finish and inspect its result/log.

Deployment checks the package checksum and PHP version, locks concurrent deployments, backs up existing code/public files, blocks public requests during the update, copies files, generates the initial application key if missing, migrates the database, provisions the optional initial admin, caches configuration/routes/views, and brings the site online. Server `.env` and runtime storage are preserved. The checkout remains clean so future cPanel pulls work.

If your hosting cannot execute these tasks, ask support to enable cPanel Git deployment commands for the account. Remote SSH login itself is not required for clicking Deploy HEAD Commit.

## 6. Verify and add cron

Open:

- `https://bronhighworks.com/up`
- `https://bronhighworks.com/site-assessment`
- `https://bronhighworks.com/admin/login`

Submit a test assessment, sign in with the admin credentials, and verify visits/documents. Check HTTPS and that private files are inaccessible. Confirm `bron-admin.json` was removed. Back up the database, `.env` including `APP_KEY`, and private uploaded files.

In **Cron Jobs**, choose **Once Per Five Minutes**. Spaceship allows up to five simultaneous cron jobs and does not permit this job to run more often than every five minutes. Use the PHP executable shown by cPanel:

```cron
*/5 * * * * /usr/local/bin/php /home/aneowclfol/bron/artisan schedule:run >> /dev/null 2>&1
```

If cPanel has separate time fields, enter `*/5` for **Minute**, `*` for the other four time fields, and paste only `/usr/local/bin/php /home/aneowclfol/bron/artisan schedule:run >> /dev/null 2>&1` into **Command**.

If the **Cron Jobs** menu is absent, send the exact completed line to Spaceship support and ask them to add it to the `aneowclfol` account crontab. Uploading or saving a shell file by itself will not schedule it. Each scheduler run starts Laravel's database queue worker, delivers pending email through Resend, and exits when the queue is empty. With Spaceship's five-minute interval, queued email normally waits up to five minutes before delivery. Failed jobs retry up to three times and are recorded in `failed_jobs` after the final attempt.

## 7. Future updates and recovery

For each release: wait for successful GitHub build/publication → take a database backup → **Update from Remote** → **Deploy HEAD Commit** → verify the website.

The private `bron-backups` folder stores code/public backups and `.env`; it is not a database backup. Keep it private, download/store backups securely, and manage retention to avoid exhausting hosting storage. Deployment intentionally leaves a 503 maintenance response if an error occurs after maintenance begins. Fix the error and redeploy. Never remove maintenance mode while the database and code are inconsistent.

If rollback is necessary, have support restore matching code/public files and assess whether the database requires its pre-deployment backup. Database restoration can lose newer requests. Do not run `migrate:fresh`, seed demo data, or regenerate `APP_KEY` on a live installation.

If Git reports a dirty checkout, do not edit deployment branch files locally. Put settings in the private files described above. If a PHP path, archive utility, permission, extension, or Git authentication error appears, send that error to support without exposing credentials.

## Copy this request to hosting support if needed

> Please enable cPanel Git pull deployment for Spaceship account aneowclfol, repository nazrinaza/bron-highworks-website, using the public HTTPS clone URL https://github.com/nazrinaza/bron-highworks-website.git. I do not have remote SSH access. The checkout will be /home/aneowclfol/repositories/bron-highworks on branch cpanel. Please confirm the PHP 8.3 CLI executable and availability of Bash, tar, sha256sum and standard file utilities for .cpanel.yml tasks. The private Laravel app will be /home/aneowclfol/bron; public files will be /home/aneowclfol/bronhighworks.com. Please confirm PDO MySQL, writable storage/bootstrap cache, Apache rewrite support, HTTPS and outbound HTTPS to api.resend.com and the eventual payment provider.

Official references:

- https://www.spaceship.com/knowledgebase/how-to-use-cpanel-file-manager/
- https://docs.cpanel.net/knowledge-base/web-services/guide-to-git-deployment/
- https://docs.cpanel.net/cpanel/files/git-version-control/

## Shared hosting without rsync or flock

The current deployment uses PHP's filesystem functions to synchronize application/public files and an atomic lock directory to prevent concurrent deployments. It does **not** require the `rsync` or `flock` commands. It preserves `.env`, runtime storage, SSL verification files and the maintenance response until installation completes; stale code is removed.

If your log still says `required hosting command "rsync" is unavailable`, you are running the older script. Wait for the latest GitHub Actions build and publish-cpanel jobs to succeed, select branch `cpanel`, click **Update from Remote**, then **Deploy HEAD Commit**.

The new log begins with `BRON deployment started (portable cPanel deployment v4).` Read the newest `/home/aneowclfol/.cpanel/logs/vc_..._git_deploy.log` for progress and errors. If that marker is absent, check the checked-out commit and the hosting task runner before retrying.

Normal exits remove `/home/aneowclfol/.bron-deploy-lock` and the temporary staging directory. If the hosting provider forcibly kills a deployment, a stale lock directory may remain. Only after confirming that no deployment is running, remove that **empty lock directory** in File Manager and retry. Do not remove an active deployment lock. A permissions error while creating the lock also needs to be resolved rather than bypassed.

## Files and migrations succeeded, but admin provisioning failed

The application/database are already installed; do not delete the database, rerun initial setup from scratch, or regenerate `APP_KEY`.

1. In File Manager, privately edit `/home/aneowclfol/bron-admin.json`. It must be a JSON object containing `name`, `email` and `password`, using double quotes with no trailing comma.
2. `name` must be non-empty text; `email` must be a real valid email address; `password` must be text with at least 12 characters including letters and numbers. Do not leave the example placeholders unchanged.
3. Save it with permissions `600`, owned by the cPanel account. Do not send this file or password in chat.
4. On branch `cpanel`, click **Update from Remote**, then **Deploy HEAD Commit**. Existing migrations are preserved; the deployment retries admin creation and finishes the caches/maintenance steps.
5. The updated helper emits `BRON ADMIN ERROR` with the exact field/format issue, without printing entered values. If it says an admin already exists because a previous run saved it, remove the provisioning JSON file and redeploy. If you do not know the existing admin password, use hosting support and `bron:admin` for recovery.

Successful deployment automatically exits maintenance mode. Do not manually remove the public maintenance rules while provisioning or deployment remains incomplete.
