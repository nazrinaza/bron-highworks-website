"""Verify that unavailable host tools produce clear errors before any public copy."""
from pathlib import Path
import os
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
        (temp / 'bron-cpanel.tar.gz').touch()
        script = temp / 'deploy/cpanel-pull.sh'
        script.write_text(source)
        # Simulate the host's command lookup without modifying any live directories.
        wrapper = 'command() { if [[ "$1" == "-v" && "$2" == "__MISSING__" ]]; then return 1; fi; builtin command "$@"; }; source "$2"'
        wrapper = wrapper.replace('__MISSING__', missing)
        environment = os.environ | {'HOME': str(account)}
        result = subprocess.run(['/bin/bash', '-c', wrapper, 'test', missing, str(script)], env=environment, capture_output=True, text=True)
        assert result.returncode == 1, result
        assert f'required hosting command "{missing}" is unavailable' in result.stderr, result.stderr
        assert 'portable cPanel deployment v4' in result.stderr
        assert not (account / 'public_html').exists()
print('Missing required archive tools report actionable errors before copying files.')
