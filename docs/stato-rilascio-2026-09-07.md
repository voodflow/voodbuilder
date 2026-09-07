# Stato rilascio VoodBuilder — 7 settembre 2026

**Tipo:** snapshot pre-codice (decisioni di prodotto + gap)  
**Scope di questo giro:** core Community, edizioni Pro/Agency, companion builder (components, elements, dynamic-data, templates), `vpopups` come SKU separato, `vcookiebar` + `vmedia` free.  
**Fuori scope:** `vforms` (prodotto a parte); `vevents` / `vpartners` / `vsponsors` / `vexhibitors` (rilascio successivo); OEM / whitelabel (solo Voodflow automation, mai edizioni del builder).

**Premesse confermate:**

- Core **agnostico**: nessun cablaggio ad hoc verso i companion; i companion estendono via plugin/bridge. Senza companion → soft-gate / testo di upselling (come già oggi).
- Free: categorie **Layout, Basic, Media, Single, Site** + **~10 sezioni Elements locali** (non remote).
- Library remota: solo con companion **`voodbuilder-elements`**.
- Components / Templates / Popups / Dynamic Data: solo con il rispettivo companion.
- `vpopups` venduto a parte; il core non assume che esista.
- Una sola demo completa (simile al sito ufficiale Voodflow, modificabile) — a carico product owner.
- Prima del publish companion: security + test; cookie deve bloccare davvero.
- Catalogo remoto: blocchi come gallery/slider devono diventare configurabili.

---

## Sintesi

| Area | Stato | Blocca? |
|---|---|---|
| Authoring ≠ rendering a licenza scaduta | ✅ | No |
| Semver 0.1.0 / VERSIONING | ✅ | No |
| Analytics rename (`voodbuilder-analytics`) | ✅ chiuso | No |
| OEM / WL sul builder | ✅ assenti | No |
| Free: Layout/Basic/Media/Single/Site + icone Single | ✅ Tabler icons + pack locale curato | No |
| Free: ~10 Elements **locali** | ✅ `COMMUNITY_SECTION_BLOCK_IDS` (13 con JS tiles) | No |
| Soft-gate companion (upsell) | ✅ + test CompanionSecurityAndSoftGate | No |
| Library remota solo con elements | ✅ `require_full_library_capability` default **true** | No |
| Catalogo remoto configurabile | ✅ `ElementConfigSchema` (+ merge in resolveElement) | Parziale (deploy Hetzner item JSON) |
| Security + test companion | ✅ suite core + vcookiebar/vmedia/elements/components/DD | No |
| vcookiebar + vmedia MIT (+ NOTICE trademark) | ✅ | No |
| Image slider `preventDefault` | ✅ | No |
| Anystack / Composer privati | ⏳ | Sì vendita |
| Demo pubblica | 👤 product owner | — |

**Verdetto:** Community soft è vicina; manca soprattutto il pacchetto free “costruisci un template” (10 sezioni locali + Single sistemati) e la chiusura MIT/anti-fork su cookie/media. Pro/Agency e companion pagati restano bloccati da security/test e dal catalogo remoto configurabile.

---

## 1. Chiuso dalla sessione 1–2 settembre

| Voce | Esito |
|---|---|
| Authoring ≠ rendering (§8 listino) | ✅ Implementato + `PublishedContentIgnoresLicenceTest` |
| Rimozione `VoodbuilderLicense` | ✅ |
| Rename `voodbuilder-analytics` (typo `analitycs`) | ✅ **Chiuso** (locale e remoto; clone locali: aggiornare `git remote` se ancora `…analitycs.git`) |
| Semver / `^0.1` / VERSIONING | ✅ |
| Fix loop spacer editor | ✅ |
| Listino Pro 149 € / Agency 349 € / vpopups 79 € | ✅ resta valido per questo giro (**senza** vforms in questa analisi) |

### Chiusura degli “ancora aperti” di quella sessione

Non restano aperti come blocco Community soft. Destinazione:

| Era aperto | Chiusura |
|---|---|
| **Fail-open su outage Anystack (authoring)** | Documentato sotto (cosa fa). **Decisione:** mantenere fail-closed soft dopo grazia 7 gg sull’*authoring* (si perde Pro/Agency in editor, non il sito pubblicato). Fail-open = tenere l’ultimo scatto valido a tempo indeterminato se l’API licenze non risponde: utile anti-disservizio, rischia di lasciare capability a chi non rinnova. Non blocca il tag soft; si può rivedere post-lancio. |
| Namespace condiviso core ↔ companion | Debito noto; si affronta col lavoro manifest/SDK, **non** con il tag Community soft. |
| Security analisi 1/9 (`PageBuilderAccess`, entitlement import/export, allowlist HTML, code splitting) | **Spostate in §4** come prerequisito publish companion / vendita Pro — non come coda indefinita della sessione 1/9. |

#### Cosa fa “fail-open su outage Anystack (authoring)”

Oggi, se `api` Anystack non risponde, dopo `grace_seconds` (7 giorni) il provider ripiega sulle capability **Community** per l’editor. Il rendering pubblico **non** dipende più dalla licenza (§8).

- **Fail-closed (oggi):** dopo la grazia, in editor torni Community anche se era un guasto di rete tuo. Sicuro commercialmente, scomodo se l’API è giù a lungo.
- **Fail-open:** in caso di errore di rete (non scadenza chiave), tieni l’ultimo scatto Pro/Agency finché Anystack non risponde di nuovo. Meglio per il cliente in caso di tuo downtime; peggio se qualcuno “smette di pagare” e l’API resta irraggiungibile.

Scelta attuale per il rilascio: **fail-closed dopo grazia**, perché il sito pubblicato è già al sicuro.

---

## 2. Versione free (Community) — completa

### Perimetro blocchi in sidebar (core)

Devono essere presenti **solo** queste categorie core:

| Categoria | Contenuto | Stato |
|---|---|---|
| **Layout** | section, container, block, div | ✅ |
| **Basic** | heading, text, rich text, link, button, icon, divider | ✅ |
| **Media** | image, video, gallery, audio, carousel, slider | ✅ |
| **Single** | reading time, reading progress, social sharing | ⚠️ da verificare funzionamento + **icone opportune** (oggi wireframe SVG minimali: cerchio/tratto — come in sidebar) |
| **Site** | nav / footer chrome helpers | ✅ |

**Non** fanno parte del free core in questo giro:

- **Events** — arriva con `vevents` (più avanti); senza companion non deve comparire.
- **Forms** — non è di questa analisi (`vforms` a parte); senza companion non deve comparire.
- Categorie Components / Templates / Dynamic / Popups — solo soft-gate upsell finché manca il plugin.

### ~10 sezioni Elements **locali** (decisione chiave)

- Vivono nel **core**, shippate col pacchetto free.
- **Non** sono il catalogo remoto `api.voodflow.com`.
- Obiettivo: chi ha solo Community può costruire un **template di sito** (hero, content, features, CTA, testimonial, …) senza `voodbuilder-elements`.
- Distinte dagli item remote (Pro + companion elements): stessi “gusti” di sezione, altra sorgente e altro ciclo di release.

Stato codice oggi: `EditorCommunityBlockCatalog` ha già ~19 `COMMUNITY_SECTION_BLOCK_IDS` misti — va **rielaborato** nella lista ufficiale “decina free locale” (scelta prodotto + QA), non confusa con teaser remoti.

### Soft-gate companion (invariato come pattern)

| Superficie | Senza companion | Con companion |
|---|---|---|
| Components | Upsell / tab chiuso | Library componenti utente |
| Templates (authoring) | Upsell | Save / import / export authoring |
| Popups | Upsell | Builder + runtime (`vpopups`, SKU a parte) |
| Dynamic Data | Upsell | Binding / collections |
| **Library remota** (modal Elements) | **Non visibile** | Solo se installato e registrato **`voodbuilder-elements`** |

Il core resta **agnostico**: niente funzioni speciali hard-coded per un companion; discovery via plugin registration / bridges / entitlements esposti dal companion.

### Cookie + media nel free

- Inclusi come pacchetti free (MIT, vedi §3/§4).
- Sito demo pubblico: a carico product owner (fuori da questo doc operativo).

### Gap free ancora da codice

1. Curare lista definitiva ~10 sezioni locali + QA template.  
2. Verificare Single (runtime reading time / progress / share) e sostituire icone wireframe.  
3. Garantire che Events/Forms non appaiano senza i rispettivi pacchetti (agnosticità).  
4. LICENSE MIT (+ policy anti-resale) su vcookiebar; vmedia già MIT.

---

## 3. Versioni a pagamento — completa

Fonte runtime: `EditionCapabilityMatrix.php`.  
OEM / whitelabel: **non esistono** per VoodBuilder.

### Edizioni builder

| Edizione | Prezzo | Cosa vende | Companion tipici |
|---|---|---|---|
| **Community** | 0 € | Editor + Layout/Basic/Media/Single/Site + ~10 sezioni locali + temi/menu/layouts/pagine; soft-gate upsell | `vcookiebar`, `vmedia` (free) |
| **Professional** | 149 €/anno / dev | Catalogo Elements **remoto** completo + Dynamic Data + template import / remote-install + theme import | `voodbuilder-elements`, `voodbuilder-dynamic-data` (+ templates dove serve import) |
| **Agency** | 349 €/anno / dev | Tutto Pro + Components (library, global classes, import/export) + export template/theme + team-share + `pages.custom-js` + provider DD custom + marketplace caps | + `voodbuilder-components` |

Capability in matrice (riferimento):

- **Community:** `editor.*`, `blocks.core`, `templates.local`, temi clone/map/studio, menu, layouts, pages.  
  *Nota tecnica:* in matrice restano ancora `pages.forms` e `popups.*` — con core agnostico vanno riallineate: le capability “modulo” vivono col companion, non cablate come se il core le possedesse.
- **Professional:** + `blocks.official.complete`, `templates.import`, `templates.remote-install`, `themes.import`, `dynamic-data.single|collections|query-builder`.
- **Agency:** + export/share template/theme, famiglia `components.*`, `pages.custom-js`, `dynamic-data.custom-providers`, `marketplace.*`.

### Gate reale

1. **Plugin Filament del companion** → modulo acceso o upsell.  
2. **Edizione / Anystack** → download aggiornamenti + capability di contorno (liste, export, custom JS, full remote library).

Contratto: la licenza dà diritto a download/aggiornamenti e alle capability; senza Composer + plugin il modulo non c’è.

### SKU in questo giro (non edizioni)

| Pacchetto | Ruolo | Note |
|---|---|---|
| `voodbuilder-elements` | Pro | Client + Library remota; HTML su `api.voodflow.com` |
| `voodbuilder-dynamic-data` | Pro | Binding / collections |
| `voodbuilder-components` | Agency | Componenti riusabili / global classes |
| `voodbuilder-templates` | Pro/Agency (authoring) | Cancello sottile, non SKU “vetrina” |
| `vpopups` | **Venduto a parte** (79 €/anno) | Core agnostico; soft-gate se assente |
| `vcookiebar` | Free MIT | Vedi anti-resale |
| `vmedia` | Free MIT | Già MIT |
| `voodbuilder-analytics` | Fuori listino | Scaffold |

**Esplicitamente fuori:** `vforms`; verticale eventi (`vevents`, …).

### LICENSE free e “non voglio fork rinominati in vendita”

Decisione: **vmedia e vcookiebar → MIT** sul file LICENSE (uso, modifica, redistribuzione permissiva).

**Attenzione:** MIT puro **non** impedisce a qualcuno di rinominare e rivendere. Per limitare i “plugin derivati” commerciali senza abbandonare MIT sullo uso:

1. **Trademark** su nomi/loghi Voodflow / VoodBuilder / vcookiebar / vmedia — obbligatorio in NOTICE; vietato usare i marchi sul fork commerciale.  
2. Opzionale **Commons Clause** (o “MIT + no commercial redistribution of the package itself”) se volete un vincolo contrattuale più forte sul *pacchetto* rivenduto come prodotto concorrente.  
3. Distinguere: ok che un’agenzia usi/modifichi nei progetti cliente; non ok che pubblichi “SuperCookieBar” = vostro codice ribattezzato su Packagist a pagamento.

Da fare in codice/docs: allineare `vcookiebar/LICENSE` a MIT (+ NOTICE trademark); stesso schema su `vmedia` se manca NOTICE.

---

## 4. Sicurezza companion — completa

Prerequisito **prima** di pubblicare i repo companion (e prima di clienti Pro/Agency).

### Per pacchetto

| Area | components | elements | dynamic-data | vpopups | vcookiebar | vmedia |
|---|---|---|---|---|---|---|
| Authz route / panel | ⏳ must | SSRF client ✅; auth preview/source ⏳ | ⏳ must | parziale | consent CSRF/throttle ✅ | upload/SVG ✅ |
| Gate non solo-UI | import/export entitlement backend ⏳ | `require_full_library_capability` da allineare a Pro | List repeat / authoring vs render ✅ direction | — | — | — |
| XSS / HTML | — | HTML remoto → allowlist core save+render ⏳ | output binding ⏳ | — | — | — |
| Test dedicati | **0** → must | **0** → must | **0** → must | 2 → rafforzare | 7 ok + smoke “senza consenso non parte X” | 16 ok |
| LICENSE | proprietary | proprietary | proprietary | proprietary | → **MIT** + trademark | **MIT** + trademark |

### Da analisi 1/9 (ora in questa checklist)

1. `PageBuilderAccess` (o equivalente) su tutte le route `voodbuilder/editor/*`.  
2. Entitlement server-side su components import/export.  
3. Allowlist HTML vera al save e al render pubblico.  
4. Cookie: ogni tracking/embed della demo dietro gate esplicito; test di regressione consenso.

### Cookie “reale”

- Oggi: script gated (`type="text/plain"` + `data-vcookiebar` / `<x-vcookiebar::gated>`) attivati solo dopo consenso → **non cosmetico**.  
- Gap: niente interceptor globale su script/iframe non marcati → policy + integrazioni tutte annotate; smoke test obbligatorio prima del publish open.

---

## 5. Catalogo remoto — completa (free = elementi locali)

Due binari distinti:

| Binario | Dove vive | Chi lo vede | Configurabilità |
|---|---|---|---|
| **Elements locali free (~10)** | Core VoodBuilder | Community senza companion elements | Traits/settings come gli altri blocchi core |
| **Library remota** | `api.voodflow.com` + client `voodbuilder-elements` | Solo con companion **elements** (+ edition Pro per full library) | Oggi HTML statico; **da fare** schema config per gallery, slider, carousel, ecc. |

Lavoro remoto (Hetzner) prima di vendere Elements:

1. Contratto JSON per blocchi che richiedono opzioni (autoplay, interval, source media, layout gallery, …) → traits editor.  
2. Deploy catalogo allineato (`voodbuilder-elements-catalog` + `deploy.sh`).  
3. Nessun teaser remoto in Community: i teaser free sono **locali** (§2).

---

## 6. Bug image slider — completa

| Voce | Dettaglio |
|---|---|
| Sintomo | Click frecce → jump in cima pagina |
| Causa | `slider-runtime.js`: handler prev/next senza `preventDefault`; possibile `<a href="#">` / submit implicito |
| Stesso pattern | Carousel in `vb-runtime.js` |
| Fix | `preventDefault` (+ `stopPropagation` se serve); `type="button"` nel markup locale e remoto |
| Priorità | Prima della demo pubblica (bug visibile in 2 secondi) |
| Stato | ❌ non fixato |

---

## 7. Sito demo

| Decisione | Dettaglio |
|---|---|
| Quante demo | **Una sola**, completa (free + companion attivi per mostrare il prodotto intero) |
| Forma | Simile al sito ufficiale Voodflow, **modificabile** nell’editor |
| Ownership | Product owner (fuori dal workstream codice di questo doc) |
| Seed locale esistente | `voodbuilder:seed-marketing-site` — utile in dev, non sostituisce la demo ufficiale |

---

## 8. Sequenza consigliata — completa

1. **Free pack** — lista ~10 sezioni locali; QA Single + icone; nascondere Events/Forms senza companion; allineare matrice capability all’agnosticità.  
2. **MIT + trademark/NOTICE** su `vcookiebar` e `vmedia`; smoke cookie reale.  
3. **Fix slider** `preventDefault`.  
4. **Security + test** components / elements / dynamic-data (+ pass vpopups); item analisi 1/9 in §4.  
5. **Schema config catalogo remoto** + deploy Hetzner.  
6. **Anystack + Composer privati** per Pro / Agency / `vpopups`.  
7. **Tag Community soft** quando 1–3 sono verdi e soft-gate companion è pulito.  
8. **Vendita Pro/Agency + vpopups** quando 4–6 sono verdi.  
9. **Demo unica** — parallel track product owner.  
10. Verticale eventi e `vforms`: **giri successivi**, fuori da questa sequenza.

---

## 9. Decisioni di prodotto — complete (7 settembre 2026)

1. Niente OEM / whitelabel come edizione o feature VoodBuilder.  
2. `vcookiebar` e `vmedia` → **MIT** + tutela marchio / anti-rivendita del pacchetto rinominato (MIT da solo non basta).  
3. Companion pagati di questo giro: **elements, dynamic-data, components**; **templates** come cancello; **vpopups** SKU separato.  
4. Core **agnostico**: i companion estendono; senza di loro solo upsell. Library remota solo con `voodbuilder-elements`.  
5. Free = Layout + Basic + Media + Single + Site + **~10 Elements locali** (non remoti). Single: verifica + icone.  
6. Security + test obbligatori prima del publish companion.  
7. Cookie deve bloccare davvero (gate esplicito + test).  
8. Catalogo remoto configurabile per gallery/slider/ecc.; free non dipende dal remoto.  
9. Image slider: fix `preventDefault` prima della demo.  
10. **Una** demo completa, stile sito ufficiale, modificabile — a carico PO.  
11. `vforms` e verticale eventi **fuori** da questa analisi/rilascio.  
12. Analytics rename: **chiuso**.  
13. Fail-open Anystack: **non adottato ora**; resta fail-closed dopo grazia sull’authoring (sito pubblicato già protetto).  
14. Prezzi Pro/Agency/vpopups del 2/9 restano validi.

### Nessuna decisione aperta bloccante

Le scelte di prodotto per questo documento sono chiuse. Restano solo **lavori di esecuzione** (§8).

---

## Riferimenti

| Doc / codice | Uso |
|---|---|
| [distribuzione-e-listino-2026-09-02.md](./distribuzione-e-listino-2026-09-02.md) | Prezzi storici, §8 licenza |
| [analisi-voodbuilder-2026-09-01.md](./analisi-voodbuilder-2026-09-01.md) | Security / perf |
| [VERSIONING.md](./VERSIONING.md) | Semver |
| [MARKETING_SITE.md](./MARKETING_SITE.md) | Seed locale (non demo ufficiale) |
| `EditionCapabilityMatrix.php` | Capability edizioni |
| `EditorCommunityBlockCatalog.php` | Allowlist free |
| `editor-utility-blocks.js` | Single + wireframe icone |
| `slider-runtime.js` | Bug frecce |

---

*Aggiornato 7 settembre 2026 pomeriggio — decisioni product owner incorporate; nessun codice avviato da questo documento.*
