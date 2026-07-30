# Purge riferimenti vpress da VoodBuilder

Data: 2026-07-29

## Contesto

Il package legacy **vpress** non esiste più. VoodBuilder vive **solo** nel proprio repository (`voodflow/voodbuilder`) e non deve includere, citare o riusare identificatori `vpress*` / `Vpress*`.

## Cosa è stato fatto

### Rinomina API / identificatori editor

- Prop GrapesJS / Editor: `vpressConfig`, `vpressShow*`, `vpressStickyNav`, ecc. → prefisso `voodbuilder*`
- Funzioni JS: `initVpressEditor`, `encodeVpressConfig`, `parseVpressConfig`, `serializeVpressConfig`, `syncVpressDynamicAttributes`, `vpressEditorPlugin`, `vpressMountThemeMap` → equivalenti `*BlockConfig` / `initVoodbuilderEditor` / `voodbuilderEditorPlugin` / `voodbuilderMountThemeMap`
- CSS / classi legacy `vpress-gjs-*` → `voodbuilder-editor-*`
- Globali browser: `window.__vpressTheme` → solo `window.__voodbuilderTheme`
- Channel search / registry: `vpressSearch` → `voodbuilderSearch`
- Code block / footer: `vpressCode*`, `vpressSocialAlign` → `voodbuilderCode*`, `voodbuilderSocialAlign`

### Cleanup collaterale dello script di rename

Lo script bulk aveva lasciato duplicati o alias spezzati; sistemati:

- `theme-script.blade.php` / `site-scripts.blade.php` / `docs/BUILD.md` (doppio `__voodbuilderTheme`)
- `theme-map/index.jsx` (doppia assegnazione `voodbuilderMountThemeMap`)
- layout blade: rimosso fallback fantasma `$voodbuilderSubThemeLegacy`
- `voodbuilder-dynamic-config.js`: solo `encodeBlockConfig` / `parseBlockConfig` / `serializeBlockConfig` (niente alias deprecati)

### File rimossi o aggiornati

- **Eliminato** `scripts/rename-to-voodbuilder.py` (tool one-shot non più necessario)
- **ConfigureVtutsForVoodbuilder**: rimosse le stringhe di upgrade da `vpress::layouts.*` (resta solo upgrade da `vtuts::layouts.*`)
- Test correlato `test_it_upgrades_vpress_layouts_to_voodbuilder` rimosso
- README / `LAYOUT_CONTRACT.md` / `BUILD.md`: togliere menzioni “legacy vpress” / Vpress
- Rebuild `resources/dist/theme-map.js` dopo il rename del mount globale

### Schema DB (fase iniziale)

Le migration di create usano già i nomi `voodbuilder_*`. In fase iniziale non serve un percorso di upgrade da tabelle `vpress_*`, quindi è stata **eliminata** anche:

`database/migrations/2026_06_29_200000_rename_vpress_tables_to_voodbuilder.php`

Su ambienti di sviluppo già migrati: `migrate:fresh` (o equivalente) così lo schema nasce solo con i nomi `voodbuilder_*`.

Nessun file del package deve contenere `vpress` / `Vpress` (questo documento è l’unica eccezione narrativa del purge).

## Fuori scope

- Nessun commit/push sul repo vpress (abbandonato)
- Host app CosmoLab: eventuale cleanup di path/composer verso vpress va gestito a parte se ancora presente
