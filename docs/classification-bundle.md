# Sylius Classification Bundle (Implementation)

**Reference plugin:** `sylius/cms-plugin` v1.1.6. Verified against a Sylius 2.2 `vendor` tree on 2026-09-01: sylius/sylius v2.2.6, sylius/resource-bundle v1.14.2, sylius/grid-bundle v1.16.1, sylius/twig-hooks v0.9.1, api-platform/metadata v4.2.26, doctrine/dbal 3.10.5, symfony/ux-autocomplete v2.36.2.

## 1. Purpose & scope

A variant-level, translatable, coded classification axis for Sylius 2: `Classification` (axis) → `ClassificationValue` (value) → assignment to a `ProductVariant`. Single- or multi-select per axis, position-ordered, with a free-form `config` JSON payload on axis and value.

Not covered by Sylius core: `ProductAttribute`/`Taxon` are product-level; `ProductOptionValue` is variant-level but drives the variant matrix, so a non-matrix axis cannot be an option.

**In scope:** domain model, admin CRUD (grids, routes, forms, Live Components, menu), product-variant assignment UI, API Platform admin resources, grid filters, shipped migrations, translations (en, cs), test application, E2E.

**Out of scope (decisions, not gaps):** product-level or polymorphic assignment; console commands; natural-order value sorting; enable/disable config toggles; shop-side rendering; fixtures; the origin application's own consumers of classifications (tags, product labels, feeds, search).

**Extraction hygiene.** Ported code carries no prose comments, dividers or rationale docblocks (CR1) — the origin sources are heavily annotated and are stripped; only PHPStan type tags and structural attributes survive. Twig carries no `{# #}` at all (T1). Numeric limits are getter methods, not constants (CR5): `getCodeMaxLength(): int` (96), `getClassificationCodeMaxLength(): int` (64), `getNameMaxLength(): int` (255). Constructor params are column-aligned with no blank lines between them (CR8). Every dependency is constructor-injected (G16), configured with `#[Autowire]` where needed (G17), never `bind:`.

## 2. Package identity

| Key | Value |
|---|---|
| Package | `fluffydiscord/sylius-classification-bundle`, `type: sylius-plugin`, MIT |
| Namespace | `FluffyDiscord\SyliusClassificationBundle\` (tests: `…\Tests\`) |
| Directory | `~/PhpstormProjects/sylius-classification-bundle` |
| Bundle class | `SyliusClassificationBundle` (extends Symfony `Bundle`, `getPath(): dirname(__DIR__)`). No `SyliusPluginTrait`: the `*Bundle` name lets Symfony's native discovery find `SyliusClassificationExtension` and register the `@SyliusClassification` Twig namespace; the trait's `/Plugin$/` convention would instead look for a non-existent `SyliusClassificationBundleExtension`. |
| Extension alias | `sylius_classification` (explicit `getAlias()`, matching `sylius-sklik-sem-plugin`) |
| `require` | `php >=8.4`; `sylius/sylius ^2.2`; `sylius/grid-bundle ^1.16`; `sylius/resource-bundle ^1.14`; `sylius/twig-hooks ^0.9 \|\| ^0.12 \|\| ^0.14`; `api-platform/metadata ^4.2 \|\| ^5.0`; `api-platform/state ^4.2 \|\| ^5.0`; `symfony/ux-autocomplete ^2.25 \|\| ^3.0`; `symfony/ux-live-component ^2.25 \|\| ^3.0`; `symfony/ux-twig-component ^2.25 \|\| ^3.0`; `symfony/form ^7.4`; `symfony/validator ^7.4`; `symfony/string ^7.4`; `doctrine/orm ^3.3`; `doctrine/dbal ^3.9 \|\| ^4.0`; `doctrine/doctrine-bundle ^2.13`; `doctrine/doctrine-migrations-bundle ^3.4` |
| `require-dev` | `sylius/test-application ^2.3@alpha`, `phpunit/phpunit ^11 \|\| ^12`, `phpstan/phpstan ^2.1`, `phpstan/phpstan-symfony ^2`, `phpstan/phpstan-doctrine ^2`, `phpstan/extension-installer ^1.4`, `sylius-labs/coding-standard ^4.4` |

Grid floor origin: `Sylius\Component\Grid\Attribute\AsFilter` (§10) exists in `vendor/sylius/grid-bundle/src/Component/Attribute/AsFilter.php` at the installed v1.16.1; the attribute family arrives with the 1.15→1.16 restructuring, so `^1.16` is the lowest constraint that compiles.

Version floor origin (verified 2026-09-01): endoflife.date/api/php.json — PHP 8.4 active support to 2026-12-31, 8.5 to 2027-12-31, 8.3 security-only since 2025-12-31. packagist p2 feed — latest Sylius stable `v2.2.8`. `sylius/test-application` publishes alphas only (`v2.2.0-ALPHA.1`), the constraint `sylius/cms-plugin` itself uses.

Supported platforms: PostgreSQL and MySQL/MariaDB. No PostgreSQL-only SQL, error strings, column types or ordering semantics outside platform-guarded migration branches.

## 3. Repository layout

```
composer.json  LICENSE  README.md  CHANGELOG.md  phpunit.xml.dist  phpstan.neon  ecs.php  .gitignore
.github/workflows/ci.yaml   php {8.4,8.5} × services {postgres:18, mysql:8.4};
                            steps: ECS, PHPStan max, PHPUnit Unit, PHPUnit Functional
.ddev/config.yaml           name: sylius-classification-bundle, type: symfony, php 8.4,
                            postgres 18, docroot tests/Application/public, nodejs 24
docs/classification-bundle.md
src/
  SyliusClassificationBundle.php
  DependencyInjection/{SyliusClassificationExtension,Configuration}.php
  Doctrine/ORM/ClassificationValuesMappingSubscriber.php
  Entity/{Classification,ClassificationInterface,ClassificationTranslation,ClassificationTranslationInterface,
          ClassificationValue,ClassificationValueInterface,ClassificationValueTranslation,
          ClassificationValueTranslationInterface,ProductVariantClassificationValue,
          ProductVariantClassificationValueInterface}.php
  Entity/Variant/{ClassificationValuesAwareInterface,ClassificationValuesAwareTrait}.php
  Repository/{ClassificationRepository,ClassificationRepositoryInterface,ClassificationValueRepository,
              ClassificationValueRepositoryInterface,ProductVariantClassificationValueRepository,
              ProductVariantClassificationValueRepositoryInterface}.php
  Provider/{AssignedClassificationValueProvider,AssignedClassificationValueProviderInterface,
            RenderableClassificationProvider,RenderableClassificationProviderInterface,
            RenderableClassification}.php
  Product/{ClassificationValueCodeDeriver,ClassificationValueCodeDeriverInterface,
           ClassificationLabelResolver,ClassificationLabelResolverInterface}.php
  Form/Type/{ClassificationType,ClassificationTranslationType,ClassificationValueType,
             ClassificationValueTranslationType,ClassificationValueAutocompleteType}.php
  Twig/Component/Admin/{ClassificationFormComponent,ClassificationValueFormComponent,
                        VariantClassificationsComponent,VariantClassificationValueRowComponent}.php
  Grid/{ClassificationGrid,ClassificationValueGrid}.php
  Grid/Filter/{ProductClassificationFilter,ClassificationValueExpressionBuilder,ClassificationFilterRoot}.php
  Validator/{SingleSelectCardinality,SingleSelectCardinalityValidator}.php
  State/{AssignClassificationValueProcessor,ClassificationUniqueCodeProcessor,
         VariantClassificationValuesProvider}.php
  Menu/AdminMenuListener.php
  Migrations/Version20260901120000.php
config/
  config.yaml  services.php  routes.yaml
  doctrine/{Classification,ClassificationTranslation,ClassificationValue,
            ClassificationValueTranslation,ProductVariantClassificationValue}.orm.xml
  api_platform/{Classification,ClassificationValue,ProductVariantClassificationValue}.xml
  validation/{Classification,ClassificationTranslation,ClassificationValue,
              ClassificationValueTranslation,ProductVariantClassificationValue}.xml
  routes/admin/{classification,classification_value}.yaml
  twig_hooks/admin/{classification,classification_value,product_variant}/*.yaml
templates/admin/{classification,classification_value,product_variant}/…
translations/{messages,validators}.{en,cs}.yaml
tests/{Unit,Functional,Application}/   e2e/
```

## 4. Domain model

Model classes carry **no** ORM or ApiResource attributes; mapping is XML in `config/doctrine`, and `ResourceMappingDriverChain::convertResourceMappedSuperclass()` (`vendor/sylius/resource-bundle/src/Bundle/Doctrine/ResourceMappingDriverChain.php:47-67`) promotes the mapped superclass to an entity for whichever class is the registered model. Every class has an interface; **all plugin code type-hints interfaces**.

### 4.1 `Classification` — table `sylius_classification`

| Field | Type | Constraint | Notes |
|---|---|---|---|
| `id` | int | PK, identity | |
| `code` | string(64) | NOT NULL, UNIQUE `uniq_sylius_classification_code` | resource identifier, write-once (§7.6, §9) |
| `multiple` | bool | NOT NULL, default false | false ⇒ at most one value per variant |
| `position` | int | NOT NULL, default 0 | axis ordering in grids and the variant form |
| `config` | json | NULL, option `jsonb: true` | free-form host payload. PostgreSQL emits `JSONB`, MySQL ignores the option and emits `JSON` (`vendor/doctrine/dbal/src/Platforms/PostgreSQLPlatform.php:1392`; `MySQLPlatform` has no `jsonb` branch) |
| `values` | OneToMany | `mappedBy: classification` | not cascaded; `sylius_classification_value.classification_id` is `ON DELETE CASCADE`, so deleting an axis deletes its values — and is refused by the database when any of those values is assigned (§4.3) |

Interface: `ClassificationInterface extends ResourceInterface, CodeAwareInterface, TranslatableInterface`; adds `isMultiple/setMultiple`, `getPosition/setPosition`, `getConfig/setConfig` (`array<string,mixed>|null`), `getValues(): Collection`, `getName/setName`.

### 4.2 `ClassificationValue` — table `sylius_classification_value`

| Field | Type | Constraint |
|---|---|---|
| `id` | int | PK |
| `classification` | ManyToOne → `ClassificationInterface` | NOT NULL, FK `ON DELETE CASCADE`, index |
| `code` | string(96) | NOT NULL, UNIQUE `uniq_sylius_classification_value_code` |
| `position` | int | NOT NULL, default 0 — ordering of values **within** an axis. Written by the admin form (§7.6); read as the primary sort of the value grid (§7.3) and of the autocomplete query (§7.6) |
| `config` | json | NULL, `jsonb: true` |

Three `position` columns exist with three distinct meanings: `Classification.position` (axis order), `ClassificationValue.position` (value order inside an axis), `ProductVariantClassificationValue.position` (assignment order on one variant).

`code` is derived on create when left blank (§8.3) and is the resource identifier.

### 4.3 `ProductVariantClassificationValue` — table `sylius_classification_variant_value`

| Field | Type | Constraint |
|---|---|---|
| `id` | int | PK |
| `productVariant` | ManyToOne → `Sylius\Component\Core\Model\ProductVariantInterface` | NOT NULL, FK `ON DELETE CASCADE` — deleting a variant removes its assignments |
| `classificationValue` | ManyToOne → `ClassificationValueInterface` | NOT NULL, FK **`NO ACTION`** |
| `classification` | ManyToOne → `ClassificationInterface` | NOT NULL, FK **`NO ACTION`**, denormalized from the value in `setClassificationValue()` |
| `position` | int | NOT NULL, default 0 — assignment order on one variant |

Indexes: UNIQUE `(product_variant_id, classification_value_id)`, index `(classification_value_id)`, index `(product_variant_id, classification_id)`.

**`NO ACTION`, not `RESTRICT`** — the distinction is load-bearing: both refuse the delete, but PostgreSQL raises SQLSTATE 23001 for `RESTRICT`, which DBAL converts to a bare `DriverException` rendered as a 500, while `NO ACTION` raises 23503 → `ForeignKeyConstraintViolationException`. MySQL treats the two as synonyms and raises errno 1451 → the same DBAL exception. That exception is what Sylius already maps: `ResourceDeleteListener` (`vendor/sylius/sylius/src/Sylius/Bundle/AdminBundle/EventListener/ResourceDeleteListener.php:24-49`) converts it into a `ResourceDeleteException` flash on admin `DELETE` routes whose name starts with `sylius` and whose `_sylius.section` is `admin` — both true of §7.1's routes. **No custom pre-delete count query is specified**: the constraint is enforced where it cannot be raced, and the existing framework handling surfaces it.

Mapping a `ManyToOne` to a Sylius model interface resolves through `DoctrineTargetEntitiesResolverPass` + `TargetEntitiesResolver::resolve()` (`vendor/sylius/resource-bundle/src/Bundle/DependencyInjection/Compiler/Helper/TargetEntitiesResolver.php:24-44`), which registers every interface implemented by exactly one registered resource model. **Documented caveat:** a host registering a second resource whose model also implements `ProductVariantInterface` drops that interface from the map and breaks this mapping (same file, lines 42-44).

### 4.4 Translations

`ClassificationTranslation` / `ClassificationValueTranslation`: `id`, `translatable_id` (NOT NULL, index), `locale` (NOT NULL), `name` string(255) NOT NULL; UNIQUE `(translatable_id, locale)`. Tables `sylius_classification_translation`, `sylius_classification_value_translation`. `name` NOT NULL matches Sylius core (`vendor/sylius/sylius/src/Sylius/Bundle/ProductBundle/Resources/config/doctrine/model/ProductTranslation.orm.xml:21`).

### 4.5 Host-side variant collection (optional, mapped by the plugin)

`ClassificationValuesAwareInterface` + `ClassificationValuesAwareTrait` give the host's `ProductVariant` a collection property named exactly **`classificationValues`** — that name is what the mapping subscriber maps, what §9's `toProperty` refers to, and what the `mappedBy`/`inversedBy` pair below names. Renaming it breaks all three.

```
initializeClassificationValues(): void          // host calls it from its constructor
getClassificationValueAssignments(): Collection<int, ProductVariantClassificationValueInterface>
getClassificationValues(string $classificationCode): list<ClassificationValueInterface>
hasClassificationValue(string $valueCode): bool
addClassificationValueAssignment(ProductVariantClassificationValueInterface $assignment): void
    // idempotent on contains(); MUST call $assignment->setProductVariant($this) —
    // the owning side is set nowhere else and product_variant_id is NOT NULL
removeClassificationValueAssignment(ProductVariantClassificationValueInterface $assignment): void
```

`ClassificationValuesMappingSubscriber` (`#[AsDoctrineListener(event: Events::loadClassMetadata)]`) maps **both sides** — the host never edits Doctrine mapping:

- metadata implementing `ClassificationValuesAwareInterface`, with no `classificationValues` association yet → `ClassMetadata::mapOneToMany()` (`vendor/doctrine/orm/src/Mapping/ClassMetadata.php:2001`) with `targetEntity` = `%sylius_classification.model.product_variant_classification_value.class%`, `mappedBy: 'productVariant'`, `cascade: ['persist']`, `orphanRemoval: true`, `orderBy: ['position' => 'ASC']`.
- metadata for the assignment model, when at least one aware variant class is registered → sets `associationMappings['productVariant']->inversedBy = 'classificationValues'`. Without this, `doctrine:schema:validate` fails: an inverse side whose owning side has no `inversedBy` is rejected at `vendor/doctrine/orm/src/Tools/SchemaValidator.php:152-156`. The owning side ships without `inversedBy` in XML because the plugin must not assume the host property exists.

Both branches live in one listener keyed on `$metadata->getName()`, so load order is irrelevant.

Implementing the interface is the single host entity step, required **only** for the admin variant-assignment tab (§8). Read access (§5.2), API assignment (§9) and admin CRUD work without it.

## 5. Services & extension points

`config/services.php` (PHP DSL, matching `sylius-sklik-sem-plugin`). `defaults()` sets `autowire(false)->autoconfigure(true)`: every service is wired explicitly, but **autoconfiguration must stay on** — `#[AsGrid]` (tag `sylius.invokable_grid`), `#[AsFilter]` (tag `sylius.grid_filter`), `#[AsLiveComponent]`, `#[AsEntityAutocompleteField]` and `#[AsDoctrineListener]` are the only registration for the two admin grids, the grid filter, the two admin Live Components, the autocomplete endpoint and the mapping subscriber. The README lists those five tags for a host that disables autoconfiguration.

Every replaceable service is aliased by its interface so a host can `#[AsDecorator]` or re-alias it.

| Extension point | Mechanism |
|---|---|
| Entity classes | `sylius_classification.resources.<name>.classes.model` (+ `.translation.classes.model`). `classes.interface` is **not** offered — it is deprecated in resource-bundle 1.14 (`TargetEntitiesResolver.php:51-62`) and interface→model resolution is automatic |
| Repositories | `…classes.repository`; interfaces `ClassificationRepositoryInterface`, `ClassificationValueRepositoryInterface`, `ProductVariantClassificationValueRepositoryInterface` |
| Model class in grids/DQL/forms/API XML | container params `%sylius_classification.model.<name>.class%`, set by `AbstractDriver::setClassesParameters()` (`vendor/sylius/resource-bundle/src/Bundle/DependencyInjection/Driver/AbstractDriver.php:45-48`) — never a hardcoded FQCN |
| Code derivation | `ClassificationValueCodeDeriverInterface` alias |
| Translated labels | `ClassificationLabelResolverInterface` alias |
| Assigned-value reads | `AssignedClassificationValueProviderInterface` alias |
| Variant-form axis list | `RenderableClassificationProviderInterface` alias |
| Forms | resource route `form.type`; all form types are services |
| Admin menu | `sylius_classification.menu.parent` / `.position`; the listener is a service the host can remove or override |
| Admin templates & layout | `hook_prefix` (§7.2) + Sylius theme template overrides |
| API resources | `%sylius_classification_api_platform_mapping_path%` — repoint at an empty directory to drop the API surface (the idiom cms-plugin hosts use to drop its API surface) |
| Grid filtering | `ProductClassificationFilter` options (§10); `ClassificationValueExpressionBuilder` is a public service |

### 5.1 Bundle configuration tree

```yaml
sylius_classification:
    resources:
        classification:
            classes:     { model, controller, repository, factory, form }
            translation:
                classes: { model, repository, factory, form }
        classification_value:
            classes:     { model, controller, repository, factory, form }
            translation:
                classes: { model, repository, factory, form }
        product_variant_classification_value:
            classes:     { model, controller, repository, factory, form }
    menu:
        parent: catalog     # admin main-menu child to nest under
        position: ~         # int; null appends
```

`prepend()` calls `registerResources('sylius_classification', 'doctrine/orm', $config['resources'], $container)`, which yields five model parameters (three resources + two translations).

### 5.2 `AssignedClassificationValueProvider`

Single read API used by the validator, the variant tab components (§8) and hosts:

```
getAssignments(ProductVariantInterface $variant, ?string $classificationCode = null): list<ProductVariantClassificationValueInterface>
getValues(ProductVariantInterface $variant, string $classificationCode): list<ClassificationValueInterface>
```

Reads the in-memory collection when the variant implements `ClassificationValuesAwareInterface` (no query, and it sees un-flushed form changes); otherwise queries the repository. This is why validation (§8.4) never silently stops enforcing when the trait is absent.

### 5.3 `ClassificationLabelResolver`

`getLabel(ClassificationInterface|ClassificationValueInterface $subject): string` — iterates `getTranslations()` and returns the first non-blank name for the request locale, then `%kernel.default_locale%`, then any translation, falling back to the subject's `code`.

`TranslatableTrait::getTranslation()` must never be called on a managed axis or value in a code path that precedes a flush: for a missing locale it **creates an empty translation and attaches it to the collection** (`vendor/sylius/resource-bundle/src/Component/src/Model/TranslatableTrait.php`, `getTranslation()` tail), which then violates `name NOT NULL` (§4.4); with no current locale it throws `RuntimeException`. Grid `name` fields stay `type: string` (a read-only request that never flushes — the Sylius-core idiom); the variant tab components (§8) go through this resolver, because their live actions end in a flush.

## 6. Schema installation

The plugin ships versioned migrations. `SyliusClassificationExtension` uses `PrependDoctrineMigrationsTrait` with namespace `FluffyDiscord\SyliusClassificationBundle\Migrations`, directory `@SyliusClassificationBundle/src/Migrations`, and `getNamespacesOfMigrationsExecutedBefore(): ['Sylius\Bundle\CoreBundle\Migrations']`.

Version names follow Doctrine's standard `VersionYYYYMMDDHHMMSS`. `Version20260901120000` creates the five tables with all three `position` columns `NOT NULL DEFAULT 0`, and is platform-guarded (`$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform` → sequences + `JSONB`; MySQL branch → `AUTO_INCREMENT` + `JSON`). `down()` drops them in FK order.

Host install: `bin/console doctrine:migrations:migrate`.

## 7. Admin CRUD

### 7.1 Routes — `config/routes/admin/*.yaml`, aggregated by `config/routes.yaml`

`type: sylius.resource`, `section: admin`, `templates: "@SyliusAdmin\\shared\\crud"`, `except: ['show']`, `redirect: update`, `permission: true`, `prefix: /%sylius_admin.path_name%`, `vars.all.hook_prefix` per §7.2 — the shape `sylius/cms-plugin/config/routes/admin/page.yaml` uses.

Route names: `sylius_classification_admin_classification_{index,create,update,delete}` and `sylius_classification_admin_classification_value_{…}`. The `sylius`-prefixed name is required, not cosmetic: `ResourceDeleteListener::isSyliusRoute()` only converts FK violations to a friendly flash for routes starting with `sylius` (§4.3). Its `getResourceNameFromRoute()` renders the flash subject as "admin classification" for this shape, exactly as it does for cms-plugin's `sylius_cms_admin_page_delete`; squatting the core `sylius_admin_*` namespace to improve that wording is not worth the collision risk.

`permission: true` is inert under the resource bundle's default `authorization_checker.disabled`, but a host running a real checker must grant `sylius.classification.{index,create,update,delete}` and `sylius.classification_value.{…}` — the README lists all eight strings.

The host imports `@SyliusClassificationBundle/config/routes.yaml`; a bundle cannot self-register routes (README step 3, as with every Sylius plugin).

### 7.2 Twig hooks

`hook_prefix: sylius_classification.admin.classification` and `…classification_value`. Hook configs live in `config/twig_hooks/admin/**`, imported by `config/config.yaml`. Structure mirrors the extracted hooks: `<prefix>.{create,update}.content` → the Live Component; `.content.form` → sections template; `.content.form.sections` → `general` (priority 200) + `translations` (priority 100).

### 7.3 Grids — `src/Grid/*.php`

Invokable `#[AsGrid(name: …)]` classes. The model classes arrive as constructor arguments from `%sylius_classification.model.<name>.class%` in `config/services.php` (never an FQCN); the driver `class` option and the entity filter's `class` form option read them. Hosts customise them with `#[AsGridMutator(grid: '<grid name>')]`; a host `sylius_grid.grids.<grid name>` YAML entry is not merged — with Sylius core's default `sylius_core.grid.use_legacy_config: true` it replaces the whole grid.

`sylius_classification_admin_classification`: sorting `position: asc`; fields `position`, `code`, `name` (`type: string`, §5.3), `multiple` (twig boolean template); filter `search` (contains, on `code`); item action `show_values` (`type: show`, icon `tabler:list-details`) — a single squared `btn btn-icon` (the `show` action template, not the `links` dropdown) linking to the value grid pre-filtered to this axis: `options.link.route: sylius_classification_admin_classification_value_index` with `parameters: {'criteria[classification]': resource.id}`, which the value grid's `classification` entity filter reads as `classification.id = <id>`; then create / update / delete / bulk delete.

`sylius_classification_admin_classification_value`: sorting `position: asc, code: asc`; fields `position`, `code`, `name`; filters `search` (contains, `code`) and `classification` (entity filter, class from the model parameter, `choice_label: code`, `fields: [classification.id]`, `autocomplete: true` → the UX-Autocomplete Stimulus controller renders it as a searchable Tom Select); same actions.

### 7.4 Live Components

`ClassificationFormComponent` / `ClassificationValueFormComponent`: `#[AsLiveComponent(name: 'sylius_classification:admin:classification[_value]:form', template: '@SyliusUi/components/default.html.twig', route: 'sylius_admin_live_component')]`, using `ResourceFormComponentTrait` + `LiveCollectionTrait` + `TemplatePropTrait`, constructed with the resource repository service and `FormFactoryInterface`.

The form section cards carry `data-loading="addClass(opacity-50)"` and no hand-rolled JS, so a Live re-render dims rather than blanks the form (LC7's "content being replaced" treatment). The Save button is rendered by Sylius admin in the page **header** hook (`title_block.actions`, a native `type="submit"` cross-DOM `form=…` button), which lives outside the Live Component's DOM — `data-loading` cannot reach it, and Sylius admin ships no per-submit disable there. Double-submit protection on the trigger is therefore Sylius admin's standard crud behavior, not something the plugin overrides; the plugin's contribution is the in-component dimming. A host that wants the header button disabled must override the core `sylius_admin.*.{create,update}.content.header.title_block.actions` hook.

### 7.5 Admin menu

`AdminMenuListener` on `sylius.menu.admin.main` adds two **flat** items — `classification_types` and `classification_values` — directly as children of `menu.parent` (default `catalog`, the child `MainMenuBuilder.php:65` always creates; falls back to the menu root if a host removed it), pointing at the index routes with their create/update routes in `extras.routes`. They are **not** wrapped in an intermediate `classifications` group: the Sylius admin nav renders only two levels (the parent section is a dropdown, its children are the links), so a group-with-children nested under `catalog` would be a third level — its links vanish and the routeless group item renders `href=""`. Flat children sit alongside Catalog's own `attributes`/`options`, exactly the same shape. `menu.position` inserts the pair at that index among the parent's children when set.

### 7.6 Forms

- `ClassificationType`: `translations` (`ResourceTranslationsType`, entry `ClassificationTranslationType`), `multiple` (checkbox), `position` (integer, optional), and a `code` `TextType` added in `PRE_SET_DATA` with `disabled` = `$classification->getId() !== null` — editable on create, frozen (write-once) on edit. It does **not** use `AddCodeFormSubscriber`: that subscriber disables on `getCode() !== null`, but inside a Live Component `ResourceFormComponentTrait` re-submits on every re-render, so once a code is typed on create the next re-render sees a non-null code and disables the field mid-create. Keying on `getId()` (null across the whole create session) keeps it editable until saved. `data_class` from the model parameter; block prefix `sylius_classification_classification`.
- `ClassificationValueType`: `translations`; `classification`, `code` and `position` all added in `PRE_SET_DATA`. `classification` (`EntityType`, class from the model parameter, `choice_label: code`) and `code` (optional `TextType`, help text "leave blank to derive from the name") are write-once — rendered on create, `disabled` once the value has an id. `position` is added **only on edit** (for reordering); on create it is not shown and is auto-assigned. `POST_SUBMIT` priority 100 runs the code deriver, ahead of Symfony's `ValidationListener` at priority 0 — a typed code is kept, a blank one derived (§8.3), and either way set before validation. A second `POST_SUBMIT` at priority 90 assigns `position` for every **new** value: `position = ClassificationValueRepositoryInterface::getNextPosition($classification)` = `MAX(position)+1` among that axis's values (0 for the first), so a new value always lands at the end of its type. The entity defaults `position` to `0`, so keying this off "field left blank" is impossible (an unset field reads 0, not null) — hence the field is simply absent on create and the assignment is unconditional for new values. On edit the disabled `code`/`classification` are not resubmitted, preserving the identifier and denormalized axis; `position` is editable.
- Translation types: `name` `TextType`, `NotBlank` + `Length(max: 255)` — matching the column (§4.4).
- `ClassificationValueAutocompleteType`: parent `TranslatableAutocompleteType`, `#[AsEntityAutocompleteField(alias: 'sylius_classification_value', route: 'sylius_admin_entity_autocomplete')]`; `class` from the model parameter; required `extra_options.classification_code` (missing/non-string ⇒ `MissingOptionsException`); `filter_query` joins `classification`, filters by that code, orders by `position` ASC then `code` ASC.

## 8. Product-variant assignment

The variant admin tab is **two nested Twig Live Components**, not a form extension. Sylius's variant `ResourceFormComponent` cannot own custom stateful actions, and a Tom Select inside a Live Component needs a shipped JS bridge (cross-webpack-entry controller registration does not share the Stimulus app) — so the tab persists its own changes immediately (edit-only: a variant must have an id to attach assignments) and searches values **server-side** via debounced live re-renders, needing zero plugin JavaScript.

### 8.1 `VariantClassificationsComponent` (parent, `sylius_classification:admin:variant_classifications`)

`DefaultActionTrait`. LiveProps: `variantId` (int, from `mount(ProductVariantInterface)`), `openTypeCodes` (writable list), `selectedTypeToAdd` (writable string). Reads state from the DB each render (no assignment state stored in props).

- Top **"Add type"** `<select>`: a searchable Tom Select via `data-controller="symfony--ux-autocomplete--autocomplete"`, in a `data-live-ignore` wrapper so morphing does not wipe the widget. Selection is wired with `data-model="norender|selectedTypeToAdd"` + `data-action="change->live#action"` `data-live-action-param="addSelectedType"` — the `norender` model still syncs on change and the action fires despite `data-live-ignore` (verified in-browser), so no JS bridge is needed. `addSelectedType()` pushes the chosen code onto `openTypeCodes` and clears the prop. Lists `getAddableTypes()` (types not already shown); because the wrapper is ignored, an added type lingers in the widget until reload but re-selecting it is a guarded no-op.
- `getShownTypeCodes()` = types that have assignments **or** are open. Per shown type it renders the child row component with `variantId` + `classificationCode` + a stable `key`.
- Edit-only: `isPersisted()` is false for a new (id-less) variant → renders the "save the variant first" note.

### 8.2 `VariantClassificationValueRowComponent` (child, `sylius_classification:admin:variant_classification_row`)

`DefaultActionTrait`. LiveProps: `variantId` (int), `classificationCode` (string), `search` (writable **scalar** string). A per-row scalar `search` is deliberate — a `writable` array LiveProp rejects model writes to keys absent from the dehydrated prop (an empty array dehydrates as a JSON list, not an object), so a shared `searches[code]` array does **not** bind; a child component per row gives each its own scalar `search` that binds cleanly.

- Value chips: each `getValues()` entry with a `×` button → `removeValue(valueCode)` (persist + flush).
- Search: a text `<input data-model="debounce(300)|search">`. `getCandidates()` runs `ClassificationValueRepositoryInterface::findForPicker(code, search, excludedAssignedIds, 8)` only when `search` is non-empty (**no prefill** — options are fetched on demand, server-side), excluding already-assigned values.
- Each candidate is a button → `addValue(valueId)`: loads the value by id, replaces the other assignments for a non-multiple axis, appends for a multiple one, persists + flushes, and clears `search`. Idempotent on a re-picked value.
- Labels come from `ClassificationLabelResolverInterface` (never `getName()` in Twig — this component flushes, and `getTranslation()` would attach an empty translation, §5.3).

Both components persist directly (their own `EntityManager` flush per action); nothing rides the variant form's Save button. `RenderableClassificationProvider` supplies the addable/shown type list (one query, memoized, `ResetInterface`).

### 8.3 `ClassificationValueCodeDeriver`

Fills a blank `code` with `<classificationCode>_<slug(name)>`, truncated to 96 chars. The name is the first non-blank translation for `%kernel.default_locale%`, else any translation; the slugger locale is that same parameter (the origin's hardcoded `'cs'` is removed). No-ops when the code is already set, the parent axis is missing, or every name is blank.

On collision it appends `_2`, `_3`, … (probing `ClassificationValueRepositoryInterface::findOneByCode()`) until free, re-truncating the base so the suffix always survives. After `_99` it leaves the code blank and lets §8.5's validation surface a form error rather than a database exception.

### 8.4 `SingleSelectCardinality`

Class constraint on the assignment, registered in `config/validation/ProductVariantClassificationValue.xml`. For a non-multiple axis it raises a violation at path `classificationValue` (key `sylius_classification.variant_value.single_select`) when the variant already carries a **different** value of the same axis, read through `AssignedClassificationValueProvider`.

**Scope: the API only.** On `POST /admin/variant-classification-values` the assignment is the root validated object, so the constraint fires. The admin variant tab (§8.1–8.2) does not rely on it: `VariantClassificationValueRowComponent::addValue()` enforces cardinality structurally — for a non-multiple axis it removes the other assignments before adding, so a single-select axis can never hold two values.

### 8.5 Resource validation — `config/validation/*.xml`

Constraints are XML (never PHP attributes on a replaceable model), on validation group `sylius`:

- `Classification`: `code` — `NotBlank`, `Length(max: 64)`, `Regex('/^[a-zA-Z0-9_\-]+$/')`; class-level `UniqueEntity(fields: ['code'], message: 'sylius_classification.classification.code.unique')`; `Valid` on `translations`.
- `ClassificationValue`: `code` — `NotBlank`, `Length(max: 96)`, the same `Regex`. `NotBlank` is safe on the admin form because §8.3's deriver runs at `POST_SUBMIT` priority 100, before Symfony's `ValidationListener` at 0, so by validation time the code is set; it is *needed* for the API path (no deriver runs there) and for the `_99`-exhaustion edge, both of which would otherwise reach the DB `NOT NULL` as a 500. Class-level `UniqueEntity(fields: ['code'], message: 'sylius_classification.classification_value.code.unique')`; `NotNull` on `classification`; `Valid` on `translations`.
- Both translation classes: `name` — `NotBlank`, `Length(max: 255)`.
- `ProductVariantClassificationValue`: `NotNull` on `productVariant` and `classificationValue`; class-level `SingleSelectCardinality`.

## 9. API Platform

Resource metadata in `config/api_platform/*.xml`, path parameterized as `%sylius_classification_api_platform_mapping_path%` and appended to `api_platform.mapping.paths` from `config/config.yaml`. XML/YAML extractors are current in api-platform 4.2.26 (`vendor/api-platform/metadata/Extractor/XmlResourceExtractor.php`, wired at `vendor/api-platform/symfony/Bundle/Resources/config/metadata/xml.php:22`).

Paths below are `uriTemplate` values. The effective URL is prefixed by the host's **app-level** `sylius_api` route import (`config/routes/sylius_api.yaml` in the sylius-standard skeleton: `prefix: "%sylius.security.api_route%"`), **not** by anything in the plugin or in Sylius core's ApiBundle. `sylius.security.api_route` defaults to `/api/v2` (`vendor/sylius/sylius/src/Sylius/Bundle/ApiBundle/Resources/config/app/config.yaml:12`) and a host may change it, so on a default skeleton `/admin/classifications` is served at `/api/v2/admin/classifications` — but the prefix is host-controlled, and the plugin's `uriTemplate`s are prefix-relative. Deliberately distinct from §7.1's HTML admin route at `/%sylius_admin.path_name%/classifications`.

**Authorization is the host's.** No operation declares `security`; every path sits under Sylius's `^%sylius.security.api_admin_regex%` admin firewall, exactly as Sylius core admin resources do. A host that mounts api-platform outside that firewall, or disables `sylius_api` while leaving the mapping path registered, exposes these operations unauthenticated — the README states this and points at the §5 escape hatch.

| Resource | Operations | Identifier |
|---|---|---|
| Classification | `GET /admin/classifications`, `GET|PUT|PATCH|DELETE /admin/classifications/{code}`, `POST /admin/classifications` (processor `ClassificationUniqueCodeProcessor`) | `code` |
| ClassificationValue | same shape under `/admin/classification-values` | `code` |
| ProductVariantClassificationValue | `POST /admin/variant-classification-values` (processor `AssignClassificationValueProcessor`), `GET|DELETE /admin/variant-classification-values/{id}`, `GET /admin/product-variants/{code}/classification-values` (provider `VariantClassificationValuesProvider`) | `id` |

`class`, `fromClass` and `toClass` are container parameters, never FQCNs — `XmlResourceExtractor` resolves parameters in all three (`XmlResourceExtractor.php:66,293,296`; container injected at `api-platform/symfony/Bundle/Resources/config/metadata/xml.php:22-26`). The variant collection link therefore reads `fromClass="%sylius.model.product_variant.class%"` with **`toProperty="productVariant"`**, never `fromProperty="classificationValues"`: `fromProperty` names a property of the *host's* class, which need not exist (README step 5 is optional), while `toProperty` names the plugin's own field.

**Write-once fields.** The `:update` groups contain `name` (translations), `multiple`, `position` and `config` only. `code` is absent, so `PUT`/`PATCH` cannot move the resource identifier onto a colliding value. `ClassificationValue.classification` is likewise present in `:create` and absent from `:update` — changing it would strand the denormalized `classification_id` on every existing assignment. `ClassificationUniqueCodeProcessor` is therefore wired on `POST` only.

Serialization groups `sylius_classification:admin:classification:{index,show,create,update}`, `…:classification_value:{…}`, `…:variant_classification_value:{show,create}` are declared in the api_platform XML, not as PHP attributes on the models.

State classes:

- `AssignClassificationValueProcessor` — wraps `EntityManagerInterface::wrapInTransaction()`, takes `LockMode::PESSIMISTIC_WRITE` on the **variant row** (locking existing assignment rows cannot serialize two concurrent first-inserts), then: a re-POST of an already-assigned value returns the existing row and applies a supplied `position`; for a non-multiple axis it removes the other assignments of that axis; otherwise it delegates to `api_platform.doctrine.orm.state.persist_processor`. It does **not** catch `UniqueConstraintViolationException`: the variant-row lock serializes every concurrent assign for one variant (an admin variant save flushes an UPDATE on the same row and blocks on the lock), so the `(product_variant_id, classification_value_id)` index cannot be hit while the existing-assignment check is the gatekeeper. A post-flush re-read would be unusable anyway — Doctrine closes the `EntityManager` in `UnitOfWork::commit()`'s `finally` on any flush failure (`vendor/doctrine/orm/src/UnitOfWork.php:452`), so a repository call after the exception throws `EntityManagerClosed`, not the intended idempotent row.
- `ClassificationUniqueCodeProcessor` — catches `UniqueConstraintViolationException` from the persist processor and distinguishes the code index from the translation index by **matching the code unique-index name** in the driver message (`uniq_sylius_classification_code` / `uniq_sylius_classification_value_code`). The index name is portable — PostgreSQL (`… unique constraint "uniq_…"`) and MySQL (`… for key '…uniq_…'`) both print it — unlike the origin's PostgreSQL-only `(code)` substring, and unlike a repository re-read, which is impossible here because the failed flush has already closed the `EntityManager` (`UnitOfWork::commit()` `finally` → `em->close()`). A code-index match throws `UnprocessableEntityHttpException` with the translated `sylius_classification.<resource>.code.unique`; any other index — canonically `(translatable_id, locale)` from a payload carrying two translations for one locale (§4.4) — re-throws unchanged.
- `VariantClassificationValuesProvider` — resolves the variant by `code` via `sylius.repository.product_variant` (404 when absent) and returns the join-fetched assignments ordered by `position` ASC.

## 10. Grid filters

`ProductClassificationFilter` — `#[AsFilter(type: 'classification_value', formType: HiddenType::class)]`.

| Option | Type | Default | Meaning |
|---|---|---|---|
| `classification_code` | string | none — must be set in the grid definition | axis to filter on. Grid filters receive a raw options array with no `OptionsResolver`, so `apply()` enforces it: missing or blank throws `\InvalidArgumentException`, rather than silently returning every row |
| `root` | `ClassificationFilterRoot` enum (`product`, `product_variant`) | `product` | the grid's root entity |
| `enabled_variants_only` | bool | `true` | restrict matching to enabled variants |

`ClassificationValueExpressionBuilder::build(QueryBuilder $queryBuilder, string $classificationCode, list<string> $valueCodes): string` returns a DQL `EXISTS` sub-select over the assignment entity (class injected from the model parameter). It **joins** `assignment.productVariant`, `assignment.classification` and `assignment.classificationValue` into aliases — DQL has no implicit joins, so `assignment.productVariant.product` is a parse error — then predicates:

- `root: product` ⇒ `<variantAlias>.product = <gridRootAlias>`; `root: product_variant` ⇒ `<variantAlias> = <gridRootAlias>`
- `<classificationAlias>.code = :<codeParam>`
- `<valueAlias>.code IN (:<valuesParam>)`
- `<variantAlias>.enabled = true`, only when `enabled_variants_only`

Alias and parameter suffixes are `substr(hash('xxh128', $classificationCode), 0, 12)`. Sanitising by character replacement (the source's `preg_replace('/[^a-z0-9_]/i', '_', …)`) is insufficient: `size-eu`, `size.eu` and `size_eu` collapse to one suffix, and two such filters on one grid either raise a duplicate-alias `QueryException` or silently overwrite each other's bound parameters.

## 11. Translations

Domains `messages` and `validators`, locales `en` and `cs`. Key roots: `sylius_classification.ui.*`, `sylius_classification.form.*`, `sylius_classification.menu.admin.main.*`, `sylius_classification.classification.code.unique`, `sylius_classification.classification_value.code.unique`, `sylius_classification.variant_value.single_select`. No origin-application key survives.

## 12. Host installation (README contract)

1. `composer require fluffydiscord/sylius-classification-bundle`
2. Register `FluffyDiscord\SyliusClassificationBundle\SyliusClassificationBundle::class => ['all' => true]` in `config/bundles.php`.
3. Import routes: `config/routes/sylius_classification.yaml` → `resource: "@SyliusClassificationBundle/config/routes.yaml"`.
4. `bin/console doctrine:migrations:migrate`
5. **Only for the product-variant assignment tab:**

```php
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

Steps 1–4 give the taxonomy, admin CRUD and API with no entity changes.

## 13. Test application & tooling

- `tests/Application` from `sylius/test-application ^2.2@alpha` with the plugin registered, routes imported, and a `ProductVariant` implementing `ClassificationValuesAwareInterface`.
- DDEV project `sylius-classification-bundle`, `type: symfony`, php 8.4, PostgreSQL 18, docroot `tests/Application/public`, nodejs 24. All PHP/composer/console/Playwright commands run inside DDEV (G1, L1).
- PHPUnit suites: `Unit` (no kernel), `Functional` (kernel + DB). CI runs both against PostgreSQL 18 and MySQL 8.4 service containers.
- PHPStan level `max` with `phpstan-symfony` + `phpstan-doctrine`; ECS via `sylius-labs/coding-standard` with no final-class rule (models must stay overridable; G19).
- Playwright specs in `e2e/`, run inside DDEV, driving the real admin UI (G29): selectors via `data-testid`/`sylius_test_html_attribute` (G30), ≤ 3 s waits (G32), asserting no response ≥ 500 and no `pageerror` (L3).

## Assumptions

| Assumption | If wrong, then… |
|---|---|
| `sylius/test-application ^2.2@alpha` boots a Sylius 2.2.6 admin sufficient for functional + E2E tests | create `sylius/sylius-standard` into `tests/Application` instead |
| A Sylius host owns an `App\Entity\Product\ProductVariant` it can add an interface to (true in sylius-standard) | README step 5 requires creating that entity first; documented |
| `ClassMetadata::mapOneToMany()` plus the `inversedBy` patch from a `loadClassMetadata` listener is honoured for a host entity whose own mapping is attribute-driven | fall back to a README mapping fragment — a regression to raise with the user, never adopted silently |

## Open Questions

| Question | Why it matters | Blocks |
|---|---|---|
| Should the plugin ship a `sylius_fixtures` suite for classifications? | Expected of a quality plugin, but outside the user's stated scope | Nothing — deferred, not built in this task |
| An origin whose assignment `position` is nullable; §4.3 makes it `NOT NULL DEFAULT 0` for MySQL-portable ordering | A later cutover of such an origin needs a data migration setting NULLs to 0 and re-basing its ordering | Nothing in this task; flagged for any cutover |

## Anti-Patterns (DO NOT)

| Don't | Do instead | Why |
|---|---|---|
| Reference a model FQCN in grids, DQL, forms, services or API XML | `%sylius_classification.model.<name>.class%` | A host overriding `classes.model` un-maps the shipped class; the grid driver and DQL then throw |
| Type-hint a concrete entity class in plugin code | Type-hint the interface | Host overrides must stay substitutable |
| Ask the host to paste Doctrine mapping into its own entity | Map both sides from the plugin's `loadClassMetadata` listener | Copy-pasted mapping is unversioned and rots on any Sylius mapping change |
| Map an inverse `mappedBy` without patching the owning side's `inversedBy` | Patch both in one listener | `SchemaValidator.php:152-156` rejects the pair |
| Detect a unique-code collision by the PostgreSQL `(code)` substring, or by a post-exception repository re-read | Match the code unique-index name in the driver message | `(code)` is PostgreSQL-only; a re-read is impossible because the failed flush already closed the EntityManager. The index name is portable and names the exact index |
| Use `RESTRICT` on the assignment FKs | `NO ACTION` | PostgreSQL raises 23001 → bare `DriverException` → 500; 23503 → `ForeignKeyConstraintViolationException` → Sylius's friendly flash |
| Leave `position` nullable and rely on NULLS-LAST ordering | `NOT NULL DEFAULT 0` | PostgreSQL sorts NULLs last on ASC, MySQL first; a Doctrine `orderBy` cannot be platform-guarded |
| Call `getTranslation()` / `getName()` in a path that ends in a flush | `ClassificationLabelResolverInterface` | `getTranslation()` attaches an empty translation for a missing locale, violating `name NOT NULL` |
| Write a nested DQL path (`assignment.productVariant.product`) | Join first, then predicate on the alias | DQL has no implicit joins — it is a parse error |
| Derive filter aliases by replacing characters in a code | Hash the code | `size-eu` / `size.eu` / `size_eu` collapse to one suffix and clobber each other's parameters |
| Write to the `EntityManager` (or `flush()`) from a form event listener | Mutate the mapped collection in memory | `POST_SUBMIT` fires on every Live Component re-render, not just on save (LC4) |
| Preset an unmapped choice field with `setData()` after building it | Pass the value as the field's `data` option in `PRE_SET_DATA` | Choices otherwise render unselected and are wiped on submit (LC5) |
| Use `AddCodeFormSubscriber` (disables on `getCode() !== null`) for a write-once code field inside a Live Component | Add the `code` field in `PRE_SET_DATA`, `disabled` keyed on `getId() !== null` | The LC re-submits on every re-render, so a typed code disables its own field mid-create |
| Render Tom Select / autocomplete widgets in a Live component without `data-live-ignore` | Wrap them | Morphing wipes the widget and double-initialises it (LC3) |
| Ship a Live Component form with no busy state | `data-loading` on the trigger and the wrapper | Silent latency reads as a dead UI and invites double-submits (LC7) |
| Load axes with `findBy()` + `getValues()->isEmpty()` per axis | One DQL query into a DTO, memoized with `ResetInterface` | N+1 on every form build and re-render; a memo without `reset()` breaks long-lived workers (G18) |
| Publish hooks under `sylius_admin.<resource>.*` | `vars.all.hook_prefix: sylius_classification.admin.*` | The unscoped prefix collides with any host resource named `classification` |
| Bake catalog policy (`variant.enabled = true`) into the shared expression builder | Expose it as a grid-filter option | Hosts have no seam to change a constant inside a shared service |
| Add ORM/ApiResource/validation PHP attributes to model classes | XML in `config/{doctrine,api_platform,validation}` | Attributes on a replaceable model cannot be overridden |
| Name a host property from plugin API metadata (`fromProperty`) | Express the link from the plugin's own side (`toProperty`) | The host property need not exist |
| Offer `classes.interface` as an extension point | Rely on auto-discovery | Deprecated in resource-bundle 1.14 (`TargetEntitiesResolver.php:51-62`) |
| Carry the source's explanatory comments into the plugin | Strip them; names and types carry the meaning | CR1 / CLAUDE.md Code Style |

## Test Case Specifications

### Unit tests

| ID | Component | Input | Expected output | Edge cases |
|---|---|---|---|---|
| TC-01 | `ClassificationValueCodeDeriver` | blank code, axis `size`, `%kernel.default_locale%` = `en`, name in that locale `Velké číslo` | code `size_velke-cislo` | diacritics, punctuation, empty name, code already set |
| TC-02 | `ClassificationValueCodeDeriver` | base longer than 96 chars | code exactly 96 chars | slug is ASCII by construction, so truncation is byte-safe; assert the collision suffix survives truncation — the base is trimmed, not the suffix |
| TC-03 | `ClassificationValueCodeDeriver` | code already `x` | unchanged `x` | axis missing ⇒ unchanged |
| TC-04 | `ClassificationValueCodeDeriver` | translations without the default locale | slug from the first translation | zero translations ⇒ unchanged |
| TC-05 | `ClassificationValueCodeDeriver` | derived code already taken by a sibling | `<axis>_<slug>_2` | taken through `_99` ⇒ code left blank |
| TC-06 | `ClassificationLabelResolver` | axis with only a `de` translation, request locale `en`, default `en` | the `de` name | all names blank ⇒ the code; **assert `getTranslations()` gains no new entry** |
| TC-07 | `SingleSelectCardinalityValidator` | non-multiple axis, variant already holds a different value of it | one violation at path `classificationValue` | same value ⇒ none; multiple axis ⇒ none; null value/variant ⇒ none |
| TC-08 | `SingleSelectCardinalityValidator` | multiple axis with three assigned values | no violation | axis null ⇒ no violation |
| TC-09 | `ClassificationValueExpressionBuilder` | `root: product`, `enabled_variants_only: true`, codes `[a,b]` | DQL parses (`EntityManager::createQuery(...)->getSQL()`), contains the injected class, `JOIN … .productVariant`, `.product = <root>`, `.enabled = true`, `.code = :…`, `IN (:…)` | — |
| TC-10 | `ClassificationValueExpressionBuilder` | `root: product_variant`, `enabled_variants_only: false` | correlates on the variant itself, no `enabled` predicate | codes `a-b` and `a.b` on one grid ⇒ distinct aliases and parameters, both predicates survive |
| TC-11 | `ProductVariantClassificationValue::setClassificationValue()` | value of axis `color` | `getClassification()` returns `color` | null value ⇒ null axis |
| TC-12 | `ClassificationValuesAwareTrait` | `addClassificationValueAssignment()` with a fresh assignment | assignment in the collection **and** `getProductVariant()` === the variant | adding twice ⇒ one entry; `getClassificationValues('x')` on an unknown code ⇒ empty |
| TC-13 | `ProductClassificationFilter::apply()` | empty data / non-array data | no query modification | non-`DataSource` ⇒ none; blank `classification_code` ⇒ `InvalidArgumentException` |
| TC-14 | `ClassificationValueAutocompleteType` options | `extra_options` without `classification_code` | `MissingOptionsException` | non-string value ⇒ same |
| TC-15 | `Configuration` | empty config | menu parent `catalog`, position null, three resources with shipped models plus two translation models — five model parameters | unknown key ⇒ `InvalidConfigurationException` |
| TC-16 | `AssignedClassificationValueProvider` | variant implementing the aware interface | reads the collection; repository never queried | variant without it ⇒ repository queried |
| TC-17 | `RenderableClassificationProvider` | called three times in one request | one query; identical DTO list; `reset()` then a fourth call ⇒ a second query | axis with zero values excluded |

### Integration / functional tests

| ID | Flow | Setup | Verification | Teardown |
|---|---|---|---|---|
| IT-01 | Mapping validity | test app with an aware variant | `doctrine:schema:validate` passes mapping **and** schema; five tables exist after migrating | drop schema |
| IT-02 | Mapping validity, opt-out | test app whose variant does not implement the interface | `doctrine:schema:validate` still passes; no `classificationValues` association; no `inversedBy` patch applied | — |
| IT-03 | Resource override | `classes.model` re-pointed at a subclass | grid, autocomplete, API XML and DQL all resolve the subclass | restore config |
| IT-04 | Inverse mapping subscriber | aware variant | host `ClassMetadata` has a `classificationValues` OneToMany with `orphanRemoval`; the assignment's `productVariant` mapping has `inversedBy` | — |
| IT-05 | Admin CRUD | logged-in admin | create/update/delete an axis and a value through the resource routes | rollback |
| IT-06 | Delete protection | axis whose value is assigned to a variant | admin `DELETE` yields the `ResourceDeleteException` flash, not a 500; the row survives | rollback |
| IT-07 | Delete cascade | variant with assignments | deleting the variant deletes its assignments | rollback |
| IT-08 | API assign, single-select | axis `color` (multiple=false) with two values, variant holding A | `POST` B leaves exactly one assignment (B) | rollback |
| IT-09 | API assign, idempotency + reorder | variant already holding A at position 1 | re-`POST` A with position 5 returns the same id at position 5; no duplicate row | rollback |
| IT-10 | API unique code | existing axis `color` | `POST` the same code ⇒ 422 with the translated message (not 500) on PostgreSQL **and** MySQL | rollback |
| IT-11 | API duplicate translation locale | payload with two `en` translations | the original exception surfaces — **not** reported as a code collision | rollback |
| IT-12 | API write-once fields | existing axis | `PATCH` with `code` ⇒ 422; `PATCH` a value's `classification` ⇒ 422 | rollback |
| IT-13 | API variant collection | variant with three assignments at positions 0,1,2 | `GET {apiPrefix}/admin/product-variants/{code}/classification-values` returns them ordered 0,1,2 on PostgreSQL and MySQL | rollback |
| IT-14 | API variant collection, missing variant | unknown code | 404 | — |
| IT-15 | Grid filter | 2 products, one with a matching enabled variant, one only a disabled variant | `enabled_variants_only: true` ⇒ 1 result; `false` ⇒ 2 | rollback |
| IT-16 | Variant tab assign/remove | variant + a multiple axis with values | `VariantClassificationValueRowComponent::addValue()` twice ⇒ two assignments; `removeValue()` ⇒ one; single-select axis `addValue` twice ⇒ exactly one (replaced) | rollback |

### E2E (Playwright, real browser, inside DDEV)

| ID | Flow | Verification |
|---|---|---|
| E2E-01 | Admin creates an axis, then a value under it | both appear in their grids; no response ≥ 500, no `pageerror` — **implemented** (`e2e/classification-admin.spec.ts`) |
| E2E-02 | Admin opens a variant, adds a type, searches (AJAX), picks a value, reloads | the row appears, the searched value is added as a chip, and it persists across reload — **implemented** (`e2e/variant-classifications.spec.ts`) |
| E2E-03 | Admin switches a single-select axis to another value | exactly one chip remains — covered by the single-select branch of `addValue` (IT-16) |

## Error Handling Matrix

### Host/environment errors

| Error | Detection | Response | Fallback | Logging | Alert |
|---|---|---|---|---|---|
| `ProductVariantInterface` ambiguous (2+ resources implement it) | mapping fails at boot | Doctrine mapping exception | none — README caveat | container build failure | CI |
| New (id-less) variant on the create page | `VariantClassificationsComponent::isPersisted()` false | "save the variant first" note, no picker | taxonomy, CRUD and API unaffected; assign after the variant is saved | none | none |
| Axis has no translation in the active locale | `ClassificationLabelResolver` finds no non-blank name | label falls back to the axis `code` | no entity mutation, no empty translation attached | none | none |
| Routes not imported | admin menu links unresolved | Symfony routing exception | README step 3 | Symfony log | none |
| Migrations not run | first query fails | DBAL table-not-found | README step 4 | Symfony log | none |
| Host disables container autoconfiguration | filter type unknown, Live Components unregistered | container build error | README lists the four tags | build failure | CI |

### API errors

| Error | Detection | Response | Fallback | Logging | Alert |
|---|---|---|---|---|---|
| Duplicate `code` (race past `UniqueEntity`) | `UniqueConstraintViolationException` + code re-read hits | 422 with the translated unique message | — | Symfony log | none |
| Duplicate `locale` inside a translations payload | same exception, code re-read misses | original exception re-thrown (500) — never mislabelled a code collision | client fixes the payload | Symfony log | none |
| Concurrent assign for one variant (same or single-select) | pessimistic write lock on the variant row | serialized; the second request sees the first's committed state and returns idempotently, so the assignment unique index is never hit | — | none | none |
| Assignment payload missing variant or value | `NotNull` | 422 validation body | — | none | none |
| Single-select cardinality violated | `SingleSelectCardinality` | 422 at path `classificationValue` | — | none | none |
| `PUT`/`PATCH` carrying `code` or a value's `classification` | field absent from the `:update` group | 422 extra-attributes error | — | none | none |
| Delete an axis or value still assigned | `ForeignKeyConstraintViolationException` | 422 (Sylius's existing mapping) | — | Symfony log | none |
| Unknown variant code on the collection route | repository miss | 404 `Product variant not found.` | — | none | none |
| Lock wait timeout / deadlock | DBAL exception | 500 (framework default) | client retries | Symfony log | host's alerting |

### Admin user-facing errors

| Error | User message | Code | Recovery |
|---|---|---|---|
| Duplicate code on create | `sylius_classification.<resource>.code.unique` | 422 form error | change the code |
| Blank translated name | `NotBlank` message | 422 form error | fill the name |
| Name over 255 chars, code over its column length, code with illegal characters | `Length` / `Regex` messages | 422 form error | correct the field |
| Delete an axis or value still assigned to a variant | Sylius `ResourceDeleteException` flash | flash + redirect | unassign it first |
| Second value picked on a single-select axis (admin tab) | not reachable — `addValue` removes the other assignment before adding, so the axis holds one value | — | — |

## References

| Topic | Location | Anchor |
|---|---|---|
| Reference plugin skeleton | `vendor/sylius/cms-plugin` | `src/DependencyInjection/SyliusCmsExtension.php:37-53` |
| Mapped-superclass promotion | `vendor/sylius/resource-bundle` | `src/Bundle/Doctrine/ResourceMappingDriverChain.php:47-67` |
| Model class parameters | `vendor/sylius/resource-bundle` | `src/Bundle/DependencyInjection/Driver/AbstractDriver.php:45-48` |
| Interface → entity resolution, `classes.interface` deprecation | `vendor/sylius/resource-bundle` | `…/Compiler/Helper/TargetEntitiesResolver.php:24-44, 51-62` |
| Inverse-side `inversedBy` requirement | `vendor/doctrine/orm` | `src/Tools/SchemaValidator.php:152-156` |
| Runtime inverse mapping | `vendor/doctrine/orm` | `src/Mapping/ClassMetadata.php:2001` |
| Live re-render re-submit | `vendor/sylius/sylius` | `…/UiBundle/Twig/Component/ResourceFormComponentTrait.php:27, 48-55` |
| Empty-translation attach | `vendor/sylius/resource-bundle` | `src/Component/src/Model/TranslatableTrait.php` `getTranslation()` |
| FK-violation flash | `vendor/sylius/sylius` | `…/AdminBundle/EventListener/ResourceDeleteListener.php:24-49` |
| `NO ACTION` vs `RESTRICT` rationale | origin migration | assignment-FK migration |
| API route prefix | `vendor/sylius/sylius` | `…/ApiBundle/Resources/config/app/config.yaml:12` |
| API XML parameter resolution | `vendor/api-platform/metadata` | `Extractor/XmlResourceExtractor.php:66, 293, 296` |
| `jsonb` platform option | `vendor/doctrine/dbal` | `src/Platforms/PostgreSQLPlatform.php:1392` |
| Admin menu `catalog` child | `vendor/sylius/sylius` | `…/AdminBundle/Menu/MainMenuBuilder.php:65` |
| Extraction source | origin application | `Entity/Product/Classification*`, `Form/Extension/ProductVariantTypeExtension`, `State/*Classification*` |
