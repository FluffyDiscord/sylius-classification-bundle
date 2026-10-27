# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); the project adheres to Semantic Versioning.

## [1.2.0]

### Changed

- The admin grids are PHP grid classes (`#[AsGrid]`) instead of YAML, so sylius/grid-bundle 1.16 no
  longer logs the legacy grid config deprecation. Grid names, fields, sorting, filters and actions are
  unchanged.
- Customising the grids: use a grid mutator (`#[AsGridMutator(grid: 'sylius_classification_admin_classification')]`).
  A `sylius_grid.grids.<grid name>` entry in your YAML is no longer merged into the bundle's grid: with
  Sylius core's default `sylius_core.grid.use_legacy_config: true` it replaces the whole grid, so a
  partial override must become a mutator or a full grid definition.

## [1.1.0]

### Added

- Support for Sylius 2.3 and Symfony UX 3. The bundle installs on Sylius `^2.2` with
  `sylius/twig-hooks` `^0.9 || ^0.12 || ^0.14`, Symfony UX (`ux-autocomplete`, `ux-live-component`,
  `ux-twig-component`) `^2.25 || ^3.0` and API Platform (`metadata`, `state`) `^4.2 || ^5.0`. Sylius 2.2
  with UX 2 keeps working unchanged.

## [1.0.1]

### Fixed

- `GET /admin/product-variants/{code}/classification-values` returned `404` for every
  variant. The operation referenced its provider and processors by class name, but the
  services are registered under `sylius_classification.state.*` ids (the class names are
  only aliases, which API Platform's provider/processor locator does not resolve). The
  four affected operations now reference their services by id, so the subresource read,
  the value/type create (unique-code guard), and the variant assignment all work.
- The variant-assignment subresource now resolves its parent through the
  `classificationValues` collection (`fromProperty`) instead of the inverse
  `productVariant` side (`toProperty`).

### Changed

- Assigning a second value of a single-select classification to a variant now **replaces**
  the existing value (the assign processor and the admin row component both remove the
  previous value), instead of being rejected with a validation error. The
  `SingleSelectCardinality` constraint is no longer wired onto the API write — the replace
  behaviour is the enforced invariant. The validator class is retained but unused.

