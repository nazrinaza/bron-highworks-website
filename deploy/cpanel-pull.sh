#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
# Keep all diagnostics on the task runner error stream.
exec 1>&2
# cPanel task runners may provide a smaller PATH than an interactive shell.
export PATH="${PATH:-/usr/bin:/bin}:/usr/local/bin:/usr/bin:/bin"
trap 'code=$?; printf "BRON ERROR: deployment stopped at script line %s (exit %s). See the preceding message.\n" "$LINENO" "$code" >&2; exit "$code"' ERR
printf 'BRON deployment started (diagnostics v2).\n' >&2
repo_dir=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
account_dir=/home2/shahjaha
app_dir=$account_dir/bron
web_dir=$account_dir/public_html/bron.serinstech.com/public
[[ -f "$repo_dir/bron-cpanel.tar.gz" ]] || { echo 'Select the cpanel branch, not main. It contains the GitHub-built application.'; exit 1; }
[[ -f "$account_dir/.bron-php-path" ]] || { echo 'Create /home2/shahjaha/.bron-php-path containing the provider-confirmed PHP 8.3 executable path.'; exit 1; }
IFS= read -r php_bin < "$account_dir/.bron-php-path" || true
php_bin=${php_bin%$'\r'}
[[ "$php_bin" = /* && -x "$php_bin" ]] || { echo 'Invalid PHP CLI path.'; exit 1; }
[[ -f "$app_dir/.env" ]] || { echo 'Create /home2/shahjaha/bron/.env first. Follow CPANEL-GIT.md.'; exit 1; }
[[ ! -L "$app_dir" && ! -L "$web_dir" ]] || { echo 'Deployment directories must not be symlinks.'; exit 1; }
printf 'BRON: checking hosting deployment tools.\n' >&2
for required_tool in rsync flock sha256sum tar mktemp mkdir cp find chmod date rm; do
    if ! command -v "$required_tool" >/dev/null 2>&1; then
        printf 'BRON ERROR: required hosting command "%s" is unavailable. Ask hosting support to enable it for cPanel Git deployment tasks. No application files have been copied.\n' "$required_tool" >&2
        exit 1
    fi
done
printf 'BRON: hosting tools found; checking lock, archive and PHP.\n' >&2
exec 9>"$account_dir/.bron-deploy.lock"
flock -n 9 || { echo 'Another deployment is running.'; exit 1; }
cd "$repo_dir"
sha256sum -c SHA256SUMS --ignore-missing
"$php_bin" -r 'if (PHP_VERSION_ID < 80300 || !extension_loaded("pdo_mysql")) { fwrite(STDERR, "PHP 8.3 and PDO MySQL are required.\n"); exit(1); }'
staging=$(mktemp -d "$account_dir/.bron-stage.XXXXXXXX")
trap 'rm -rf -- "$staging"' EXIT
tar -xzf "$repo_dir/bron-cpanel.tar.gz" -C "$staging"
"$php_bin" -r 'require $argv[1];' "$staging/bron/vendor/composer/platform_check.php"
mkdir -p "$app_dir/storage" "$web_dir"
initial=1
if [[ -f "$app_dir/artisan" ]]; then
    initial=0
    cd "$app_dir"
    "$php_bin" artisan down --retry=60
fi
# Block HTTP even during the first install, before Laravel itself is configured.
# A failed deployment intentionally leaves this Apache maintenance response in place.
backup_dir="$account_dir/bron-backups/$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -p "$backup_dir"
chmod 700 "$account_dir/bron-backups" "$backup_dir"
if [[ "$initial" == 0 ]]; then
    tar --exclude='./storage' -czf "$backup_dir/application.tar.gz" -C "$app_dir" .
fi
tar -czf "$backup_dir/public.tar.gz" -C "$web_dir" .
cp "$app_dir/.env" "$backup_dir/environment"
cat > "$web_dir/.htaccess" <<'MAINTENANCE'
RewriteEngine On
RewriteRule ^ - [R=503,L]
ErrorDocument 503 "BRON is undergoing maintenance. Please try again shortly."
MAINTENANCE
trap 'code=$?; printf "BRON ERROR: deployment stopped at script line %s (exit %s); the site remains in maintenance.\n" "$LINENO" "$code" >&2; exit "$code"' ERR
printf 'BRON: copying application and public files.\n' >&2
# Excluded runtime paths remain untouched; stale code files are removed.
rsync -a --delete --exclude='/.env' --exclude='/storage/' "$staging/bron/" "$app_dir/"
# Initialize missing runtime directories without overwriting existing files.
rsync -a --ignore-existing "$staging/bron/storage/" "$app_dir/storage/"
rsync -a --delete --exclude='/.htaccess' --exclude='/.well-known/' --exclude='/storage' "$staging/bron/public/" "$web_dir/"
cp "$repo_dir/deploy/public-index.php" "$web_dir/index.php"
find "$app_dir" -path "$app_dir/storage" -prune -o -type d -exec chmod 755 {} +
find "$app_dir" -path "$app_dir/storage" -prune -o -type f ! -name '.env' -exec chmod 644 {} +
find "$web_dir" -path "$web_dir/.well-known" -prune -o -type d -exec chmod 755 {} +
find "$web_dir" -path "$web_dir/.well-known" -prune -o -type f -exec chmod 644 {} +
chmod 600 "$app_dir/.env"
cd "$app_dir"
"$php_bin" artisan config:clear
if [[ "$initial" == 1 ]]; then
    if ! "$php_bin" -r 'require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); exit(config("app.key") ? 0 : 1);'; then
        "$php_bin" artisan key:generate --force
    fi
fi
"$php_bin" artisan migrate --force
if [[ -f "$account_dir/bron-admin.json" ]]; then
    "$php_bin" "$repo_dir/deploy/first-admin.php" "$app_dir" "$account_dir/bron-admin.json"
fi
"$php_bin" artisan config:cache
"$php_bin" artisan route:cache
"$php_bin" artisan view:cache
"$php_bin" artisan up
cp "$staging/bron/public/.htaccess" "$web_dir/.htaccess"
chmod 644 "$web_dir/.htaccess"
trap - ERR
echo 'BRON deployed. Test https://bron.serinstech.com/site-assessment and /admin/login. Database backups are managed separately.'
