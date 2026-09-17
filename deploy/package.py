"""Build a clean cPanel archive from source and installed production dependencies."""
import hashlib
import os
from pathlib import Path
import shutil
import tarfile
import zipfile

root = Path(__file__).resolve().parents[1]
source = Path(os.environ.get('BRON_PACKAGE_SOURCE', root / 'backend')).resolve()
build = root / 'build'
stage = build / 'bron'
if stage.exists():
    shutil.rmtree(stage)
stage.mkdir(parents=True)
for directory in ('app', 'config', 'public', 'resources', 'routes', 'vendor'):
    shutil.copytree(source / directory, stage / directory)
for filename in ('artisan', 'composer.json', 'composer.lock', 'README.md'):
    shutil.copy2(source / filename, stage / filename)
shutil.copytree(source / 'database/migrations', stage / 'database/migrations')
(stage / 'bootstrap').mkdir(exist_ok=True)
for filename in ('app.php', 'providers.php'):
    shutil.copy2(source / 'bootstrap' / filename, stage / 'bootstrap' / filename)
for directory in ('bootstrap/cache', 'storage/app/private', 'storage/app/public',
                  'storage/framework/cache/data', 'storage/framework/sessions',
                  'storage/framework/views', 'storage/logs'):
    target = stage / directory
    target.mkdir(parents=True, exist_ok=True)
    (target / '.gitignore').write_text('*\n!.gitignore\n')
shutil.copy2(root / 'deploy/production.env.example', stage / '.env.example')
shutil.copy2(root / 'deploy/CPANEL.md', stage / 'CPANEL.md')
(stage / 'public/hot').unlink(missing_ok=True)
if (stage / 'public/storage').exists() or (stage / 'public/storage').is_symlink():
    raise SystemExit('Refusing to package local public/storage files.')
for path in stage.rglob('*'):
    if path.is_symlink():
        raise SystemExit(f'Unexpected symlink: {path.relative_to(stage)}')
    if path.name == '.env' or path.suffix in ('.sqlite', '.log'):
        raise SystemExit(f'Unexpected runtime file: {path.relative_to(stage)}')
assert (stage / 'vendor/autoload.php').is_file()
assert not (stage / 'vendor/phpunit').exists(), 'Install production dependencies with --no-dev first.'
with zipfile.ZipFile(build / 'bron-cpanel.zip', 'w', zipfile.ZIP_DEFLATED) as archive:
    for path in sorted(stage.rglob('*')):
        if path.is_file():
            archive.write(path, path.relative_to(build))
with tarfile.open(build / 'bron-cpanel.tar.gz', 'w:gz') as archive:
    archive.add(stage, arcname='bron')
with (build / 'SHA256SUMS').open('w') as sums:
    for filename in ('bron-cpanel.zip', 'bron-cpanel.tar.gz'):
        digest = hashlib.sha256((build / filename).read_bytes()).hexdigest()
        sums.write(f'{digest}  {filename}\n')
print('Built cPanel archives with production dependencies and empty runtime directories.')
