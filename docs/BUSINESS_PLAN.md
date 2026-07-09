# VoodMedical — Business Plan

> **Versione:** 1.0 — luglio 2026  
> **Scopo:** documento di presentazione per partner, investitori e primi clienti pilota.  
> **Stato progetto:** mockup mobile funzionante + specifiche backend complete; backend in sviluppo.

---

## Executive summary

**VoodMedical** è una piattaforma SaaS white-label per **informatori scientifici del farmaco (ISF)**. Non è un CRM generico: integra in un unico prodotto ciò che oggi le aziende pharma gestiscono con strumenti separati (CRM medici, agenda visite, magazzino campioni, analytics territorio, note spese, suggerimenti AI).

| Elemento | Valore |
|----------|--------|
| Mercato target iniziale | PMI pharma e divisioni commerciali italiane (50–500 ISF) |
| Modello di ricavo | Canone piattaforma + utenti attivi + moduli + setup dedicato |
| Investimento stimato fino al go-live | €300.000 – €440.000 |
| Ricavo per cliente medio (80 utenti) | ~€27.000/anno (~€2.300/mese) |
| Break-even operativo stimato | 5 clienti SaaS o 2–3 installazioni dedicate |
| Valore già creato (mockup + architettura) | ~€90.000 – €130.000 equivalenti di mercato |

**Opportunità per un partner/finanziatore:** accelerare lo sviluppo (fasi 1–3 del backend), partecipare alla progettazione del prodotto, ottenere periodo gratuito o scontato sulla licenza, e diventare **cliente pilota** con case study nel settore pharma.

---

## 1. Il problema

Le aziende farmaceutiche con rete di informatori scientifici affrontano oggi:

| Problema | Impatto |
|----------|---------|
| Strumenti frammentati | CRM, Excel, email, app magazzino non parlano tra loro |
| Visibilità debole sul territorio | Supervisor e area manager non hanno KPI in tempo reale |
| Magazzino campioni complesso | FIFO, lotti, scorte agente, richieste refill — spesso su fogli |
| Pianificazione visite inefficiente | Agente non sa chi visitare, cosa portare, quando |
| Costi IT elevati | Soluzioni enterprise (Veeva, Salesforce) fuori portata per PMI |
| Dati non sfruttati | Report farmacie, visite, campioni non aggregati per decisioni |

**VoodMedical** risolve questo con una piattaforma verticale, modulare, disponibile in **SaaS multi-tenant** o **installazione dedicata** sui nostri server.

---

## 2. La soluzione — come funziona

### 2.1 Cosa fa la piattaforma

```
┌─────────────────────────────────────────────────────────────┐
│  PLATFORM LAYER                                              │
│  Super Admin · Billing · Onboarding tenant · White-label     │
├─────────────────────────────────────────────────────────────┤
│  TENANT (es. Azienda Pharma X)                               │
│  Branding custom · Catalogo prodotti · Rete medici · Team    │
├─────────────────────────────────────────────────────────────┤
│  MODULI ATTIVABILI                                           │
│  CRM · Visite · Magazzino · Analytics · Fleet · AI · Billing │
└─────────────────────────────────────────────────────────────┘
         │                              │
         ▼                              ▼
   App mobile ISF              Pannello admin web
   (iOS/Android/PWA)          (FilamentPHP / browser)
```

### 2.2 Ruoli e flussi principali

| Ruolo | Cosa fa nell'app |
|-------|------------------|
| **Informatore (ISF)** | Visite, medici, appuntamenti, campioni, mappa, report giornaliero, VoodAI |
| **Supervisor** | Team, metriche agenti, approvazione refill campioni, spese |
| **Area Manager** | KPI per area geografica, confronto territori |
| **HR / Workforce** | Pianificazione risorse, copertura territoriale |
| **Magazzino** | Stock centrale, movimenti FIFO, QR scan, ordini produttore |
| **Analista dati** | Export, report vendite farmacie, dashboard aggregate |
| **Admin azienda** | Utenti, branding, aree, catalogo, configurazione |

### 2.3 Moduli commerciali

| Modulo | Funzionalità | Valore per il cliente |
|--------|--------------|----------------------|
| **Core + CRM + Visite + Team** | Medici, appuntamenti, assegnazioni, contatti | Base operativa quotidiana |
| **Magazzino** | FIFO, lotti, scorte agente, richieste refill | Controllo campioni e costi |
| **Analytics** | KPI agente/supervisor, vendite farmacie, export | Decisioni commerciali data-driven |
| **Fleet** | Tracciamento spostamenti, note spese | Rendicontazione e ottimizzazione giri |
| **AI (VoodAI)** | Suggerimenti visite, brief pre-visita, report intelligenti | Produttività agente +15–25% stimata |
| **Billing** | Abbonamenti, metering utenti/AI | Solo per modello SaaS |

### 2.4 Modalità di deploy

| Modalità | Per chi | Come si vende |
|----------|---------|---------------|
| **SaaS multi-tenant** | PMI pharma, startup commerciali | Abbonamento mensile/annuale |
| **Dedicato su nostri server** | Aziende con requisiti IT ma senza infrastruttura | Setup + canone mensile |
| **On-premise** (futuro) | Enterprise con VPN e policy rigide | Licenza annuale + supporto |

---

## 3. Stato attuale del progetto

### 3.1 Cosa è già stato fatto

| Asset | Dettaglio |
|-------|-----------|
| **App mobile mockup** | ~26.000 righe TypeScript, 34 schermate, 8 ruoli, PWA |
| **Funzionalità demo** | Agenda, mappa medici, magazzino, analytics, AI, spese, report giornaliero |
| **Documentazione tecnica** | ~2.750 righe: architettura backend, API, modello dati, roadmap |
| **Architettura definita** | Laravel + FilamentPHP 5, plugin modulari, multi-tenant |

### 3.2 Cosa manca per il go-live

| Voce | Stima |
|------|-------|
| Backend MVP (fasi 0–4) | 21–26 settimane, ~2.250 ore |
| Integrazione app ↔ API reali | inclusa nel backend + 250–350 ore frontend |
| Pubblicazione App Store / Play Store | Capacitor + QA, ~780–1.050 ore |
| Infrastruttura produzione | 2–4 settimane setup |

### 3.3 Valore già creato

Se lo stesso lavoro fosse stato commissionato a una software house:

| Voce | Ore equivalenti | Costo mercato |
|------|-----------------|---------------|
| Discovery + UX/UI | ~400 h | €25.000 – €35.000 |
| Sviluppo frontend mockup | ~840 h | €50.000 – €75.000 |
| Architettura + specifiche backend | ~140 h | €10.000 – €15.000 |
| **Totale** | **~1.380 h** | **€90.000 – €130.000** |

---

## 4. Mercato e posizionamento

### 4.1 Target clienti

| Segmento | Dimensione tipica | Pain point principale | Canale |
|----------|-------------------|----------------------|--------|
| **PMI pharma italiana** | 30–150 ISF | Costo CRM enterprise, Excel | Referral, congressi, LinkedIn |
| **Divisione commerciale mid-market** | 80–300 ISF | Visibilità territorio, campioni | Demo pilota, partner IT |
| **Gruppo pharma multi-brand** | 200–1.000 ISF | White-label per divisioni | Vendita diretta, RFP |
| **Startup commerciali / CRO** | 10–50 ISF | Tool leggero e veloce | SaaS self-service |

### 4.2 Concorrenza

| Competitor | Punti di forza | Debolezza vs VoodMedical |
|------------|----------------|--------------------------|
| **Veeva CRM** | Standard enterprise pharma | Costo altissimo, complessità, overkill per PMI |
| **Salesforce + custom** | Flessibile | Costo setup, non verticale ISF |
| **CRM generici** (HubSpot, Zoho) | Economici | Nessun magazzino campioni, nessuna gerarchia ISF |
| **Excel + WhatsApp** | Gratis | Nessuna visibilità, errori, non scalabile |

**Posizionamento VoodMedical:** *"La piattaforma verticale per ISF che le PMI pharma possono permettersi — con la profondità funzionale che i CRM generici non hanno."*

### 4.3 Dimensione mercato (stima Italia)

| Metrica | Stima |
|---------|-------|
| Aziende pharma con rete ISF in Italia | ~200–400 |
| Target addressable (PMI + mid-market) | ~80–150 aziende |
| ISF totali in Italia | ~15.000–25.000 |
| Spesa media annua IT commerciale per azienda mid | €50.000 – €200.000 |
| **TAM servibile (5 anni)** | **€8M – €25M/anno** |

---

## 5. Modello di ricavo

### 5.1 Fonti di ricavo

```
                    RICAVI VOODMEDICAL
                           │
        ┌──────────────────┼──────────────────┐
        ▼                  ▼                  ▼
   ABBONAMENTO         SETUP            SERVIZI
   RICORRENTE         UNA TANTUM         AGGIUNTIVI
        │                  │                  │
   ├─ Canone base    ├─ Migrazione dati  ├─ Integrazioni custom
   ├─ Per utente     ├─ White-label      ├─ Formazione extra
   ├─ Moduli extra   ├─ Infrastruttura   ├─ Sviluppo su misura
   └─ AI premium     └─ Training         └─ Supporto premium
```

### 5.2 Listino SaaS (indicativo)

| Piano | Canone piattaforma | Per utente/mese | Moduli inclusi |
|-------|-------------------|-----------------|----------------|
| **Starter** | €149/mese | €22 | CRM + Visite + Team |
| **Professional** | €199/mese | €26 | + Magazzino + Analytics |
| **Enterprise** | €299/mese | €30 | Tutti + SLA prioritario |

**Moduli aggiuntivi:**

| Modulo | Prezzo |
|--------|--------|
| Magazzino avanzato | +€299/mese |
| Analytics / Analista | +€199/mese |
| AI Premium (VoodAI) | +€8/utente o +€399/mese flat |
| Fleet (spostamenti + spese) | +€149/mese |

### 5.3 Esempi di ricavo per cliente

| Profilo cliente | Utenti | Piano | Canone mensile | **Ricavo annuo** |
|-----------------|--------|-------|----------------|------------------|
| Startup commerciale | 25 | Starter | €699 | **€8.400** |
| PMI pharma | 80 | Professional | €2.279 | **€27.350** |
| Mid-market | 150 | Professional + AI | €4.348 | **€52.200** |
| Divisione gruppo | 300 | Enterprise + moduli | €9.649 | **€115.800** |

### 5.4 Installazione dedicata (Azienda X sui nostri server)

| Voce | Una tantum | Ricorrente/mese |
|------|------------|-----------------|
| Setup (migrazione, branding, training) | €25.000 – €40.000 | — |
| Hosting dedicato HA | — | €600 – €1.000 |
| Licenza piattaforma | — | €800 – €1.500 |
| Manutenzione + supporto 8×5 | — | €1.200 – €2.500 |
| AI Premium | — | €200 – €400 |
| **Totale tipico** | **€25.000 – €40.000** | **€2.800 – €5.400/mese** |
| **Ricavo anno 1 (dedicato)** | | **€58.600 – €105.000** |

---

## 6. Scenari di ricavo — proiezioni 3 anni

### 6.1 Ipotesi comuni

| Parametro | Valore |
|-----------|--------|
| Churn annuo SaaS | 10–15% |
| Crescita utenti per cliente | +10%/anno |
| Upsell moduli | 30% clienti anno 2 |
| Costo acquisizione cliente (CAC) | €5.000 – €15.000 |
| Tempo medio chiusura vendita | 3–6 mesi |

### 6.2 Scenario conservativo

| Anno | Clienti SaaS | Clienti dedicati | MRR fine anno | **ARR** |
|------|----------------|------------------|---------------|---------|
| 1 | 2 | 1 | €6.500 | **€78.000** |
| 2 | 5 | 2 | €16.000 | **€192.000** |
| 3 | 9 | 3 | €28.000 | **€336.000** |

### 6.3 Scenario base (realistico con partner pilota)

| Anno | Clienti SaaS | Clienti dedicati | MRR fine anno | **ARR** |
|------|----------------|------------------|---------------|---------|
| 1 | 4 | 1 | €12.000 | **€144.000** |
| 2 | 10 | 3 | €32.000 | **€384.000** |
| 3 | 18 | 5 | €58.000 | **€696.000** |

### 6.4 Scenario aggressivo (con investimento + sales)

| Anno | Clienti SaaS | Clienti dedicati | MRR fine anno | **ARR** |
|------|----------------|------------------|---------------|---------|
| 1 | 8 | 2 | €24.000 | **€288.000** |
| 2 | 20 | 5 | €62.000 | **€744.000** |
| 3 | 35 | 8 | €115.000 | **€1.380.000** |

### 6.5 Grafico ricavi cumulativi (scenario base)

```
ARR (€)
700k │                                          ████
600k │                                     ████
500k │                                ████
400k │                           ████
300k │                      ████
200k │                 ████
100k │            ████
  0  └──────┬──────┬──────┬──────
           Anno 1  Anno 2  Anno 3
```

---

## 7. Costi e investimento

### 7.1 Investimento fino al go-live

| Voce | Costo |
|------|-------|
| Lavoro già svolto (mockup + docs) | €90.000 – €130.000 *(già fatto)* |
| Backend MVP (fasi 0–4) | €140.000 – €200.000 |
| App store-ready (Capacitor + QA) | €53.000 – €82.000 |
| Setup infrastruttura | €15.000 – €25.000 |
| **Totale da oggi al go-live** | **€208.000 – €307.000** |
| **Totale progetto (incluso già fatto)** | **€298.000 – €437.000** |

### 7.2 Costi operativi mensili post-launch

| Voce | Avvio (≤5 clienti) | Scalato (10–20 clienti) |
|------|--------------------|-------------------------|
| Infrastruttura cloud (ridondante + backup) | €450 – €680 | €1.200 – €2.000 |
| AI (LLM per tutti i tenant) | €100 – €400 | €500 – €2.000 |
| Team minimo (0,5 dev + supporto) | €4.000 – €6.000 | €8.000 – €12.000 |
| Tooling, dominio, email, monitoring | €200 – €400 | €400 – €800 |
| **Totale OPEX mensile** | **€4.750 – €7.480** | **€10.100 – €16.800** |

### 7.3 Break-even

| Scenario | Clienti necessari | MRR necessario | Note |
|----------|------------------|----------------|------|
| Coprire OPEX minimo | ~3 clienti medi | ~€7.000/mese | Senza ammortizzare investimento |
| Break-even operativo sostenibile | ~5 clienti medi | ~€11.500/mese | Con 0,5 FTE dev |
| Break-even con team completo | ~8–10 clienti | ~€18.000/mese | 2 dev + supporto |
| ROI investimento in 3 anni | ~12 clienti attivi | ~€30.000/mese MRR | Scenario base anno 3 |

---

## 8. Proposta per partner / finanziatore

### 8.1 Cosa offriamo al partner

| Beneficio | Dettaglio |
|-----------|-----------|
| **Voce in capitolo** | Partecipazione a design review prodotto (4–6 sessioni) |
| **Licenza agevolata** | Anno 1: gratis o -50% · Anno 2: -25/50% |
| **Setup dedicato scontato** | -30/50% se diventa tenant pilota |
| **Priorità roadmap** | Le sue esigenze entrano nel backlog prioritario |
| **Case study** | Visibilità come early adopter nel settore |
| **Equity o revenue share** | Da negoziare: 5–15% equity **oppure** 10–20% revenue share per 3 anni |

### 8.2 Cosa chiediamo al partner

| Contributo | Range |
|------------|-------|
| Investimento in sviluppo | €50.000 – €150.000 |
| Tempo per co-progettazione | ~8 h/mese per 6–12 mesi |
| Accesso a dati/rete per pilota | Rete ISF reale per validazione |
| Referenza commerciale | Introduzione ad altre aziende pharma |

### 8.3 Esempio concreto — Partner Pharma con 80 ISF

**Senza partner (SaaS standard):**
- Setup: €0
- Canone: €2.279/mese × 12 = €27.350/anno

**Con partner (pilota):**
- Investimento una tantum: €75.000
- Anno 1 licenza: €0 (gratis)
- Anno 2 licenza: €13.675 (-50%)
- Anno 3+ licenza: €27.350 (prezzo pieno)
- Equity: 8% **oppure** 15% revenue share su altri clienti da sua intro

**Valore per il partner:**
- Risparmio licenza anni 1–2: ~€41.000
- Prodotto su misura per la sua rete ISF
- Influenza sul prodotto verticale pharma
- Possibile ritorno da equity se il progetto scala

**Valore per VoodMedical:**
- €75.000 per accelerare backend fasi 1–3 (~6 mesi risparmiati)
- Cliente pilota reale con feedback
- Validazione commerciale + referenza
- Primo caso studio per vendite

---

## 9. Roadmap e milestone

### 9.1 Timeline tecnica

| Fase | Periodo | Deliverable | Investimento |
|------|---------|-------------|--------------|
| **0 — Fondamenta** | Mesi 1–2 | Core Laravel, auth, multi-tenant, Filament | €25.000 – €35.000 |
| **1 — MVP operativo** | Mesi 2–5 | CRM, visite, team, API mobile, admin base | €50.000 – €70.000 |
| **2 — Magazzino** | Mesi 5–6 | FIFO, movimenti, QR, ruoli warehouse | €20.000 – €28.000 |
| **3 — Intelligence** | Mesi 6–8 | Notifiche, AI rule-based, analytics | €28.000 – €40.000 |
| **4 — SaaS commerciale** | Mesi 8–9 | Billing, metering, platform admin | €20.000 – €28.000 |
| **5 — Store + go-live** | Mesi 9–11 | Capacitor, App Store, Play Store | €53.000 – €82.000 |
| **Go-live** | **Mese 11–12** | **Primo cliente pagante** | |

### 9.2 Milestone commerciali

| Milestone | Quando | Obiettivo |
|-----------|--------|-----------|
| Demo pilota con partner | Mese 6 | Backend MVP + app collegata |
| Primo contratto pagante | Mese 11–12 | Go-live |
| 3 clienti attivi | Mese 18 | Break-even OPEX |
| 10 clienti attivi | Mese 30 | Team completo sostenibile |
| Espansione EU (DE, FR) | Mese 36+ | Multi-lingua, GDPR EU |

---

## 10. Rischi e mitigazioni

| Rischio | Probabilità | Impatto | Mitigazione |
|---------|-------------|---------|-------------|
| Tempi sviluppo backend più lunghi | Media | Alto | Partner pilota finanzia MVP; scope ridotto fase 1 |
| Resistenza al cambio (ISF) | Alta | Medio | UX già validata nel mockup; onboarding guidato |
| Concorrenza Veeva/Salesforce | Bassa (PMI) | Medio | Prezzo 10× inferiore, verticale ISF |
| Costi AI imprevisti | Bassa | Basso | AI rule-based gratis; LLM solo su piano premium |
| GDPR / dati medici | Media | Alto | Architettura tenant isolation, DPA, audit log |
| Churn alto anno 1 | Media | Alto | Setup dedicato, supporto, pilota con feedback continuo |

---

## 11. Team necessario

### 11.1 Fase sviluppo (fino al go-live)

| Ruolo | FTE | Durata |
|-------|-----|--------|
| Tech Lead / Architect Laravel | 0,5 | 12 mesi |
| Senior Backend Developer | 2 | 9 mesi |
| Senior Frontend / Mobile | 1 | 6 mesi |
| DevOps | 0,25 | 12 mesi |
| QA | 0,25 | 6 mesi |
| Product Owner | 0,5 | 12 mesi |
| **Totale** | **~4,5 FTE** | |

### 11.2 Fase operativa (post-launch)

| Ruolo | FTE | Costo mensile |
|-------|-----|---------------|
| Full-stack developer | 1 | €5.000 – €7.000 |
| Supporto / onboarding clienti | 0,5 | €2.000 – €3.000 |
| Commerciale / BD pharma | 0,5–1 | €3.000 – €6.000 |
| **Totale team minimo** | **2–2,5 FTE** | **€10.000 – €16.000/mese** |

---

## 12. Sintesi per la conversazione

### Cosa dire al tuo amico in 2 minuti

> *"Ho costruito il prototipo completo di una piattaforma SaaS per informatori scientifici del farmaco. Oggi funziona come demo con dati fake — 34 schermate, 8 ruoli diversi, magazzino campioni, AI, analytics. Vale già ~€100k di lavoro professionale.*
>
> *Manca il backend vero e la pubblicazione sugli store — altri €200–300k di investimento, 9–12 mesi con un team.*
>
> *Il modello di ricavo è abbonamento: ~€2.300/mese per un'azienda con 80 informatori. Con 5 clienti copriamo i costi fissi, con 10–12 siamo in pareggio sull'investimento.*
>
> *Se entra come partner pilota — mette €50–150k, partecipa al design, ha l'app gratis il primo anno — accelera tutto di 6 mesi e diventa il nostro caso studio nel pharma."*

### Numeri chiave da ricordare

| Domanda | Risposta |
|---------|----------|
| Quanto vale già oggi? | ~€100.000 |
| Quanto serve per andare live? | ~€200–300k + 9–12 mesi |
| Quanto paga un cliente? | €700–2.300/mese (SaaS) o €2.800–5.400/mese (dedicato) |
| Quanti clienti per il pareggio? | 5 (OPEX) · 10–12 (ROI investimento) |
| Cosa offriamo al partner? | Anno 1 gratis, voce nel prodotto, equity/revenue share |
| Cosa chiediamo? | €50–150k + rete ISF per pilota |

---

## 13. Prossimi passi

| # | Azione | Responsabile | Quando |
|---|--------|--------------|--------|
| 1 | Validare interesse partner e profilo (SaaS vs dedicato) | Founder | Questa settimana |
| 2 | Definire term sheet partner (investimento, equity, sconti) | Founder + partner | 2 settimane |
| 3 | Avviare backend fase 0 (Laravel core) | Dev team | Al closing partner |
| 4 | Demo backend MVP al partner | Dev team | Mese 6 |
| 5 | Go-live con partner come cliente pilota | Tutti | Mese 11–12 |

---

## Allegati e riferimenti

| Documento | Contenuto |
|-----------|-----------|
| `docs/BACKEND_SPEC.md` | Specifica backend completa, API, modello dati |
| `docs/BACKEND_PLAN_FILAMENT5.md` | Architettura plugin, roadmap tecnica, pricing moduli |
| `docs/ORGANIZATIONAL_STRUCTURE.md` | Gerarchia commerciale, aree, HR |
| `docs/CONTACT_TRACKING.md` | Tracciamento chiamate e email |
| `mobile-app/` | Mockup funzionante (avviare con `npm run dev`) |

---

*VoodMedical Business Plan v1.0 — luglio 2026*  
*Documento confidenziale — per uso interno e presentazione a partner selezionati.*
