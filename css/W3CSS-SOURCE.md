# W3.CSS in this module

One copy rule: the W3CSS Theme 3.x (`drupal8_w3css_theme`) and its sub-themes,
at any depth, load their own W3.CSS library. This module's copy,
`w3-modern.css` (library `w3css_paragraphs/w3css-framework`), loads only on
every other theme. `W3cssThemeDetection` answers the question; the preprocess
hook attaches the library and adds the `theme` cache context. Solo and its
sub-themes still skip it when the theme setting `typography.load_w3css` is on.

## Source

Derived on 2026-11-10 from `d8w3css/css/base/w3css-drupal.css` (sha256
`aed6e0d84e289849e104e0ab05c7e9245ca6f84c7074b5f22b6e06a2a2a4e709`), the
modern rebuild of W3.CSS 4.15 (see that theme's `css/w3-css/SOURCE.md`).

How: the cascade layers are removed (this module's CSS is unlayered, like
Paragraphs Bundles) and the blocks are kept in this order: tokens, layout,
library, utilities, locked. The `!important` left (18) is the theme's own
`w3css.locked` set: show/hide classes, focus ring, reduced motion.

The theme's `w3css.reset` and `w3css.base` layers are not copied, because they
style `html`, `body` and headings of a foreign theme. A small scoped stand-in at
the end of the file gives the module's `.p-container` wrappers the box sizing,
heading and image rules. Root font size (93.75%) is not set; rem sizes follow
the host theme.

Centering: the library centers `.w3-display-*` with `transform: translate()`.
Only one source sets it now, so nothing adds up.

Edit the theme's file, then derive this copy again; never edit it here.
