# BRON Highworks

BRON Highworks website and Laravel operations application for drone-powered building, façade, and solar panel cleaning.

- `backend/`: Laravel application with the BRON website, public site-assessment form, admin visit management, quotations, customer and supplier purchase orders, invoices, and delivery orders.
- `dist/`: standalone static marketing website.

## Laravel setup and hosting

See [backend/README.md](backend/README.md) for installation, admin account creation, cPanel deployment, and workflow details. Requires PHP 8.3+. Point the hosting domain document root to `backend/public` when deploying this repository layout. Use a dedicated MySQL/MariaDB database and HTTPS in production.

Dependencies are pinned in `backend/composer.lock`; vendor files, local databases, credentials, and runtime files are excluded. The current app uses ready-to-serve CSS and JavaScript and does not require a frontend build. GitHub Actions, automatic deployment, Resend email, and payment integrations are not configured yet.

## Verification

The Laravel suite passes 12 tests with 123 assertions on PHP 8.4 using SQLite. The hosting server and MariaDB deployment still require verification.

## Brand and contact

Primary colour: `#cbdd29`. WhatsApp: +60 19-652 2238. Email: hello@bronhighworks.com.

Office: 316-B, Lorong Kedah, Taman Melawati, 53100 Kuala Lumpur, Malaysia.

The hero image is an AI-generated illustration, not a photograph of a completed BRON project.
