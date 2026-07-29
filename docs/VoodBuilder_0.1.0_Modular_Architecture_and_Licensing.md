# VoodBuilder 0.1.0 — Modular Architecture, Licensing and Commercial Product Specification

**Document type:** technical architecture plan, refactoring brief for Cursor, licensing model and commercial baseline  
**Target branch:** `refactor/modular-architecture`  
**Target version:** `0.1.0`  
**Current version:** `0.0.11`  
**Status:** commercial plugin wave in progress (Phases 0–11 largely done; companion packages live)  
**Last progress update:** 2026-07-29  
**Primary goal:** transform the current monolithic VoodBuilder package into a modular, extensible and licensable platform without changing existing behaviour or breaking compatibility.

---

# 0. Where we are (2026-07-29) — read this first

Living detail also lives in [`docs/progress/commercial-plugin-wave.md`](progress/commercial-plugin-wave.md) and the workstation handoff in **§27** below.

## Done

| Area | State |
|---|---|
| Internal modular Core (modules, capabilities, editor split) | Done on `refactor/modular-architecture` |
| Popups companion | **Pushed** `voodflow/voodbuilder-popups` — owns UI/runtime |
| Components companion | **Pushed** `voodflow/voodbuilder-components` — full library + soft-gate when plugin off |
| Dynamic Data companion | **Pushed** `voodflow/voodbuilder-dynamic-data` — all DD behind plugin; List repeat still needs Pro entitlement |
| Templates companion | **Pushed** `voodflow/voodbuilder-templates` — authoring only; Core keeps list + **install-from-URL** (marketplace) |
| Forms / Analytics / Cookiebar | **Pushed scaffolds only** (`forms`, `analitycs` spelling, `cookiebar`) |
| Components UX polish | Compatibility false positives reduced; import copy + icon contrast improved (Core) |

**Commercial gate pattern (settled):** Composer package alone is not enough. Host must register `*Plugin::make()` on the Filament panel. Soft-gate the editor UI to match what the API can actually do.

## Not done / continue here

1. **Analytics** — implement page analytics on top of popup analytics (`voodbuilder-analitycs`).
2. **Cookiebar** — real consent bar (block GA / Meta Pixel / embeds; disclose media; no Google Fonts dep in Core).
3. **Forms** — invent last (`voodbuilder-forms` scaffold already exists).
4. **Templates marketplace URL** — optional signed/one-shot install links (today: HTTPS + anti-SSRF only).
5. **Packaging** — still path-repo clones under `packages/voodflow/` (not Packagist yet).
6. **Edition matrices in §7.x** — still describe older Community/Pro/Agency tables; runtime gates for Components / Dynamic Data / Templates authoring are now **plugin-based**. Treat §7 as product intent; treat companion READMEs + `commercial-plugin-wave.md` as runtime truth until matrices are rewritten.

## Remote push checklist (2026-07-29)

| Repo | Remote | Branch | Pushed? |
|---|---|---|---|
| `voodbuilder` (Core) | `git@voodflow-git:voodflow/voodbuilder.git` | `refactor/modular-architecture` | Yes (push required for workstation; see §27) |
| `voodbuilder-popups` | `…/voodbuilder-popups.git` | `main` | Yes |
| `voodbuilder-components` | `…/voodbuilder-components.git` | `main` | Yes |
| `voodbuilder-dynamic-data` | `…/voodbuilder-dynamic-data.git` | `main` | Yes |
| `voodbuilder-templates` | `…/voodbuilder-templates.git` | `main` | Yes |
| `voodbuilder-forms` | `…/voodbuilder-forms.git` | `main` | Yes (scaffold) |
| `voodbuilder-cookiebar` | `…/voodbuilder-cookiebar.git` | `main` | Yes (scaffold) |
| `voodbuilder-analitycs` | `…/voodbuilder-analitycs.git` | `main` | Yes (scaffold) |

---

# 1. Executive summary

VoodBuilder is not simply a visual page builder.

It is a **Visual Website Platform for Laravel and Filament**, combining:

- visual page editing;
- page management;
- automatic routing;
- menus;
- navigation and footer composition;
- multiple layouts;
- multiple themes assigned to different content areas;
- theme cloning and colour configuration;
- dynamic data binding to Filament models;
- conditional visibility;
- reusable components;
- complete page templates;
- Tailwind CSS styling and JIT compilation;
- animations;
- history and saved revisions;
- popup building and popup analytics;
- multilingual content;
- extension points for external plugins;
- future template, component and plugin marketplace support.

The refactoring must not start by physically splitting the repository into many Composer packages.

The correct sequence is:

1. audit the current monolith;
2. define stable contracts;
3. create a module registry;
4. move existing functionality behind modules;
5. add feature and entitlement resolution;
6. extract one pilot plugin;
7. extract Popups as the first commercial package;
8. prepare an SDK for third-party plugins and marketplace assets.

The current Editor integration is a particularly high-risk area. Editor itself has deliberately not been patched, which is positive, but VoodBuilder customisation currently lives largely in a JavaScript bundle of more than 2.5 MB. This bundle must be audited and modularised gradually, with strong regression testing and no behavioural rewrite during the first phase.

---

# 2. Product identity and positioning

## 2.1 Product name

**VoodBuilder**

## 2.2 Primary positioning

> **The Website Platform for Filament.**

Alternative descriptive line:

> Build pages, manage content, create themes and publish complete websites directly from Filament.

## 2.3 What VoodBuilder is

VoodBuilder is:

- a visual editor;
- a page and layout CMS;
- a theme orchestration system;
- a dynamic data rendering layer;
- a component and template platform;
- an extensible runtime for official and third-party plugins.

## 2.4 What VoodBuilder is not

VoodBuilder is not:

- a generic WordPress clone;
- a single-page landing page editor;
- a SaaS-only hosted builder;
- a theme that replaces Laravel or Filament;
- a closed runtime that prevents developers from writing Laravel code;
- a collection of unrelated premium widgets.

## 2.5 Main differentiators

The most important differentiators are:

1. **Filament-native architecture**
2. **Laravel-native data and routing**
3. **Multiple layouts for different website areas**
4. **Multiple themes mapped visually to content channels**
5. **Dynamic binding to existing Filament models**
6. **Tailwind-native styling**
7. **Visual conditions and data-aware rendering**
8. **Reusable components and page templates**
9. **Plugin architecture designed for Laravel packages**
10. **No direct patching of Editor core**

---

# 3. Existing technical baseline

| Area | Current status |
|---|---|
| Laravel host application | `v13.23.0`, constraint `^13.8` |
| Package Laravel contracts | `illuminate/contracts ^12\|^13` |
| Filament | `^5.0`, currently installed `v5.7.3` |
| Minimum PHP | `^8.4` |
| Editor app constraint | `^0.22.12` |
| Editor lock version | `0.22.16` |
| Installer Editor declaration | also references `^0.23.2`; must be aligned |
| Editor bundling | Vite |
| React theme map | separate esbuild bundle |
| React libraries | React and `@xyflow/react` |
| Package development | path repository inside Laravel demo app |
| PHP tests | PHPUnit 11 and Orchestra Testbench |
| Frontend tests | minimal Vitest smoke test only |
| Migrations | loaded directly from package |
| Migration publishing | not supported and should remain unnecessary |
| Current JavaScript customisation | one very large file/bundle, over 2.5 MB |
| Editor modifications | no direct Editor core patches |

---

# 4. Non-negotiable refactoring principles

Cursor must follow these rules throughout the migration.

## 4.1 No feature rewrite during architectural extraction

The first refactoring phase is structural, not functional.

Do not:

- redesign UI;
- rename visible concepts;
- change database fields;
- change route behaviour;
- change frontend HTML output;
- change theme token behaviour;
- change component JSON format;
- alter popup trigger semantics;
- change template import format;
- remove features considered redundant before confirming usage;
- upgrade Editor during the same refactor.

## 4.2 No direct Editor core modifications

VoodBuilder must continue using:

- plugins;
- commands;
- traits;
- component types;
- block registration;
- event listeners;
- storage adapters;
- style manager customisation;
- panels and views;
- external wrappers and adapters.

Never patch Editor source files in `node_modules`.

## 4.3 Backward compatibility

Version `0.1.0` must open and render data created by `0.0.11`.

Existing:

- pages;
- layouts;
- menu definitions;
- themes;
- theme assignments;
- component JSON;
- page templates;
- popup definitions;
- dynamic bindings;
- visibility conditions;
- translations;
- scheduled publication;
- history records

must remain valid.

## 4.4 Modular internally before splitting physically

All modules initially remain in the same repository.

A feature becomes a separate Composer package only after:

- its public contracts are stable;
- its dependencies are explicit;
- tests cover its lifecycle;
- removal from the host package does not break the Core;
- it can be installed and uninstalled cleanly.

## 4.5 Licence checks must not be scattered

Do not write checks such as:

```php
if ($license->plan === 'agency') {
    // ...
}
```

throughout the source.

Use capabilities:

```php
VoodBuilder::can('components.export');
VoodBuilder::can('templates.import');
VoodBuilder::can('dynamic-data.collections');
```

The mapping between plans and capabilities must be centralised.

---

# 5. Product editions

The commercial editions are:

1. **Community**
2. **Professional**
3. **Agency**

VoodBuilder Community is the public open-source Core.

Professional and Agency capabilities are delivered through proprietary packages, premium asset access, or both.

---

# 6. Licensing model

## 6.1 Recommended commercial unit

Use **developer-seat licensing**, not primary per-site licensing.

### Community

- free;
- open source;
- public repository;
- optionally distributed through Packagist;
- no artificial production-domain restriction;
- suitable for personal and commercial projects;
- no access to proprietary packages or premium asset libraries.

### Professional

- one named developer;
- unlimited projects and client websites for that developer;
- no licence sharing with other developers;
- access to Professional proprietary modules and assets;
- standard support;
- annual renewal.

### Agency

- up to five named developers initially;
- unlimited projects and client websites;
- team library and sharing;
- advanced import/export;
- marketplace submission capabilities;
- priority support;
- annual renewal.

A future Enterprise edition can cover larger teams, SLAs and custom terms.

## 6.2 Why developer-seat licensing

This market is composed primarily of:

- Laravel developers;
- Filament developers;
- agencies;
- software studios;
- internal technical teams.

Per-site licensing punishes productive developers and creates friction around staging domains, local environments and customer handovers.

Developer-seat licensing aligns price with the party receiving the productivity benefit.

## 6.3 Installation tracking

AnyStack activation may still track installations for abuse prevention, but activation count should not be the principal commercial promise.

Recommended rules:

- localhost does not count;
- test environments do not count;
- CI environments do not count;
- staging can be paired with a production project;
- anomalous activation volume can trigger review;
- a developer can deactivate old installations from their account.

## 6.4 Behaviour after expiry

When a paid licence expires:

- the public website continues to render;
- existing pages continue to render;
- saved premium blocks continue to render;
- existing dynamic bindings continue to render;
- existing popups continue to render;
- the editor should remain usable for already installed functionality where technically possible;
- no new proprietary package versions are available;
- no premium support is available;
- no new premium templates or components can be downloaded;
- cloud libraries and future cloud services may be disabled;
- AI or metered services may be disabled;
- private package installation credentials may expire.

Never intentionally break a live site because a renewal was missed.

## 6.5 Client project handover

A Professional or Agency developer may:

- create websites for clients;
- invoice and sell the completed website;
- deploy VoodBuilder inside the client project;
- transfer the project source code to the client;
- leave the installed version operational after their own licence expires.

The customer needs their own active licence when:

- they want direct access to premium updates;
- another developer takes over maintenance and needs premium packages;
- they want to download new premium assets;
- they want official support;
- they want cloud services linked to a licence account.

## 6.6 Open-source boundary

Community must contain only code that may legally be open sourced.

Proprietary functionality must not be shipped inside Community and merely hidden with a frontend switch.

Recommended separation:

```text
VoodBuilder Community
    public, open source

Professional modules
    private Composer packages

Agency modules
    private Composer packages

Official premium templates/components
    proprietary digital assets

Cloud services
    authenticated services

Brand and trademark
    separately protected
```

A true open-source licence generally permits modification and derivative works. Therefore the commercial defence is not “nobody may extend Community”, but:

- proprietary code is not distributed publicly;
- official premium assets are not redistributable;
- the VoodBuilder trademark is protected;
- official marketplace distribution is controlled;
- package updates and support require an active licence;
- premium remote services require authentication.

The final licence texts must be reviewed by a qualified lawyer.

---

# 7. Final edition matrix

## 7.1 Core CMS and website architecture

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Visual editor | Yes | Yes | Yes |
| Page management | Yes | Yes | Yes |
| Automatic routing | Yes | Yes | Yes |
| Published toggle | Yes | Yes | Yes |
| Scheduled publication | Yes | Yes | Yes |
| Base language per page | Yes | Yes | Yes |
| Full UI translations for pages | Yes | Yes | Yes |
| Full UI translations for menus | Yes | Yes | Yes |
| Multiple layouts | Yes | Yes | Yes |
| Layout assignment per page/channel | Yes | Yes | Yes |
| Full-width / 80rem / custom width | Yes | Yes | Yes |
| Header and footer composition | Yes | Yes | Yes |
| Multiple menus | Yes | Yes | Yes |
| Nav menu positions | Yes | Yes | Yes |
| Four footer menu positions | Yes | Yes | Yes |
| Text, icon or combined menu entries | Yes | Yes | Yes |
| External URLs | Yes | Yes | Yes |
| Internal paths | Yes | Yes | Yes |
| Page links | Yes | Yes | Yes |
| Named app routes | Yes | Yes | Yes |
| Email links | Yes | Yes | Yes |
| Third-party menu provider API | Yes | Yes | Yes |
| Site search modal | Yes | Yes | Yes |
| Notifications icon integration | Yes | Yes | Yes |
| User profile menu | Yes | Yes | Yes |
| Light/dark switch | Yes | Yes | Yes |
| Language switch | Yes | Yes | Yes |
| Sticky navigation | Yes | Yes | Yes |
| Site settings | Yes | Yes | Yes |
| Brand settings | Yes | Yes | Yes |
| Favicon | Yes | Yes | Yes |
| Default language | Yes | Yes | Yes |
| Site SEO metadata | Yes | Yes | Yes |
| Geographic metadata | Yes | Yes | Yes |
| AI metadata placeholders | Yes | Yes | Yes |
| Tracking code field | Yes | Yes | Yes |

These functions define the identity of VoodBuilder and should not be artificially removed from Community.

## 7.2 Theme Studio

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Multiple themes in one site | Yes | Yes | Yes |
| Assign theme to content channel | Yes | Yes | Yes |
| Visual theme-to-channel map | Yes | Yes | Yes |
| Built-in Landing theme | Yes | Yes | Yes |
| Built-in Documentation theme | Yes | Yes | Yes |
| Clone built-in theme | Yes | Yes | Yes |
| Edit cloned theme colours | Yes | Yes | Yes |
| Light palette | Yes | Yes | Yes |
| Dark palette | Yes | Yes | Yes |
| Sync dark palette from light | Yes | Yes | Yes |
| Import official theme | No | Yes | Yes |
| Export theme | No | No | Yes |
| Share theme privately | No | No | Yes |
| Team theme library | No | No | Yes |
| Marketplace submission | No | No | Yes |
| White-label theme metadata | No | No | Optional/future |

The visual theme assignment map is a flagship Core feature and should remain available in Community.

## 7.3 Editor foundation

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Layout elements | Yes | Yes | Yes |
| Container, Block, Div, Section | Yes | Yes | Yes |
| Heading | Yes | Yes | Yes |
| Basic text | Yes | Yes | Yes |
| Rich text | Yes | Yes | Yes |
| Text link | Yes | Yes | Yes |
| Button | Yes | Yes | Yes |
| Icon support | Yes | Yes | Yes |
| Divider | Yes | Yes | Yes |
| Image | Yes | Yes | Yes |
| Video | Yes | Yes | Yes |
| Audio | Yes | Yes | Yes |
| Embed | Yes | Yes | Yes |
| Basic gallery | Yes | Yes | Yes |
| Basic carousel/slider | Yes | Yes | Yes |
| Reading time | Yes | Yes | Yes |
| Reading progress | Yes | Yes | Yes |
| Nav element | Yes | Yes | Yes |
| Footer element | Yes | Yes | Yes |
| Dynamic element settings | Yes | Yes | Yes |
| Desktop/tablet/mobile viewport | Yes | Yes | Yes |
| Undo/redo | Yes | Yes | Yes |
| Element outlines | Yes | Yes | Yes |
| Drop-zone display | Yes | Yes | Yes |
| CSS class inspector on hover | Yes | Yes | Yes |
| Full preview | Yes | Yes | Yes |
| Live light/dark switching | Yes | Yes | Yes |
| Independently hide tool panels | Yes | Yes | Yes |
| Resizable tool panels | Yes | Yes | Yes |
| Layers panel | Yes | Yes | Yes |
| History/revisions | Yes | Yes | Yes |
| Global tags | Yes | Yes | Yes |
| Visual conditions | Yes | Yes | Yes |
| Tailwind classes | Yes | Yes | Yes |
| Tailwind JIT compilation | Yes | Yes | Yes |
| Copy classes | Yes | Yes | Yes |
| Copy complete styling | Yes | Yes | Yes |
| Inline Editor styles | Yes | Yes | Yes |
| Animation editor | Yes | Yes | Yes |

History, conditions and animations remain in Community.

## 7.4 Official block library

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Fundamental editor elements | Full | Full | Full |
| Ready-made Tailwind sections | Approximately 15 | Complete official library | Complete official library |
| Current library size target | Core subset | 100+ | 100+ |
| New official sections | Limited Community releases | Included while active | Included while active |
| Premium block packs | No | Available/included according to offer | Included or discounted |
| Private team block packs | No | No | Yes |

Community must remain genuinely useful, but the full curated section library is a Professional value driver.

## 7.5 Page templates

> **Runtime update (2026-07):** authoring (Save / JSON import / export / multi-select) is gated by companion plugin `voodflow/voodbuilder-templates`. Core always allows list/apply and **install-from-URL** (marketplace purchase link) without that plugin. The edition table below is historical product intent and needs a rewrite.

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Built-in templates | 2 | Full official library | Full official library |
| Save page as local template | Yes | Yes | Yes |
| Use local template in same installation | Yes | Yes | Yes |
| Import template JSON | No | Yes | Yes |
| Install template from URL | No | Yes | Yes |
| Export template | No | Limited or No | Yes |
| Share between own projects | No | No | Yes |
| Team template library | No | No | Yes |
| Submit to marketplace | No | No | Yes |
| Load Agency-created premium bundle | No | No | Yes |

An asset created with an Agency-only feature may declare required capabilities. A lower plan must not import an asset that depends on unavailable capabilities.

## 7.6 Components

> **Runtime update (2026-07):** Components library is gated by companion plugin `voodflow/voodbuilder-components` (Filament registration), not Agency edition alone. Soft-gate the Components tab when the plugin is off.

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Save arbitrary selection as reusable component | No | No | Yes |
| Local component library | No | No | Yes |
| Export component | No | No | Yes |
| Import component | No | No | Yes |
| Install component from URL | No | No | Yes |
| Share within agency | No | No | Yes |
| Component categorisation | No | No | Yes |
| Import Tailwind HTML/code | No | No | Yes |
| Live render from pasted code | No | No | Yes |
| Compatibility report | No | No | Yes |
| Extract CSS from style tags | No | No | Yes |
| Marketplace submission | No | No | Yes |

This is intentionally an Agency feature because code import and reusable component generation can replace a significant part of the premium block library.

## 7.7 Dynamic data and model integration

Recommended split:

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Global tags | Yes | Yes | Yes |
| Bind one element to one model record | Yes | Yes | Yes |
| Bind text/media fields | Yes | Yes | Yes |
| One source per element | Yes | Yes | Yes |
| Collection/list iteration | No | Yes | Yes |
| Dynamic repeaters | No | Yes | Yes |
| Filtering | No | Yes | Yes |
| Ordering | No | Yes | Yes |
| Relations | No | Yes | Yes |
| Pagination | No | Yes | Yes |
| Dynamic “latest item” queries | No | Yes | Yes |
| Visual query builder | No | Yes | Yes |
| Custom data-source providers | No | No | Yes |
| Provider SDK for plugins | No | No | Yes |
| Shared query presets | No | No | Yes |
| Export dynamic binding presets | No | No | Yes |

Recommended package boundary:

```text
voodflow/voodbuilder
    basic single-record binding

voodflow/voodbuilder-dynamic-data
    proprietary Professional/Agency package
```

This avoids distributing advanced data logic inside Community.

## 7.8 Popups

Popups are the first natural commercial plugin.

Package:

```text
voodflow/voodbuilder-popups
```

Capabilities:

- visual popup editor using the same editor runtime;
- activation and pause;
- start/end scheduling;
- language targeting;
- page targeting;
- page-load trigger;
- delay trigger;
- exit-intent trigger;
- scroll-depth trigger;
- element/selector trigger;
- every visit;
- once per session;
- once ever;
- every N days;
- day-of-week scheduling;
- opening analytics;
- closing analytics;
- click analytics;
- close-button click analytics;
- future conversion events;
- future A/B tests.

Commercial treatment:

| Capability | Community | Professional | Agency |
|---|:---:|:---:|:---:|
| Install Popup plugin separately | Yes | Yes | Yes |
| Popup plugin included | No | Optional/separate | Recommended included |
| Popup analytics | With plugin | With plugin | With included plugin |
| Advanced future A/B tests | Add-on or Pro plugin tier | Add-on | Included or discounted |

The plugin can be bought by a Community user without requiring Professional, because the Core acts as the host platform.

---

# 8. VDocs relationship

VoodBuilder Community includes:

- generic theme system;
- built-in Documentation theme;
- ability to create documentation-style pages manually;
- theme assignment to a documentation channel.

VDocs is an independent product that may work without VoodBuilder.

VDocs provides:

- documentation models;
- nested structure;
- automatic sidebar;
- navigation tree;
- versioning;
- search;
- code presentation;
- previous/next links;
- table of contents;
- structured content workflow;
- documentation permissions;
- its own Documentation theme.

When both packages are installed, VDocs may expose its content channel and blocks to VoodBuilder.

---

# 9. Proposed package architecture

## 9.1 Initial repository structure

Keep one repository during the first refactor:

```text
packages/voodflow/voodbuilder/
├── src/
│   ├── Contracts/
│   ├── Core/
│   ├── Modules/
│   │   ├── Pages/
│   │   ├── Layouts/
│   │   ├── Menus/
│   │   ├── Themes/
│   │   ├── Editor/
│   │   ├── DynamicData/
│   │   ├── Conditions/
│   │   ├── History/
│   │   ├── Templates/
│   │   ├── Components/
│   │   ├── Popups/
│   │   └── Settings/
│   ├── Licensing/
│   ├── Marketplace/
│   ├── Support/
│   └── VoodBuilderServiceProvider.php
├── resources/
│   ├── js/
│   │   ├── editor/
│   │   ├── theme-map/
│   │   ├── shared/
│   │   └── entrypoints/
│   ├── css/
│   └── views/
├── routes/
├── database/
├── tests/
│   ├── Unit/
│   ├── Feature/
│   ├── Architecture/
│   └── Fixtures/
└── composer.json
```

## 9.2 Later physical packages

```text
voodflow/voodbuilder                 # Core (this repo)
voodflow/voodbuilder-popups          # DONE — companion plugin
voodflow/voodbuilder-components      # DONE — companion plugin
voodflow/voodbuilder-dynamic-data    # DONE — companion plugin
voodflow/voodbuilder-templates       # DONE — authoring companion (marketplace URL stays in Core)
voodflow/voodbuilder-forms           # scaffold only
voodflow/voodbuilder-cookiebar       # scaffold only
voodflow/voodbuilder-analitycs       # scaffold only (intentional spelling)
voodflow/voodbuilder-sdk             # later
```

Avoid splitting every module into its own package.

Only extract modules with a clear commercial or lifecycle boundary.

**Runtime note (2026-07):** companions unlock via Filament `*Plugin::make()`, not by edition alone. Edition capabilities may still soft-gate nested features (e.g. Dynamic Data list repeat = `dynamic-data.collections`).

---

# 10. Module system

## 10.1 Core interface

```php
interface VoodBuilderModule
{
    public function id(): string;

    public function name(): string;

    public function version(): string;

    public function dependencies(): array;

    public function capabilities(): array;

    public function register(ModuleContext $context): void;

    public function boot(ModuleContext $context): void;
}
```

## 10.2 Optional module contributor interfaces

A module may implement one or more specific interfaces:

```php
RegistersFilamentResources
RegistersRoutes
RegistersBlocks
RegistersElements
RegistersEditorPanels
RegistersEditorCommands
RegistersAssets
RegistersMigrations
RegistersSettings
RegistersPermissions
RegistersDataSources
RegistersConditions
RegistersThemeChannels
RegistersMenuProviders
RegistersTemplateTypes
RegistersComponentTypes
RegistersAnalyticsEvents
```

Prefer small interfaces over one enormous base class.

## 10.3 Module registry

```php
final class ModuleRegistry
{
    public function register(VoodBuilderModule $module): void;
    public function all(): Collection;
    public function enabled(): Collection;
    public function get(string $id): ?VoodBuilderModule;
    public function has(string $id): bool;
    public function boot(): void;
}
```

## 10.4 Module lifecycle

```text
Discover
→ Validate dependencies
→ Resolve entitlement
→ Register services
→ Register migrations
→ Register routes
→ Register Filament integration
→ Register editor contributions
→ Boot module
```

## 10.5 Core must not depend directly on optional modules

Bad:

```php
new PopupManager();
```

Good:

```php
$registry->contributors(RegistersPopupTypes::class);
```

or:

```php
VoodBuilder::modules()->get('popups');
```

The Core must continue to work when Popups is not installed.

---

# 11. Capability and entitlement system

## 11.1 Capability examples

```text
editor.core
editor.animations
editor.conditions
editor.history

blocks.core
blocks.official.complete

templates.local
templates.import
templates.export
templates.remote-install
templates.marketplace-submit

components.create
components.import
components.export
components.code-import
components.team-share

dynamic-data.single
dynamic-data.collections
dynamic-data.query-builder
dynamic-data.custom-providers

themes.clone
themes.import
themes.export
themes.team-share

popups.builder
popups.analytics

marketplace.consume
marketplace.submit
```

## 11.2 Plan resolver

```php
interface EntitlementProvider
{
    public function capabilities(): CapabilitySet;

    public function licenceStatus(): LicenceStatus;
}
```

Possible providers:

- `CommunityEntitlementProvider`
- `AnyStackEntitlementProvider`
- `TestingEntitlementProvider`
- `CachedEntitlementProvider`

## 11.3 Local capability cache

The editor must not call a remote licensing server on every request.

Use a signed local cache containing:

- licence identifier;
- plan;
- capabilities;
- expiry;
- last successful validation;
- grace period;
- package entitlements.

## 11.4 Graceful failure

If AnyStack is temporarily unreachable:

- continue using last valid entitlement within a grace period;
- never break public rendering;
- show an admin warning;
- do not silently revoke installed functionality.

## 11.5 Enforcement layers

Capabilities must be enforced at:

1. package availability;
2. service-provider registration;
3. backend actions;
4. Filament resource visibility;
5. editor configuration;
6. API routes;
7. import validation;
8. marketplace download;
9. export action;
10. cloud service access.

Frontend-only hiding is insufficient.

---

# 12. AnyStack integration

AnyStack must be isolated behind VoodBuilder contracts.

Do not allow AnyStack SDK calls across the codebase.

Recommended namespace:

```text
src/Licensing/
├── Contracts/
├── AnyStack/
├── Cache/
├── CapabilitySet.php
├── LicenceStatus.php
└── EntitlementManager.php
```

The commercial architecture must remain replaceable if the licensing provider changes.

Expected responsibilities:

- licence activation;
- developer-seat identity;
- plan resolution;
- package access;
- renewal state;
- cached capabilities;
- support entitlement;
- premium asset download token;
- optional installation fingerprinting.

---

# 13. Editor refactoring plan

## 13.1 Current risk

The current implementation keeps Editor itself unmodified, but a large part of VoodBuilder behaviour is concentrated in a JavaScript file or bundle exceeding 2.5 MB.

Risks:

- hidden coupling;
- duplicated code;
- duplicated event listeners;
- order-dependent initialisation;
- commands registered multiple times;
- global mutable state;
- difficult tree-shaking;
- difficult testing;
- difficult plugin extraction;
- high regression risk;
- unclear ownership of editor features;
- slow onboarding;
- future Editor upgrade difficulty.

## 13.2 Mandatory rule

Do not “rewrite the big file” in one pass.

The migration must be incremental and behaviour-preserving.

## 13.3 First audit outputs

Cursor must first produce:

```text
docs/audit/editor-js-inventory.md
docs/audit/editor-event-map.md
docs/audit/editor-command-map.md
docs/audit/editor-global-state.md
docs/audit/editor-dependency-map.md
docs/audit/editor-duplicate-candidates.md
docs/audit/editor-entrypoints.md
```

## 13.4 Identify and catalogue

The audit must identify:

- Editor initialisation;
- block registration;
- component-type registration;
- commands;
- panels;
- style manager sectors;
- trait definitions;
- event listeners;
- keyboard shortcuts;
- editor state;
- Tailwind JIT integration;
- CSS compatibility analyser;
- dynamic data binding;
- visibility conditions;
- layer customisation;
- history integration;
- responsive viewport controls;
- panel resizing;
- light/dark preview;
- template actions;
- component actions;
- import/export;
- popup editor extensions;
- contextual toolbar;
- global tags;
- React theme-map bridge;
- Laravel API calls;
- CSRF handling;
- notifications and errors;
- duplicated helpers;
- dead code candidates.

## 13.5 Target JavaScript structure

```text
resources/js/editor/
├── bootstrap/
│   ├── create-editor.js
│   ├── editor-config.js
│   ├── register-core.js
│   └── lifecycle.js
├── contracts/
├── registry/
│   ├── command-registry.js
│   ├── block-registry.js
│   ├── component-registry.js
│   ├── panel-registry.js
│   ├── data-source-registry.js
│   └── condition-registry.js
├── core/
│   ├── canvas/
│   ├── commands/
│   ├── panels/
│   ├── storage/
│   ├── viewport/
│   ├── preview/
│   ├── history/
│   └── layers/
├── styling/
│   ├── tailwind/
│   ├── jit/
│   ├── style-manager/
│   ├── class-copy/
│   └── animations/
├── dynamic-data/
├── conditions/
├── templates/
├── components/
├── popups/
├── imports/
├── api/
├── state/
├── utils/
└── entrypoints/
    ├── page-editor.js
    ├── layout-editor.js
    └── popup-editor.js
```

## 13.6 Shared editor runtime

Pages, layouts and popups use the same visual editor.

Do not create three separate editor implementations.

Create:

```js
createVoodBuilderEditor({
    mode: 'page' | 'layout' | 'popup',
    capabilities,
    modules,
    initialData,
});
```

Mode-specific behaviour must be contributed through modules.

## 13.7 No global singleton where avoidable

Prefer dependency injection and explicit context:

```js
export function registerHistoryModule(editor, context) {}
```

Avoid:

```js
window.voodBuilderEditor = ...
```

except for a narrow compatibility bridge during migration.

## 13.8 Compatibility bridge

During migration, old functions may be exposed temporarily through a compatibility layer:

```text
resources/js/editor/legacy/compatibility-bridge.js
```

Every bridge item must include:

- original global name;
- replacement module;
- removal target version;
- regression test.

## 13.9 Bundle strategy

Goals:

- separate editor entrypoints;
- shared chunks;
- lazy-load heavy optional features;
- lazy-load popup-specific code;
- lazy-load code importer;
- lazy-load compatibility analyser;
- avoid bundling React theme map into the editor;
- maintain the separate theme-map bundle until a deliberate consolidation decision is made.

Do not optimise bundle size by removing code until tests prove it unused.

## 13.10 Editor upgrade strategy

Do not combine modular refactoring with a Editor version upgrade.

First:

1. align declared constraints;
2. keep current locked version;
3. complete modular extraction;
4. build regression tests;
5. create a separate upgrade branch;
6. test against the next supported Editor version.

---

# 14. Frontend extension SDK

Third-party plugins must be able to contribute without editing Core files.

## 14.1 Example plugin registration

```js
VoodBuilder.registerPlugin({
    id: 'vendor/example-plugin',
    version: '1.0.0',

    register(context) {
        context.blocks.register(...);
        context.components.register(...);
        context.commands.register(...);
        context.panels.register(...);
        context.dataSources.register(...);
        context.conditions.register(...);
    },
});
```

## 14.2 Required registries

The public SDK should eventually expose:

- blocks;
- elements;
- component types;
- traits;
- commands;
- panels;
- settings panels;
- toolbar actions;
- data sources;
- dynamic field types;
- visibility conditions;
- global tags;
- theme channels;
- menu providers;
- template manifests;
- component manifests;
- analytics events;
- import validators.

## 14.3 Version compatibility

Each plugin manifest must declare:

```json
{
  "name": "vendor/plugin",
  "version": "1.0.0",
  "requires": {
    "voodbuilder": "^0.1",
    "php": "^8.4",
    "filament": "^5.0"
  },
  "capabilities": [
    "plugin.example"
  ]
}
```

## 14.4 Stable API policy

Anything under:

```text
Contracts/
SDK/
PublicApi/
```

is considered public and must follow semantic versioning.

Anything under:

```text
Internal/
Support/Internal/
```

is not public.

---

# 15. Template and component bundle format

Every exported asset should have a manifest.

Example:

```json
{
  "schema": "voodbuilder-template",
  "schema_version": "1.0",
  "id": "vendor/template-name",
  "name": "Template name",
  "version": "1.0.0",
  "author": "Vendor",
  "requires": {
    "voodbuilder": "^0.1",
    "capabilities": [
      "blocks.official.complete",
      "dynamic-data.collections"
    ],
    "plugins": []
  },
  "content": {},
  "checksum": "..."
}
```

Import must validate:

- schema;
- schema version;
- required VoodBuilder version;
- required capabilities;
- required plugins;
- unsupported classes;
- unsafe code;
- remote assets;
- checksum/signature;
- marketplace approval status where applicable.

A lower edition must reject assets that require unavailable capabilities.

---

# 16. Marketplace strategy

## Phase 1 — Official catalogue

Only Voodflow publishes:

- plugins;
- starter kits;
- templates;
- components;
- themes;
- block packs.

## Phase 2 — Compatible directory

Third parties may list compatible packages, but purchase and support can remain external.

## Phase 3 — Curated marketplace

Introduce:

- vendor accounts;
- upload;
- automated validation;
- manual review;
- signatures;
- malware and unsafe-code checks;
- compatibility testing;
- commissions;
- refunds policy;
- versioning;
- security reports.

Do not permit arbitrary direct cross-site sharing of unreviewed bundles through the official service.

Agency users may export for their own organisation, but public distribution should go through an approved marketplace process.

---

# 17. Security and trust boundaries

## 17.1 Import from code

Pasted HTML/Tailwind code is untrusted input.

The importer must:

- reject executable server-side code;
- sanitise script tags;
- handle inline event handlers safely;
- analyse remote URLs;
- detect unsupported dependencies;
- isolate preview rendering;
- report CSS requirements;
- avoid arbitrary JavaScript execution;
- preserve an audit log.

## 17.2 Templates and components

Remote bundles must not be blindly executed.

Validate and sanitise before import.

## 17.3 Plugin packages

Composer plugins are trusted code installed by the developer. Marketplace review reduces risk but cannot make third-party PHP code harmless.

The marketplace must clearly distinguish:

- official;
- verified;
- community;
- unreviewed external.

---

# 18. Audit phase

Before code changes, Cursor must produce an audit commit containing documentation only.

## 18.1 Required audit documents

```text
docs/audit/
├── architecture-current-state.md
├── php-module-map.md
├── editor-js-inventory.md
├── editor-event-map.md
├── editor-command-map.md
├── editor-global-state.md
├── database-map.md
├── route-map.md
├── filament-registration-map.md
├── asset-build-map.md
├── public-api-candidates.md
├── plugin-boundary-candidates.md
├── test-coverage-map.md
├── risk-register.md
└── migration-plan.md
```

## 18.2 Audit questions

Cursor must answer:

- Which classes directly instantiate other feature classes?
- Which service providers register everything?
- Which JavaScript features depend on initialisation order?
- Which state is stored globally?
- Which events are listened to more than once?
- Which utilities are duplicated?
- Which routes belong to which module?
- Which tables belong to which module?
- Which migrations can remain Core?
- Which features can disappear without breaking existing saved JSON?
- Which editor features are shared by page, layout and popup modes?
- Which APIs are already effectively public?
- Which package code assumes Popups always exists?
- Which code assumes Components always exists?
- Which code assumes advanced Dynamic Data always exists?
- Which tests protect current behaviour?
- Which critical behaviours have no tests?

---

# 19. Branch and release plan

## 19.1 Branch

```text
refactor/modular-architecture
```

## 19.2 Version

During development:

```text
0.1.0-dev
```

Final modular Core release:

```text
0.1.0
```

Popup plugin first release:

```text
0.1.0
```

## 19.3 Commit discipline

Use small commits by concern:

```text
docs: add architecture audit
test: capture current page editor behaviour
refactor: introduce module contracts
refactor: add module registry
refactor: migrate history module
refactor: migrate conditions module
refactor: split editor command registry
feat: add entitlement abstraction
refactor: extract popup module boundary
```

Do not combine unrelated formatting, dependency upgrades and architecture changes.

---

# 20. Migration phases

## Phase 0 — Freeze and baseline

- create branch;
- tag current `0.0.11`;
- export representative fixtures;
- record screenshots;
- record public HTML output;
- record API responses;
- record editor JSON;
- ensure current PHP tests pass;
- add a minimal frontend CI job.

**Acceptance:** repeatable baseline exists.

## Phase 1 — Audit

Documentation only.

**Acceptance:** all audit documents completed and reviewed.

## Phase 2 — Characterisation tests

Add tests around current behaviour without changing implementation.

Priority:

- route resolution;
- future publication;
- theme assignment;
- theme clone;
- menus;
- translations;
- dynamic single-record binding;
- dynamic collections;
- conditions;
- template import;
- component import;
- popup triggers;
- popup scheduling;
- popup analytics;
- editor save/load;
- layout save/load.

**Acceptance:** critical flows are protected.

## Phase 3 — Public contracts

Introduce interfaces and DTOs, still backed by old implementation.

**Acceptance:** no visible changes.

## Phase 4 — Module registry

Create registry and register one low-risk module.

Suggested pilot:

- History, or
- Conditions.

Do not use Popups as the first internal migration because it is commercially important and structurally broad.

**Acceptance:** pilot module can be enabled/disabled in tests.

## Phase 5 — Editor registries

Extract:

- commands;
- blocks;
- components;
- panels;
- data sources;
- conditions.

Keep legacy compatibility bridge.

**Acceptance:** same editor output and behaviour.

## Phase 6 — Progressive PHP migration

Move modules one by one:

1. History
2. Conditions
3. Templates
4. Themes
5. Menus
6. Layouts
7. Pages
8. Dynamic Data
9. Components
10. Popups

**Acceptance:** tests pass after every module.

## Phase 7 — Entitlement system

Add capability resolver with test provider first.

Do not connect AnyStack until local capability behaviour is complete.

**Acceptance:** plan matrices can be simulated in tests.

## Phase 8 — Commercial package boundaries

Move advanced Dynamic Data, Components and premium assets behind proprietary package boundaries.

**Acceptance:** Community installs and runs without proprietary packages.

## Phase 9 — Popup extraction

Extract Popups to:

```text
voodflow/voodbuilder-popups
```

**Acceptance:**

- Core works without Popups;
- installing Popups adds all backend and editor features;
- uninstalling does not break Core;
- existing popup data is preserved;
- public rendering does not fail if the plugin is temporarily absent;
- clear admin warning is shown for orphaned popup data.

## Phase 10 — AnyStack

Implement activation and capability retrieval through the licensing adapter.

**Acceptance:** temporary AnyStack outage does not break live sites.

## Phase 11 — SDK and marketplace readiness

Document plugin APIs and asset schemas.

**Acceptance:** a sample third-party plugin can register one block, one data source and one condition without changing Core files.

---

# 21. Testing strategy

## 21.1 PHP tests

Continue with PHPUnit 11 and Orchestra Testbench.

Add:

```text
tests/Architecture/
tests/Contracts/
tests/Modules/
tests/Licensing/
tests/Compatibility/
tests/Upgrade/
```

## 21.2 Frontend tests

Create structured Vitest suites for:

- editor bootstrap;
- command registration;
- event registration;
- capability filtering;
- page/layout/popup modes;
- dynamic data UI;
- conditions;
- template import;
- component import;
- Tailwind compatibility analyser;
- history;
- theme switching.

## 21.3 End-to-end tests

Introduce Playwright or equivalent for critical flows:

- open page editor;
- drag element;
- edit text;
- apply Tailwind class;
- save;
- reload;
- switch viewport;
- switch light/dark;
- bind data;
- add condition;
- save template;
- import template;
- open layout editor;
- configure nav;
- create popup when plugin installed.

## 21.4 Visual regression

Use stable screenshots for:

- editor shell;
- left panel tabs;
- right panel tabs;
- nav configuration;
- theme map;
- popup settings;
- template import modal;
- component code import modal.

## 21.5 Fixture compatibility

Maintain fixture files created by `0.0.11`.

Every release must load them successfully.

---

# 22. Performance objectives

Do not set arbitrary performance targets before measuring.

First create baselines for:

- editor initial bundle size;
- initial load time;
- time to interactive;
- Editor initialisation;
- Tailwind JIT compile;
- save operation;
- template import;
- component code analysis;
- page render;
- dynamic collection render.

After modularisation:

- optional modules should be lazy-loaded;
- popup editor code should not load in page mode;
- code importer should not load until opened;
- theme-map React bundle should load only in Theme Studio;
- duplicate dependencies should be removed;
- repeated listeners must be eliminated;
- source maps must remain available in development.

---

# 23. Commercial plugin roadmap

## Official first wave — status 2026-07-29

1. **VoodBuilder Popups** — DONE (extracted)
2. **VoodBuilder Components** — DONE (extracted; plugin gate)
3. **VoodBuilder Dynamic Data** — DONE (extracted; plugin gate; collections still Pro)
4. **VoodBuilder Templates** — DONE authoring plugin; Core keeps marketplace install-from-URL
5. **VoodBuilder Analytics** — scaffold only → **NEXT implement**
6. **VoodBuilder Cookiebar** — scaffold only → implement after Analytics
7. **VoodBuilder Forms** — scaffold only → **LAST**
8. **VDocs integration** — separate product (already in host)
9. **VoodBuilder Blog/Content** — later
10. **VoodBuilder AI** — later
11. **VoodBuilder Shop** — later

## Plugin criteria

A feature should become a plugin when it:

- has its own models or tables;
- has its own Filament resources;
- has its own editor registrations;
- has its own asset bundle;
- can be sold independently;
- can evolve independently;
- is useful only to a subset of users.

---

# 24. Legal and commercial asset rules

Recommended rules:

- users may sell completed websites to clients;
- users may not redistribute official premium templates as standalone products;
- users may not redistribute official premium components outside permitted project use;
- Agency users may share assets within their licensed organisation;
- marketplace distribution requires review;
- imported assets must declare required capabilities;
- VoodBuilder branding and trademarks are protected separately;
- third parties may build compatible plugins using the SDK;
- third parties may not claim official affiliation;
- proprietary VoodBuilder code may not be copied into competing products;
- licence texts must not attempt to redefine open-source rights of Community code.

Avoid vague clauses such as “no plugin similar to VoodBuilder”. Instead protect:

- copyrighted proprietary source;
- trademarks;
- premium asset files;
- private APIs and services;
- misleading branding;
- unauthorised redistribution.

---

# 25. Cursor operating instructions

Cursor must work as follows.

## Before every phase

1. read this document;
2. inspect current code;
3. write a short phase plan;
4. list files expected to change;
5. list risks;
6. confirm tests to run.

## During implementation

- preserve behaviour;
- prefer adapters over rewrites;
- add tests before risky changes;
- keep commits focused;
- do not upgrade dependencies without explicit approval;
- do not rename database columns;
- do not change stored JSON format without a versioned migrator;
- do not remove “unused” code without proving it unused;
- do not duplicate old logic in new modules indefinitely;
- mark compatibility bridges with removal versions.

## After every phase

Produce:

```text
docs/progress/<phase-name>.md
```

containing:

- work completed;
- files changed;
- tests added;
- tests executed;
- known issues;
- remaining legacy code;
- rollback notes.

---

# 26. Definition of done for VoodBuilder 0.1.0

Version `0.1.0` is complete only when:

- current `0.0.11` data loads correctly;
- the Core is modular internally;
- the editor is split into documented modules;
- the 2.5 MB JavaScript implementation is no longer a single opaque source unit;
- Editor remains unpatched;
- page, layout and popup modes share one editor runtime;
- module registration is centralised;
- capabilities are centralised;
- Community runs without proprietary packages;
- Professional and Agency capabilities can be simulated in tests;
- Popup can be extracted cleanly or its extraction is fully prepared;
- AnyStack is behind an adapter;
- licence expiry cannot break public rendering;
- template/component manifests support capability requirements;
- plugin SDK contracts are documented;
- frontend CI exists;
- regression fixtures exist;
- critical end-to-end flows pass;
- performance baselines are documented;
- no known critical regression remains.

---

# 27. Workstation handoff — continue from here (2026-07-29)

This replaces the old “Phase 0 freeze” immediate action. Phases 0–11 and the first commercial companions are already in flight on `refactor/modular-architecture`.

## 27.1 Goal tomorrow

1. Pull **Core** `voodbuilder` on `refactor/modular-architecture`.
2. Clone missing companions into `packages/voodflow/` (path repos; not Packagist yet).
3. Wire host Cosmolab Composer + Filament plugins.
4. Continue the wave: **Analytics → Cookiebar → Forms (last)**.

## 27.2 Remotes (SSH `voodflow-git`)

```text
git@voodflow-git:voodflow/voodbuilder.git
git@voodflow-git:voodflow/voodbuilder-popups.git
git@voodflow-git:voodflow/voodbuilder-components.git
git@voodflow-git:voodflow/voodbuilder-dynamic-data.git
git@voodflow-git:voodflow/voodbuilder-templates.git
git@voodflow-git:voodflow/voodbuilder-forms.git
git@voodflow-git:voodflow/voodbuilder-cookiebar.git
git@voodflow-git:voodflow/voodbuilder-analitycs.git
```

## 27.3 Filo per filo — host Cosmolab (Docker DetDocker)

Assume host app root:

```text
…/dev/progetti/cosmolab/app
```

and packages:

```text
…/dev/progetti/cosmolab/app/packages/voodflow/
```

### A. Core VoodBuilder

```bash
cd …/app/packages/voodflow
# if missing:
git clone git@voodflow-git:voodflow/voodbuilder.git
cd voodbuilder
git fetch origin
git checkout refactor/modular-architecture
git pull --ff-only origin refactor/modular-architecture
```

### B. Companion clones (skip any folder that already exists)

```bash
cd …/app/packages/voodflow

git clone git@voodflow-git:voodflow/voodbuilder-popups.git
git clone git@voodflow-git:voodflow/voodbuilder-components.git
git clone git@voodflow-git:voodflow/voodbuilder-dynamic-data.git
git clone git@voodflow-git:voodflow/voodbuilder-templates.git
git clone git@voodflow-git:voodflow/voodbuilder-forms.git
git clone git@voodflow-git:voodflow/voodbuilder-cookiebar.git
git clone git@voodflow-git:voodflow/voodbuilder-analitycs.git

# update existing:
for p in voodbuilder-popups voodbuilder-components voodbuilder-dynamic-data voodbuilder-templates \
         voodbuilder-forms voodbuilder-cookiebar voodbuilder-analitycs; do
  git -C "$p" checkout main && git -C "$p" pull --ff-only
done
```

### C. Host `composer.json` (path repos)

Ensure `require` includes (at least):

```json
"voodflow/voodbuilder": ">=0.0.2",
"voodflow/voodbuilder-popups": "*@dev",
"voodflow/voodbuilder-components": "*@dev",
"voodflow/voodbuilder-dynamic-data": "*@dev",
"voodflow/voodbuilder-templates": "*@dev"
```

Ensure `repositories` path entries exist. For Docker/virtiofs prefer:

```json
"options": { "symlink": false }
```

on companion packages (popups, components, dynamic-data, templates). Core `voodbuilder` can stay symlinked.

Then:

```bash
cd …/app
composer update voodflow/voodbuilder voodflow/voodbuilder-popups \
  voodflow/voodbuilder-components voodflow/voodbuilder-dynamic-data \
  voodflow/voodbuilder-templates --with-all-dependencies
```

After editing a path package with `symlink: false`, re-run that package’s `composer update` so vendor gets a fresh copy.

### D. Filament panel plugins

In `app/Providers/Filament/AdminPanelProvider.php`:

```php
->plugins([
    VoodbuilderPlugin::make(),
    VoodbuilderPopupsPlugin::make(),
    VoodbuilderComponentsPlugin::make(),
    VoodbuilderDynamicDataPlugin::make(),
    VoodbuilderTemplatesPlugin::make(),
    // VoodbuilderFormsPlugin::make(),      // invent later
    // VoodbuilderCookiebarPlugin::make(),  // after Analytics
    // VoodbuilderAnalitycsPlugin::make(),  // next implement
])
```

Toggle plugins on/off to test soft-gates (Components tab upsell, Templates consume-only = link install, etc.).

### D2. `.env` — mirror Cosmolab commercial wave

**Important:** companion features unlock primarily via `*Plugin::make()` on the Filament panel (§D), **not** via Composer alone.  
`.env` only flips master switches / edition. Cosmolab “here” currently has **no** `VOODBUILDER_*` keys in `.env` (all package defaults), with these plugins registered: Popups, Components, Dynamic Data, Templates.

Paste into host `.env` to make the same setup explicit (and to enable Pro entitlements where useful):

```dotenv
# --- VoodBuilder Core ---
# community | professional | agency
# Use professional/agency if you need List repeat (dynamic-data.collections)
# and in-editor template catalog (templates.remote-install).
VOODBUILDER_EDITION=community
VOODBUILDER_LICENSE_ENFORCE=false
VOODBUILDER_LICENSE_DRIVER=config
VOODBUILDER_LICENSE_CACHE=false

# Core modules (defaults are all true — set false only to kill a surface)
VOODBUILDER_MODULE_PAGES=true
VOODBUILDER_MODULE_MENUS=true
VOODBUILDER_MODULE_LAYOUTS=true
VOODBUILDER_MODULE_TEMPLATES=true
VOODBUILDER_MODULE_THEMES=true
VOODBUILDER_MODULE_HISTORY=true
VOODBUILDER_MODULE_CONDITIONS=true
VOODBUILDER_MODULE_DYNAMIC_DATA=true
VOODBUILDER_MODULE_COMPONENTS=true
VOODBUILDER_MODULE_POPUPS=true

VOODBUILDER_EDITOR_ENABLED=true
VOODBUILDER_CHROME_LAYOUTS_ENABLED=true
VOODBUILDER_PAGES_DEFAULT_BUILDER=grapesjs

# Optional: remote template marketplace catalog JSON (Pro+ entitlement still required)
# VOODBUILDER_PAGE_TEMPLATE_CATALOG_URL=https://example.com/templates-catalog.json

# --- Companion package master switches (default true) ---
# Keep *_AUTO_REGISTER=false on Filament hosts: activation = Plugin::make() on the panel.
VOODBUILDER_POPUPS_ENABLED=true
VOODBUILDER_POPUPS_AUTO_REGISTER=false

COMPONENTS_ENABLED=true
COMPONENTS_AUTO_REGISTER=false

DYNAMIC_DATA_ENABLED=true
DYNAMIC_DATA_AUTO_REGISTER=false

TEMPLATES_ENABLED=true
TEMPLATES_AUTO_REGISTER=false

# Scaffolds — leave off until implemented / registered on the panel
FORMS_ENABLED=true
FORMS_AUTO_REGISTER=false
COOKIEBAR_ENABLED=true
COOKIEBAR_AUTO_REGISTER=false
ANALITYCS_ENABLED=true
ANALITYCS_AUTO_REGISTER=false
```

**Parity with “here” (full wave testing):**

| Goal | `.env` | Panel plugin |
|---|---|---|
| Same as Cosmolab now | block above with `VOODBUILDER_EDITION=community` | Popups + Components + Dynamic Data + Templates |
| + List repeat / template catalog | `VOODBUILDER_EDITION=professional` (or `agency`) | same plugins |
| Soft-gate Components | leave `COMPONENTS_ENABLED=true` | comment out `VoodbuilderComponentsPlugin` |
| Soft-gate Templates authoring (keep marketplace URL) | leave `TEMPLATES_ENABLED=true` | comment out `VoodbuilderTemplatesPlugin` |
| Soft-gate Dynamic Data | leave `DYNAMIC_DATA_ENABLED=true` | comment out `VoodbuilderDynamicDataPlugin` |

After changing `.env`:

```bash
cd …/app
php artisan config:clear
# if you use config:cache in deploy: php artisan config:cache
```

Do **not** set `*_AUTO_REGISTER=true` on the Filament Cosmolab host unless you intentionally bypass panel plugin registration (Testbench/headless only).

### E. Frontend assets

visual editor assets build from the **host** Vite config (entries under `packages/voodflow/voodbuilder/...`):

```bash
cd …/app
npx vite build
# or: npm run dev
```

Hard-refresh the browser after build.

### F. Quick smoke

1. Editor opens; Popups work if plugin on.
2. Components: Save works only with Components plugin; without plugin → upsell, no Save.
3. Templates: without Templates plugin → only install-from-URL (+ list/apply); with plugin → Save / JSON / export / select.
4. Dynamic Data: without plugin → Dynamic tab soft-gated; with plugin → bindings; List repeat still needs Pro edition entitlement.

### G. What to implement next (priority)

1. **`voodbuilder-analitycs`** — page analytics extending popup analytics.
2. **`voodbuilder-cookiebar`** — real consent runtime.
3. **`voodbuilder-forms`** — last.
4. Optional: signed marketplace template URLs; rewrite §7.5–7.7 matrices to match plugin model.

Open Cursor on Cosmolab with context:

- this document §0 + §27
- `docs/progress/commercial-plugin-wave.md`
- companion README in each package

Tell the agent: *continua dalla commercial plugin wave: Analytics next, Cookiebar, Forms last; non spezzare il gate marketplace Templates.*

---

# 28. Product principle

The Core must feel complete.

Commercial modules must extend VoodBuilder rather than repair an intentionally crippled free product.

The commercial value should come from:

- productivity;
- curated premium libraries;
- advanced dynamic data;
- reusable components;
- code import;
- team sharing;
- premium plugins;
- marketplace access;
- updates;
- support;
- future cloud services.

The long-term architecture should make this possible:

```text
VoodBuilder Core
    ↓
Official premium modules
    ↓
Third-party plugins
    ↓
Templates and components
    ↓
Curated marketplace
    ↓
Optional cloud services
```

That is the target platform architecture for VoodBuilder 0.1.0 and beyond.
