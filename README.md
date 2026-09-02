# Sylius Classification Bundle

Variant-level, translatable, coded classification axes for Sylius 2 — a taxonomy that Sylius core does not
provide: product attributes and taxons are product-level, and product options drive the variant matrix, so a
free non-matrix axis (a care symbol, a certification, a supplier tag) has no home. This plugin adds one.

- **Classification** — a coded, translatable axis (`code`, `multiple`, `position`, free-form `config`).
- **ClassificationValue** — a coded, translatable value belonging to one axis.
- **ProductVariantClassificationValue** — an assignment of a value to a `ProductVariant`, single- or
  multi-select per axis, position-ordered.

Ships admin CRUD, an admin API (API Platform), a product-variant assignment tab, grid filters, and platform-aware
migrations (PostgreSQL and MySQL/MariaDB). Every entity, repository, form and service is overridable the standard
Sylius way.

Requires PHP >= 8.4 and Sylius ^2.2.

## Installation

### 1. Require the package

```bash
composer require fluffydiscord/sylius-classification-bundle
```

### 2. Register the bundle

```php
// config/bundles.php
return [
    // ...
    FluffyDiscord\SyliusClassificationBundle\SyliusClassificationBundle::class => ['all' => true],
];
```

### 3. Import the admin routes

```yaml
# config/routes/sylius_classification.yaml
sylius_classification:
    resource: "@SyliusClassificationBundle/config/routes.yaml"
```

### 4. Update the database

```bash
bin/console doctrine:migrations:migrate
```

Steps 1–4 give the taxonomy, the admin CRUD screens (under the **Catalog** menu by default) and the admin API,
with no changes to your own entities.

### 5. (Optional) the product-variant assignment tab

To assign classification values to variants from the admin product-variant screen, make your `ProductVariant`
implement `ClassificationValuesAwareInterface`. The plugin maps the inverse Doctrine relation for you — you do
**not** edit any mapping file.

```php
// src/Entity/Product/ProductVariant.php
namespace App\Entity\Product;

use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareInterface;
use FluffyDiscord\SyliusClassificationBundle\Entity\Variant\ClassificationValuesAwareTrait;
use Sylius\Component\Core\Model\ProductVariant as BaseProductVariant;

class ProductVariant extends BaseProductVariant implements ClassificationValuesAwareInterface
{
    use ClassificationValuesAwareTrait;

    public function __construct()
    {
        parent::__construct();
        $this->initializeClassificationValues();
    }
}
```

If your app already overrides `ProductVariant`, just add the interface, the trait, and the
`initializeClassificationValues()` call. Register the override under `sylius_product.resources.product_variant.classes.model`
if it is not already.

## Admin API

The paths below are the `uriTemplate` values. The host's `sylius_api` route import prepends its prefix —
`%sylius.security.api_route%`, which the sylius-standard skeleton sets to `/api/v2` (a host may change it) — so
on a default skeleton `/admin/classifications` is served at `/api/v2/admin/classifications`, behind the admin firewall:

| Method | Path | Purpose |
|--------|------|---------|
| `GET`/`POST` | `/admin/classifications` | list / create axes |
| `GET`/`PUT`/`PATCH`/`DELETE` | `/admin/classifications/{code}` | read / update / delete an axis |
| `GET`/`POST` | `/admin/classification-values` | list / create values |
| `GET`/`PUT`/`PATCH`/`DELETE` | `/admin/classification-values/{code}` | read / update / delete a value |
| `POST` | `/admin/variant-classification-values` | assign a value to a variant |
| `GET`/`DELETE` | `/admin/variant-classification-values/{id}` | read / remove one assignment |
| `GET` | `/admin/product-variants/{code}/classification-values` | a variant's assignments |

`code` is write-once (absent from the update groups). Authorization is the host's — every operation sits under
the Sylius API admin firewall (`%sylius.security.api_admin_regex%`) and declares no `security` of its own.

To disable the API entirely, point its mapping at an empty directory:

```yaml
# config/packages/sylius_classification.yaml
parameters:
    sylius_classification_api_platform_mapping_path: '%kernel.project_dir%/config/api_platform/classification_disabled/'
```

## Grid filter

Filter a product (or variant) grid by classification value:

```yaml
# in your grid definition
filters:
    color:
        type: classification_value
        options:
            classification_code: color          # required
            root: product                       # 'product' (default) or 'product_variant'
            enabled_variants_only: true          # default true
```

## Configuration

```yaml
# config/packages/sylius_classification.yaml
sylius_classification:
    menu:
        parent: catalog     # admin main-menu child to nest the Classifications group under
        position: ~         # integer to reorder; null appends
    resources:              # override any model / repository / form the standard Sylius way
        classification:
            classes:
                model: App\Entity\Classification
```

### Extension points

- **Entities** — override `sylius_classification.resources.<name>.classes.model` (and `.translation.classes.model`).
  All plugin code type-hints the interfaces, so a substitute must implement the matching `*Interface`.
- **Repositories** — override `...classes.repository`; the interfaces are `ClassificationRepositoryInterface`,
  `ClassificationValueRepositoryInterface`, `ProductVariantClassificationValueRepositoryInterface`.
- **Model class in grids / DQL / forms / API** — always the container parameter
  `%sylius_classification.model.<name>.class%`, never a hardcoded FQCN, so an entity override propagates.
- **Services** — every replaceable service is aliased by its interface (`ClassificationValueCodeDeriverInterface`,
  `ClassificationLabelResolverInterface`, `AssignedClassificationValueProviderInterface`,
  `RenderableClassificationProviderInterface`); decorate or re-alias to replace.
- **Templates & layout** — override the plugin's Twig via your theme, or repoint the twig-hook keys under the
  `sylius_classification.admin.classification[_value]` prefixes.

### Container autoconfiguration

The plugin relies on attribute autoconfiguration for its grid filter (`#[AsFilter]`), its two admin Live
Components (`#[AsLiveComponent]`), its autocomplete field (`#[AsEntityAutocompleteField]`) and its Doctrine
mapping subscriber (`#[AsDoctrineListener]`). If you disable autoconfiguration for third-party bundles, tag these
services by hand (`sylius.grid_filter`, `container.service_subscriber` / the UX Live Component tags,
`doctrine.event_listener`).

## Database platforms

PostgreSQL and MySQL/MariaDB are both supported; the shipped migration branches per platform. The `config`
column is `JSONB` on PostgreSQL and `JSON` on MySQL. Assignment foreign keys use `NO ACTION` (not `RESTRICT`) so
that trying to delete an axis or value still assigned to a variant surfaces as Sylius's friendly "cannot delete"
flash rather than a raw 500.

## Development

The plugin carries a Sylius test application under `tests/Application` (from `sylius/test-application`).

```bash
# static analysis
vendor/bin/phpstan analyse

# unit + functional tests
vendor/bin/phpunit

# browser tests (require the test app's admin assets built)
(cd e2e && npx playwright test)
```

## License

MIT.
