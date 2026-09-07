#!/usr/bin/env python3
import json
import re
from pathlib import Path

path = Path('packages/voodflow/voodbuilder/resources/fonts/core-catalog.json')
data = json.loads(path.read_text())
changed = 0

for entry in data:
    stack = entry.get('stack', '')
    match = re.match(r"^('[^']+'|\"[^\"]+\"|[^,]+)\s*(,.*)?$", stack.strip())

    if not match:
        continue

    primary = match.group(1).strip()
    rest = match.group(2) or ''

    if primary.startswith("'") or primary.startswith('"'):
        inner = primary.strip('"\'')
        new_primary = f"'{inner}'"
    else:
        new_primary = f"'{primary}'"

    new_stack = new_primary + rest

    if new_stack != stack:
        entry['stack'] = new_stack
        changed += 1
        print(f"{entry['id']} => {new_stack}")

path.write_text(json.dumps(data, indent=2, ensure_ascii=False) + '\n')
print(f'changed {changed}')
