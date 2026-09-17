"""Verify that unavailable host tools produce clear errors before any public copy."""
from pathlib import Path
import subprocess
import tempfile

root = Path(__file__).resolve().parents[1]
source = (root / 'deploy/cpanel-pull.sh').read_text()
for missing in ('tar', 'mktemp'):
    with tempfile.TemporaryDirectory(prefix='bron-preflight-') as temporary:
        temp = Path(temporary)
        (temp / 'deploy').mkdir()
        account = temp / 'account'
        (account / 'bron').mkdir(parents=True)
        (account / 'bron/.env').write_text('APP_ENV=testing\n')
        (account / '.bron-php-path').write_text('/usr/bin/true\n')
        (temp / 'bron-cpanel.tar.gz').touch()
        script = temp / 'deploy/cpanel-pull.sh'
        script.write_text(source.replace('account_dir=/home2/shahjaha', f'account_dir={account}'))
        # Simulate the host's command lookup without modifying any live directories.
        wrapper = 'command() { if [[ "$1" == "-v" && "$2" == "__MISSING__" ]]; then return 1; fi; builtin command "$@"; }; source "$2"'
        wrapper = wrapper.replace('__MISSING__', missing)
        result = subprocess.run(['/bin/bash', '-c', wrapper, 'test', missing, str(script)], capture_output=True, text=True)
        assert result.returncode == 1, result
        assert f'required hosting command "{missing}" is unavailable' in result.stderr, result.stderr
        assert 'portable PHP copy v3' in result.stderr
        assert not (account / 'public_html').exists()
print('Missing required archive tools report actionable errors before copying files.')
