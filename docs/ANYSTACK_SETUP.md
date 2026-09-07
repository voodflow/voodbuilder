# Configurazione Anystack — VoodBuilder (rilascio 0.1.x)

Guida operativa per creare prodotti, prezzi e repository Composer. I tag git già pubblicati sono `v0.1.1` (builder + companion) e `v0.2.1` (`vmedia`).

## Modello commerciale (riepilogo)

| SKU Anystack | Prezzo | Rinnovo (60%) | Composer / plugin | Edizione capability |
|---|---:|---:|---|---|
| **VoodBuilder Community** | 0 € | — | `voodflow/voodbuilder` (+ opz. `vcookiebar`, `vmedia` MIT) | `community` |
| **VoodBuilder Pro** | **149 €/anno** / sviluppatore | **89 €** | + `voodbuilder-elements`, `voodbuilder-dynamic-data`, `voodbuilder-templates` | `professional` |
| **VoodBuilder Agency** | **349 €/anno** / sviluppatore | **209 €** | tutto Pro + `voodbuilder-components` | `agency` |
| **VoodPopups** | **79 €/anno** / sviluppatore | **47 €** | `voodflow/vpopups` (**richiede** `voodbuilder ^0.1`) | SKU separato |

- Siti **illimitati** per chiave sviluppatore.
- **Niente OEM / whitelabel** come edizione VoodBuilder.
- `vforms` e verticale eventi: **fuori** da questo listino.
- Free open: `vcookiebar`, `vmedia` (Packagist / repo pubblici MIT) — non servono prodotti Anystack a pagamento.

## Prodotti da creare su Anystack

### 1. Prodotto `voodbuilder` (core + edizioni)

Un solo product id: **`voodbuilder`** (è il valore che il client PHP manda in `POST …/entitlements` → `"product": "voodbuilder"`).

**Tre piani / varianti di prezzo** sullo stesso prodotto:

| Piano | `edition` nella risposta entitlements | Capability (oltre Community) |
|---|---|---|
| Community | `community` | editor, `blocks.core`, template locali, temi, menu, layouts, pages |
| Pro | `professional` | + `blocks.official.complete`, template import/remote-install, theme import, `dynamic-data.*` |
| Agency | `agency` | + `components.*`, export/share, `pages.custom-js`, marketplace caps |

**Repository Composer privati da associare al prodotto `voodbuilder`:**

| Piano | Repo da sbloccare con la chiave |
|---|---|
| Community | `voodflow/voodbuilder` (solo) |
| Pro | + `voodflow/voodbuilder-elements`, `voodflow/voodbuilder-dynamic-data`, `voodflow/voodbuilder-templates` |
| Agency | + `voodflow/voodbuilder-components` |

Tag Composer: `^0.1` / tag git `v0.1.1`.

**Payload entitlements atteso** (driver già nel core):

```json
{
  "edition": "professional",
  "active": true,
  "capabilities": ["…lista o omessa se il client ricostruisce dalla matrice…"],
  "identifier": "optional",
  "expires_at": "2027-09-07T00:00:00Z",
  "message": null
}
```

Se ometti `capabilities`, il core usa `EditionCapabilityMatrix::forEdition($edition)`.

### 2. Prodotto `vpopups` (SKU autonomo)

- Product id: **`vpopups`** (serve variabile/driver dedicato sul pacchetto; oggi il client Anystack del **core** è cablato su `voodbuilder` — per vendere vpopups a parte o:
  - includi l’accesso al repo `voodflow/vpopups` come **add-on** del piano Agency, **oppure**
  - aggiungi un secondo entitlement client nel pacchetto `vpopups` (lavoro codice successivo).
- **Dipendenza:** `voodflow/voodbuilder: ^0.1` obbligatoria (stesso editor GrapesJS per costruire i popup).
- Prezzo: 79 €/anno, rinnovo 47 €.
- Repo: `voodflow/vpopups` @ `v0.1.2+`.

**Raccomandazione pratica per il lancio:** vendi vpopups come **add-on Anystack** del prodotto `voodbuilder` (Agency lo include; Pro/Community lo acquistano a 79 €) così una sola chiave `voodbuilder` sblocca anche il repo popups. Eviti un secondo driver finché non lo implementi.

### 3. Community gratis — se Anystack free non permette prodotti a 0 €

Sul **piano gratuito Anystack** spesso non puoi creare un prodotto/piano Community a prezzo zero.
In quel caso **non bloccare il lancio**:

| Cosa | Come |
|---|---|
| Core Community | Pubblica `voodbuilder` (e MIT `vcookiebar`/`vmedia`) su **Packagist / repo pubblico** senza chiave |
| Lead + email marketing | Form sul sito voodflow.com (“Download Community”) con email + opt-in GDPR → MailerLite/Brevo/… |
| Conteggio install | Packagist download stats (grezzo) + lead form; **non** Anystack |
| Pro / Agency / Popups | Solo questi su Anystack (piani a pagamento) |

Quando passerai a un piano Anystack che supporta prodotti free, allora potrai aggiungere Community 0 € con chiave e unificare lead+licenza.

**Non** creare un “Community” a 1 € solo per aggirare il limite: confonde il listino e costringe a gestire rimborsi/chiavi inutili.

## Variabili env lato cliente (builder)

```env
VOODBUILDER_LICENSE_DRIVER=anystack
VOODBUILDER_LICENSE_KEY=vb_…
VOODBUILDER_ANYSTACK_ENDPOINT=https://…  # endpoint entitlements Anystack
```

Grace: 7 giorni sull’authoring se l’API non risponde; il sito pubblicato non dipende dalla licenza.

## Conteggio install Community e email marketing

**Oggi non esiste telemetria** nel core: un’installazione Community offline non “chiama casa”.

| Fonte | Cosa dà | Limite |
|---|---|---|
| **Anystack piano Community (0 €) con registrazione** | N. chiavi emesse + email + opt-in | Solo chi scarica via Anystack |
| Packagist / Composer download stats | Volume grezzo | Nessuna email, no install uniche |
| Demo ufficiale / sito voodflow.com | Lead form | Solo visitatori del sito |
| Heartbeat opt-in futuro su `api.voodflow.com` | Install attive | Va costruito (consenso + privacy policy); **non** fatto in questo giro |

**Per marketing:** crea Community su Anystack con email obbligatoria e checkbox marketing separato. Non usare email di licenza pagante per newsletter senza opt-in.

---

## Checklist Anystack

1. [ ] Product `voodbuilder` + 3 piani (0 / 149 / 349) + rinnovi 60%
2. [ ] Repo Composer privati collegati per piano (tabella sopra)
3. [ ] Tag `v0.1.1` (e successivi) pubblicati sui repo
4. [ ] Endpoint entitlements che restituisce `edition` + `active`
5. [ ] Community: registrazione email + opt-in marketing
6. [ ] VoodPopups: add-on 79 € **o** product dedicato + codice client
7. [ ] Documentazione install: `composer config repositories…` + `auth.json` Anystack

## Non creare

- Prodotti OEM / whitelabel per VoodBuilder
- Prodotti a pagamento per `vcookiebar` / `vmedia`
- SKU separati per `templates` / `analytics` (templates è cancello; analytics è scaffold)
