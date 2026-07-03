# Nav & Footer — Tailwind UI blocks (luglio 2026)

## Navbar (`site_nav_*`)

Otto varianti ispirate a [Tailwind UI Navbars](https://tailwindcss.com/plus/ui-blocks/application-ui/navigation/navbars):

| Block ID | Variante |
|----------|----------|
| `site_nav_simple` | Simple |
| `site_nav_simple_dark` | Simple dark |
| `site_nav_with_search` | With search |
| `site_nav_with_search_dark` | With search (dark) |
| `site_nav_with_action` | Quick action |
| `site_nav_with_action_dark` | Quick action (dark) |
| `site_nav_centered_links` | Centered secondary links |
| `site_nav_menu_left` | Menu button on left |

- Menu **main** e **header_extra** da Admin → Menu (plugin voodpress).
- Supporto menu **multilivello** (fino a 3 livelli) con flyout desktop e accordion mobile.
- Logo e menu ravvicinati (`gap-3 md:gap-4`); override per pagina via trait editor (`main_nav_align`, `sticky_nav`, ecc.).
- Legacy: `site_header` → `site_nav_simple` (`GrapesJsLegacySiteBlockMap`).

## Footer (`site_footer_*`)

Otto varianti ispirate a [Tailwind UI Footers](https://tailwindcss.com/plus/ui-blocks/marketing/sections/footers):

| Block ID | Layout |
|----------|--------|
| `site_footer_columns_mission` | 4-column with company mission |
| `site_footer_columns_brand_end` | 4-column, brand at end |
| `site_footer_columns_simple` | 4-column simple |
| `site_footer_columns_cta` | 4-column with CTA |
| `site_footer_columns_newsletter` | 4-column with newsletter |
| `site_footer_columns_newsletter_below` | Newsletter below columns |
| `site_footer_centered` | Simple centered |
| `site_footer_social` | Simple with social links |

- Colonne collegate a placement `footer_col_1` … `footer_col_4`.
- Menu multilivello nelle colonne via `footer-menu-item` ricorsivo.
- Legacy: `site_footer` → `site_footer_columns_simple`, `site_footer_a`…`e` mappati alle nuove varianti.

## File principali

- `src/Support/GrapesJs/SiteNavBlocks.php`, `SiteFooterBlocks.php`
- `resources/views/components/nav.blade.php`
- `resources/views/grapesjs/blocks/site-nav.blade.php`
- `resources/views/grapesjs/blocks/footers/*`
- `resources/views/components/menu-nav-item.blade.php`, `menu-nav-dropdown-item.blade.php`, `footer-menu-item.blade.php`
