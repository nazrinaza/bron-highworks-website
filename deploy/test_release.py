"""Smoke-test the production archive and private first-admin provisioning."""
import base64
import json
import os
from pathlib import Path
import subprocess
import tempfile
import zipfile

root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='bron-release-test-') as temp:
    with zipfile.ZipFile(root / 'build/bron-cpanel.zip') as archive:
        assert 'bron/.env' not in archive.namelist()
        archive.extractall(temp)
    app = Path(temp) / 'bron'
    database = app / 'database/test.sqlite'
    database.touch()
    env = os.environ | {
        'APP_ENV': 'testing', 'APP_KEY': 'base64:' + base64.b64encode(os.urandom(32)).decode(),
        'DB_CONNECTION': 'sqlite', 'DB_DATABASE': str(database), 'CACHE_STORE': 'array',
        'SESSION_DRIVER': 'array', 'QUEUE_CONNECTION': 'sync',
    }
    for command in ('migrate --force', 'config:cache', 'route:cache', 'view:cache'):
        subprocess.run(['php', 'artisan', *command.split()], cwd=app, env=env, check=True, stdout=subprocess.DEVNULL)
    credential_file = Path(temp) / 'admin.json'
    payload = {'name': 'Test Admin', 'email': 'admin@example.test', 'password': 'Test-Only-Admin-Password-987'}
    command = ['php', str(root / 'deploy/first-admin.php'), str(app), str(credential_file)]
    credential_file.write_text('{"password": "never-log-this",}')
    result = subprocess.run(command, cwd=app, env=env, capture_output=True)
    assert result.returncode != 0 and b'invalid JSON' in result.stderr
    assert b'never-log-this' not in result.stdout + result.stderr
    credential_file.write_text(json.dumps({'email': 'invalid', 'password': 'short'}))
    result = subprocess.run(command, cwd=app, env=env, capture_output=True)
    assert all(message in result.stderr for message in (b'name must', b'email must', b'password must'))
    credential_file.write_text(json.dumps(payload | {'password': 'short'}))
    result = subprocess.run(command, cwd=app, env=env, capture_output=True)
    assert result.returncode != 0 and credential_file.exists(), 'Invalid password must be rejected'
    assert b'at least 12 characters' in result.stderr
    credential_file.write_text('\ufeff' + json.dumps(payload))
    result = subprocess.run(command, cwd=app, env=env, capture_output=True)
    assert result.returncode == 0 and not credential_file.exists(), result.stderr.decode()
    credential_file.write_text(json.dumps(payload | {'email': 'second@example.test'}))
    result = subprocess.run(command, cwd=app, env=env, capture_output=True)
    assert result.returncode != 0 and credential_file.exists(), 'Existing admins must prevent re-provisioning'
    assert b'An administrator already exists' in result.stderr
    assert payload['password'].encode() not in result.stdout + result.stderr
    check = '''require "vendor/autoload.php"; $app=require "bootstrap/app.php"; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); $user=App\\Models\\User::first(); exit(App\\Models\\User::count()===1 && $user->is_admin && Illuminate\\Support\\Facades\\Hash::check("Test-Only-Admin-Password-987", $user->password) ? 0 : 1);'''
    subprocess.run(['php', '-r', check], cwd=app, env=env, check=True)
print('Production archive caches/migrations and first-admin security checks passed.')
