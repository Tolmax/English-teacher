"""Sync only this update's files; preserve unrelated local edits and runtime data."""
import json
from pathlib import Path
import shutil
import tarfile
import tempfile

site = Path(__file__).resolve().parents[1] / 'website'
project = Path('/Users/tolmax/Documents/Projects/English Teacher')
backup = Path(tempfile.mkdtemp(prefix='english-site-local-backup-', dir='/private/tmp'))
with tarfile.open('/private/tmp/english-yandex-update.tar.gz') as archive:
    manifest = json.load(archive.extractfile('manifest.json'))
paths = [item['path'] for item in manifest] + ['.env.example', '.docs/project-status.md', 'YANDEX-WEBSITE.md']
for name in paths:
    destination = project / name
    source = site / name
    if destination.exists():
        saved = backup / name
        saved.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(destination, saved)
    if source.is_file():
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(source, destination)
    elif name == 'app/services/gigachat-service.php' and destination.is_file():
        destination.unlink()
print(f'Synced {len(paths)} files. Backup: {backup}')
