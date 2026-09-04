/**
 * Animation / transition utilities authored by the Style Manager Animation sector.
 * Dependency-free so page JIT can skip compile overlays and theme.css can @source
 * these exact class name literals for the canvas/public stylesheet.
 *
 * Keep full class strings (incl. hover:/active:) as literals — Tailwind @source
 * does not see runtime `${prefix}${base}` concatenation.
 */

/** Marker used by Interaction → on visible. */
export const VISIBLE_MARKER_CLASS = 'vb-animate-on-visible';

/**
 * Complete catalog of Animation-sector utilities (incl. hover:/active: variants).
 * @type {Set<string>}
 */
export const STYLE_ANIMATION_BUNDLED_UTILITIES = new Set([
    VISIBLE_MARKER_CLASS,

    // presets
    'animate-spin', 'hover:animate-spin', 'active:animate-spin',
    'animate-ping', 'hover:animate-ping', 'active:animate-ping',
    'animate-pulse', 'hover:animate-pulse', 'active:animate-pulse',
    'animate-bounce', 'hover:animate-bounce', 'active:animate-bounce',
    'animate-wiggle', 'hover:animate-wiggle', 'active:animate-wiggle',
    'animate-wiggle-more', 'hover:animate-wiggle-more', 'active:animate-wiggle-more',
    'animate-rotate-y', 'hover:animate-rotate-y', 'active:animate-rotate-y',
    'animate-rotate-x', 'hover:animate-rotate-x', 'active:animate-rotate-x',
    'animate-jump', 'hover:animate-jump', 'active:animate-jump',
    'animate-jump-in', 'hover:animate-jump-in', 'active:animate-jump-in',
    'animate-jump-out', 'hover:animate-jump-out', 'active:animate-jump-out',
    'animate-shake', 'hover:animate-shake', 'active:animate-shake',
    'animate-fade', 'hover:animate-fade', 'active:animate-fade',
    'animate-fade-down', 'hover:animate-fade-down', 'active:animate-fade-down',
    'animate-fade-up', 'hover:animate-fade-up', 'active:animate-fade-up',
    'animate-fade-left', 'hover:animate-fade-left', 'active:animate-fade-left',
    'animate-fade-right', 'hover:animate-fade-right', 'active:animate-fade-right',
    'animate-flip-up', 'hover:animate-flip-up', 'active:animate-flip-up',
    'animate-flip-down', 'hover:animate-flip-down', 'active:animate-flip-down',

    // iteration
    'animate-infinite', 'hover:animate-infinite', 'active:animate-infinite',
    'animate-once', 'hover:animate-once', 'active:animate-once',
    'animate-twice', 'hover:animate-twice', 'active:animate-twice',
    'animate-thrice', 'hover:animate-thrice', 'active:animate-thrice',

    // cycle duration
    'animate-duration-75', 'hover:animate-duration-75', 'active:animate-duration-75',
    'animate-duration-100', 'hover:animate-duration-100', 'active:animate-duration-100',
    'animate-duration-150', 'hover:animate-duration-150', 'active:animate-duration-150',
    'animate-duration-200', 'hover:animate-duration-200', 'active:animate-duration-200',
    'animate-duration-300', 'hover:animate-duration-300', 'active:animate-duration-300',
    'animate-duration-500', 'hover:animate-duration-500', 'active:animate-duration-500',
    'animate-duration-700', 'hover:animate-duration-700', 'active:animate-duration-700',
    'animate-duration-1000', 'hover:animate-duration-1000', 'active:animate-duration-1000',
    'animate-duration-2000', 'hover:animate-duration-2000', 'active:animate-duration-2000',
    'animate-duration-3000', 'hover:animate-duration-3000', 'active:animate-duration-3000',
    'animate-duration-5000', 'hover:animate-duration-5000', 'active:animate-duration-5000',
    'animate-duration-8000', 'hover:animate-duration-8000', 'active:animate-duration-8000',
    'animate-duration-10000', 'hover:animate-duration-10000', 'active:animate-duration-10000',
    'animate-duration-15000', 'hover:animate-duration-15000', 'active:animate-duration-15000',
    'animate-duration-20000', 'hover:animate-duration-20000', 'active:animate-duration-20000',
    'animate-duration-30000', 'hover:animate-duration-30000', 'active:animate-duration-30000',

    // delay
    'animate-delay-none', 'hover:animate-delay-none', 'active:animate-delay-none',
    'animate-delay-75', 'hover:animate-delay-75', 'active:animate-delay-75',
    'animate-delay-100', 'hover:animate-delay-100', 'active:animate-delay-100',
    'animate-delay-150', 'hover:animate-delay-150', 'active:animate-delay-150',
    'animate-delay-200', 'hover:animate-delay-200', 'active:animate-delay-200',
    'animate-delay-300', 'hover:animate-delay-300', 'active:animate-delay-300',
    'animate-delay-500', 'hover:animate-delay-500', 'active:animate-delay-500',
    'animate-delay-700', 'hover:animate-delay-700', 'active:animate-delay-700',
    'animate-delay-1000', 'hover:animate-delay-1000', 'active:animate-delay-1000',

    // easing
    'animate-ease', 'hover:animate-ease', 'active:animate-ease',
    'animate-ease-linear', 'hover:animate-ease-linear', 'active:animate-ease-linear',
    'animate-ease-in', 'hover:animate-ease-in', 'active:animate-ease-in',
    'animate-ease-out', 'hover:animate-ease-out', 'active:animate-ease-out',
    'animate-ease-in-out', 'hover:animate-ease-in-out', 'active:animate-ease-in-out',

    // direction
    'animate-normal', 'hover:animate-normal', 'active:animate-normal',
    'animate-reverse', 'hover:animate-reverse', 'active:animate-reverse',
    'animate-alternate', 'hover:animate-alternate', 'active:animate-alternate',
    'animate-alternate-reverse', 'hover:animate-alternate-reverse', 'active:animate-alternate-reverse',

    // fill mode
    'animate-fill-none', 'hover:animate-fill-none', 'active:animate-fill-none',
    'animate-fill-forwards', 'hover:animate-fill-forwards', 'active:animate-fill-forwards',
    'animate-fill-backwards', 'hover:animate-fill-backwards', 'active:animate-fill-backwards',
    'animate-fill-both', 'hover:animate-fill-both', 'active:animate-fill-both',

    // CSS transitions (Animation → Transitions tab)
    'transition', 'transition-all', 'transition-colors', 'transition-opacity',
    'transition-shadow', 'transition-transform', 'transition-none',
    'duration-75', 'duration-100', 'duration-150', 'duration-200', 'duration-300',
    'duration-500', 'duration-700', 'duration-1000',
    'ease-linear', 'ease-in', 'ease-out', 'ease-in-out',
    'delay-75', 'delay-100', 'delay-150', 'delay-200', 'delay-300',
    'delay-500', 'delay-700', 'delay-1000',
]);
