# Database map

Migrations are discovered and run from the package (`discoversMigrations` + `runsMigrations`). Publishing migrations is intentionally unsupported.

## Tables owned by VoodBuilder

| Table | Module (target) | Core keep? |
|---|---|---|
| `site_pages` | Pages | Yes |
| `voodbuilder_menus` | Menus | Yes |
| `voodbuilder_menu_items` | Menus | Yes |
| `voodbuilder_settings` | Settings | Yes |
| `voodbuilder_chrome_layouts` | Layouts | Yes |
| `voodbuilder_site_page_revisions` | History | Yes (Community) |
| `voodbuilder_global_classes` | Components / Editor | Yes for now |
| `voodbuilder_components` | Components | Agency boundary later |
| `voodbuilder_page_templates` | Templates | Yes (local templates Community) |
| `voodbuilder_model_integrations` | Dynamic Data | Soft-boundary later |
| `voodbuilder_popups` | Popups | Extractable package |
| `voodbuilder_popup_events` | Popups | Extractable package |

## Host table touches

| Migration | Effect |
|---|---|
| `2026_06_05_220000_add_avatar_to_users_table` | Adds `avatar` on `users` if missing |

## Notable columns (pages)

`site_pages` accumulates: slug, published flags, schedule, grapes HTML/CSS/JS, sub-theme, landing options, hide nav/header, locale/translations, `chrome_layout_id`, section fields, etc. **Do not rename** in 0.1.0.

## Orphan risk when Popups module disabled

Rows in `voodbuilder_popups` / `voodbuilder_popup_events` must remain; Core must not FK-delete them. Admin warning required (Phase 9).
