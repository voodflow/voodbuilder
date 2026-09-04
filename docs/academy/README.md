# VoodBuilder Academy (vdocs source)

English documentation for **VoodBuilder**, formatted for [vdocs](https://github.com/voodflow/vdocs) import (VitePress-style folders and frontmatter).

## Structure

| Section | Slug | Audience |
|---------|------|----------|
| Home | `index.md` | Landing hero |
| Getting Started | `getting-started/` | Install, first page, settings, translations |
| Builder | `builder/` | Pages, chrome, menus, editor, dynamic data |
| Developer | `developer/` | Block authoring, Tailwind, registration APIs |

Topic slug in vdocs: **`voodbuilder`**.

## Import into vdocs

1. Merge `vdocs-import.php` into `config/vdocs.php` (section + sidebar catalog).
2. Run from the Laravel app root:

```bash
php artisan vdocs:import-vitepress packages/voodflow/voodbuilder/docs/academy \
  --topic=voodbuilder \
  --create-topic \
  --locale=en \
  --force
```

Public URLs: `/docs/voodbuilder/`, `/docs/voodbuilder/getting-started/installation`, etc.

## Re-import after edits

Use `--force` to replace pages for the topic/locale only. Other topics and locales are preserved.

## Scope

- Documents **core** (`voodflow/voodbuilder`, MIT) vs **companions** (Dynamics, Components, Templates Pro, Popups).
- Does **not** document internal pipeline implementation details.
- **Theme Studio** page-creation workflow is intentionally deferred.

Source manuals in `../manual/` were used as input; this tree is the public academy copy.
