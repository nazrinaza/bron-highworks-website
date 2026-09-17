#!/usr/bin/env bash
# Used only for updates to an already configured installation.
set -euo pipefail
umask 077
app_dir=$1
php_bin=$2
upload_dir=$3
[[ "$app_dir" =~ ^/home/[A-Za-z0-9_-]+/bron$ ]] || { echo 'Expected /home/ACCOUNT/bron'; exit 1; }
[[ -f "$app_dir/.env" && -f "$app_dir/artisan" ]] || { echo 'Complete initial installation first.'; exit 1; }
[[ -x "$php_bin" ]] || { echo 'PHP CLI path is not executable.'; exit 1; }
command -v rsync >/dev/null
command -v flock >/dev/null
exec 9>"$(dirname "$app_dir")/.bron-deploy.lock"
flock -n 9 || { echo 'Another deployment is running.'; exit 1; }
cd "$upload_dir"
sha256sum -c SHA256SUMS --ignore-missing
tar -xzf bron-cpanel.tar.gz
"$php_bin" -r 'if (PHP_VERSION_ID < 80300 || !extension_loaded("pdo_mysql")) { exit(1); }'
"$php_bin" -r 'require $argv[1];' "$upload_dir/bron/vendor/composer/platform_check.php"
cd "$app_dir"
"$php_bin" artisan down --retry=60
trap 'echo "Deployment failed; maintenance mode remains enabled. See deploy/CPANEL.md recovery steps."' ERR
backup_dir="$(dirname "$app_dir")/bron-backups"
mkdir -p "$backup_dir"
chmod 700 "$backup_dir"
tar --exclude='./storage' -czf "$backup_dir/code-$(date -u +%Y%m%dT%H%M%SZ).tar.gz" .
# Preserve server secrets, runtime data and certificate validation files.
# --delete removes stale application code only; excluded paths are protected.
rsync -a --delete --exclude='/.env' --exclude='/storage/' --exclude='/public/storage' --exclude='/public/.well-known/' "$upload_dir/bron/" "$app_dir/"
find "$app_dir" -path "$app_dir/storage" -prune -o -type d -exec chmod 755 {} +
find "$app_dir" -path "$app_dir/storage" -prune -o -type f ! -name '.env' -exec chmod 644 {} +
chmod 600 "$app_dir/.env"
"$php_bin" artisan config:clear
"$php_bin" artisan migrate --force
"$php_bin" artisan config:cache
"$php_bin" artisan route:cache
"$php_bin" artisan view:cache
"$php_bin" artisan up
trap - ERR
rm -rf -- "$upload_dir"
echo 'Deployment completed. Database backups are managed separately in cPanel.'
