# W3CSS Paragraphs

W3CSS Paragraphs provides a suite of paragraph types made with the W3.CSS
front-end framework. It combines W3.CSS with the Paragraphs module, so content
editors can build layouts and structured pages without writing code.

With W3CSS Paragraphs you can build features like slideshows, accordions, hero
banners, image overlays, parallax sections, 3D carousels, modals, tabs
(horizontal or vertical), 3D flip boxes, and one, two and three column layouts
in minutes.

The main module has the first two basic paragraph types (Text and Image). Each
other paragraph type is in its own sub-module. All images use the Media module.

- Project page:
  [drupal.org/project/w3css_paragraphs](https://www.drupal.org/project/w3css_paragraphs)
- Issue queue:
  [drupal.org/project/issues/w3css_paragraphs](https://www.drupal.org/project/issues/w3css_paragraphs)


## Table of contents

- Requirements
- Recommended modules
- Installation
- Configuration
- Paragraph types
- Features
- Credits


## Requirements

This module requires the following modules:

- [Paragraphs](https://www.drupal.org/project/paragraphs)
- [Entity Reference Revisions](https://www.drupal.org/project/entity_reference_revisions)
- [Field Group](https://www.drupal.org/project/field_group)

It also needs these core modules: Block, Field, File, Filter, Image, Link,
Media, Media Library, Options, Text, User and Views.


## Recommended modules

- [W3CSS Theme](https://www.drupal.org/project/d8w3css): the paragraph types
  use W3.CSS classes, and this theme loads the W3.CSS library.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

Then enable the sub-module of each paragraph type you need.


## Configuration

The heading level of paragraph titles is set at Administration > Configuration >
Content authoring > W3CSS Paragraphs, from h2 to h6. The default is h3.
Accordion item headings use the next level down. A theme that targets
`h3.content-title` should target `.content-title` before choosing another level.


## Paragraph types

- W3CSS Paragraphs 3D Carousel (images)
- W3CSS Paragraphs 3D Carousel (text)
- W3CSS Paragraphs 3D Flip Box
- W3CSS Paragraphs Accordion
- W3CSS Paragraphs Card
- W3CSS Paragraphs Contact Form
- W3CSS Paragraphs Content Type
- W3CSS Paragraphs Custom Block
- W3CSS Paragraphs Drupal Block
- W3CSS Paragraphs Hero
- W3CSS Paragraphs Hero Full Width
- W3CSS Paragraphs Image
- W3CSS Paragraphs Image Overlay
- W3CSS Paragraphs Modal
- W3CSS Paragraphs Menu
- W3CSS Paragraphs Parallax
- W3CSS Paragraphs Simple
- W3CSS Paragraphs Slideshow
- W3CSS Paragraphs Tabs (horizontal or vertical)
- W3CSS Paragraphs One Column
- W3CSS Paragraphs Three Columns
- W3CSS Paragraphs Two Columns
- W3CSS Paragraphs Quicklinks
- W3CSS Paragraphs Responsive Image
- W3CSS Paragraphs Views
- W3CSS Paragraphs Webform


## Features

Some paragraph types have more or fewer of these settings.

- Background colors: 30 colors to choose from.
- Background color on hover: 30 colors to choose from.
- Text colors: 30 colors to choose from.
- Text color on hover: 30 colors to choose from.
- Border color: 30 colors to choose from.
- Border color on hover: 30 colors to choose from.
- Background color opacity: after you choose the background color, you can
  choose its opacity: 5%, 10%, 20%, 30%, 40%, 50%, 60%, 70%, 80%, 90% or 95%
  (11 x 30 = 330 colors). The opacity applies to the background color only.
- Width: 30%, 40%, 50%, 60%, 70%, 80%, 90% or 100%.
- Borders: 11 styles to choose from.
- Rounded borders: 6 styles to choose from.
- Margin: 6 styles to choose from.
- Padding: 8 styles to choose from.
- Card: add a 2px or 4px bordered shadow.
- W3CSS classes: one extra field for any W3.CSS classes.


## Credits

The section icons in `icons/` are
[Google Material Symbols](https://fonts.google.com/icons), Apache License 2.0,
GPL-compatible.
