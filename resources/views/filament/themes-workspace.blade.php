<div class="voodbuilder-themes-ws" data-studio-role="{{ $studioRole }}">
    <style>
        .voodbuilder-themes-ws {
            --vp-ws-radius: 0.75rem;
            --vp-ws-accent: #ea580c;
            --vp-ws-accent-hover: #c2410c;
            font-family: inherit;
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }

        .voodbuilder-themes-ws__catalog {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 1.25rem 1.25rem;
            align-items: start;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 1100px) {
            .voodbuilder-themes-ws__catalog {
                grid-template-columns: 1fr;
            }
        }

        .voodbuilder-themes-ws__catalog-column {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .voodbuilder-themes-ws__catalog-note {
            grid-column: 1 / -1;
            margin: 0 0 0.25rem;
            font-size: 0.75rem;
            line-height: 1.4;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-themes-ws__catalog-note {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 900px) {
            .voodbuilder-themes-ws__grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 520px) {
            .voodbuilder-themes-ws__grid {
                grid-template-columns: 1fr;
            }
        }

        .voodbuilder-themes-ws__card {
            position: relative;
            width: 100%;
            flex-shrink: 0;
            min-height: 6.25rem;
            border-radius: var(--vp-ws-radius);
            overflow: hidden;
            cursor: pointer;
            transition: box-shadow 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
            box-shadow: 0 1px 2px rgb(15 23 42 / 0.05);
            display: flex;
            flex-direction: column;
            border: 1px solid rgb(226 232 240);
            /* Keep theme surface as a soft tint — never opaque wash in dark mode */
            background:
                linear-gradient(
                    180deg,
                    color-mix(in srgb, var(--vp-card-surface, #f8fafc) 55%, #fff) 0%,
                    #fff 72%
                );
        }

        .dark .voodbuilder-themes-ws__card {
            border-color: rgb(71 85 105);
            box-shadow: 0 4px 14px rgb(0 0 0 / 0.22);
            background:
                linear-gradient(
                    180deg,
                    color-mix(in srgb, var(--vp-card-accent, #ea580c) 18%, rgb(30 41 59)) 0%,
                    rgb(30 41 59) 70%
                );
        }

        .voodbuilder-themes-ws__header {
            margin-bottom: 1rem;
        }

        .voodbuilder-themes-ws__header-hint {
            margin: 0;
            font-size: 0.8125rem;
            line-height: 1.45;
            color: rgb(100 116 139);
            max-width: 42rem;
        }

        .dark .voodbuilder-themes-ws__header-hint {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__catalog-column-header {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            min-height: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .voodbuilder-themes-ws__card--import.is-importing {
            pointer-events: none;
            opacity: 0.7;
        }

        .voodbuilder-themes-ws__import-loading {
            margin-top: 0.25rem;
            min-height: 1.125rem;
            font-size: 0.6875rem;
            font-weight: 500;
            line-height: 1.125rem;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-themes-ws__import-loading {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__card--import {
            border: 1px dashed rgb(203 213 225);
            background: rgb(248 250 252 / 0.45);
            box-shadow: none;
            cursor: pointer;
        }

        .voodbuilder-themes-ws__card--import:hover {
            border-color: rgb(148 163 184);
            background: rgb(248 250 252 / 0.85);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgb(15 23 42 / 0.06);
        }

        .voodbuilder-themes-ws__card-strip--import span {
            background: rgb(226 232 240);
        }

        .voodbuilder-themes-ws__card--import .voodbuilder-themes-ws__card-name {
            color: rgb(71 85 105);
        }

        .voodbuilder-themes-ws__card--import .voodbuilder-themes-ws__card-badge {
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-themes-ws__card--import {
            border-color: rgb(71 85 105);
            background: rgb(15 23 42 / 0.35);
        }

        .dark .voodbuilder-themes-ws__card--import:hover {
            border-color: rgb(100 116 139);
            background: rgb(30 41 59 / 0.55);
        }

        .dark .voodbuilder-themes-ws__card-strip--import span {
            background: rgb(51 65 85);
        }

        .dark .voodbuilder-themes-ws__card--import .voodbuilder-themes-ws__card-name {
            color: rgb(226 232 240);
        }

        .voodbuilder-themes-ws__card--import:hover .voodbuilder-themes-ws__card-icon {
            color: color-mix(in srgb, var(--vp-ws-accent) 75%, rgb(71 85 105));
        }

        .voodbuilder-themes-ws__import-label {
            position: relative;
            display: block;
            margin: 0;
        }

        .voodbuilder-themes-ws__import-label input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .dark .voodbuilder-themes-ws__empty-card {
            border-color: rgb(71 85 105);
            background: rgb(15 23 42 / 0.35);
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__btn {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            padding: 0.4375rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.8125rem;
            font-weight: 600;
            line-height: 1.25rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .voodbuilder-themes-ws__btn--primary {
            background-color: var(--vp-ws-accent, #ea580c);
            color: #ffffff !important;
            border-color: var(--vp-ws-accent, #ea580c);
        }

        .voodbuilder-themes-ws__btn--primary:hover {
            background-color: var(--vp-ws-accent-hover, #c2410c);
            border-color: var(--vp-ws-accent-hover, #c2410c);
        }

        .voodbuilder-themes-ws__btn--ghost {
            background: #fff;
            color: rgb(51 65 85);
            border-color: rgb(203 213 225);
        }

        .voodbuilder-themes-ws__btn--ghost:hover {
            background: rgb(248 250 252);
            border-color: rgb(148 163 184);
        }

        .dark .voodbuilder-themes-ws__btn--ghost {
            background: rgb(30 41 59);
            color: rgb(226 232 240);
            border-color: rgb(71 85 105);
        }

        .voodbuilder-themes-ws__meta-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
            margin: 0 0 1.25rem;
            padding: 0.85rem 1rem;
            border-radius: 0.65rem;
            background: rgb(248 250 252);
            box-shadow: inset 0 0 0 1px rgb(226 232 240);
        }

        .dark .voodbuilder-themes-ws__meta-actions {
            background: rgb(15 23 42 / 0.45);
            box-shadow: inset 0 0 0 1px rgb(51 65 85);
        }

        .voodbuilder-themes-ws__meta-actions .voodbuilder-themes-ws__field-hint {
            margin: 0;
            flex: 1 1 14rem;
        }

        .voodbuilder-themes-ws__meta-actions .voodbuilder-themes-ws__btn--primary {
            flex: 0 0 auto;
        }

        .voodbuilder-themes-ws__brand-picker {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.55rem 0.75rem;
            margin: 0 0 1rem;
        }

        .voodbuilder-themes-ws__brand-picker label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-themes-ws__brand-picker label {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__brand-picker select {
            min-width: 12rem;
            max-width: 100%;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            background: rgb(255 255 255);
            color: rgb(15 23 42);
            padding: 0.45rem 0.55rem;
            font: inherit;
            font-size: 0.875rem;
        }

        .dark .voodbuilder-themes-ws__brand-picker select {
            border-color: rgb(71 85 105);
            background: rgb(15 23 42);
            color: rgb(248 250 252);
        }

        .voodbuilder-themes-ws__import-action {
            position: relative;
            margin: 0;
        }

        .voodbuilder-themes-ws__import-action input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }

        .voodbuilder-themes-ws__import-action.is-importing {
            opacity: 0.7;
            pointer-events: none;
        }

        .voodbuilder-themes-ws__section-title {
            font-size: 0.875rem;
            font-weight: 600;
            margin: 0;
            color: rgb(100 116 139);
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .dark .voodbuilder-themes-ws__section-title {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__card--selected {
            box-shadow: 0 0 0 2px var(--vp-card-accent, var(--vp-ws-accent));
            border-color: color-mix(in srgb, var(--vp-card-accent, var(--vp-ws-accent)) 50%, rgb(226 232 240));
        }

        .dark .voodbuilder-themes-ws__card--selected {
            border-color: color-mix(in srgb, var(--vp-card-accent, var(--vp-ws-accent)) 55%, rgb(71 85 105));
        }

        .voodbuilder-themes-ws__card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgb(15 23 42 / 0.08);
        }

        .dark .voodbuilder-themes-ws__card:hover {
            box-shadow: 0 8px 20px rgb(0 0 0 / 0.35);
        }

        .voodbuilder-themes-ws__card--bundled {
            cursor: pointer;
        }

        .voodbuilder-themes-ws__card-strip {
            display: flex;
            height: 0.5rem;
        }

        .voodbuilder-themes-ws__card-strip span {
            flex: 1;
            min-width: 0;
        }

        .voodbuilder-themes-ws__card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 0.625rem 0.75rem 1.75rem;
            min-height: 3.75rem;
        }

        .voodbuilder-themes-ws__card-icon {
            position: absolute;
            right: 0.5rem;
            bottom: 0.4375rem;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: none;
            background: transparent;
            box-shadow: none;
            color: color-mix(in srgb, rgb(51 65 85) 42%, transparent);
            pointer-events: none;
            transition: color 0.15s ease, opacity 0.15s ease;
        }

        .voodbuilder-themes-ws__card-icon--action {
            pointer-events: auto;
            cursor: pointer;
            border-radius: 0.375rem;
        }

        .voodbuilder-themes-ws__card-icon--action:hover {
            color: color-mix(in srgb, var(--vp-card-accent, rgb(51 65 85)) 85%, rgb(51 65 85));
        }

        .voodbuilder-themes-ws__card-icon svg {
            width: 0.8125rem;
            height: 0.8125rem;
        }

        .voodbuilder-themes-ws__card:hover .voodbuilder-themes-ws__card-icon {
            color: color-mix(in srgb, var(--vp-card-accent, rgb(51 65 85)) 72%, rgb(51 65 85));
        }

        .dark .voodbuilder-themes-ws__card-icon {
            color: color-mix(in srgb, rgb(226 232 240) 45%, transparent);
        }

        .dark .voodbuilder-themes-ws__card:hover .voodbuilder-themes-ws__card-icon {
            color: color-mix(in srgb, var(--vp-card-accent, rgb(226 232 240)) 80%, rgb(226 232 240));
        }

        .voodbuilder-themes-ws__card-name {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: rgb(30 41 59);
            line-height: 1.3;
            word-break: break-word;
        }

        .dark .voodbuilder-themes-ws__card-name {
            color: rgb(241 245 249);
        }

        .voodbuilder-themes-ws__card-topline {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.4rem;
        }

        .voodbuilder-themes-ws__card-origin {
            flex-shrink: 0;
            font-size: 0.6rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__card-shell {
            display: inline-flex;
            margin-top: 0.25rem;
            padding: 0.1rem 0.4rem;
            border-radius: 0.3rem;
            font-size: 0.6rem;
            font-weight: 600;
            background: color-mix(in srgb, var(--vp-card-accent, #94a3b8) 16%, transparent);
            color: color-mix(in srgb, var(--vp-card-accent, #64748b) 70%, rgb(51 65 85));
        }

        .dark .voodbuilder-themes-ws__card-shell {
            color: color-mix(in srgb, var(--vp-card-accent, #94a3b8) 55%, rgb(226 232 240));
        }

        .voodbuilder-themes-ws__card-badge {
            display: -webkit-box;
            margin-top: 0.25rem;
            min-height: 1.125rem;
            font-size: 0.6875rem;
            font-weight: 500;
            line-height: 1.25;
            color: rgb(100 116 139);
            overflow: hidden;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            white-space: normal;
        }

        .dark .voodbuilder-themes-ws__card-badge {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__card-meta {
            display: block;
            margin-top: 0.2rem;
            font-size: 0.625rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: color-mix(in srgb, var(--vp-card-accent, #ea580c) 75%, rgb(100 116 139));
        }

        .dark .voodbuilder-themes-ws__card-meta {
            color: color-mix(in srgb, var(--vp-card-accent, #ea580c) 65%, rgb(148 163 184));
        }

        .voodbuilder-themes-ws__editor-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .voodbuilder-themes-ws__icon-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.25rem;
            margin-left: auto;
        }

        .voodbuilder-themes-ws__icon-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            padding: 0;
            border: 0;
            border-radius: 0.45rem;
            background: transparent;
            color: rgb(100 116 139);
            cursor: pointer;
            transition: background 0.12s ease, color 0.12s ease;
        }

        .voodbuilder-themes-ws__icon-btn svg {
            width: 1.05rem;
            height: 1.05rem;
        }

        .voodbuilder-themes-ws__icon-btn:hover {
            background: rgb(241 245 249);
            color: rgb(15 23 42);
        }

        .dark .voodbuilder-themes-ws__icon-btn {
            color: rgb(148 163 184);
        }

        .dark .voodbuilder-themes-ws__icon-btn:hover {
            background: rgb(30 41 59);
            color: rgb(248 250 252);
        }

        .voodbuilder-themes-ws__icon-btn--danger:hover {
            background: rgb(254 226 226);
            color: rgb(185 28 28);
        }

        .dark .voodbuilder-themes-ws__icon-btn--danger:hover {
            background: rgb(127 29 29 / 0.35);
            color: rgb(252 165 165);
        }

        .voodbuilder-themes-ws__icon-btn--file {
            position: relative;
            overflow: hidden;
        }

        .voodbuilder-themes-ws__icon-btn--file input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
        }

        .voodbuilder-themes-ws__icon-btn--file.is-importing {
            opacity: 0.55;
            pointer-events: none;
        }

        .voodbuilder-themes-ws__editor {
            margin-top: 1.25rem;
            padding-top: 1.25rem;
            border-top: 1px solid rgb(226 232 240 / 0.9);
        }

        .dark .voodbuilder-themes-ws__editor {
            border-top-color: rgb(51 65 85 / 0.9);
        }

        .voodbuilder-themes-ws__editor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.875rem;
        }

        .voodbuilder-themes-ws__editor-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 0;
            color: rgb(15 23 42);
        }

        .dark .voodbuilder-themes-ws__editor-title {
            color: rgb(248 250 252);
        }

        .voodbuilder-themes-ws__editor-close {
            font-size: 0.75rem;
            font-weight: 500;
            color: rgb(100 116 139);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .voodbuilder-themes-ws__fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .voodbuilder-themes-ws__field label {
            display: block;
            font-size: 0.6875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100 116 139);
            margin-bottom: 0.25rem;
        }

        .voodbuilder-themes-ws__field input,
        .voodbuilder-themes-ws__field textarea,
        .voodbuilder-themes-ws__field select {
            width: 100%;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            padding: 0.4375rem 0.625rem;
            font-size: 0.875rem;
            background: #fff;
            color: rgb(15 23 42);
        }

        .voodbuilder-themes-ws__field select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            padding-right: 2rem;
            background-color: #fff;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.5rem center;
            background-size: 1rem 1rem;
            box-shadow: none;
        }

        .voodbuilder-themes-ws__field select:focus {
            outline: 2px solid color-mix(in srgb, var(--vp-ws-accent) 35%, transparent);
            outline-offset: 1px;
            border-color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__field input:disabled {
            background: rgb(248 250 252);
            color: rgb(100 116 139);
        }

        .voodbuilder-themes-ws__field-hint {
            margin-top: 0.25rem;
            font-size: 0.75rem;
            color: rgb(100 116 139);
        }

        .dark .voodbuilder-themes-ws__field input,
        .dark .voodbuilder-themes-ws__field textarea,
        .dark .voodbuilder-themes-ws__field select {
            background-color: rgb(15 23 42);
            border-color: rgb(71 85 105);
            color: #fff;
        }

        .dark .voodbuilder-themes-ws__field select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='m19.5 8.25-7.5 7.5-7.5-7.5'/%3E%3C/svg%3E");
        }

        .voodbuilder-themes-ws__palette-block {
            margin-bottom: 0.875rem;
        }

        .voodbuilder-themes-ws__palette-label {
            font-size: 0.75rem;
            font-weight: 600;
            margin-bottom: 0.375rem;
            color: rgb(71 85 105);
        }

        .voodbuilder-themes-ws__strip {
            display: flex;
            height: 1.75rem;
            border-radius: 0.5rem;
            overflow: hidden;
            border: 1px solid rgb(226 232 240);
        }

        .voodbuilder-themes-ws__swatch {
            flex: 1;
            min-width: 0;
            border: none;
            cursor: pointer;
            transition: opacity 0.15s ease;
        }

        .voodbuilder-themes-ws__swatch:hover {
            opacity: 0.88;
        }

        .voodbuilder-themes-ws__swatch--empty {
            background: repeating-linear-gradient(
                -45deg,
                rgb(241 245 249),
                rgb(241 245 249) 6px,
                rgb(248 250 252) 6px,
                rgb(248 250 252) 12px
            ) !important;
        }

        .voodbuilder-themes-ws__palette-actions {
            margin-top: 0.375rem;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .voodbuilder-themes-ws__preview-mode {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
            margin-top: 0.5rem;
            margin-bottom: 0.65rem;
        }

        .voodbuilder-themes-ws__preview-mode > button {
            border: 0;
            border-radius: 0.4rem;
            padding: 0.3rem 0.65rem;
            font-size: 0.75rem;
            font-weight: 600;
            cursor: pointer;
            background: rgb(241 245 249);
            color: rgb(71 85 105);
        }

        .dark .voodbuilder-themes-ws__preview-mode > button {
            background: rgb(30 41 59);
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__preview-mode > button.is-active {
            background: color-mix(in srgb, var(--vp-ws-accent) 16%, transparent);
            color: var(--vp-ws-accent);
        }

        .voodbuilder-themes-ws__preview-mode .voodbuilder-themes-ws__palette-actions {
            margin-top: 0;
        }

        .voodbuilder-themes-ws__tiles {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.55rem;
            margin-bottom: 0;
        }

        @media (max-width: 720px) {
            .voodbuilder-themes-ws__tiles {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .voodbuilder-themes-ws__tile {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
            padding: 0;
            border: 0;
            background: transparent;
            cursor: pointer;
            text-align: left;
            min-width: 0;
        }

        .voodbuilder-themes-ws__tile-swatch {
            display: block;
            height: 3.25rem;
            border-radius: 0.55rem;
            box-shadow: inset 0 0 0 1px rgb(15 23 42 / 0.06);
        }

        .dark .voodbuilder-themes-ws__tile-swatch {
            box-shadow: inset 0 0 0 1px rgb(248 250 252 / 0.08);
        }

        .voodbuilder-themes-ws__tile-label {
            font-size: 0.65rem;
            font-weight: 650;
            color: rgb(71 85 105);
            letter-spacing: 0.02em;
        }

        .dark .voodbuilder-themes-ws__tile-label {
            color: rgb(148 163 184);
        }

        .voodbuilder-themes-ws__tile-hex {
            font-size: 0.65rem;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: rgb(100 116 139);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .voodbuilder-themes-ws__live-preview {
            margin-top: 1.35rem;
            border-radius: 0.75rem;
            overflow: hidden;
            background: var(--lp-bg, #fff);
            color: var(--lp-text, #0f172a);
            box-shadow: 0 0 0 1px rgb(15 23 42 / 0.06);
        }

        .dark .voodbuilder-themes-ws__live-preview {
            box-shadow: 0 0 0 1px rgb(248 250 252 / 0.08);
        }

        .voodbuilder-themes-ws__catalog-tabs {
            margin-bottom: 0.85rem;
        }

        .voodbuilder-themes-ws__catalog--tabs {
            display: block;
            margin: 0;
        }

        .voodbuilder-themes-ws__catalog--tabs .voodbuilder-themes-ws__catalog-column {
            display: block;
        }

        .voodbuilder-themes-ws__live-preview-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.4rem 0.65rem;
            font-size: 0.625rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            background: rgb(248 250 252);
            color: rgb(100 116 139);
            border-bottom: 1px solid rgb(226 232 240 / 0.9);
        }

        .dark .voodbuilder-themes-ws__live-preview-label {
            background: rgb(15 23 42 / 0.65);
            color: rgb(148 163 184);
            border-bottom-color: rgb(51 65 85 / 0.8);
        }

        .voodbuilder-themes-ws__live-preview-label span {
            font-weight: 600;
            opacity: 0.85;
            text-transform: none;
            letter-spacing: 0;
        }

        .voodbuilder-themes-ws__live-preview-chrome {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.55rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 650;
            background: var(--lp-header, var(--lp-bg));
            color: var(--lp-header-text, var(--lp-text));
        }

        .voodbuilder-themes-ws__live-preview-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            font-weight: 700;
            letter-spacing: -0.01em;
        }

        .voodbuilder-themes-ws__live-preview-brand-mark {
            width: 0.55rem;
            height: 0.55rem;
            border-radius: 999px;
            background: var(--lp-primary);
            box-shadow: 0 0 0 2px color-mix(in srgb, var(--lp-primary) 28%, transparent);
        }

        .voodbuilder-themes-ws__live-preview-nav {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
            font-size: 0.68rem;
            font-weight: 550;
            opacity: 0.88;
        }

        .voodbuilder-themes-ws__live-preview-nav span.is-active {
            opacity: 1;
            font-weight: 700;
            color: var(--lp-primary);
        }

        .voodbuilder-themes-ws__live-preview-body {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem;
        }

        .voodbuilder-themes-ws__live-preview-body p {
            flex: 1 1 100%;
            margin: 0.15rem 0 0;
            font-size: 0.75rem;
            line-height: 1.4;
            opacity: 0.85;
        }

        .voodbuilder-themes-ws__live-preview-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.45rem;
            border-radius: 0.3rem;
            font-size: 0.65rem;
            font-weight: 600;
            background: color-mix(in srgb, var(--lp-secondary) 18%, transparent);
            color: var(--lp-secondary);
        }

        .voodbuilder-themes-ws__live-preview-btn {
            display: inline-flex;
            align-items: center;
            padding: 0.3rem 0.65rem;
            border-radius: 0.4rem;
            font-size: 0.7rem;
            font-weight: 650;
            background: var(--lp-primary);
            color: #fff;
        }

        .voodbuilder-themes-ws__link {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--vp-ws-accent);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .voodbuilder-themes-ws__modal-backdrop {
            /* Teleported to body — keep accent tokens on the modal host itself. */
            --vp-ws-accent: #ea580c;
            --vp-ws-accent-hover: #c2410c;
            position: fixed;
            inset: 0;
            background: rgb(15 23 42 / 0.55);
            z-index: 200;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .voodbuilder-themes-ws__modal {
            background: #fff;
            border-radius: 0.75rem;
            padding: 1.125rem;
            width: 100%;
            max-width: 22rem;
            border: 1px solid rgb(226 232 240);
            box-shadow: 0 16px 40px rgb(15 23 42 / 0.16);
        }

        .dark .voodbuilder-themes-ws__modal {
            background: rgb(30 41 59);
            border-color: rgb(51 65 85);
        }

        .voodbuilder-themes-ws__modal h3 {
            margin: 0 0 0.25rem;
            font-size: 0.9375rem;
            font-weight: 600;
            color: rgb(15 23 42);
        }

        .dark .voodbuilder-themes-ws__modal h3 {
            color: rgb(248 250 252);
        }

        .voodbuilder-themes-ws__modal-subtitle {
            margin: 0 0 0.875rem;
            font-size: 0.8125rem;
            color: rgb(100 116 139);
        }

        .voodbuilder-themes-ws__color-input-row {
            display: flex;
            gap: 0.625rem;
            align-items: center;
        }

        .voodbuilder-themes-ws__color-input-row input[type="color"] {
            width: 2.75rem;
            height: 2.75rem;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.5rem;
            padding: 0.125rem;
            cursor: pointer;
            flex-shrink: 0;
            background: #fff;
        }

        .voodbuilder-themes-ws__color-input-row input[type="text"] {
            flex: 1;
            border-radius: 0.5rem;
            border: 1px solid rgb(203 213 225);
            padding: 0.4375rem 0.625rem;
            font-size: 0.875rem;
            font-family: ui-monospace, monospace;
        }

        .voodbuilder-themes-ws__opacity-block {
            margin-top: 1rem;
            padding-top: 0.875rem;
            border-top: 1px solid #e2e8f0;
        }

        .voodbuilder-themes-ws__opacity-toggle {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            cursor: pointer;
        }

        .voodbuilder-themes-ws__opacity-slider-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 0.75rem;
            font-size: 0.8125rem;
            color: #334155;
        }

        .voodbuilder-themes-ws__opacity-slider {
            width: 100%;
            margin-top: 0.35rem;
            accent-color: #3451b2;
        }

        .voodbuilder-themes-ws__opacity-preview {
            margin-top: 0.75rem;
            height: 2rem;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
        }

        .voodbuilder-themes-ws__seed-row {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .voodbuilder-themes-ws__seed-row input[type="color"] {
            width: 2.25rem;
            height: 2.25rem;
            border: 1px solid rgb(203 213 225);
            border-radius: 0.375rem;
            padding: 0.125rem;
            flex-shrink: 0;
        }

        .voodbuilder-themes-ws__seed-row input[type="text"] {
            flex: 1;
        }
    </style>

    @if ($studioRole !== 'editor')
    @unless ($studioDesk)
    <div class="voodbuilder-themes-ws__header">
        <p class="voodbuilder-themes-ws__header-hint">{{ __('voodbuilder::settings.themes_library_intro') }}</p>
    </div>
    @endunless

    @if ($studioDesk)
        <div class="voodbuilder-themes-ws__preview-mode voodbuilder-themes-ws__catalog-tabs">
            <button
                type="button"
                class="@if ($catalogTab === 'plugin') is-active @endif"
                wire:click="setCatalogTab('plugin')"
            >{{ __('voodbuilder::settings.theme_origin_base') }}</button>
            <button
                type="button"
                class="@if ($catalogTab === 'custom') is-active @endif"
                wire:click="setCatalogTab('custom')"
            >{{ __('voodbuilder::settings.theme_origin_custom') }}</button>
        </div>
    @endif

    <div class="voodbuilder-themes-ws__catalog @if ($studioDesk) voodbuilder-themes-ws__catalog--tabs @endif">
        @if (! $studioDesk || $catalogTab === 'plugin')
        <div class="voodbuilder-themes-ws__catalog-column" @if ($studioDesk) wire:key="catalog-tab-plugin" @endif>
            @unless ($studioDesk)
            <div class="voodbuilder-themes-ws__catalog-column-header">
                <h3 class="voodbuilder-themes-ws__section-title">{{ __('voodbuilder::settings.theme_workspace_plugin_themes') }}</h3>
            </div>
            @endunless
            @if ($groups['plugin'] !== [])
                <div class="voodbuilder-themes-ws__grid" wire:key="plugin-themes-grid">
                    @foreach ($groups['plugin'] as $card)
                        <div wire:key="plugin-theme-{{ $card['id'] }}-{{ implode('-', $card['strip'] ?? []) }}">
                            @include('voodbuilder::filament.partials.theme-card', ['card' => $card, 'selectedId' => $selectedId])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
        @endif

        @if (! $studioDesk || $catalogTab === 'custom')
        <div class="voodbuilder-themes-ws__catalog-column" @if ($studioDesk) wire:key="catalog-tab-custom" @endif>
            @unless ($studioDesk)
            <div class="voodbuilder-themes-ws__catalog-column-header">
                <h3 class="voodbuilder-themes-ws__section-title">{{ __('voodbuilder::settings.theme_workspace_your_themes') }}</h3>
            </div>
            @endunless
            <div class="voodbuilder-themes-ws__grid" wire:key="custom-themes-grid">
                @foreach ($groups['custom'] as $card)
                    <div wire:key="custom-theme-{{ $card['id'] }}-{{ implode('-', $card['strip'] ?? []) }}">
                        @include('voodbuilder::filament.partials.theme-card', ['card' => $card, 'selectedId' => $selectedId])
                    </div>
                @endforeach
                <div wire:key="import-theme-card">
                    @include('voodbuilder::filament.partials.theme-import-card')
                </div>
            </div>
        </div>
        @endif
    </div>
    @endif

    @if ($studioRole !== 'catalog')
    @if ($selectedId)
        <div class="voodbuilder-themes-ws__editor" wire:key="editor-{{ $selectedId }}">
            @unless ($brandDesk)
            <div class="voodbuilder-themes-ws__editor-header">
                <h2 class="voodbuilder-themes-ws__editor-title">
                    {{ __('voodbuilder::settings.theme_workspace_edit', ['name' => $label]) }}
                </h2>
                @if ($studioDesk && ($metaEditable || $canEditColors))
                    @include('voodbuilder::filament.partials.theme-editor-icon-actions')
                @elseif (! $studioDesk)
                <button type="button" class="voodbuilder-themes-ws__editor-close" wire:click="closeEditor">
                    {{ __('voodbuilder::settings.theme_workspace_close') }}
                </button>
                @endif
            </div>
            @endunless

            @if ($brandDesk && $brandThemeOptions !== [])
                <div class="voodbuilder-themes-ws__brand-picker">
                    <label for="voodbuilder-brand-theme-select">{{ __('voodbuilder::settings.theme_studio_colors_layout') }}</label>
                    <select
                        id="voodbuilder-brand-theme-select"
                        wire:change="selectBrandTheme($event.target.value)"
                    >
                        @foreach ($brandThemeOptions as $themeId => $themeLabel)
                            <option value="{{ $themeId }}" @selected($themeId === $selectedId)>{{ $themeLabel }}</option>
                        @endforeach
                    </select>
                    <button
                        type="button"
                        class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost"
                        wire:click="applyColorsToAssignedLayouts"
                    >
                        {{ __('voodbuilder::settings.theme_studio_colors_apply_all') }}
                    </button>
                </div>
            @endif

            @if (! $brandDesk && ! $studioDesk && ($metaEditable || $canEditColors))
                <div class="voodbuilder-themes-ws__editor-actions">
                    <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost" wire:click="openCloneModal('{{ $selectedId }}')">
                        {{ __('voodbuilder::settings.clone_theme') }}
                    </button>
                    <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost" wire:click="exportTheme('{{ $selectedId }}')">
                        {{ __('voodbuilder::settings.export_theme') }}
                    </button>
                    <label class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost voodbuilder-themes-ws__import-action" wire:loading.class="is-importing" wire:target="importArchive">
                        <span wire:loading.remove wire:target="importArchive">{{ __('voodbuilder::settings.import_theme') }}</span>
                        <span wire:loading wire:target="importArchive">{{ __('voodbuilder::settings.theme_workspace_importing') }}</span>
                        <input type="file" wire:model="importArchive" accept=".zip,application/zip" />
                    </label>
                    @if ($canEditColors)
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost" wire:click="copyColorScheme">
                            {{ __('voodbuilder::settings.copy_color_scheme') }}
                        </button>
                        @if ($canPasteColorScheme)
                            <button
                                type="button"
                                class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost"
                                style="border-color:#93c5fd;color:#1d4ed8"
                                x-data
                                x-on:click="
                                    if (navigator.clipboard?.readText) {
                                        navigator.clipboard.readText()
                                            .then((text) => $wire.pasteColorScheme(text))
                                            .catch(() => $wire.pasteColorScheme())
                                    } else {
                                        $wire.pasteColorScheme()
                                    }
                                "
                            >
                                {{ __('voodbuilder::settings.paste_color_scheme') }}
                            </button>
                        @endif
                    @endif
                    @if ($canDelete)
                        <button
                            type="button"
                            class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost"
                            style="color:#b91c1c;border-color:#fecaca"
                            wire:click="confirmDelete"
                        >
                            {{ __('voodbuilder::settings.delete_theme') }}
                        </button>
                    @endif
                </div>
            @endif

            @unless ($brandDesk)
            <div class="voodbuilder-themes-ws__fields">
                <div class="voodbuilder-themes-ws__field @if ($studioDesk) voodbuilder-themes-ws__field--full @endif" @if ($studioDesk) style="grid-column: 1 / -1;" @endif>
                    <label>{{ __('voodbuilder::settings.theme_workspace_title') }}</label>
                    <input type="text" wire:model.blur="label" @disabled(! $metaEditable) />
                </div>
                @unless ($studioDesk)
                <div class="voodbuilder-themes-ws__field">
                    <label>{{ __('voodbuilder::settings.theme_workspace_alias') }}</label>
                    <input
                        type="text"
                        wire:model.blur="themeSlug"
                        pattern="[a-z][a-z0-9-]*"
                        @disabled(! $metaEditable)
                    />
                    <p class="voodbuilder-themes-ws__field-hint">{{ __('voodbuilder::settings.theme_workspace_alias_help') }}</p>
                </div>
                @endunless
                <div class="voodbuilder-themes-ws__field" style="grid-column: 1 / -1;">
                    <label>{{ __('voodbuilder::settings.theme_workspace_description') }}</label>
                    <textarea wire:model.blur="description" rows="2" @disabled(! $metaEditable)></textarea>
                </div>
            </div>

            @if ($metaEditable && ! $studioDesk)
                <div class="voodbuilder-themes-ws__meta-actions">
                    <p class="voodbuilder-themes-ws__field-hint">{{ __('voodbuilder::settings.theme_workspace_meta_save_hint') }}</p>
                    <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--primary" wire:click="saveMetadata">
                        {{ __('voodbuilder::settings.theme_workspace_save_details') }}
                    </button>
                </div>
            @endif
            @endunless

            @if ($canEditColors)
                @if ($studioDesk)
                    <div class="voodbuilder-themes-ws__preview-mode">
                        <button
                            type="button"
                            class="@if ($previewMode === 'light') is-active @endif"
                            wire:click="setPreviewMode('light')"
                        >{{ __('voodbuilder::settings.theme_light_mode') }}</button>
                        <button
                            type="button"
                            class="@if ($previewMode === 'dark') is-active @endif"
                            wire:click="setPreviewMode('dark')"
                        >{{ __('voodbuilder::settings.theme_dark_mode') }}</button>
                        <div class="voodbuilder-themes-ws__palette-actions" style="margin-left:auto;">
                            <button type="button" class="voodbuilder-themes-ws__link" wire:click="openGenerateModal">
                                {{ __('voodbuilder::settings.theme_workspace_generate') }}
                            </button>
                            <button type="button" class="voodbuilder-themes-ws__link" wire:click="syncDarkFromLight">
                                {{ __('voodbuilder::settings.sync_dark_theme_colors') }}
                            </button>
                            <button type="button" class="voodbuilder-themes-ws__link" wire:click="resetColors">
                                {{ __('voodbuilder::settings.reset_theme_colors') }}
                            </button>
                        </div>
                    </div>
                    @php
                        $palette = $previewMode === 'dark' ? $dark : $light;
                        $previewKeys = ['primary', 'secondary', 'body_bg', 'text', 'header_bg'];
                    @endphp
                    <div class="voodbuilder-themes-ws__tiles">
                        @foreach ($previewKeys as $key)
                            @php
                                $hex = $palette[$key] ?? null;
                                $bg = $hex ?? '#e2e8f0';
                                $labelKey = $key === 'text' ? 'theme_body_text' : 'theme_'.$key;
                            @endphp
                            <button
                                type="button"
                                class="voodbuilder-themes-ws__tile"
                                wire:click="openColor('{{ $previewMode }}', '{{ $key }}')"
                            >
                                <span class="voodbuilder-themes-ws__tile-swatch" style="background: {{ $bg }}"></span>
                                <span class="voodbuilder-themes-ws__tile-label">{{ __('voodbuilder::settings.'.$labelKey) }}</span>
                                <span class="voodbuilder-themes-ws__tile-hex">{{ $hex ?? '—' }}</span>
                            </button>
                        @endforeach
                    </div>
                    @php
                        $p = $palette['primary'] ?? '#3451b2';
                        $s = $palette['secondary'] ?? $p;
                        $bg = $palette['body_bg'] ?? '#ffffff';
                        $tx = $palette['text'] ?? '#0f172a';
                        $hd = $palette['header_bg'] ?? $bg;
                        $ht = $palette['header_text'] ?? $tx;
                    @endphp
                    <div
                        class="voodbuilder-themes-ws__live-preview"
                        style="--lp-bg: {{ $bg }}; --lp-text: {{ $tx }}; --lp-primary: {{ $p }}; --lp-secondary: {{ $s }}; --lp-header: {{ $hd }}; --lp-header-text: {{ $ht }};"
                    >
                        <div class="voodbuilder-themes-ws__live-preview-label">
                            {{ __('voodbuilder::settings.theme_studio_live_preview') }}
                            <span>{{ $previewMode === 'dark' ? __('voodbuilder::settings.theme_dark_mode') : __('voodbuilder::settings.theme_light_mode') }}</span>
                        </div>
                        <div class="voodbuilder-themes-ws__live-preview-chrome" aria-hidden="true">
                            <span class="voodbuilder-themes-ws__live-preview-brand">
                                <span class="voodbuilder-themes-ws__live-preview-brand-mark"></span>
                                {{ __('voodbuilder::settings.theme_studio_live_preview_brand') }}
                            </span>
                            <nav class="voodbuilder-themes-ws__live-preview-nav">
                                <span class="is-active">{{ __('voodbuilder::settings.theme_studio_live_preview_nav_home') }}</span>
                                <span>{{ __('voodbuilder::settings.theme_studio_live_preview_nav_about') }}</span>
                                <span>{{ __('voodbuilder::settings.theme_studio_live_preview_nav_contact') }}</span>
                            </nav>
                        </div>
                        <div class="voodbuilder-themes-ws__live-preview-body">
                            <span class="voodbuilder-themes-ws__live-preview-tag">{{ __('voodbuilder::settings.theme_studio_live_preview_tag') }}</span>
                            <span class="voodbuilder-themes-ws__live-preview-btn">{{ __('voodbuilder::settings.theme_studio_live_preview_action') }}</span>
                            <p>{{ __('voodbuilder::settings.theme_studio_live_preview_sample') }}</p>
                        </div>
                    </div>
                @else
                @foreach (['light' => __('voodbuilder::settings.theme_light_mode'), 'dark' => __('voodbuilder::settings.theme_dark_mode')] as $mode => $modeLabel)
                @php
                    $palette = $mode === 'light' ? $light : $dark;
                @endphp
                <div class="voodbuilder-themes-ws__palette-block">
                    <div class="voodbuilder-themes-ws__palette-label">{{ $modeLabel }}</div>
                    <div class="voodbuilder-themes-ws__strip">
                        @foreach ($colorKeys as $key)
                            @php
                                $hex = $palette[$key] ?? null;
                                $bg = $hex ?? '#e2e8f0';
                                $labelKey = $key === 'text' ? 'theme_body_text' : 'theme_'.$key;
                            @endphp
                            <button
                                type="button"
                                class="voodbuilder-themes-ws__swatch @if (! $hex) voodbuilder-themes-ws__swatch--empty @endif"
                                style="background: {{ $bg }}"
                                title="{{ __('voodbuilder::settings.'.$labelKey) }}"
                                wire:click="openColor('{{ $mode }}', '{{ $key }}')"
                            ></button>
                        @endforeach
                    </div>
                    <div class="voodbuilder-themes-ws__palette-actions">
                        @if ($mode === 'light')
                            <button type="button" class="voodbuilder-themes-ws__link" wire:click="openGenerateModal">
                                {{ __('voodbuilder::settings.theme_workspace_generate') }}
                            </button>
                        @else
                            <button type="button" class="voodbuilder-themes-ws__link" wire:click="syncDarkFromLight">
                                {{ __('voodbuilder::settings.sync_dark_theme_colors') }}
                            </button>
                        @endif
                        <button type="button" class="voodbuilder-themes-ws__link" wire:click="resetColors">
                            {{ __('voodbuilder::settings.reset_theme_colors') }}
                        </button>
                    </div>
                </div>
            @endforeach
                @endif
            @else
                <div class="voodbuilder-themes-ws__palette-block">
                    <p class="voodbuilder-themes-ws__field-hint">{{ __('voodbuilder::settings.theme_workspace_bundled_colors_help') }}</p>
                    <button type="button" class="voodbuilder-themes-ws__link" wire:click="resetColors">
                        {{ __('voodbuilder::settings.reset_theme_colors') }}
                    </button>
                </div>
            @endif
        </div>
    @else
        <div class="voodbuilder-theme-studio__empty" wire:key="editor-empty">
            <h3 class="voodbuilder-theme-studio__empty-title">{{ __('voodbuilder::settings.theme_studio_edit_empty_title') }}</h3>
            <p class="voodbuilder-theme-studio__empty-body">{{ __('voodbuilder::settings.theme_studio_edit_empty_body') }}</p>
        </div>
    @endif
    @endif

    @if ($showColorModal)
        <template x-teleport="body">
            <div class="voodbuilder-themes-ws__modal-backdrop" wire:click.self="closeColorModal" x-data>
                <div class="voodbuilder-themes-ws__modal" wire:click.stop @click.stop>
                    <h3>{{ __('voodbuilder::settings.theme_workspace_pick_color_for', ['label' => $colorKeyLabel]) }}</h3>
                    <p class="voodbuilder-themes-ws__modal-subtitle">{{ ucfirst($colorMode) }} · {{ __('voodbuilder::settings.theme_workspace_auto_apply') }}</p>
                    <div class="voodbuilder-themes-ws__color-input-row">
                        <input type="color" wire:model.live="colorValue" />
                        <input type="text" wire:model.live.debounce.300ms="colorValue" maxlength="7" />
                    </div>
                    @if ($colorKey === 'header_bg')
                        <div class="voodbuilder-themes-ws__opacity-block">
                            <label class="voodbuilder-themes-ws__opacity-toggle">
                                <input type="checkbox" wire:model.live="headerBgTransparent" />
                                <span>{{ __('voodbuilder::settings.theme_header_bg_transparent') }}</span>
                            </label>
                            <p class="voodbuilder-themes-ws__field-hint">{{ __('voodbuilder::settings.theme_header_bg_transparent_help') }}</p>
                            @if ($headerBgTransparent)
                                <label class="voodbuilder-themes-ws__opacity-slider-label" for="voodbuilder-header-bg-opacity">
                                    {{ __('voodbuilder::settings.theme_header_bg_opacity') }}
                                    <strong>{{ $headerBgOpacity }}%</strong>
                                </label>
                                <input
                                    id="voodbuilder-header-bg-opacity"
                                    class="voodbuilder-themes-ws__opacity-slider"
                                    type="range"
                                    min="0"
                                    max="100"
                                    step="1"
                                    wire:model.live="headerBgOpacity"
                                />
                                <div
                                    class="voodbuilder-themes-ws__opacity-preview"
                                    style="background-image:
                                        linear-gradient({{ \Voodflow\Voodbuilder\Support\ThemePalette::headerBackgroundCssValue($colorValue, (int) $headerBgOpacity) }}, {{ \Voodflow\Voodbuilder\Support\ThemePalette::headerBackgroundCssValue($colorValue, (int) $headerBgOpacity) }}),
                                        repeating-conic-gradient(#cbd5e1 0% 25%, #f8fafc 0% 50%);
                                        background-size: auto, 12px 12px;"
                                ></div>
                            @endif
                        </div>
                    @endif
                    <div class="flex gap-2 mt-4 justify-between">
                        <button type="button" class="voodbuilder-themes-ws__link" wire:click="clearColor('{{ $colorMode }}', '{{ $colorKey }}')">
                            {{ __('voodbuilder::settings.theme_workspace_clear_color') }}
                        </button>
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--primary" wire:click="closeColorModal">
                            {{ __('voodbuilder::settings.theme_workspace_done') }}
                        </button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if ($showCloneModal)
        <template x-teleport="body">
            <div class="voodbuilder-themes-ws__modal-backdrop" wire:click.self="$set('showCloneModal', false)" x-data>
                <div class="voodbuilder-themes-ws__modal" wire:click.stop @click.stop>
                    <h3>{{ __('voodbuilder::settings.clone_theme') }}</h3>
                    <p class="voodbuilder-themes-ws__modal-subtitle">{{ __('voodbuilder::settings.clone_theme_help') }}</p>
                    <div class="voodbuilder-themes-ws__field mb-3">
                        <label>{{ __('voodbuilder::settings.create_theme_id') }}</label>
                        <input type="text" wire:model="cloneTargetId" pattern="[a-z][a-z0-9-]*" />
                    </div>
                    <div class="voodbuilder-themes-ws__field mb-4">
                        <label>{{ __('voodbuilder::settings.create_theme_label') }}</label>
                        <input type="text" wire:model="cloneLabel" />
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost" wire:click="$set('showCloneModal', false)">{{ __('Cancel') }}</button>
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--primary" wire:click="executeClone">{{ __('voodbuilder::settings.clone_theme') }}</button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if ($showGenerateModal)
        <template x-teleport="body">
            <div class="voodbuilder-themes-ws__modal-backdrop" wire:click.self="$set('showGenerateModal', false)" x-data>
                <div class="voodbuilder-themes-ws__modal" wire:click.stop @click.stop style="max-width: 24rem;">
                    <h3>{{ __('voodbuilder::settings.generate_theme_palette') }}</h3>
                    <p class="voodbuilder-themes-ws__modal-subtitle">{{ __('voodbuilder::settings.generate_theme_palette_help') }}</p>
                    @foreach (['seedPrimary' => 'palette_seed_primary', 'seedSecondary' => 'palette_seed_secondary', 'seedHeaderBg' => 'palette_seed_header_bg'] as $prop => $langKey)
                        <div class="voodbuilder-themes-ws__field mb-3">
                            <label>{{ __('voodbuilder::settings.'.$langKey) }}</label>
                            <div class="voodbuilder-themes-ws__seed-row">
                                <input type="color" wire:model.live="{{ $prop }}" />
                                <input type="text" wire:model.live.debounce.300ms="{{ $prop }}" placeholder="#3451b2" />
                            </div>
                        </div>
                    @endforeach
                    <div class="flex gap-2 justify-end">
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost" wire:click="$set('showGenerateModal', false)">{{ __('Cancel') }}</button>
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--primary" wire:click="generatePalette">{{ __('voodbuilder::settings.generate_theme_palette') }}</button>
                    </div>
                </div>
            </div>
        </template>
    @endif

    @if ($showDeleteModal)
        <template x-teleport="body">
            <div class="voodbuilder-themes-ws__modal-backdrop" wire:click.self="$set('showDeleteModal', false)" x-data>
                <div class="voodbuilder-themes-ws__modal" wire:click.stop @click.stop>
                    <h3>{{ __('voodbuilder::settings.delete_theme') }}</h3>
                    <p class="voodbuilder-themes-ws__modal-subtitle">{{ __('voodbuilder::settings.delete_theme_help') }}</p>
                    <div class="voodbuilder-themes-ws__field mb-4">
                        <label>{{ __('voodbuilder::settings.delete_theme_fallback') }}</label>
                        <select wire:model="deleteFallbackId">
                            @foreach ($fallbackOptions as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2 justify-end">
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--ghost" wire:click="$set('showDeleteModal', false)">{{ __('Cancel') }}</button>
                        <button type="button" class="voodbuilder-themes-ws__btn voodbuilder-themes-ws__btn--primary" style="background:#dc2626;border-color:#dc2626" wire:click="deleteTheme">{{ __('voodbuilder::settings.delete_theme') }}</button>
                    </div>
                </div>
            </div>
        </template>
    @endif
</div>
