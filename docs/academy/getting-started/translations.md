---
title: Translations
description: Create and manage localized page and menu variants.
---

# Translations

When your host application enables **locales**, VoodBuilder lets you maintain separate content for each language.

## What you can translate (core)

| Content | How |
|---------|-----|
| **Site pages** | Create translation variants from the page list or edit screen |
| **Menus** | Clone or translate menu trees per locale |
| **Chrome layouts** | Localized layout variants when your project supports them |

Exact action labels depend on your Filament resources — look for **Create translation**, **Clone for locale**, or locale tabs on edit forms.

## Typical workflow

1. Build and publish the **default locale** page (usually English).
2. From the page list, open **Create translation** (or equivalent) and choose the target locale.
3. Open the translated page in the visual editor and adapt copy — structure can match the original or diverge.
4. Repeat for menus so navigation labels match each locale.
5. View the public site with your app’s locale switcher or locale-prefixed URLs.

## Editor behaviour

- The canvas shows content for the locale you are editing.
- **Conditions** can include locale rules (e.g. show a banner only on `it`). See [Visibility conditions](../builder/visibility-conditions).
- Dynamic bindings resolve against the same models regardless of locale; translate static labels in the editor.

## Tips

- Keep slugs consistent across translations when URLs are locale-prefixed (`/en/about`, `/it/about`).
- Translate chrome (header/footer) in the **layout editor** for each locale variant.
- After large copy changes, preview the public page without `?edit=1` for each locale.

Next: [Builder overview](../builder/)
