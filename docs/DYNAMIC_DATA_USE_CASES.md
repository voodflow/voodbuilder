# Dati dinamici: use case e binding

Documento di lavoro sul comportamento dei **tag / binding dinamici** in Voodbuilder (GrapesJS + Rich Text), con focus sugli use case reali — non solo sul meccanismo tecnico.

---

## Problema risolto

Scegliere “Users → Name” nel Rich Text (o un binding Users · Latest) mostrava il **nome dell’ultimo utente creato in database**, non quello dell’**utente autenticato** nella sessione.

Per un hero tipo *«Ciao Paolo»* o un CTA *«Vai al tuo profilo»* serve la sessione (`auth()`), non `User::latest('id')->first()`.

---

## Tre contesti per modello (use case)

Per ogni Model Integration (es. `users`, `tutorials`) esistono fino a tre source ID:

| Source | Chiave | Use case tipico |
|--------|--------|-----------------|
| **Utente autenticato** | `{alias}.auth` | Saluto, nome in header, link al proprio profilo. Solo se il modello è `Authenticatable` (es. `User`). |
| **Ultimo record** | `{alias}.latest` | Hero marketing: ultimo tutorial, ultimo iscritto in vetrina, “novità”. Query DB `latest('id')` (+ scope pubblici se presenti). |
| **Elemento lista** | `{alias}.item` | Dentro un `data-voodbuilder-repeat="{alias}.list"`: card ripetute, griglie, feed. |

Esempi HTML:

```html
<!-- Utente loggato (sessione) -->
<span data-voodbuilder-bind="users.auth.name" data-voodbuilder-hide-when-empty="1">[Users: Name]</span>

<!-- Ultimo record in DB (non la sessione) -->
<span data-voodbuilder-bind="users.latest.name">[Users: Name]</span>

<!-- Riga di una lista ripetuta -->
<div data-voodbuilder-repeat="users.list">
  <article data-voodbuilder-repeat-item>
    <h3 data-voodbuilder-bind="users.item.name">…</h3>
  </article>
</div>
```

---

## Come sceglie il picker Rich Text

Il menu raggruppa per **modello** (Users, Tutorials, …), non per “Latest / List item” duplicati.

Priorità automatica al click su un campo:

1. Se il Rich Text è **dentro** un repeat di quel modello → inserisce `.item`
2. Altrimenti, se esiste la source **`.auth`** (modello Authenticatable) → inserisce `.auth`
3. Altrimenti → inserisce `.latest`

Per Users, sotto i campi “default” (auth) il menu aggiunge anche voci esplicite:

- `Ultimo record · Name`
- `Ultimo record · Email`
- …

così resta possibile il caso marketing “ultimo iscritto” senza confonderlo col saluto.

I tag inseriti usano `data-voodbuilder-hide-when-empty="1"`: se guest / valore vuoto, il nodo viene rimosso in render pubblico.

---

## Visibilità di blocco (condizioni)

I binding riempiono **testo / href / src**. Per mostrare/nascondere **sezioni intere** (hero solo per loggati, banner guest, …) usare le condizioni esistenti:

- `data-voodbuilder-conditions` → chiave `user_logged_in`, ruolo, locale, path, date, …

Esempio: sezione “Ciao {name}” con condition `user_logged_in == 1` + bind `users.auth.name`.

---

## Label e URL indipendenti (CTA / link)

Su bottoni e link:

- `data-voodbuilder-bind` → label / testo
- `data-voodbuilder-bind-href` → URL

Possono essere uno statico e uno dinamico, o entrambi dinamici (anche da source diverse, es. label `users.auth.name` + href statico `/account`).

Dettaglio contratto: [BINDINGS.md](./BINDINGS.md).

---

## Pipeline render

1. Condizioni elemento  
2. Componenti / repeat  
3. **BindingRenderer** (`data-voodbuilder-bind` / `bind-href`)  
4. Blocchi server dinamici  

In editor (`?edit=1`) i binding si risolvono in preview quando il gate lo consente: per `.auth` si vede l’admin/utente con cui sei loggato mentre editi.

---

## File toccati (implementazione auth)

| Area | File |
|------|------|
| Source PHP | `ModelIntegrationAuthBindingSource.php` |
| Registrazione | `ModelIntegrationBindingRegistrar.php` (registra `.auth` se Authenticatable) |
| Picker RTE | `resources/js/grapesjs/rich-text-dynamic-tags.js` |
| Label editor | `GrapesJsEditorGate.php` + `lang/*/pro.php` + `lang/*/model_integrations.php` |
| Test | `tests/Unit/ModelIntegrationAuthBindingSourceTest.php` |

---

## Checklist use case

| Obiettivo | Cosa usare |
|-----------|------------|
| «Ciao {nome}» | `users.auth.name` + (opz.) condition logged-in |
| «Vai al tuo profilo» | CTA: label statica o `users.auth.name`, href statico `/account` o campo URL auth |
| «Ultimo tutorial» | `vtuts.latest.title` / `url` / `image` |
| «Ultimo iscritto in vetrina» | picker → **Ultimo record · Name** → `users.latest.name` |
| Griglia utenti / tutorial | repeat `{alias}.list` + bind `{alias}.item.*` |
| Niente testo se guest | `hide-when-empty` e/o conditions `user_logged_in` |

---

## Related

- [BINDINGS.md](./BINDINGS.md) — contratto attributi, API, repeat
- [GRAPESJS.md](./GRAPESJS.md) — editor e pipeline pagina
