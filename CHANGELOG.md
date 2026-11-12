# Changelog

## Unreleased

- W3CSS Paragraphs Menu ships as an empty obsolete stub, and an update uninstalls it: a site that had it enabled no longer stops `drush updb` with a missing-module error.
- Built for W3CSS Theme 3.x: 3.x class and id names, one W3.CSS copy (the module's own loads only on other themes), unlayered CSS with most `!important` removed, color classes read the theme's tokens, no jQuery; conflicts with `drupal/d8w3css` below 3.0.
- Sub-modules: logical properties, flex tab bar and accordion header, visible focus inside clipped paragraphs, reduced motion for parallax; tab arrow keys follow the layout on screen; once ids `w3css-paragraphs-3d-carousel` and `w3css-paragraphs-slideshow` (were `w3cssParagraphs3dCarousel`, `w3cssParagraphsSlideshow`).
