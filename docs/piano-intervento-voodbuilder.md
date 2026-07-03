# Piano di intervento VoodBuilder

> **Origine:** brainstorm in [`IDEE.md`](./IDEE.md) (ispirazione Bricks Builder).  
> **Scopo:** prioritizzare cosa implementare, in che ordine e con quale approccio nel nostro stack (Laravel 13, Filament 5, GrapesJS, Tailwind v4).  
> **Stato:** documento di pianificazione — da rivedere in fase di pulizia docs.

---

## Legenda

| Simbolo | Efforto indicativo | Note |
|--------|-------------------|------|
| 🟢 S | 3–10 giorni | estensione di codice già presente |
| 🟡 M | 2–6 settimane | feature end-to-end con UI + persistenza |
| 🔴 L | 6+ settimane | modulo nuovo o integrazione profonda |

**Priorità:** 1 = più urgente / più valore immediato per chi usa l’editor oggi.

---

## Già presente (base su cui costruire)

| Area | Stato attuale | File / doc |
|------|---------------|------------|
| Libreria componenti | Import codice, catalogo, export JSON slim | `components-ui.js`, `GrapesJsComponentExportNormalizer` |
| Dynamic data | `data-voodbuilder-bind`, pannello Dynamic, preview | [`BINDINGS.md`](./BINDINGS.md) |
| Condizioni | Visibilità elementi per contesto | `conditions-ui.js` |
| Classi globali | CRUD classi riutilizzabili | `global-classes-ui.js` |
| Repeat / query loop | `data-voodbuilder-repeat` su binding | `GrapesJsRepeatRenderer` |
| Menu sito | Backend Filament, 2 livelli, placement `main` / `header_extra` / `footer` | README § Navigation |
| Revisioni pagina | Snapshot `builder_payload` | `GrapesJsPageRevisionsController` |
| Performance CSS | Compile in editor, zero JIT in pubblico | [`analisi-homepage-performance-2026-07-02.md`](./analisi-homepage-performance-2026-07-02.md) |
| Toolbar canvas | Lucide, parent select, clone, dynamic | `canvas-component-toolbar.js` |

---

## Fase 1 — Quick win (🟢 S, priorità 1–2)

Implementare per primi: basso rischio, valore visibile subito nell’editor.

### 1.1 Menu contestuale sui layer / “Salva come componente” — 🟢 S · P1 ✅

**Stato:** implementato (luglio 2026) — menu contestuale su canvas e layer tree con salva nel catalogo, export, duplica, seleziona parent.

**Cosa:** click destro (o menu `⋯`) sul layer: *Duplica*, *Salva nel catalogo*, *Esporta*, *Elimina* — allineato al flusso componenti già usato in sidebar.

**Perché ora:** il catalogo componenti esiste; manca solo il ponte dal canvas/layer senza passare dalla libreria.

**Approccio:**
- Estendere `canvas-context-menu.js` e/o menu layer GrapesJS.
- Riutilizzare API `GrapesJsComponentsController` (stesso payload di export normalizer).
- Opzione “salva selezione” vs “salva ramo intero”.

**Dipendenze:** nessuna nuova tabella.

**Riferimento Bricks:** [Components academy](https://academy.bricksbuilder.io/builder/features/components/).

---

### 1.2 Miglioramenti Dynamic Data — 🟢 S · P1 ✅

**Stato:** implementato (luglio 2026) — ricerca campi, etichette tipo dato, anteprima valore nel pannello Dynamic.

**Cosa:** UX del pannello Dynamic più vicina a un “dynamic tag picker”: anteprima live più chiara, ricerca campi, hint tipo dato, binding su attributi (`title`, `aria-label`), lista campi nested.

**Perché ora:** il motore c’è; il gap è scopribilità e casi d’uso frequenti (meta, alt immagini, link secondari).

**Approccio:**
- Iterare su `bindings-ui.js` + catalogo sorgenti plugin.
- Documentare pattern in `BINDINGS.md`.
- Eventuale `data-voodbuilder-bind-attr` per attributi non testo.

**Riferimento Bricks:** [Dynamic data](https://bricksbuilder.io/dynamic-data/).

---

### 1.3 Suggeritore classi Tailwind nel pannello Classes — 🟢 S · P2 ✅

**Stato:** implementato (luglio 2026) — autocomplete utility comuni + hint classi non ancora in pagina.

**Cosa:** autocomplete classi Tailwind mentre si digita, **incluso** classi non ancora presenti nel CSS compilato della pagina (con badge “verrà compilata al save”).

**Perché ora:** Style Manager e global classes esistono; gli autori usano utility arbitrarie su componenti importati.

**Approccio:**
- Lista utility da preset Tailwind v4 del progetto (JSON generato in build).
- Integrazione in `global-classes-ui.js` / trait `classes` del componente.
- Al save: `GrapesJsComponentTailwindCompiler` già ricompila — collegare feedback in editor.

**Rischio:** classi inventate senza significato — mitigare con whitelist + `arbitrary` opzionale.

---

### 1.4 Blocchi header / nav allineati al tema — 🟢 S–🟡 M · P2 ✅

**Stato:** implementato (luglio 2026) — blocco `site_header` allineato al nav del tema (logo, menu main/extra, docs, search, account, notifiche, mobile drawer).

**Cosa:** blocchi GrapesJS per header come nel tema base: logo, slot menu `main` + `header_extra`, icona search, login, notifiche (placeholder o binding).

**Perché ora:** i menu sono in DB ma l’header in pagina è ancora spesso manuale o hardcoded nel layout.

**Approccio:**
- Blocchi dynamic `voodbuilder-header-*` con render server-side che legge menu + `SiteSettings`.
- Documentare placement in README.
- Fase 1: markup + classi tema; fase 2: varianti (sticky, trasparente).

**Dipendenze:** renderer menu esistente nel tema host.

---

### 1.5 Elementi mancanti nel Block Manager — 🟢 S · P2 ✅ (batch 1)

**Stato:** implementato (luglio 2026) — batch 1 in categoria Basic/Media: Divider, Icon box, Styled list, Embed.

**Cosa:** audit rispetto a [Bricks elements](https://bricksbuilder.io/elements/) — aggiungere solo ciò che manca e ha senso in GrapesJS (divider, icon box, list styled, embed, map, countdown, pricing table già parzialmente da sezioni).

**Approccio:**
- Tabella gap in issue tracker.
- Preferire blocchi da `section-source-blocks.json` + dynamic blocks Filament.
- Niente copia 1:1 UI Bricks.

---

## Fase 2 — Medio termine (🟡 M, priorità 3–5)

### 2.1 Template pagina: salva / importa — 🟡 M · P3

**Cosa:** salvare una pagina intera come template e inserirla in un nuovo documento (come componenti ma scope pagina: html + css + js + conditions).

**Approccio:**
- Modello `voodbuilder_page_templates` o riuso `voodbuilder_components` con `type=page`.
- Export/import JSON simmetrico a `GrapesJsComponentBundle`.
- UI: azione in topbar editor + wizard “Nuova pagina da template”.

**Riferimento Bricks:** [Builder templates](https://bricksbuilder.io/builder/).

---

### 2.2 Screenshot anteprima template al save — 🟡 M · P4

**Cosa:** generare thumbnail full-page quando si salva template o componente catalogo.

**Approccio:**
- Job queue + Browsershot/Puppeteer su URL preview autenticata (`?preview=1`).
- Storage su disco/S3; campo `preview_url` su component/template.
- Fallback: anteprima SVG wireframe (già usata in `buildComponentBlockMedia`).

**Nota:** costo infra; opzionale per tenant self-hosted.

---

### 2.3 Potenziamento menu builder (admin) — 🟡 M · P3 (fase A ✅)

**Stato:** fase A implementata (luglio 2026) — anteprima iframe header/footer nel form Filament modifica menu.

**Cosa:** esperienza admin più visuale: anteprima header live, drag migliorato, assegnazione icone voce, badge, visibilità per ruolo.

**Stato:** menu oggi solo backend Filament (`README` § Navigation).

**Approccio:**
- Fase A: preview iframe header nel form Filament.
- Fase B: collegamento voci a pagine GrapesJS / rotte app.
- Non duplicare un builder canvas per i menu nella v1.

**Riferimento Bricks:** [Menu builder](https://bricksbuilder.io/menu-builder/).

---

### 2.4 Query loop / model integrations — espansioni — 🟡 M · P3

**Cosa:** estendere repeat binding: filtri UI, ordinamento, paginazione, template item, empty state.

**Stato:** base con `data-voodbuilder-repeat` e sorgenti plugin.

**Approccio:**
- Trait GrapesJS “Query loop” sul container.
- Allineamento API a `BindingSource` esistenti.
- Doc espansioni in `BINDINGS.md`.

**Riferimento Bricks:** [Query loop](https://bricksbuilder.io/query-loop-builder/).

---

### 2.5 Form auth funzionanti (login / register / reset) — 🟡 M · P4

**Cosa:** blocchi form collegati a route Laravel Fortify/Breeze già nel progetto host.

**Approccio:**
- Blocchi `voodbuilder-form-login` con action/hidden CSRF da config.
- Validazione e errori via partial Blade o Livewire nel tema.
- Non reinventare auth nel package.

---

### 2.6 Custom icon sets — 🟡 M · P4

**Cosa:** upload set SVG (MIT/ISC), selezione icona in trait “Icon”.

**Approccio:**
- Storage + registry simile a global classes.
- Picker in Style Manager / trait.
- Default: Lucide (già inlined in `editor-icons.js`).

---

### 2.7 Font manager (solo font liberi, no Google Fonts) — 🟡 M · P5

**Cosa:** catalogo font self-hosted (Inter, Instrument Sans, JetBrains Mono già in Vite), attivazione per sub-tema.

**Approccio:**
- Tabella font + file in `storage/app/fonts`.
- `@font-face` generato al publish.
- Escludere CDN Google per privacy/GDPR.

**Vincolo:** solo licenze OSS commercial-friendly documentate.

---

## Fase 3 — Strategico (🔴 L, priorità 6+)

### 3.1 Popup builder — 🔴 L · P6

**Cosa:** modali/popup con trigger (load, exit intent, click, tempo), frequenza, audience (logged, ruolo, pagina).

**Approccio:**
- Nuovo modello `voodbuilder_popups` + payload GrapesJS.
- Script leggero frontend per trigger e cookie frequency cap.
- Integrazione conditions esistenti.

**Riferimento Bricks:** [Popup builder](https://bricksbuilder.io/popup-builder/).

---

### 3.2 Interactions / animazioni — 🔴 L · P6

**Cosa:** tab Animazioni nello Style Manager integrata con [tailwindcss-animated](https://www.tailwindcss-animated.com/) — **non** clonare il configuratore esterno.

**Approccio:**
- Preset animazione → classi utility generate.
- Preview solo in canvas editor.
- Compile classi nuove al save (stesso pipeline Tailwind componenti).

**Complessità:** conflitto con `prefers-reduced-motion`, performance, SSR.

---

### 3.3 Analisi design system & performance SEO — 🔴 L · P7

**Cosa:** documenti di parity checklist vs Bricks su design tokens, semantic HTML, Core Web Vitals.

**Deliverable:** estensione di `analisi-homepage-performance-2026-07-02.md` + audit elementi mancanti.

**Riferimenti:** [Design](https://bricksbuilder.io/design/), [Performance & SEO](https://bricksbuilder.io/performance-seo/), [Academy](https://academy.bricksbuilder.io/).

---

## Ordine di esecuzione consigliato

```text
Sprint A (2–3 settimane)
  → 1.1 Layer menu + salva componente
  → 1.2 Dynamic UX
  → 1.3 Tailwind class suggester (MVP)

Sprint B (3–4 settimane)
  → 1.4 Header/nav blocks
  → 1.5 Elementi mancanti (batch 1)
  → 2.3 Menu admin preview (fase A)

Sprint C (4–6 settimane)
  → 2.1 Template pagina
  → 2.4 Query loop espansioni
  → 2.2 Screenshot (se infra ok)

Backlog prodotto
  → 2.5 Auth forms · 2.6 Icon sets · 2.7 Font manager
  → 3.x Popup · Animazioni · Audit design/SEO
```

---

## Criteri di “fatto” per ogni voce

1. Documentazione minima in `docs/` o README.
2. Test PHPUnit e/o smoke test editor dove tocca persistenza.
3. i18n `en` + `it` per stringhe UI package.
4. Nessuna regressione su publish CSS (no compile runtime in pubblico).
5. Feature flag o config `voodbuilder.php` se impatta host multi-tenant.

---

## Documenti correlati (da tenere o consolidare in pulizia)

| File | Ruolo |
|------|--------|
| [`IDEE.md`](./IDEE.md) | Brainstorm grezzo |
| [`TEMP-performance-marketing.md`](./TEMP-performance-marketing.md) | Bozza marketing performance |
| [`recap-2026-07-01.md`](./recap-2026-07-01.md) | Storico sessioni |
| [`analisi-homepage-performance-2026-07-02.md`](./analisi-homepage-performance-2026-07-02.md) | Analisi tecnica TTFB/CSS |

**Pulizia futura:** unire TEMP/recap in archivio o wiki interno; mantenere questo piano come roadmap viva fino a ticketing su GitHub.

---

*Ultimo aggiornamento: 3 luglio 2026*
