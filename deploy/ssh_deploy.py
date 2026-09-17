"""Upload a tested package and update an existing cPanel installation over verified SSH."""
import os
from pathlib import Path
import re
import shlex
import subprocess
import tempfile
import uuid
from urllib.parse import urlparse


def required(name):
    value = os.environ.get(name, '').strip()
    if not value:
        raise SystemExit(f'Missing GitHub production setting: {name}')
    return value


host = required('CPANEL_HOST')
user = required('CPANEL_USER')
port = os.environ.get('CPANEL_PORT', '').strip() or '22'
app = required('CPANEL_APP_DIR')
php = required('CPANEL_PHP')
url = required('BRON_URL').rstrip('/')
assert re.fullmatch(r'[A-Za-z0-9][A-Za-z0-9.-]*', host), 'Invalid host'
assert re.fullmatch(r'[A-Za-z0-9_-]+', user), 'Invalid user'
assert port.isdigit() and 1 <= int(port) <= 65535, 'Invalid port'
assert app == f'/home/{user}/bron', 'This workflow expects /home/CPANEL_USER/bron'
assert re.fullmatch(r'/[A-Za-z0-9_./-]+', php), 'Invalid PHP executable path'
parsed = urlparse(url)
assert parsed.scheme == 'https' and parsed.hostname and not parsed.username and not parsed.query and not parsed.fragment, 'Use an HTTPS site URL'
root = Path(__file__).resolve().parents[1]
artifacts = root / 'artifact'
archives = list(artifacts.rglob('bron-cpanel.tar.gz'))
checksums = list(artifacts.rglob('SHA256SUMS'))
assert len(archives) == len(checksums) == 1, 'Expected one tested deployment package'
upload = f'/home/{user}/.bron-deploy-{uuid.uuid4().hex}'
with tempfile.TemporaryDirectory() as temp:
    key = Path(temp) / 'key'
    known = Path(temp) / 'known_hosts'
    key.write_text(required('CPANEL_SSH_KEY') + '\n')
    key.chmod(0o600)
    known.write_text(required('CPANEL_KNOWN_HOSTS') + '\n')
    options = ['-i', str(key), '-o', 'BatchMode=yes', '-o', 'IdentitiesOnly=yes',
               '-o', 'StrictHostKeyChecking=yes', '-o', f'UserKnownHostsFile={known}', '-o', 'ConnectTimeout=30']
    ssh = ['ssh', *options, '-p', port, f'{user}@{host}']
    subprocess.run([*ssh, shlex.join(['mkdir', '-m', '700', upload])], check=True)
    subprocess.run(['scp', *options, '-P', port, str(archives[0]), str(checksums[0]), f'{user}@{host}:{upload}/'], check=True)
    subprocess.run([*ssh, shlex.join(['bash', '-s', '--', app, php, upload])],
                   input=(root / 'deploy/server-update.sh').read_bytes(), check=True)
subprocess.run(['curl', '--fail', '--silent', '--show-error', '--retry', '3', '--max-time', '30', '--output', '/dev/null', url + '/up'], check=True)
print('HTTPS health check passed. Verify a public assessment and admin login after deployment.')
