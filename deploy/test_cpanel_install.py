"""Exercise first install and update in an isolated account using the production archive."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
with tempfile.TemporaryDirectory(prefix='bron-cpanel-install-') as temp:
    base = Path(temp)
    repo, account = base / 'repo', base / 'account'
    repo.mkdir()
    app = account / 'bron'
    app.mkdir(parents=True)
    shutil.copytree(root / 'deploy', repo / 'deploy')
    for name in ('bron-cpanel.tar.gz', 'SHA256SUMS'):
        shutil.copy2(root / 'build' / name, repo / name)
    script = repo / 'deploy/cpanel-pull.sh'
    script.write_text(script.read_text().replace('account_dir=/home2/shahjaha', f'account_dir={account}'))
    (account / '.bron-php-path').write_text(shutil.which('php') + '\n')
    (app / '.env').write_text('APP_ENV=testing\nAPP_KEY=\nAPP_DEBUG=false\n')
    database = account / 'test.sqlite'
    database.touch()
    env = os.environ | {'APP_ENV': 'testing', 'DB_CONNECTION': 'sqlite', 'DB_DATABASE': str(database),
                        'CACHE_STORE': 'array', 'SESSION_DRIVER': 'array', 'QUEUE_CONNECTION': 'sync'}
    env.pop('APP_KEY', None)
    web = account / 'public_html/bron.serinstech.com/public'
    (web / '.well-known').mkdir(parents=True)
    (web / '.well-known/token').write_text('ssl-token')
    for iteration in range(2):
        result = subprocess.run(['bash', str(script)], cwd=repo, env=env, capture_output=True, text=True)
        assert result.returncode == 0, result.stdout + result.stderr
        assert 'PHP file synchronization completed (public)' in result.stderr
        assert (web / 'index.php').exists()
        assert not (web / '.env').exists()
        assert (web / '.well-known/token').read_text() == 'ssl-token'
        assert not (account / '.bron-deploy-lock').exists()
        assert not list(account.glob('.bron-stage.*'))
        if iteration == 0:
            original_env = (app / '.env').read_text()
            assert 'APP_KEY=base64:' in original_env
            (app / 'storage/customer.txt').write_text('customer-data')
            (app / 'stale-code.php').write_text('outdated')
        else:
            assert (app / '.env').read_text() == original_env
            assert (app / 'storage/customer.txt').read_text() == 'customer-data'
            assert not (app / 'stale-code.php').exists()
print('Full cPanel install and update passed with preserved app key, customer data and certificate files.')
