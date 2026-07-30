# Schema baseline VoodBuilder

Data: 2026-07-30

## Contesto

In fase di sviluppo pre-produzione, le migration incrementali del package sono state consolidate in una baseline unica. Installazioni fresche / `migrate:fresh` usano solo questo schema.

## Migration

File unico:

`database/migrations/2026_07_30_100000_create_voodbuilder_schema.php`

Crea:

- `users.avatar` (colonna opzionale sulla tabella host `users`)
- `voodbuilder_chrome_layouts`
- `voodbuilder_menus` / `voodbuilder_menu_items`
- `voodbuilder_settings`
- `voodbuilder_model_integrations`
- `voodbuilder_page_templates`
- `site_pages`
- `voodbuilder_site_page_revisions`

## Ambienti già migrati

Dopo il consolidamento, rieseguire `migrate:fresh` (o equivalente) così non restano record orfani in `migrations` per i file rimossi.

## Note ricerca IDE

`vendor/` e `.phpunit.result.cache` sono in `.gitignore`. Se una ricerca workspace mostra ancora vecchi nomi di package, escludere `vendor` / cache oppure ripulire la cartella `vendor` del package e rieseguire `composer install` nei test.
