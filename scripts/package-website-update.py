"""Build a code-only update against the downloaded live-site baseline."""
import hashlib
import io
import json
from pathlib import Path
import tarfile

root = Path(__file__).resolve().parents[1]
site = root / 'website'
baseline = tarfile.open('/private/tmp/english-site-before-yandex.tar.gz')
old = {m.name.removeprefix('website/'): baseline.extractfile(m).read()
       for m in baseline.getmembers() if m.isfile() and not any(part.startswith('._') for part in Path(m.name).parts)}
paths = set(old)
for folder in ('app', 'config', 'templates'):
    paths.update(str(p.relative_to(site)) for p in (site / folder).rglob('*') if p.is_file())
paths.add('assets/css/blocks/presentation-slides.css')
manifest = []
with tarfile.open('/private/tmp/english-yandex-update.tar.gz', 'w:gz') as package:
    for name in sorted(paths):
        target = site / name
        data = target.read_bytes() if target.is_file() else None
        previous = old.get(name)
        if data == previous:
            continue
        manifest.append({'path': name, 'before': hashlib.sha256(previous).hexdigest() if previous is not None else None,
                         'after': hashlib.sha256(data).hexdigest() if data is not None else None})
        if data is not None:
            info = tarfile.TarInfo('files/' + name)
            info.size = len(data)
            info.mode = 0o644
            package.addfile(info, io.BytesIO(data))
    data = json.dumps(manifest).encode()
    info = tarfile.TarInfo('manifest.json')
    info.size = len(data)
    package.addfile(info, io.BytesIO(data))
    package.add(root / 'scripts/deploy-website-update.php', arcname='deploy.php')
print('\n'.join(('REMOVE ' if item['after'] is None else 'UPDATE ') + item['path'] for item in manifest))
