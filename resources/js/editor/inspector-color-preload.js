/**
 * Side-effect preload: patch HTMLInputElement.value BEFORE Editor/Spectrum load.
 * Import this as the first statement in editor/init.js.
 */
import { installGlobalColorInputValueFix } from './inspector-color-fix.js';

installGlobalColorInputValueFix();
