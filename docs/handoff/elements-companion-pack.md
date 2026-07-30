# Guida: companion **Elements** (`voodflow/voodbuilder-elements`)

Documento per la prossima sessione Cursor / sviluppatore. Obiettivo: estrarre dalla libreria Editor i blocchi “premium” in un package companion, senza rompere Core Community né l’iniezione di blocchi terzi.

**Stato oggi (2026-07-30):** il codice HTML/JS dei blocchi companion è **ancora in Core**, ma la **sidebar Community** è già limitata da `EditorCommunityBlockCatalog` + capability `blocks.official.complete`. Questo doc spiega *come* fare il package fisico.

Liste ID machine-readable: [`elements-companion-manifest.json`](./elements-companion-manifest.json).  
Allowlist PHP: `src/Support/Editor/EditorCommunityBlockCatalog.php`.

---

## 1. Elements ≠ Components (non confondere)

| | **Elements** (questo companion) | **Components** (`voodbuilder-components`) |
|--|----------------------------------|------------------------------------------|
| Cosa sono | Blocchi **pronti** della libreria (Hero, Gallery, Tabs, Form…) | Pezzi **salvati dall’utente** (o team): HTML/CSS riusabili, classi globali |
| Sidebar | Categoria Elements / sezioni del BlockManager | Tab **Components** (library utente) |
| Origine | Catalogo prodotto VoodBuilder | Autore nel progetto host |
| Package | `voodflow/voodbuilder-elements` (**da creare**) | `voodflow/voodbuilder-components` (**già esiste**) |

I Components restano un companion a sé (library + API + soft-gate tab).  
**Non** fondere Elements in Components: gli Elements sono il catalogo commerciale; i Components sono “elementi creati dall’utente”. Se un giorno la UI convergerà, sarà solo presentazione — i confini di package restano distinti.

---

## 2. Cosa fa Core già (non rifare)

1. Community **senza** `blocks.official.complete` → sidebar solo foundation + ~2 sezioni per categoria free.
2. Pro/Agency **con** `blocks.official.complete` → vede tutto ciò che Core registra (anche ID companion ancora in Core).
3. ID **non** in `coreOwnedIds()` (plugin terzi / futuro Elements package dopo extract) → restano visibili anche in Community (iniezione sicura).
4. `chrome_content_slot` → solo chrome layout editor (`blocks?chrome=1`), non nella categoria Site delle pagine.

Gate JS: `EditorGate.blockAllowlist` → `resources/js/editor/block-allowlist.js`.

---

## 3. Split prodotto (riassunto)

### Core Community (resta forever)

- Layout, Basic, Media, Single → **completi**
- Site → completo **senza** Page content area
- Hero, Content, Features, Articles, Animated, Testimonials, Team, Steps, CTA → **2 essenziali** ciascuno (`COMMUNITY_SECTION_BLOCK_IDS`)

### Companion Elements (o unlock Pro via capability)

Categorie intere: Gallery, Stats, Pricing, Contact, Shop, Header, Footer (catalog sezioni), Code, Forms.  
Tabs: solo extras (Underline, Segmented) — **Pills** resta in Community.  
Più tutte le varianti extra delle categorie free → `COMPANION_BLOCK_IDS` / manifest JSON.

---

## 4. Strategia di implementazione consigliata

Due fasi. Non saltare alla 2 se non serve subito.

### Fase A — Soft unlock (veloce, zero move)

Il companion (o solo la license Pro) **non sposta file**: attiva `blocks.official.complete` (già in `EditionCapabilityMatrix::professional()`).  
Package Elements può essere uno stub Filament che documenta “requires Pro” oppure grant locale per demo.

Utile finché non vuoi alleggerire il bundle Core.

### Fase B — Extract fisico (obiettivo package)

1. Creare repo `voodflow/voodbuilder-elements`.
2. Spostare HTML/JS degli ID in `COMPANION_BLOCK_IDS` fuori da Core.
3. Core registra **solo** foundation + community sections.
4. Companion, se plugin attivo, richiama `Voodbuilder::editorBlock(...)` (+ JS BlockManager per Tabs/Forms/Animated extras).
5. Community senza companion → sidebar magra; pagine già pubblicate con blocchi companion **continuano a renderizzare** (HTML in DB), solo non riappare il tile in libreria.

Preferenza prodotto: **niente banner upsell** in Core se il companion manca (come Templates: funzioni assenti, non tease). Opzionale soft-gate solo nel companion marketing site.

---

## 5. Come creare il package (checklist operativa)

### 5.1 Repo e Composer

```
packages/voodflow/voodbuilder-elements/
  composer.json
  src/VoodbuilderElementsServiceProvider.php
  src/Filament/VoodbuilderElementsPlugin.php
  src/ElementsCatalog.php
  src/Support/...          # HTML spostato / builder da JSON
  resources/js/editor/plugin.js
  resources/js/editor/...  # moduli JS spostati da Core
  tests/
  README.md
```

`composer.json` (sketch):

```json
{
  "name": "voodflow/voodbuilder-elements",
  "type": "library",
  "require": {
    "php": "^8.4",
    "voodflow/voodbuilder": "*"
  },
  "autoload": {
    "psr-4": {
      "Voodflow\\VoodbuilderElements\\": "src/"
    }
  },
  "extra": {
    "laravel": {
      "providers": ["Voodflow\\VoodbuilderElements\\VoodbuilderElementsServiceProvider"]
    }
  }
}
```

Host `composer.json` path repository + require; Filament panel:

```php
->plugins([
    VoodbuilderPlugin::make(),
    VoodbuilderElementsPlugin::make(),
    // Components resta separato:
    // VoodbuilderComponentsPlugin::make(),
])
```

### 5.2 Filament plugin

Pattern come Templates/Components:

- `VoodbuilderElementsPlugin::make()` → `activate()` / `boot()`
- Classe facade `VoodbuilderElements::isEnabled()` / `registerCatalog()`
- Se il plugin non è nel panel → non registrare blocchi companion (Core resta Community)

### 5.3 Registrazione PHP (dopo extract)

```php
use Voodflow\Voodbuilder\Voodbuilder;

foreach (ElementsCatalog::definitions() as $block) {
    Voodbuilder::editorBlock(
        id: $block['id'],
        label: $block['label'],
        category: $block['category'],
        content: $block['content'],
        attributes: ['title' => $block['label']],
    );
}
```

Fonte definizioni:

1. Filtra `resources/editor/section-blocks.json` (Core) per ID in `COMPANION_BLOCK_IDS`.
2. Porta i metodi HTML da:
   - `VoodbuilderLanding01/02/03Sections.php`
   - `VoodbuilderMediaSections.php` (bg-video, sliders)
3. Lascia in Core solo i metodi/ID community (+ foundation JS).

### 5.4 Registrazione JS

Estendere il glob in Core `resources/js/editor/plugin-bridge.js` con `voodbuilder-elements` (come popups/components/templates).

`resources/js/editor/plugin.js` del companion:

```js
export default {
    id: 'voodbuilder-elements',
    mount(editor) {
        // Dopo extract: registerTabsExtras / registerFormsBlocks / registerAnimatedExtras
        // importati dai moduli spostati fuori da Core.
    },
};
```

Finché il JS resta in Core, `block-allowlist.js` nasconde i tile; il companion non deve ridoppiare i tipi GrapesJS se Core li registra ancora per il canvas (solo BlockManager.add è filtrato).

### 5.5 Cosa togliere da Core in Fase B

| File Core | Azione |
|-----------|--------|
| `VoodbuilderServiceProvider::registerEditorBlocks` | Non chiamare landing/media full; o filtrare ID companion |
| `VoodbuilderSectionEditorBlocks` | Caricare solo ID community dal JSON (o JSON split) |
| `editor-animated-blocks.js` / logo-cloud / tabs / forms / code | Companion registra i blocchi non-community; Core tiene tipi canvas necessari al runtime se una pagina li usa già |
| `EditorCommunityBlockCatalog::COMPANION_BLOCK_IDS` | Dopo extract, quegli ID **non** sono più in `coreOwnedIds()` → se qualcuno li registra da companion, passano il filtro Community solo con plugin |

Attenzione runtime: componenti GrapesJS (`addType`) per counter/tabs/form devono restare caricabili se una pagina Community ha già quel markup (oppure il companion è obbligatorio per editare quelle pagine). Preferenza: **tipi canvas in Core** (leggeri) + **solo BlockManager tile nel companion**.

### 5.6 Test minimi

- Community: sidebar non contiene `vb-gallery-1`, contiene `vb-hero-2` e `voodbuilder-heading`.
- Community + `editorBlock('acme-x')`: tile presente.
- Community: `chrome_content_slot` assente su page editor, presente con `?chrome=1`.
- Pro / Elements plugin: companion IDs presenti.
- Pagina con HTML `vb-gallery-*` già salvato: front OK senza plugin.

Test già presenti: `tests/Unit/EditorCommunityBlockCatalogTest.php`.

---

## 6. API da riusare (non inventarne di nuove)

| Bisogno | API |
|---------|-----|
| Blocco HTML sidebar | `Voodbuilder::editorBlock(...)` — `docs/SDK_PLUGIN_API.md` |
| Server block (nav/footer Blade) | `Voodbuilder::editorServerBlock($category, $class)` |
| JS mount | `window.VoodbuilderEditor.registerPlugin` / path `plugin.js` — `docs/EDITOR_JS_PLUGINS.md` |
| Capability | `Voodbuilder::can('blocks.official.complete')` |

---

## 7. Prompt Cursor (copia-incolla prossima sessione)

```
Leggi packages/voodflow/voodbuilder/docs/handoff/elements-companion-pack.md
e docs/handoff/elements-companion-manifest.json.

Obiettivo: creare voodflow/voodbuilder-elements (Fase B extract) oppure Fase A stub.

Vincoli:
- Non confondere con voodbuilder-components (library utente).
- Non rompere filtro Community / iniezione ID sconosciuti.
- Preferire tipi canvas in Core; companion registra solo BlockManager / editorBlock.
- Niente soft-gate teaser in Core se il plugin manca.
- Aggiorna plugin-bridge glob e commercial-plugin-wave.md.
```

---

## 8. Riferimenti rapidi

| Cosa | Dove |
|------|------|
| Allowlist / companion IDs | `src/Support/Editor/EditorCommunityBlockCatalog.php` |
| Matrix edition | `src/Licensing/EditionCapabilityMatrix.php` (`blocks.core`, `blocks.official.complete`) |
| Catalog sezioni | `resources/editor/section-blocks.json` |
| Wave companion esistenti | `docs/progress/commercial-plugin-wave.md` |
| Inventario umano | `docs/lista_elements_suggerimenti.md` |
