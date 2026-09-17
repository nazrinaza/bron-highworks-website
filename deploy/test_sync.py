"""Deployment copy safety: preserve secrets/data, remove stale code, do not follow links."""
from pathlib import Path
import subprocess
import tempfile

script = Path(__file__).resolve().with_name('sync-files.php')
with tempfile.TemporaryDirectory(prefix='bron-copy-test-') as temp:
    base = Path(temp)
    source, destination, outside = (base / name for name in ('source', 'destination', 'outside'))
    for path in (source, destination, outside):
        path.mkdir()
    def write(root, name, value):
        file = root / name
        file.parent.mkdir(parents=True, exist_ok=True)
        file.write_text(value)
    def sync(mode, success=True):
        result = subprocess.run(['php', str(script), str(source), str(destination), mode], capture_output=True, text=True)
        assert (result.returncode == 0) == success, result.stderr
    write(source, 'app/new.php', 'new')
    write(source, '.env', 'do not deploy')
    write(source, 'storage/placeholder', 'placeholder')
    write(destination, '.env', 'server-secret')
    write(destination, 'storage/customer', 'customer-data')
    write(destination, 'old/code.php', 'stale')
    write(outside, 'keep', 'untouched')
    (destination / 'app').symlink_to(outside, target_is_directory=True)
    sync('app')
    assert (destination / '.env').read_text() == 'server-secret'
    assert (destination / 'storage/customer').read_text() == 'customer-data'
    assert not (destination / 'storage/placeholder').exists()
    assert not (destination / 'old').exists()
    assert not (destination / 'app').is_symlink()
    assert (destination / 'app/new.php').read_text() == 'new'
    assert (outside / 'keep').read_text() == 'untouched'
    write(destination, '.well-known/token', 'ssl-token')
    write(destination, '.htaccess', 'maintenance')
    sync('public')
    assert (destination / '.well-known/token').read_text() == 'ssl-token'
    assert (destination / '.htaccess').read_text() == 'maintenance'
    assert (destination / 'storage/customer').read_text() == 'customer-data'
    write(source, 'app/new.php', 'replacement')
    write(source, 'empty-runtime/.gitignore', '*')
    sync('storage')
    assert (destination / 'app/new.php').read_text() == 'new'
    assert (destination / 'empty-runtime/.gitignore').exists()
    (source / 'unsafe-link').symlink_to(outside, target_is_directory=True)
    write(destination, 'must-not-delete', 'safe')
    sync('app', success=False)
    assert (destination / 'must-not-delete').read_text() == 'safe'
print('PHP copy preserves secrets, runtime data and SSL files, deletes stale code, and handles symlinks safely.')
