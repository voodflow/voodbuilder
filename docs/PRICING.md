# VoodBuilder — listino confermato

**Decisione:** 15 settembre 2026  
**Unità:** seat sviluppatore · progetti/siti illimitati · rinnovo annuale (~60%)

| Edizione | Prezzo | Seat | Bundle companion |
|---|---|---|---|
| **Community** | 0 € | — | `vmedia`, `vcookiebar` (MIT) |
| **Developer** | **149 €/anno** | 1 | Elements · Dynamic Data · Templates authoring |
| **Agency** | **399 €/anno** | fino a 5 | Tutto Developer + Components · Dynamic API · export/share · `pages.custom-js` · **vpopups incluso** |

Runtime: AnyStack può inviare `edition: "developer"` o `"professional"` (alias → stessa matrice). Agency = `agency`.

## Cosa perde chi non rinnova

- Niente aggiornamenti Composer privati / catalogo remoto nuovo.
- Authoring torna a Community quando AnyStack risponde `active: false`.
- **Pagine pubblicate invariate** (List repeat, binding, HTML). Eccezione deliberata: JS d’autore (`AuthorScriptPolicy`) resta gated in scrittura e in lettura pubblica.
- Outage AnyStack: **fail-open** sull’ultimo snapshot valido (non degrada a Community solo perché l’endpoint è giù).

## SKU fuori bundle

| Prodotto | Prezzo | Nota |
|---|---|---|
| VoodForms | 199 €/anno / seat | Autonomo |
| VoodPopups | 79 €/anno / seat | Incluso in Agency |
| vdocs / vtuts | listino proprio | — |

Vedi anche [ANYSTACK_SETUP.md](./ANYSTACK_SETUP.md) per prodotti e entitlements.
