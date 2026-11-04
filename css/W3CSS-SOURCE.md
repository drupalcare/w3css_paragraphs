# W3.CSS in this module

`w3-modern.css` is a byte-identical copy of the D8W3CSS theme's
`css/w3-css/w3-modern.css` (W3.CSS 4.15, modernized; its `SOURCE.md` lists the
changes). Edit it there, never here, then copy it again.

The library `w3css_paragraphs/w3css-framework` loads it only when the active
theme does not provide W3.CSS:

- D8W3CSS (`drupal8_w3css_theme`) and its sub-themes always load it.
- Solo and its sub-themes load it when the theme setting `typography.load_w3css`
  is on.

Every other theme gets this copy, so the paragraph layouts (rows, columns,
display containers, cards, buttons) work without it.
