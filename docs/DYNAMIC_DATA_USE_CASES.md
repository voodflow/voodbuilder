# Dati dinamici: use case e binding

Documento di lavoro sul comportamento dei **tag / binding dinamici** in Voodbuilder (Editor + Rich Text), con focus sugli use case reali — non solo sul meccanismo tecnico.

> **Nota:** la larghezza contenuto (full / 80rem / custom) è un controllo di *layout*, non un binding. Vedi [CONTENT_WIDTH.md](./CONTENT_WIDTH.md).

---

## Problema risolto

Scegliere “Users → Name” nel Rich Text (o un binding Users · Latest) mostrava il **nome dell’ultimo utente creato in database**, non quello dell’**utente autenticato** nella sessione.

Per un hero tipo *«Ciao Paolo»* o un CTA *«Vai al tuo profilo»* serve la sessione (`auth()`), non `User::latest('id')->first()`.

---

## Global text tags (site-wide)

Plain curly tags for **any** Editor text / rich text / chrome string. **Not** the same as `data-voodbuilder-bind` (“Make dynamic” on news/articles/users).

| Tag | Resolves to |
|-----|-------------|
| `{current_year}` | Calendar year (`date('Y')`) |
| `{brand_name}` | Settings brand name |
| `{site_name}` | SEO site name, else site title |
| `{site_url}` | `APP_URL` |
| `{logged_username}` | Authenticated user `name` (fallback `username`); **empty string** if guest or missing |

Example (text or rich text block):

```text
Ciao {logged_username}, il {current_year} è il tuo anno!
```

→ logged-in Paolo: `Ciao Paolo, il 2026 è il tuo anno!`  
→ guest: `Ciao , il 2026 è il tuo anno!` (use a `user_logged_in` condition if you want to hide the whole sentence)

Copyright default: `© {current_year} {brand_name}` → `© 2026 VoodBuilder`.

### Where they apply

| Surface | Resolved by |
|---------|-------------|
| Page content (text + rich text HTML) | `EditorRenderer::render()` → `GlobalTextTags::replaceInHtml()` |
| Site chrome (nav/footer layouts) | `EditorChromeHtmlPipeline` |
| Footer copyright / tagline helpers | `SiteFooterConfig::resolveCopyright()` / `resolveTagline()` |
| Public page with `?edit=1` preview | `EditorGate` (same PHP replace) |

### Editor UX

- In the Editor canvas, leave `{tags}` visible so authors see the tokens.
- Copyright fields that show a resolved `© 2026` are re-tagged on save (`retagCurrentYear`) so the year stays dynamic.
- Values for preview helpers are also passed to the editor as `globalTextTags` (including `logged_username` for the current admin session).

### vs model bindings

| Need | Use |
|------|-----|
| Year, brand, site URL, quick hello with username | Global text tags `{…}` |
| News/article fields, typed fields, href/src, hide-when-empty | Make dynamic → `data-voodbuilder-bind` |
| Hide whole section for guests | `data-voodbuilder-conditions` (`user_logged_in`) |

### Performance

One `strtr` pass over the final HTML string — no DOM walk, no extra JS libs. Negligible cost.

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
5. **GlobalTextTags** (`{current_year}`, `{logged_username}`, …) su tutto l’HTML risultante  

In editor (`?edit=1`) i binding si risolvono in preview quando il gate lo consente: per `.auth` si vede l’admin/utente con cui sei loggato mentre editi.

---

## File toccati (implementazione auth)

| Area | File |
|------|------|
| Source PHP | `ModelIntegrationAuthBindingSource.php` |
| Registrazione | `ModelIntegrationBindingRegistrar.php` (registra `.auth` se Authenticatable) |
| Picker RTE | `resources/js/editor/rich-text-dynamic-tags.js` |
| Label editor | `EditorGate.php` + `lang/*/pro.php` + `lang/*/model_integrations.php` |
| Test | `tests/Unit/ModelIntegrationAuthBindingSourceTest.php` |

---

## Checklist use case

| Obiettivo | Cosa usare |
|-----------|------------|
| «Ciao {nome}» (semplice) | Global tag `{logged_username}` nel testo / rich text |
| «Ciao {nome}» (campo modello + hide) | `users.auth.name` + (opz.) condition logged-in |
| «Vai al tuo profilo» | CTA: label statica o `users.auth.name`, href statico `/account` o campo URL auth |
| «Ultimo tutorial» | `vtuts.latest.title` / `url` / `image` |
| «Ultimo iscritto in vetrina» | picker → **Ultimo record · Name** → `users.latest.name` |
| Griglia utenti / tutorial | repeat `{alias}.list` + bind `{alias}.item.*` |
| Niente testo se guest | `hide-when-empty` e/o conditions `user_logged_in` |

---

## Related

- [BINDINGS.md](./BINDINGS.md) — contratto attributi, API, repeat
- [EDITOR.md](./EDITOR.md) — editor e pipeline pagina
