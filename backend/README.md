# BRON Highworks — Laravel operations

Laravel 13 application with the BRON website, public site-assessment requests, staff-only visit management, quotations, customer purchase orders, supplier purchase orders, invoices, and delivery orders.

## Local setup

Requires PHP 8.3+ and Composer. The dependency lock targets PHP 8.3; automated tests were run on PHP 8.4.

```sh
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan bron:admin your-email@example.com
php artisan serve --host=127.0.0.1 --port=8085
```

The admin command prompts privately for a password and confirmation. There are no default admin credentials and no public staff registration. It can also reset a staff password.

- Website: `/`
- Customer form: `/site-assessment`
- Staff login: `/admin/login`
- Visits: `/admin/visits`
- Documents: `/admin/documents`

No Node build is required: the Blade views use the supplied CSS and JavaScript in `public/`.

## cPanel installation

1. Confirm PHP 8.3 or newer and Laravel's required PHP extensions, including PDO MySQL, mbstring, OpenSSL, XML/DOM, ctype, fileinfo, curl, session, and tokenizer. Enable HTTPS for the chosen domain.
2. Upload/extract the application outside the public web directory, e.g. `/home/ACCOUNT/bron`. Point the domain document root at `/home/ACCOUNT/bron/public`. Never expose the project root or `.env` through `public_html`.
3. Create a dedicated MySQL database and database user in cPanel. Grant that user access only to the BRON database.
4. Copy `.env.example` to `.env` and set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://YOUR-DOMAIN`, `DB_CONNECTION=mysql`, `DB_HOST=localhost`, `DB_PORT=3306`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, and `SESSION_SECURE_COOKIE=true`. Do not copy a local database or application key into production.
5. If installing the source archive, run `composer install --no-dev --optimize-autoloader`. The cPanel bundle already includes production dependencies. Run `composer check-platform-reqs --no-dev` if Composer is available.
6. Run `php artisan key:generate`, `php artisan migrate --force`, and `php artisan bron:admin YOUR-STAFF-EMAIL` using the correct cPanel PHP binary. Make `storage/` and `bootstrap/cache/` writable by the hosting PHP process; do not use world-writable permissions.
7. Set `QUEUE_CONNECTION=database`. Configure cron to run `php artisan schedule:run` at the shortest interval supported by the host; the scheduler drains queued email and exits. Run `php artisan config:cache`, `php artisan route:cache`, and `php artisan view:cache`. Verify public submission, staff login, visit updates, and a document printout over HTTPS. Back up the database and application key securely.

If cPanel cannot point the document root at `public/`, obtain the host's recommended Laravel setup before launch. Exact hosting PHP version, document root, database, and deployment access remain unverified.

## Workflow

Customer requests receive a reference. The preferred date is not a confirmed appointment. Staff record a schedule, assignee, internal notes, and assessment findings; completed visits require findings.

Create documents from a visit or independently. A linked document copies party and line-item details into a new draft; review them before issuing, especially when changing between customers and suppliers. Monetary amounts use integer cents with half-up rounding per line and tax on the subtotal. Maximum subtotal is MYR 10 billion. No tax rate is assumed. Numbering uses immutable, unique database IDs.

Drafts are editable with stale-update protection. Issued/received documents are locked and cannot return to draft. Status transitions distinguish quotation acceptance, customer PO fulfilment, supplier receipt, invoice payment, and delivery completion. Cancellation preserves the record. Printed delivery orders omit prices and include a receipt signature area. Print / Save as PDF uses the browser print dialog.

Status changes do not send email, create WhatsApp messages, process payments, or integrate with MyInvois. Invoice paid status is a manual staff record, not payment reconciliation. There are no attachment uploads or public document URLs in this version. Admin accounts share the same permissions. Authentication uses session cookies, CSRF protection, and login throttling. Public forms are validated and rate-limited with a honeypot field.

## Checks

```sh
vendor/bin/phpunit
vendor/bin/pint --dirty --format agent
```

The existing Sites-hosted marketing page is separate and remains live until the Laravel app is deployed to PHP hosting.
