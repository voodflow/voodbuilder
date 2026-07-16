/**
 * Side-effect preload: patch HTMLInputElement.value BEFORE GrapesJS/Spectrum load.
 * Import this as the first statement in editor/init.js.
 */
import { installGlobalColorInputValueFix } from './inspector-color-fix.js';

installGlobalColorInputValueFix();
