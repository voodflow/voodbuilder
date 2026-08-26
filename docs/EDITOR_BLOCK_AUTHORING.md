# Linea guida: authoring blocchi Editor (VoodBuilder)

Documento per umani e AI. Obiettivo: elementi della libreria **facili da selezionare, droppare e configurare** in editor, e **identici in render pubblico**.

Contesto tipico del problema: hero a strati (media assoluto + shade + contenuto `z-10`). Se il markup non è pensato per Editor, l’immagine non si clicca, i pulsanti finiscono *sopra* la section e il menu contestuale “lampeggia”.

---

## 1. Principi non negoziabili

1. **Editor ≈ pubblico**
   - Layout degli **Elements di libreria** = **utility Tailwind nell’HTML** (compilate dal page JIT su canvas e su `#voodbuilder-page-css`). Classi BEM (`voodbuilder-slider__*`, …) solo come hook JS/selector — **non** come fogli CSS per-elemento in `theme.css`.
   - Cover/media che non sono esprimibili come utility (hero absolute fill, container queries) restano in `theme.css` / classi stabili (es. `.voodbuilder-hero-media__img`) e/o `style=""`.
   - Il normalizer `EditorLibraryLayoutNormalizer` unisce utility obbligatorie sugli hook strutturali noti (save + catalogo + render pubblico), così editor e frontend restano allineati senza CSS ad hoc.
2. **Una responsabilità per layer** — media, shade, contenuto editabile sono nodi distinti e nominati.
3. **Drop solo dove ha senso** — zone drop esplicite; layer decorativi non droppable.
4. **Content panel prima del click sul canvas** — media e CTA devono avere trait/opzioni quando selezioni la section o lo slot contenuto.
5. **Immagini statiche** — nel pannello Content (colonna destra): URL + Scegli/Rimuovi (Asset Manager), alt per img inline, opacità/fit/posizione per hero background. Niente controlli se l’immagine ha binding dinamico.
5. **Token tema** — `bg-vp-*`, `text-vp-*`, `border-vp-*` (niente `dark:` hardcoded). Eccezione: bande cinematiche sempre scure (`bg-zinc-950`) se intenzionali.
6. **Niente nav/footer** nei page template starter (chrome-shell già li fornisce).

---

## 2. Anatomia di una section “bene”

Contratto larghezza: **section sempre full** (bg/media) → **primo content wrapper** full|normal|custom. Dettagli: [CONTENT_WIDTH.md](./CONTENT_WIDTH.md).

> **Companion / blocchi dinamici:** non aggiungere Section width, Section padding o Content width nel pannello settings a destra. Quei controlli vivono solo nella toolbar contestuale (icona content-width). Il markup sotto è obbligatorio affinché la toolbar funzioni. I root `data-voodbuilder-block` (`.voodbuilder-editor-dynamic`) espongono la stessa icona content-width del blocco section catalogo, perché l’albero interno è locked.

> **Non confondere con Layout Container:** `class="voodbuilder-editor-container"` + `data-voodbuilder-role="content"` è solo lo shell di misura. **Non** mettere `data-voodbuilder-layout="container"` sui companion — quell’attributo attiva Columns / layout picker e non ha senso sui blocchi dinamici.

> **Bordi:** mai `border` nudo. Usa `border border-vp-divider` (come i blocchi catalogo) o `ring-1 ring-vp-divider`.

```html
<section
  class="voodbuilder-editor-section relative overflow-hidden …"
  data-voodbuilder-section-block="vb-….…"
  style="min-height:70vh;"
>
  <!-- A. MEDIA (decorativo / cover) — NON è la dropzone principale -->
  <div class="voodbuilder-hero-media" data-voodbuilder-role="media" aria-hidden="true">
    <img class="voodbuilder-hero-media__img" src="…" alt="" style="opacity:0.55;" />
    <div class="voodbuilder-hero-media__shade …" data-voodbuilder-role="shade"></div>
  </div>

  <!-- B. CONTENUTO — unica dropzone primaria per testo/CTA/blocchi UI -->
  <div
    class="voodbuilder-editor-container relative z-10 …"
    data-voodbuilder-role="content"
    data-voodbuilder-content-width="normal"
    data-voodbuilder-dropzone="content"
  >
    <!-- slot interni opzionali, ancora più precisi -->
    <div data-voodbuilder-dropzone="copy">…titoli, paragrafi…</div>
    <div data-voodbuilder-dropzone="actions">…link/CTA…</div>
  </div>
</section>
```

### Ruoli (`data-voodbuilder-role`)

| Ruolo | Selezionabile canvas | Droppable | Layerabile | Note |
|--------|----------------------|-----------|------------|------|
| `media` | sì (o via Layers) | **no** | sì | Solo img / picture |
| `shade` | no (o sì ma locked) | **no** | sì | Sempre `pointer-events: none` |
| `content` | sì | **sì** | sì | Dropzone principale |
| `copy` / `actions` | sì | **sì** | sì | Sotto-dropzone strette |

Quando registri un `DomComponents` type custom, mappa questi attributi a:

- `droppable: false` su media/shade  
- `droppable: true` (o filtro) su content/actions  
- `highlightable: true` sulle dropzone  
- `name` leggibile in Layers (`Hero media`, `Hero content`, `Actions`)

---

## 3. Dropzone: regole operative

### Cosa rende una dropzone “facile”

1. **Area cliccabile reale** — padding interno (`py-8` / `min-h` ragionevole), non un wrapper alto quanto tutta la section che mangia i drop destinati ai figli.
2. **Non coprire i media con un unico `min-h-[70vh] z-10` droppable** che intercetta tutto. Pattern corretto:
   - media: `absolute inset-0`, `pointer-events-none` sul wrapper, `pointer-events-auto` solo sull’`img` se vuoi click diretto;
   - content: `relative z-10` ma **alto quanto il contenuto** (o flex con `justify` senza essere l’unico target di drop a tutta altezza).
3. **Filtro droppable** — una `section` non deve accettare altre `section` annidate (già in `voodbuilder-section`). Le dropzone `content`/`actions` accettano bottoni, link, testo, icone; **non** altre full-section.
4. **Evitare drop “tra” section** — se il blocco UI finisce *sopra* la section con menu contestuale che lampeggia, di solito:
   - la dropzone content non è riconosciuta / non è droppable;
   - oppure un overlay cattura l’hit-test;
   - oppure il blocco trascinato è tipizzato come qualcosa che la section rifiuta e Editor lo appoggia sul parent sbagliato.
5. **Drop interni** — Editor, se un container ha già figli, mostra solo linee *tra* elementi. In editor VoodBuilder monta slot temporanei `data-voodbuilder-inner-drop` (flex/grid/dropzone) durante il drag così puoi annidare in fondo al container; vengono rimossi al drop/export.

### Checklist drop prima di shippare un blocco

- [ ] Posso droppare un **Button** della libreria **dentro** `data-voodbuilder-dropzone="actions"` senza che finisca fuori dalla section.
- [ ] Il contenitore hero a tutta altezza **non** ha `data-voodbuilder-dropzone="content"` (solo `data-voodbuilder-role="content"`): altrimenti ruba i drop dei Button.
- [ ] Posso droppare testo/heading in `copy`.
- [ ] Shade e media **non** accettano drop (nessun highlight di drop su di loro).
- [ ] In Layers i nomi sono chiari (niente tre `Div` anonimi di fila).

---

## 4. Media selezionabile dal pannello Content (colonna destra)

Per hero / banner / card con foto, **non** obbligare l’utente a cacciare l’`<img>` sotto lo shade.

Quando è selezionata la **section** (o lo slot `content`), esporre trait tipo:

- `Hero image` → apre Asset Manager / aggiorna `src` dell’img in `[data-voodbuilder-role="media"] img`
- Toolbar canvas **Modifica immagine** (Jodit) → crop/filtri sull’`img` selezionata (o sulla section `vb-bg-image`); al salvataggio fa upload e aggiorna `src` / `data-vb-bg-src`
- opzionale: `Image opacity`, `Object position`

Pattern consigliato:

1. Custom component type sulla section (`vb-hero-cinematic`, ecc.) con `traits` che scrivono sull’img figlia.
2. Oppure trait sull’img con `name: 'Hero image'` e section che, in `init`, promuove quel trait a livello section.
3. L’img resta nel DOM (meglio per accessibilità / SEO) ma il **controllo UX** sta nel Content panel.

Non usare “nascondi shade in Layers” come workflow: l’occhio Layers può persistire `display:none` al save e rompere il render.

---

## 5. Pulsanti / CTA della libreria

- Preferire i CTA già annotati (`data-voodbuilder-cta` / tipo button-link del pacchetto): label via trait, non RTE fragile.
- Il markup del bottone deve essere **droppabile solo in zone actions/content**, non come wrapper section.
- Evitare che un “Button” sia in realtà una mini-section o un blocco con `section` interna.
- Dopo il drop: un solo nodo selezionabile; niente toolbar che lampeggia per conflitto parent/child.

---

## 6. CSS cover (editor = pubblico)

Problema tipico: in editor Vite ha tutte le utility; in pubblico l’img resta letterboxed.

**Obbligatorio per hero full-bleed:**

```css
/* già in section-utilities.css */
.voodbuilder-hero-media { position:absolute; inset:0; overflow:hidden; }
.voodbuilder-hero-media__img {
  position:absolute; inset:0; width:100%; height:100%;
  max-width:none; object-fit:cover; object-position:center;
}
.voodbuilder-hero-media__shade { pointer-events:none; position:absolute; inset:0; }
```

- Aggiungere `@source` al file PHP/HTML del blocco in `section-utilities.css`.
- Regole cover hero (`.voodbuilder-hero-media*`) e frame media (`.voodbuilder-media-frame`) vivono in **`theme.css`** così editor e sito pubblico condividono lo stesso CSS.
- Non affidarsi solo a `h-full w-full object-cover` senza la classe stabile.
- Evitare `width`/`height` HTML grandi sull’img cover (confondono il layout se il CSS manca).

Dopo nuove classi: `npm run build` / `npm run dev`.

---

## 7. Page template vs blocco libreria

| | Page template | Blocco section |
|--|---------------|----------------|
| Uso | pagina intera (stack di section) | pezzo riutilizzabile |
| Drop | applicato a page-content slot | droppato in canvas |
| Nav/footer | mai | mai |
| Composizione | riusa section della libreria se esistono | HTML autonomo + type/traits |

Nuovi landing (Astrolus/Daiva/NASA):  
1) section come blocchi con dropzone + trait media;  
2) template = concatenazione di quelle section.

---

## 8. Anti-pattern (non fare)

- Un solo `div` `absolute inset-0 z-10 min-h-[70vh]` che contiene testo **e** intercetta tutti i drop/click.
- Shade senza `pointer-events-none`.
- Overlay nascosto via Layers per “raggiungere” l’immagine.
- Utility arbitrarie critiche (`min-h-[70vh]`, `object-cover`) senza fallback classe/CSS bundle.
- Section dentro section.
- `dark:` Tailwind al posto dei token `vp-*` (tranne bande cinematiche intenzionali).
- Immagini da placeholder host random; usare data-URI neutri / asset locali.

---

## 9. Prompt breve da dare a un’AI

Copia-incolla:

> Stai authorando blocchi Editor per VoodBuilder. Ogni section deve avere: (1) media layer non-droppable con cover CSS stabile `.voodbuilder-hero-media*`, (2) shade `pointer-events-none`, (3) content dropzone esplicita `data-voodbuilder-dropzone="content|copy|actions"` dove si droppano Button/testo della libreria senza uscire dalla section, (4) trait Content-panel per cambiare l’immagine hero senza cacciare l’img sotto lo shade, (5) token `vp-*`, (6) editor e render pubblico equivalenti. Segui `packages/voodflow/voodbuilder/docs/EDITOR_BLOCK_AUTHORING.md`. Non nascondere overlay per editare media. Non annidare section. Aggiorna `@source` in `section-utilities.css` e testa drop Button + cambio img da Content + preview pubblica senza letterbox.

---

## 10. Verifica manuale (Definition of Done)

1. Layers: nomi `… media`, `… shade`, `… content`, `… actions`.
2. Content panel: seleziono la section → posso cambiare hero image.
3. Drop Button in `actions` → resta dentro la section; toolbar stabile (no flicker).
4. Preview / pagina pubblicata: immagine cover full-bleed, shade leggibile, nessun letterbox.
5. Light/dark: testi e superfici con token (o cinematico intenzionale).
