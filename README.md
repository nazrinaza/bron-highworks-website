# BRON Highworks

BRON Highworks website and Laravel operations application for drone-powered building, façade, and solar panel cleaning.

- `backend/`: Laravel application with the BRON website, public site-assessment form, admin visit management, quotations, customer and supplier purchase orders, invoices, and delivery orders.
- `dist/`: standalone static marketing website.

## Laravel setup and hosting

See [backend/README.md](backend/README.md) for installation, admin account creation, cPanel deployment, and workflow details. Requires PHP 8.3+. Point the hosting domain document root to `backend/public` when deploying this repository layout. Use a dedicated MySQL/MariaDB database and HTTPS in production.

Dependencies are pinned in `backend/composer.lock`; vendor files, local databases, credentials, and runtime files are excluded. The current app uses ready-to-serve CSS and JavaScript and does not require a frontend build. GitHub Actions tests and builds a cPanel installation package on every push to `main`. The tested package is published to the `cpanel` branch for cPanel Git pull deployment. Assessment notifications and admin-sent business documents are queued in the database and delivered through Resend by Laravel's scheduler.

## Verification

The Laravel suite passes 12 tests with 123 assertions on PHP 8.4 using SQLite. The hosting server and MariaDB deployment still require verification.

## Brand and contact

Primary colour: `#cbdd29`. WhatsApp: +60 19-652 2238. Email: hello@bronhighworks.com.

Office: 316-B, Lorong Kedah, Taman Melawati, 53100 Kuala Lumpur, Malaysia.

The hero image is an AI-generated illustration, not a photograph of a completed BRON project.

## cPanel deployment guide

Follow [deploy/CPANEL.md](deploy/CPANEL.md) for detailed File Manager installation, database setup, admin creation, cron, GitHub environment secrets, automated SSH updates, and recovery.

## Current hosting: cPanel Git Version Control

For `bronhighworks.com` on Spaceship account `aneowclfol`, use [deploy/CPANEL-GIT.md](deploy/CPANEL-GIT.md). GitHub Actions publishes the tested application with dependencies to branch `cpanel`; select that branch in cPanel and use Update from Remote / Deploy HEAD Commit. Public files deploy to `/home/aneowclfol/bronhighworks.com`, while private Laravel files stay in `/home/aneowclfol/bron`.

The repository is public: use HTTPS clone URL `https://github.com/nazrinaza/bron-highworks-website.git` in cPanel. No deploy key is needed for cloning. Server credentials and customer data remain outside Git.
