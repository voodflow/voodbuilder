# Piano di distribuzione e listino — VoodBuilder e companion

**Data:** 2 settembre 2026
**Canale di vendita:** Anystack (licenze + repository Composer privati)
**Premessa del committente:** VoodBuilder senza companion è gratuito e serve da upselling; i companion si
combinano in pacchetti; chi compra la licenza compra i pacchetti.
**Metodo:** inventario del codice pacchetto per pacchetto, lettura della matrice capability e del
provider di entitlement, nessuna stima basata su intenzioni dichiarate nei README.

---

## Sintesi — se leggi solo questa pagina

Tre cose da sapere prima di parlare di prezzi.

**Il prodotto più vendibile non è il page builder.** `vforms` ha 25 573 righe di PHP e 48 file di
test; `voodbuilder-components`, che sarebbe una delle voci a pagamento, ne ha 1 795 e **zero** test.
Se domani apri il negozio, l'unico pacchetto che regge un cliente pagante senza assistenza continua
è vforms.

**Oggi la licenza non fa da cancello.** Per quasi tutti i companion il gate reale è "hai registrato
il plugin Filament", non "hai una licenza valida". La matrice edizioni modula solo alcune funzioni di
contorno. Questo non è un difetto se accetti che il cancello vero sia la **distribuzione** (senza
credenziali Anystack non fai `composer require`), ma va deciso consapevolmente, perché cambia cosa
scrivi nel contratto e cosa succede a chi non rinnova.

**C'è un rischio che va chiuso prima di incassare il primo euro.** Se la licenza scade, o se
`api.voodflow.com` è irraggiungibile per più di sette giorni, il provider ripiega sulle capability
Community e `DynamicDataCollectionsBridge::renderRepeats()` restituisce l'HTML senza renderizzare le
liste. Cioè: **il sito pubblicato del cliente si degrada perché il tuo sistema di fatturazione ha un
disservizio.** Nessuno compra un page builder che può rompere il sito del proprio cliente. È la
decisione §8 e va presa prima del lancio.

Il listino che propongo, in una riga: **Community gratis**, **Pro 149 €/anno per sviluppatore**,
**Agency 349 €/anno per sviluppatore**, siti illimitati in entrambi i casi, più due SKU autonomi
(vforms, vpopups) e una linea verticale eventi da tenere fuori dal listino fino a quando ha dei test.

---

## 1. Cosa hai davvero da vendere oggi

Numeri misurati, non stimati. La colonna che conta per il prezzo è "test", perché determina quanta
assistenza ti costerà ogni licenza venduta.

| Pacchetto | PHP (righe) | File di test | Migrazioni | Stato reale |
|---|---:|---:|---:|---|
| `vforms` | 25 573 | **48** | 9 | Maturo, autonomo |
| `vevents` | 10 876 | 13 | 20 | Maturo, verticale |
| `vmedia` | 9 830 | 15 | 8 | Maturo, **oggi MIT** |
| `vexhibitors` | 9 092 | 6 | 22 | Sostanzioso, test scarsi |
| `vpopups` | 2 898 | 2 | 4 | Funzionante, autonomo |
| `vsponsors` | 3 017 | **1** | 1 | Prima versione |
| `vpartners` | 3 016 | **1** | 1 | Prima versione |
| `voodbuilder-dynamic-data` | 2 391 | **0** | 0 | Funzionante, non testato |
| `voodbuilder-components` | 1 795 | **0** | 3 | Funzionante, non testato |
| `vcookiebar` | 1 414 | 7 | 0 | Funzionante, autonomo |
| `voodbuilder-elements` | 913 | **0** | 0 | Runtime del catalogo remoto |
| `voodbuilder-templates` | 187 | **0** | 0 | Solo interruttore di authoring |
| `voodbuilder-analitycs` | 344 | **0** | 0 | **Impalcatura vuota** |
| `voodbuilder-elements-catalog` | 0 | 0 | 0 | Contenuti: 77 item JSON |

Tre osservazioni che il listino deve rispettare.

**`voodbuilder-analitycs` non è vendibile e oggi non è nemmeno accendibile.** La dashboard Filament è
un segnaposto testuale, le route sono commentate, e il modulo richiede la capability
`analytics.dashboard` che **non esiste nella matrice edizioni**: con il driver `config` non si attiva
mai. Va tolto dai piani commerciali e messo in roadmap. Nota separata ma urgente: il nome del
pacchetto Composer contiene un errore di battitura — `voodbuilder-analitycs` — e i nomi Composer
pubblicati non si cambiano senza rompere gli installati. Da correggere **prima** di qualsiasi
pubblicazione.

**`voodbuilder-templates` e `voodbuilder-elements` non sono pacchetti, sono cancelli.** Il primo sono
187 righe che accendono l'authoring dei template, mentre la logica HTTP resta nel core; il secondo
sono 913 righe di client verso un catalogo remoto che vive su `api.voodflow.com`. Venderli come
"pacchetti" separati fa sembrare il listino più ricco e il prodotto più povero: sono **funzioni di un
livello**, non prodotti. È il motivo per cui propongo bundle e non vendita a pezzi.

**`vmedia` è MIT.** È una dipendenza obbligatoria di tutto il verticale eventi. Lasciarla gratuita è
la scelta giusta — è il tuo imbuto verso il verticale — ma va detto nel listino, perché un'agenzia
che legge "media library inclusa" e poi scopre che è MIT si chiede cos'altro sta pagando.

---

## 2. Il cancello: cosa blocca davvero le funzioni a pagamento

Va chiarito perché determina tutto il resto.

Ci sono **due** meccanismi sovrapposti, e non fanno la stessa cosa.

Il primo è la **registrazione del plugin Filament**. Installare il pacchetto Composer non basta:
bisogna anche registrare `VoodbuilderDynamicDataPlugin::make()` sul panel. Senza quello il modulo non
esiste, le route non sono registrate, l'admin non appare. Questo è il cancello che regge il peso oggi,
ed è quello che i companion documentano nei propri `moduleShouldBeEnabled()`.

Il secondo è la **matrice capability** (`community` / `professional` / `agency`), che modula alcune
funzioni dentro un modulo già acceso: List repeat richiede `dynamic-data.collections`,
import/export componenti richiedono `components.import` e `components.export`, l'install da URL dei
template richiede `templates.remote-install`, il JS d'autore richiede `pages.custom-js`.

La conseguenza pratica: **chi ha il pacchetto Composer e registra il plugin ottiene la funzione
principale a prescindere dalla licenza.** Solo le funzioni di contorno sono legate all'edizione.

Non è necessariamente sbagliato. È il modello di Laravel Nova e della maggior parte dei plugin
Filament: il controllo avviene all'acquisto e al download, non a runtime. Ma allora va detto
esplicitamente in tre punti:

1. Nel contratto: la licenza dà diritto al **download e agli aggiornamenti**, non è un interruttore
   di funzionamento.
2. Nel codice: `VoodbuilderLicense::validateKey()` oggi accetta **qualsiasi** stringa che inizi per
   `vb_` e sia lunga almeno 24 caratteri. È un segnaposto. Va collegato ad Anystack o rimosso, perché
   nella forma attuale invita a credere che `license.enforce=true` verifichi qualcosa.
3. Nella comunicazione: se il cliente non rinnova, cosa perde? Vedi §8.

---

## 3. Struttura dei pacchetti: due livelli a pagamento, non sei

La tentazione è vendere i sei pacchetti separatamente. Sconsigliato, per tre ragioni concrete.

Due dei sei sono sottili (§1), uno è vuoto, e uno è solo contenuto. Rimangono `dynamic-data` e
`components` come prodotti autonomi credibili — troppo pochi per un listino a sei voci.

Il cliente dovrebbe capire da solo di cosa ha bisogno prima di aver usato il prodotto. Un'agenzia che
valuta un page builder non sa se le servirà "global classes": lo scopre al terzo progetto.

E ogni SKU in più è un prodotto Anystack in più, una chiave in più, un repository Composer privato in
più, una matrice di compatibilità in più da testare. Con 0 test su quattro dei sei pacchetti, il costo
di manutenzione di sei SKU ti mangia il margine.

La struttura che propongo mantiene le tre edizioni che il codice già conosce, così non c'è lavoro di
migrazione, e riempie ciascuna con un argomento di vendita chiaro.

### Community — gratis

Il builder completo per pagine statiche: editor, animazioni, condizioni, cronologia, blocchi core,
temi, menu, layout chrome, pagine, e **installazione** di template. Include anche `pages.forms`,
`popups.*` e `vcookiebar`, che sono già in `community()` nella matrice.

L'argomento: costruisci e pubblichi un sito completo senza pagare nulla. Il tetto lo incontri quando
il sito deve mostrare **dati** e quando smetti di voler rifare le stesse sezioni a mano.

### Pro — 149 €/anno per sviluppatore, siti illimitati

Aggiunge le due cose per cui la gente paga davvero un page builder:

- **La libreria ufficiale di sezioni** (`blocks.official.complete`, i 77 item del catalogo Elements).
  È l'upsell più immediato perché si dimostra in dieci secondi: apri il pannello e ci sono cinquanta
  sezioni pronte invece di un box vuoto.
- **Dynamic Data** (`dynamic-data.single`, `.collections`, `.query-builder`). È ciò che trasforma un
  page builder in un front-end per il CMS: le pagine leggono dai modelli Eloquent invece di ripetere
  testo statico.
- **Authoring e import dei template** (`templates.import`, `templates.remote-install`, `themes.import`).

### Agency — 349 €/anno per sviluppatore, siti illimitati

Aggiunge ciò che conta quando gestisci molti siti e più persone:

- **Componenti riutilizzabili e global classes** (`components.library`, `.create`, `.global-classes`,
  `.code-import`), con import/export (`components.import`, `.export`) per spostarli tra progetti.
- **Export di template e temi** (`templates.export`, `themes.export`) e condivisione di squadra
  (`themes.team-share`, `components.team-share`).
- **JS d'autore** (`pages.custom-js`), che resta l'ultimo livello e spento per default: è un canale
  che esegue codice nel browser di ogni visitatore.
- **Provider dynamic data personalizzati** (`dynamic-data.custom-providers`) e accesso al marketplace
  (`marketplace.consume`, `marketplace.submit`).

Questa ripartizione è **già quella nel codice**. Non serve riscrivere la matrice: serve decidere che è
il listino e allinearci la comunicazione.

### Fuori dal listino builder: due SKU autonomi

`vforms` e `vpopups` non dipendono da voodbuilder (`suggest`, non `require`) e funzionano da soli. Non
sono livelli del builder, sono prodotti.

- **VoodForms — 199 €/anno per sviluppatore.** È il pacchetto più maturo che hai: schema multistep,
  condizionali, data source HTTP ed Eloquent, inbox delle submission, webhook, captcha, analytics,
  embed Livewire. Prezzo più alto del Pro del builder perché il prodotto è più completo e il mercato
  dei form builder Laravel/Filament è meno affollato.
- **VoodPopups — 79 €/anno per sviluppatore**, oppure incluso in Agency. Funziona ed è utile, ma 2 file
  di test su 2 898 righe non giustificano un prezzo alto.

### Il verticale eventi: non ancora

`vevents` (10 876 righe, 13 test) più `vexhibitors` (9 092 righe, **6** test), `vpartners` e
`vsponsors` (3 000 righe ciascuno, **1** test ciascuno). Sono prodotti sostanziosi con copertura di
test insufficiente per un cliente pagante, e c'è un accoppiamento noto: il plugin JS di `vevents`
gestisce anche i blocchi di `vpartners` e `vsponsors`, quindi quei due **non sono vendibili
separatamente** oggi (§5.2 dell'analisi del 1° settembre).

Raccomandazione: tenerli fuori dal listino pubblico, venderli come progetto su misura finché non
hanno test, e sciogliere l'accoppiamento prima di listarli.

---

## 4. Perché questi prezzi

Il riferimento non è Elementor, è il mercato degli strumenti per sviluppatori Laravel. Chi compra è
uno sviluppatore o un'agenzia che fattura il progetto al cliente: valuta lo strumento su quante ore
gli fa risparmiare, non sul prezzo assoluto.

| Riferimento | Prezzo | Modello |
|---|---|---|
| Laravel Nova | 99–199 $/anno | per progetto / illimitato |
| Plugin Filament commerciali | 49–199 € | una volta, aggiornamenti a tempo |
| Bricks Builder | ~99 $/anno o 299 $ perpetuo | per numero di siti |
| Statamic Pro | 259 $ | per sito, perpetuo |
| Elementor Pro | 59–399 $/anno | per numero di siti |

Pro a 149 € sta sopra un plugin Filament singolo e sotto Nova, che è la collocazione giusta: dai più
di un plugin e meno di un framework di amministrazione. Agency a 349 € è meno di un giorno di lavoro
di un'agenzia: se i componenti riutilizzabili le fanno risparmiare un giorno l'anno — e ne fanno
risparmiare molto di più — la decisione è ovvia.

**Per sviluppatore e non per sito.** Tre motivi. Il codice non ha attivazione per dominio, quindi il
per-sito non sarebbe verificabile senza costruirlo. Le agenzie odiano il per-sito perché le costringe
a rifatturare al cliente ogni rinnovo. E il per-sito ti mette in conflitto con il tuo miglior cliente:
quello che fa venti siti l'anno è quello che vuoi trattenere, non tassare.

**Annuale con funzionamento perpetuo.** Alla scadenza il cliente perde aggiornamenti e accesso al
catalogo remoto, ma **ciò che ha installato continua a funzionare** e i siti pubblicati non cambiano
comportamento. Vedi §8, perché oggi il codice fa il contrario.

**Rinnovo al 60% del prezzo pieno** (Pro 89 €, Agency 209 €). È la pratica standard in questo mercato
e riduce l'abbandono al primo rinnovo, che è dove si perde più gente.

**Sul perpetuo/lifetime: non farlo adesso.** Attira cassa immediata e ti lega a manutenere il catalogo
Elements per sempre a fronte di un incasso unico. Se ti serve cassa per partire, fai una finestra
"early adopter" a tempo — Pro 99 € il primo anno per i primi cento — che è reversibile, invece di un
lifetime che non lo è.

---

## 5. Template: installarli gratis, crearli a pagamento

La risposta è già nel codice ed è quella giusta: `templates.local` è in Community, `templates.import`
e `templates.remote-install` sono in Pro, `templates.export` e `templates.marketplace-submit` sono in
Agency.

Tradotto in prodotto: **chiunque può installare un template**, anche dal catalogo remoto una volta in
Pro; **crearne di propri, esportarli e pubblicarli** è a pagamento.

È l'assetto corretto perché i template sono il tuo strumento di acquisizione. Un template gratuito che
si installa in un clic su un'installazione Community è la migliore demo che puoi fare del builder, e
il momento in cui l'utente vuole modificarlo e salvarselo è esattamente il momento in cui gli chiedi
di pagare.

Una raccomandazione operativa: dei 77 item del catalogo Elements, **rendine visibili una decina in
Community** e tieni le altre in Pro. Un pannello vuoto non vende niente; un pannello con dieci sezioni
belle e cinquanta con il lucchetto vende Pro.

---

## 6. Marketplace per terzi: tre fasi, non aprirlo adesso

Le capability esistono già (`marketplace.consume`, `marketplace.submit`,
`templates.marketplace-submit`), ma il marketplace non è un problema di capability, è un problema di
contratti d'interfaccia.

**Fase 1 — oggi: catalogo di prima parte.** È ciò che hai già: `api.voodflow.com` serve
`elements/index.json` e gli item, `RemoteElementsCatalogClient` li consuma. Nessun terzo coinvolto,
nessuna promessa di stabilità da mantenere. Da consolidare, non da estendere.

**Fase 2 — dopo il lavoro architetturale §4: contenuti di terzi.** Template ed Elements inviati da
altri sono a **basso rischio**, perché sono HTML e CSS che passano dal tuo sanitizer e dalla tua coda
di revisione: non eseguono codice tuo. Divisione dei ricavi 70/30 a favore dell'autore, come da
consuetudine. Questa fase richiede soltanto una coda di moderazione e la dichiarazione di quale markup
è contratto (il documento `EDITOR_BLOCK_AUTHORING.md` c'è già).

**Fase 3 — solo dopo manifest + SDK: estensioni di codice.** Qui il rischio è tutto tuo. Oggi la
scoperta dei plugin JS è una lista scritta a mano in `plugin-bridge.js` e i companion importano
percorsi interni del core con quattro livelli di `../` (§4.1 e §4.2 dell'analisi del 1° settembre):
cinque file in `vevents`, `vexhibitors`, `voodbuilder-elements` e `vpopups` che **si romperebbero in
un'installazione Composer normale**. Aprire un marketplace di codice prima di aver sistemato questo
significa che ogni tuo refactor rompe gli sviluppatori terzi e diventa un problema di assistenza a tuo
carico.

Prerequisiti non negoziabili per la fase 3, tutti già identificati nell'analisi: manifest dichiarativo
nel `composer.json` dei companion, entry point stabile (`@voodbuilder/editor-sdk` o alias Vite
ufficiale), documento che dichiara cosa è API pubblica e cosa è interno, e una politica semver con
changelog dei breaking change sul markup dei blocchi e sulle chiavi del payload di `EditorGate`.

---

## 7. Distribuzione su Anystack: cosa c'è e cosa manca

### Come funziona oggi

Il driver `anystack` fa `POST {endpoint}/entitlements` con `{"licence_key": "...", "product":
"voodbuilder"}` e si aspetta `edition`, `active`, `capabilities`, più `identifier`, `expires_at`,
`message` opzionali. Salva l'esito in cache permanente e, se la chiamata fallisce, riusa lo scatto
precedente entro `grace_seconds` (7 giorni). Le variabili d'ambiente sono già tutte cablate
(`VOODBUILDER_LICENSE_DRIVER`, `VOODBUILDER_LICENSE_KEY`, `VOODBUILDER_ANYSTACK_ENDPOINT`, ecc.).

Per il listino builder a tre edizioni **questo basta così com'è**: un prodotto Anystack chiamato
`voodbuilder`, tre varianti di prezzo, e la risposta che porta `edition` più le capability.

### Cosa manca

**Il product è cablato nel codice** (`'product' => 'voodbuilder'`). Per vendere VoodForms e VoodPopups
come SKU autonomi serve che ogni pacchetto vendibile abbia il proprio prodotto Anystack, la propria
variabile di licenza e la propria risoluzione. Non è un cambio grosso, ma va fatto prima di listarli:
oggi una chiave VoodForms interrogherebbe il prodotto sbagliato.

**Il repository Composer privato è il vero cancello** (§2) e va configurato per prodotto su Anystack.
La documentazione di installazione per Voodflow c'è già
(`packages/voodflow/voodflow/docs/developer/installation.md`); serve la stessa per il builder e per
ogni SKU.

**I pacchetti non sono pubblicabili così come sono.** Oltre al nome con l'errore di battitura (§1),
il `composer.json` del core ha in `autoload-dev` i namespace PSR-4 di vpopups, vforms, components e
dynamic-data **dentro il namespace del core** (§5.5 dell'analisi). È un artefatto della suite di test
del monorepo, non un problema di runtime, ma va separato prima di aprire i repository.

**I vincoli di versione sono inutilizzabili.** I companion dichiarano `voodflow/voodbuilder: >=0.0.11`,
che in pratica significa "qualsiasi versione futura". Con companion di prima parte lo assorbi con i
test; con clienti paganti diventa assistenza. Serve un vincolo `^0.1` e una politica semver dichiarata,
e serve **prima** del primo cliente, non dopo.

---

## 8. La decisione che blocca il lancio: cosa accade a licenza scaduta

Oggi il comportamento è questo. `AnyStackEntitlementProvider::graceSnapshot()` riusa l'ultimo scatto
per sette giorni; oltre, ripiega sulle capability **Community**. Il commento nel codice dice «Soft
fail: Community capabilities only — never break public rendering».

Il commento non è vero. `DynamicDataCollectionsBridge::renderRepeats()` controlla
`Voodbuilder::can('dynamic-data.collections')` e, se manca, **restituisce l'HTML senza renderizzare le
liste**. I messaggi di traduzione lo confermano: «Install and register voodbuilder-dynamic-data (and
ensure your licence includes dynamic-data.collections) to edit **and render** them».

Quindi, nella configurazione attuale, il sito pubblicato di un cliente perde le sue liste dinamiche
sette giorni dopo che il suo abbonamento è scaduto — **o sette giorni dopo un disservizio prolungato
del tuo endpoint di licenze.** Il secondo caso è il più grave: è un tuo problema di infrastruttura che
si manifesta come un guasto sul sito del cliente del tuo cliente.

Le opzioni sono tre.

**Separare authoring da rendering** (raccomandata). Le capability controllano cosa si può **creare e
modificare**; il rendering di ciò che è già pubblicato non le consulta mai. Una licenza scaduta blocca
l'editor delle liste ma le pagine continuano a mostrarle. È l'unico assetto in cui puoi dire a
un'agenzia "il sito del tuo cliente non dipende dal mio server di licenze" e essere credibile.

**Grazia molto più lunga.** Portare `grace_seconds` da 7 a 60 o 90 giorni riduce l'esposizione al
disservizio ma non la elimina, e non risolve la scadenza legittima.

**Lasciare così e dichiararlo.** Difendibile solo se il contratto lo dice a caratteri grandi. Sconsiglio:
è il tipo di clausola che si scopre nel momento peggiore e produce recensioni che costano più di
qualche mancato rinnovo.

C'è anche una scelta secondaria dentro la prima opzione: un disservizio dell'endpoint dovrebbe
**mantenere** le capability precedenti a tempo indeterminato (fallire in apertura) invece di ripiegare
su Community (fallire in chiusura). Per un errore di rete, fallire in apertura è la scelta corretta:
il danno di lasciare per qualche giorno una funzione a chi non la paga più è incomparabilmente minore
del danno di togliere una funzione a chi la paga.

---

## 9. Sequenza di rilascio

L'ordine è dettato dalle dipendenze, non dalle preferenze.

**Prima di poter vendere qualsiasi cosa.** Chiudere la decisione §8 e implementarla. Correggere il nome
di `voodbuilder-analitycs`. Collegare o rimuovere `VoodbuilderLicense::validateKey()`. Separare
l'`autoload-dev` del monorepo. Dichiarare la politica semver e portare i vincoli dei companion a `^0.1`.

**Primo SKU a listino: VoodForms.** È il più maturo, è autonomo, non dipende dalle decisioni sul
builder, e ti fa imparare il flusso Anystack completo — prodotto, repository privato, chiave,
installazione dal lato cliente — su un pacchetto che non rischia di rompere nulla.

**Secondo: le tre edizioni del builder.** Community pubblica e gratuita, Pro e Agency su Anystack.
Qui serve anche la decina di item Elements sbloccati in Community (§5), altrimenti il livello gratuito
non dimostra niente.

**Terzo: VoodPopups**, che è veloce perché non ha dipendenze.

**Quarto: i test sui pacchetti che vendi.** `dynamic-data` e `components` sono a zero test e sono
dentro Pro e Agency. Ogni bug che scoprirà un cliente pagante ti costa più del tempo di scrivere i
test adesso.

**Quinto: il lavoro architetturale §4** (manifest, SDK, estrazione della UI commerciale dal core), che
è il prerequisito della fase 3 del marketplace e anche del code splitting rimasto (§1.4 dell'analisi).

**Sesto: il verticale eventi**, dopo aver sciolto l'accoppiamento `vevents` → `vpartners`/`vsponsors` e
portato i test a un livello decente.

---

## 10. Decisioni che servono da te

1. **Comportamento a licenza scaduta** (§8): separare authoring da rendering, allungare la grazia, o
   lasciare così e dichiararlo.
2. **Per sviluppatore o per sito.** Propongo per sviluppatore con siti illimitati; il per-sito richiede
   di costruire l'attivazione per dominio, che oggi non esiste.
3. **I prezzi.** 149 € Pro, 349 € Agency, 199 € VoodForms, 79 € VoodPopups, rinnovi al 60%.
4. **Finestra early adopter** sì o no, e a quale prezzo.
5. **VoodPopups: SKU a sé o incluso in Agency.** Incluso semplifica il listino, a sé aggiunge una voce
   di ricavo su un pacchetto poco testato.
6. **`vmedia` resta MIT** o passa a licenza commerciale insieme al verticale eventi.
