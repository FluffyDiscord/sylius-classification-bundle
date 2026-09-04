# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/); the project adheres to Semantic Versioning.

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

