# Politica di versionamento — VoodBuilder e companion

**In vigore da:** 2 settembre 2026 · **Linea corrente:** `0.1.0`

Questo documento esiste per una ragione pratica: fino a oggi i companion dichiaravano
`voodflow/voodbuilder: >=0.0.11`, che significa "qualsiasi versione futura". Finché i companion
sono tutti di prima parte il danno lo assorbe la suite di test; con clienti paganti diventa
assistenza, perché Composer installa allegramente un core incompatibile e il cliente scopre il
problema in produzione. I vincoli sono ora `^0.1` e questo documento dice cosa promettono.

## Cosa promette `^0.1`

Nella linea `0.x` di Composer, `^0.1` accetta `>=0.1.0 <0.2.0`. Quindi:

- **Patch** (`0.1.0` → `0.1.1`): correzioni. Nessun cambiamento alle API elencate sotto.
- **Minor** (`0.1.x` → `0.2.0`): può contenere breaking change. **I companion vanno aggiornati
  nello stesso rilascio** e il loro vincolo passa a `^0.2`.
- **Major** (`0.x` → `1.0`): stabilizzazione. Dopo `1.0` valgono le regole semver classiche e
  `^1.0` diventa una promessa di compatibilità reale su tutta la linea.

Non usiamo `>=` per le dipendenze interne. Mai.

## Cosa è API pubblica

Questa è la parte che conta, perché un breaking change è tale solo rispetto a un contratto
dichiarato. Sono API pubbliche — e quindi soggette alle regole sopra:

**PHP**

- `Voodflow\Voodbuilder\Voodbuilder` (facade): `can()`, `cannot()`, `entitlements()`,
  `modules()`, `registerModule()`, `fonts()`
- `Voodflow\Voodbuilder\Modules\AbstractVoodBuilderModule` e le interfacce in
  `Voodflow\Voodbuilder\Contracts\`
- `Voodflow\Voodbuilder\Licensing\EditionCapabilityMatrix` e i nomi delle capability
- `Voodflow\Voodbuilder\Support\Editor\EditorGate::registerLabelProvider()`
- `Voodflow\Voodbuilder\Support\Editor\EditorHostChrome::REQUEST_ATTRIBUTE` — il contratto con
  cui i companion capiscono che l'editor ha preso il viewport
- Le chiavi del payload di configurazione dell'editor (`EditorGate::config()`)

**Markup dei blocchi**

Gli attributi `data-voodbuilder-*` sul markup salvato. Sono un contratto verso i *contenuti già
pubblicati*, non solo verso il codice: rinominarne uno rende inerte il markup salvato da tutti i
clienti, e non esiste una migrazione lato loro. Un rename richiede un normalizzatore che accetti
entrambe le forme, per almeno una minor.

**JavaScript**

Oggi `window.VoodbuilderEditor` (`getEditor()`, registrazione plugin). I companion che importano
percorsi interni del core con `../../../../voodbuilder/resources/js/editor/...` **non stanno usando
un'API pubblica**: quegli import funzionano solo nel monorepo e si rompono in un'installazione
Composer normale. Vanno sostituiti dall'entry point stabile (`@voodbuilder/editor-sdk` o alias
Vite ufficiale) prima di aprire estensioni di terze parti.

## Cosa non è API pubblica

Tutto il resto di `src/`, incluse le classi `Support\Editor\*` non elencate sopra, i normalizzatori
e i sanitizer. Possono cambiare in una patch.

## Regole di rilascio

1. Ogni minor porta una voce di changelog che elenca i breaking change per nome, non "varie
   correzioni".
2. Il core non si rilascia da solo: se una minor rompe un companion, il companion si rilascia
   nello stesso momento, con il vincolo aggiornato.
3. Un breaking change sul markup dei blocchi o sulle chiavi del payload va annunciato una minor
   prima, con il vecchio formato ancora accettato.
4. Le capability si aggiungono alla matrice **solo** quando la funzione esiste. Una capability
   dichiarata e non implementata è indistinguibile da un bug per chi paga
   (vedi `voodbuilder-analytics`, dove `analytics.dashboard` è volutamente assente).

## Vincoli correnti

| Pacchetto | Vincolo su `voodflow/voodbuilder` |
| --- | --- |
| `voodbuilder-dynamic-data` | `^0.1` |
| `voodbuilder-components` | `^0.1` |
| `voodbuilder-templates` | `^0.1` |
| `voodbuilder-elements` | `^0.1` |
| `voodbuilder-analytics` | `^0.1` |

`vforms`, `vpopups`, `vcookiebar` e `vmedia` non dipendono dal core: sono plugin Filament autonomi
con integrazione opzionale. Se in futuro dichiarassero la dipendenza, seguono la stessa regola.

## Nota aperta: namespace condiviso

`voodbuilder-dynamic-data` e `voodbuilder-components` spediscono classi **dentro il namespace del
core** (`Voodflow\Voodbuilder\Support\Editor\Bindings\`,
`Voodflow\Voodbuilder\Modules\DynamicData\`). Composer lo permette e il caricamento morbido via
`class_exists()` ci si appoggia, ma significa che due pacchetti scrivono nello stesso prefisso: se
il core aggiungesse un file con lo stesso percorso, lo oscurerebbe silenziosamente. Va separato
insieme al lavoro su manifest e SDK; finché non è fatto, non aggiungere file del core sotto
`Support/Editor/Bindings/`.
