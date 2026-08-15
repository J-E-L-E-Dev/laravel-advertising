# Laravel Advertising

[![Laravel 10.x](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com/docs/10.x)
[![Laravel 11.x](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com/docs/11.x)
[![Laravel 12.x](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com/docs/12.x)

[![Latest Stable Version](http://poser.pugx.org/ahinest/laravela-dvertising/v)](https://packagist.org/packages/ahinest/laravel-advertising)
[![Total Downloads](http://poser.pugx.org/ahinest/laravel-advertising/downloads)](https://packagist.org/packages/ahinest/laravel-advertising)
[![License](http://poser.pugx.org/ahinest/laravel-advertising/license)](https://packagist.org/packages/ahinest/laravel-advertising)

## User guide:
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/J-E-L-E-Dev/laravel-advertising)
[![es](https://img.shields.io/badge/lang-es-yellow.svg)](https://github.com/J-E-L-E-Dev/laravel-advertising/blob/main/README.es.md)

Laravel package for managing advertising categories, resources, containers and advertisements.

## Installation

```bash
composer require ahinest/laravel-advertising
php artisan vendor:publish --tag=advertising-config
php artisan migrate
php artisan advertising:seed-categories
```

Laravel discovers the package automatically. By default it creates `advertising_*` tables and exposes `GET /advertising/{slug}`. Configuration lets you change routes, disk, tables, models and field aliases.

## Domain

A `Category` has many `Resource` records. A `Resource` may belong to many `Container` records. A `Container` may belong to many `Advertisement` records. The public endpoint returns only non-expired resources.

### Included categories

The `advertising:seed-categories` seeder idempotently includes the three categories from `demo`:

| Code | Category | File | Content fields |
| --- | --- | --- | --- |
| `IB` | Image with description only | `image` or `file` | `description` |
| `IE` | Image with link | `image` or `file` | `eyebrow`, `title`, `description`, `button_label`, `button_url` |
| `IV` | Advertising video | `video` or `file` | `title` |

When creating a resource, send `category` (or `category_id`) with an identifier from one of these categories. The package verifies that image categories receive an image/file and that the video category receives a video/file.

### Category visible fields

`Category.fields` defines the fields a frontend should show when creating or editing a resource for that category. It is UI metadata: the package does **not** make those fields required when saving because each application keeps its own `FormRequest` business rules.

The following formats are supported and automatically normalized into `fields_schema`:

```php
// Compatible with existing categories: all fields are visible and optional.
'fields' => ['eyebrow', 'title', 'description']

// Short syntax: mandatory tells the frontend to mark the field as required.
'fields' => ['eyebrow:mandatory', 'title', 'description:optional']

// Recommended for new categories: expressive and extensible.
'fields' => [
    ['name' => 'eyebrow', 'required' => true],
    ['name' => 'title', 'required' => false],
    ['name' => 'description', 'required' => false, 'visible' => true],
]
```

For example, `['eyebrow:mandatory', 'title']` produces:

```json
[
  {"name": "eyebrow", "required": true, "visible": true},
  {"name": "title", "required": false, "visible": true}
]
```

The frontend selects a category, reads `fields_schema`, and builds the required inputs. Its `FormRequest` must enforce the actual business rules for required fields.

## Safe CRUD actions

`AdvertisingActions` centralizes creation, updates, relationship synchronization, soft deletion, force deletion and restoration. Only attributes defined in the target model's `$fillable` are persisted. If no allowed field is present, it returns `false`.

```php
use Ahinest\LaravelAdvertising\Actions\AdvertisingActions;

$actions = app(AdvertisingActions::class);
$resource = $actions->createResource([
    'resource' => 'advertising/offer.jpg', // mapped to path
    'title' => 'Summer offer',
    'containers' => [1, 2],
]);

$actions->updateResource($resource, ['title' => 'New offer', 'containers' => [2]]);
$actions->delete($resource);       // soft delete
$actions->restore($resource);      // obtain first with onlyTrashed()
$actions->delete($resource, true); // permanent delete
```

For containers, `createContainer($input)` accepts `resources: [ids]`. For advertisements, `createAdvertisement($input)` accepts `containers: [ids]`. Updates synchronize a relationship only when its key is present.

`createCategory` and `updateCategory` are also available. A resource may receive an `UploadedFile` in `file`, `image` or `video`; the package stores it automatically using the configured disk and path.

### Application or API response

Every `AdvertisingActions` method accepts `$response` as its final argument: use `'application'` (default) to receive an `AdvertisingResponse`, independent of Blade, Inertia or Livewire; use `'api'` to receive a `JsonResponse`.

```php
$web = $actions->createResource($input);        // AdvertisingResponse
$api = $actions->createResource($input, 'api'); // JsonResponse: success, message, data
```

Application responses contain `success`, `message`, `data` and `status`; use `$web->toArray()` in the layer you prefer. Invalid input returns status 422. Successful deletion actions return 204.

Files are stored only by `createResource` and `updateResource`. A forced deletion (`delete($resource, true)`) also removes the file from the configured disk; categories, containers and advertisements do not manage files.

### Complete relationship flow

```php
$actions = app(\Ahinest\LaravelAdvertising\Actions\AdvertisingActions::class);
$resource = $actions->createResource(['category' => 1, 'image' => $request->file('image')])->data;
$container = $actions->createContainer(['title' => 'Cover', 'resources' => [$resource->id]])->data;
$advertisement = $actions->createAdvertisement(['title' => 'Home', 'containers' => [$container->id]])->data;

// Stores the new file, updates the database and deletes the former file.
$actions->updateResource($resource, ['image' => $request->file('image')]);
```

`all($type, $with)`, `find($type, $id)`, `trashed($type, $with)` and `restoreMany($type, $ids)` cover listing, lookup, trash and bulk restoration. Available types are `category`, `resource`, `container` and `advertisement`. Action exceptions use Laravel `Log::error` with `message`, `line`, `file` and `data`.

## Input formatting

The package converts external field names before mass assignment. By default it accepts vocabulary from other projects:

| Input | Package field |
| --- | --- |
| `title` | `name` for categories, containers and advertisements |
| `resource` | `path` |
| `alt_resource` | `alt` |
| `top_title` | `eyebrow` |
| `button` / `button_link` | `button_label` / `button_url` |
| `end_of_advertising` | `expires_at` |

Edit `input_map` in the published configuration to change or add aliases. `Model::create($input)` does not transform input by itself: use `AdvertisingActions` or `CrudAction::attributes()` before creating directly.

## Publish and replace models

```bash
php artisan vendor:publish --tag=advertising-models
```

This creates ready-to-edit subclasses in `App\Models\Advertising`. Edit `$fillable`, casts or accessors as needed, then change the matching class in `advertising.models` in the configuration file. Relationships and actions use those configured classes.

## Expiration

```php
Schedule::command('advertising:expire')->daily();
```

The command soft-deletes expired resources, containers and advertisements.

## Resource queries

`indexResources()` returns all non-deleted resources with their category and containers. It accepts `category`/`category_id`, `container`/`container_id` and `active` filters.

```php
$actions = app(\Ahinest\LaravelAdvertising\Actions\AdvertisingActions::class);

$all = $actions->indexResources();
$activeFromCategory = $actions->indexResources(['category' => 1, 'active' => true], 'api');

// Includes category and containers. Returns 404 when it does not exist.
$resource = $actions->showResource(15);
```

`showResource($id)` queries one non-deleted resource. Both methods accept `'application'` (default) or `'api'` to choose the response type.

`createResource` and `updateResource` accept an array or a `Request`/`FormRequest`. When they receive a `FormRequest`, they use `$request->validated()`, preserving validated `image` and `video` files.

```php
public function create(StoreResourcesRequest $request)
{
    return $this->actions->createResource($request, 'api');
}
```

When replacing a file during an update, the resource must have an associated category (or receive `category`/`category_id`) to validate whether it is an image or a video. Updating text, date or containers alone does not require a category.

If `image` or `video` is received without a valid category, the package returns 422 with a specific message. If the category exists but the file type does not match (for example, an image for `IV`), it also reports the expected type.

When creating resources, the recommended field is `category`. The package converts it to `category_id` before mass assignment. If the configuration file was published, ensure aliases are present in `advertising.input_map.resource` and run `php artisan config:clear` after changing it.

`category` and `containers` are different: `category` maps to the `category_id` column, while `containers` synchronizes through the many-to-many pivot table. New requests must use `containers: [1, 2]`. If your API uses another name for the relationship, change it in `advertising.relation_inputs.resource_containers`; it is not added to `input_map` because it is not a resource column.

### Filters on every index

Every `index...` and `indexTrashed...` method accepts a filter array as its first argument, followed by the response type to receive.

```php
$actions->indexCategories(['name' => 'video'], 'api');
$actions->indexContainers(['slug' => 'cover', 'active' => true], 'api');
$actions->indexAdvertisements(['description' => 'summer', 'expires_at' => 'active']);
$actions->indexResources(['category_id' => 1, 'container' => 5, 'active' => true]);
```

Models expose common scopes: `name` and `description` perform partial searches, `slug` is exact, and `expiresAt` accepts a date (`YYYY-MM-DD`), `null`, `expired` or `active`. Only filters registered for each model in `advertising.index_filters` are applied; unregistered keys are deliberately ignored.

To enable filtering for a field added to a published model, define its scope and register it in configuration. For example, for `external_id` on `App\Models\Advertising\Resource`:

```php
public function scopeExternalId($query, string $value)
{
    return $query->where('external_id', $value);
}
```

```php
// config/advertising.php
'index_filters' => [
    'resource' => [
        // Keep any existing filters you want to use.
        'external_id' => 'externalId',
    ],
],
```

Then `indexResources(['external_id' => 'CRM-42'], 'api')` applies that scope. If you published configuration, add the entry inside the existing array —do not replace it entirely— and run `php artisan config:clear` when the application caches configuration.

## CRUD and queries for all models

Yes: the package lets you work with all models and their relationships, not only resources. These explicit operations are available:

| Model | Create / edit | Index / detail | Loaded relationships |
| --- | --- | --- | --- |
| Category | `createCategory`, `updateCategory` | `indexCategories`, `showCategory` | resources |
| Resource | `createResource`, `updateResource` | `indexResources`, `showResource` | category, containers |
| Container | `createContainer`, `updateContainer` | `indexContainers`, `showContainer` | resources, advertisements |
| Advertisement | `createAdvertisement`, `updateAdvertisement` | `indexAdvertisements`, `showAdvertisement` | containers, resources |

All models support `delete($model)`, `delete($model, true)`, `restore($model)`, `restoreMany($type, $ids)` and `trashed($type)`.

Each model also has specific deletion methods: `deleteResourceById`, `deleteContainerById`, `deleteAdvertisementById` and `deleteCategoryById`. They preserve the `'application'` or `'api'` response type and return 404 when the record to delete does not exist.

Each model also has trash and restore-by-ID methods: `indexTrashedResources` / `showTrashedResource` / `restoreResourceById`, `indexTrashedContainers` / `showTrashedContainer` / `restoreContainerById`, and `indexTrashedAdvertisements` / `showTrashedAdvertisement` / `restoreAdvertisementById`. They preserve the `'application'` or `'api'` response type and return 404 when the deleted record does not exist.

When creating or updating a container, both relationships may be sent independently: `resources: [ids]` synchronizes resources and `advertisements: [ids]` synchronizes advertisements that use it. If a key is omitted, that relationship is unchanged; if it is sent as an empty array, every record is detached from that relationship.

When a create or update request includes a synchronizable relationship, the response reloads and includes that relationship in its final state. For example, creating a resource with `containers: [1, 2]` returns the resource together with `containers`.

Category has complete CRUD: `name`/`title`, `code` and `fields` are editable. `fields` is stored as a JSON array and defines the presentation fields required by each category. Categories use soft deletion, just like resources, containers and advertisements.

`description` is also editable. After updating the package, run `php artisan migrate` to add the column to existing installations.

### Update or delete by identifier

`showCategory()` returns a response, not a `Category` instance; therefore it must not be used as a model for `delete()`. For services that receive an ID, use these package methods, which return 404 if the record does not exist:

```php
$actions->updateCategoryById($id, $request->all(), 'api');
$actions->deleteCategoryById($id, false, 'api'); // soft deletion
$actions->deleteCategoryById($id, true, 'api');  // permanent deletion
```

## Internal architecture

`AdvertisingActions` contains only public domain operations: categories, resources, containers, advertisements and their relationships. Shared infrastructure —responses, internal lookups, generic listings, restoration, deletion and logs— lives in the inherited `AbstractAdvertisingAction`. File storage, validation and deletion live in `ResourceFileService`, because files belong exclusively to resources.

## Published models and constants

Published models are package subclasses; **they do not replace the original model with an empty model**. They inherit relationships, `$casts`, accessors, soft deletes and methods. You may add your own methods directly. To add allowed fields without replacing base `$fillable`, use:

```php
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);
    $this->mergeFillable(['frequency', 'web_publish']);
}
```

The package uses `Ahinest\LaravelAdvertising\Constants\ModelName` instead of internal strings. For example: `ModelName::RESOURCE`, `ModelName::CATEGORY`, `ModelName::CONTAINER` and `ModelName::ADVERTISEMENT`.

## Response language

Package responses automatically follow Laravel's active locale (`app()->getLocale()`). Internally every message uses an English key, so when no translation exists for the active locale, Laravel returns the English message as the fallback.

The package already includes Spanish translations and loads them automatically. With `APP_LOCALE=es`, for example, `Category created.` is returned as `CategorÃ­a creada.`. With `APP_LOCALE=en`, it is returned as `Category created.`.

To use any other language, add the English key and its translation to your application's language JSON file. For example, in `lang/fr.json`:

```json
{
    "Category created.": "CatÃ©gorie crÃ©Ã©e.",
    "Resource created.": "Ressource crÃ©Ã©e.",
    "The request is incorrect or incomplete; review the allowed fields.": "La requÃªte est incorrecte ou incomplÃ¨te ; vÃ©rifiez les champs autorisÃ©s."
}
```

You do not need to change actions or controllers: both the `'application'` and `'api'` responses use the same translator. Use the available keys in the package's `lang/es.json` as a source and copy only the keys you want to translate into `lang/{locale}.json` in your project.
