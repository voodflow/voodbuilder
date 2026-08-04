/**
 * VitePress / vdocs sidebar for VoodBuilder manuals.
 *
 * Merge into your site config, e.g.:
 *
 *   import { voodbuilderManualSidebar } from './path/to/voodbuilder/docs/manual/sidebar'
 *   export default defineConfig({
 *     themeConfig: { sidebar: { '/voodbuilder/': voodbuilderManualSidebar } }
 *   })
 *
 * Adjust the `base` prefix if your docs mount under a different path.
 */
export const voodbuilderManualSidebar = [
  {
    text: 'VoodBuilder',
    items: [
      { text: 'Overview', link: '/index' },
    ],
  },
  {
    text: 'User manual',
    collapsed: false,
    items: [
      { text: 'Introduction', link: '/user/' },
      { text: 'Getting started', link: '/user/getting-started' },
      {
        text: 'Admin',
        collapsed: false,
        items: [
          { text: 'Site pages', link: '/user/admin/pages' },
          { text: 'Menus', link: '/user/admin/menus' },
          { text: 'Chrome layouts', link: '/user/admin/chrome-layouts' },
          { text: 'Settings & Theme Studio', link: '/user/admin/settings-and-themes' },
        ],
      },
      {
        text: 'Visual builder',
        collapsed: false,
        items: [
          { text: 'Opening the editor', link: '/user/builder/opening-the-editor' },
          { text: 'Understanding the layout', link: '/user/builder/understanding-the-layout' },
          { text: 'Content width', link: '/user/builder/content-width' },
          { text: 'Canvas & toolbar', link: '/user/builder/canvas-and-toolbar' },
          { text: 'Library & blocks', link: '/user/builder/library-and-blocks' },
          { text: 'Inspector — Content', link: '/user/builder/inspector-content' },
          { text: 'Inspector — Style', link: '/user/builder/inspector-style' },
          { text: 'Site chrome in the editor', link: '/user/builder/site-chrome' },
          { text: 'Visibility conditions', link: '/user/builder/visibility-conditions' },
          { text: 'Revisions', link: '/user/builder/revisions' },
          { text: 'Page templates', link: '/user/builder/page-templates' },
          { text: 'Forms, tabs & code', link: '/user/builder/forms-tabs-code' },
          { text: 'Image editing', link: '/user/builder/image-editing' },
          { text: 'Global text tags', link: '/user/builder/global-text-tags' },
        ],
      },
      { text: 'Publishing & preview', link: '/user/publishing-and-preview' },
      { text: 'Companions (later)', link: '/user/companions-overview' },
    ],
  },
  {
    text: 'Developer manual',
    collapsed: false,
    items: [
      { text: 'Introduction', link: '/developer/' },
      { text: 'Architecture', link: '/developer/architecture' },
      { text: 'Installation (host app)', link: '/developer/installation' },
      { text: 'Config reference', link: '/developer/config-reference' },
      { text: 'Extending overview', link: '/developer/extending-overview' },
      {
        text: 'PHP SDK',
        collapsed: false,
        items: [
          { text: 'Blocks', link: '/developer/php-sdk/blocks' },
          { text: 'Server blocks', link: '/developer/php-sdk/server-blocks' },
          { text: 'Bindings', link: '/developer/php-sdk/bindings' },
          { text: 'Conditions', link: '/developer/php-sdk/conditions' },
          { text: 'Content channels', link: '/developer/php-sdk/content-channels' },
          { text: 'Menu item types', link: '/developer/php-sdk/menu-item-types' },
          { text: 'Fonts', link: '/developer/php-sdk/fonts' },
          { text: 'Sub-themes', link: '/developer/php-sdk/sub-themes' },
          { text: 'Modules', link: '/developer/php-sdk/modules' },
          { text: 'Entitlements', link: '/developer/php-sdk/entitlements' },
        ],
      },
      { text: 'JS plugins', link: '/developer/js-plugins' },
      { text: 'Block authoring', link: '/developer/block-authoring' },
      { text: 'Rendering pipeline', link: '/developer/rendering-pipeline' },
      { text: 'Events & hooks', link: '/developer/events-and-hooks' },
      { text: 'Sample plugin', link: '/developer/sample-plugin' },
    ],
  },
]

export default voodbuilderManualSidebar
