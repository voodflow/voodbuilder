# Dati dinamici: use case e binding

Documento di lavoro sul comportamento dei **tag / binding dinamici** in Voodbuilder (GrapesJS + Rich Text), con focus sugli use case reali — non solo sul meccanismo tecnico.

---

## Problema risolto

Scegliere “Users → Name” nel Rich Text (o un binding Users · Latest) mostrava il **nome dell’ultimo utente creato in database**, non quello dell’**utente autenticato** nella sessione.

Per un hero tipo *«Ciao Paolo»* o un CTA *«Vai al tuo profilo»* serve la sessione (`auth()`), non `User::latest('id')->first()`.

---

## Global text tags (site-wide)

Plain curly tags available in **any** chrome/page HTML text (footer copyright, taglines, free text). Not the same as `data-voodbuilder-bind` model bindings.

| Tag | Resolves to |
|-----|-------------|
| `{current_year}` | Calendar year (`date('Y')`) |
| `{brand_name}` | Settings brand name |
| `{site_name}` | SEO site name, else site title |
| `{site_url}` | `APP_URL` |

Example copyright: `© {current_year} {brand_name}` → `© 2026 VoodBuilder`.

Resolved at render time by `GlobalTextTags` (Blade helpers + chrome/page HTML pipeline). In the layout editor, typing `{current_year}` in copyright works; saving a resolved `© 2026 …` is re-tagged to keep the year dynamic.

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

Stile **Bricks**: gruppo dedicato in cima, poi i modelli.

| Voce menu | Source | Significato |
|-----------|--------|-------------|
| **Profilo utente** | `{alias}.auth` | Utente della sessione (`auth()->user()`), come “User profile” in Bricks |
| **Users / Tutorials / …** | `.latest` oppure `.item` | Record DB: ultimo fuori lista, elemento corrente dentro un repeat |

Priorità automatica (Make dynamic / insert “smart”):

1. Dentro un repeat di quel modello → `.item`
2. Altrimenti, se esiste `.auth` → `.auth`
3. Altrimenti → `.latest`

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
