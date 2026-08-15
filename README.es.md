# Laravel Advertising

[![Laravel 10.x](https://img.shields.io/badge/Laravel-10.x-red.svg)](https://laravel.com/docs/10.x)
[![Laravel 11.x](https://img.shields.io/badge/Laravel-11.x-red.svg)](https://laravel.com/docs/11.x)
[![Laravel 12.x](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com/docs/12.x)

[![Latest Stable Version](http://poser.pugx.org/ahinest/laravela-dvertising/v)](https://packagist.org/packages/ahinest/laravel-advertising)
[![Total Downloads](http://poser.pugx.org/ahinest/laravel-advertising/downloads)](https://packagist.org/packages/ahinest/laravel-advertising)

## Guía de usuario:
[![en](https://img.shields.io/badge/lang-en-red.svg)](https://github.com/J-E-L-E-Dev/laravel-advertising)
[![es](https://img.shields.io/badge/lang-es-yellow.svg)](https://github.com/J-E-L-E-Dev/laravel-advertising/blob/main/README.es.md)

Paquete Laravel para administrar categorías, recursos, contenedores y anuncios publicitarios.

## Instalación

```bash
composer require ahinest/laravel-advertising
php artisan vendor:publish --tag=advertising-config
php artisan migrate
php artisan advertising:seed-categories
```

Laravel descubre el paquete automáticamente. Por defecto crea tablas `advertising_*` y publica `GET /advertising/{slug}`. La configuración permite modificar ruta, disco, tablas, modelos y equivalencias de campos.

## Dominio

Una `Category` tiene muchos `Resource`. Un `Resource` puede pertenecer a muchos `Container`. Un `Container` puede pertenecer a muchos `Advertisement`. El endpoint público devuelve únicamente recursos no vencidos.

### Categorías incluidas

El seeder `advertising:seed-categories` incorpora las tres categorías de `demo`, de forma idempotente:

| Código | Categoría | Archivo | Campos de contenido |
| --- | --- | --- | --- |
| `IB` | Imagen solo descripción | `image` o `file` | `description` |
| `IE` | Imagen con enlace | `image` o `file` | `eyebrow`, `title`, `description`, `button_label`, `button_url` |
| `IV` | Video publicitario | `video` o `file` | `title` |

Al crear un recurso, envíe `category` (o `category_id`) con el identificador de una de estas categorías. El paquete verifica que las categorías de imagen reciban imagen/archivo y que la categoría de video reciba video/archivo.

### Campos visibles de categoría

`Category.fields` define los campos que el front debe mostrar al crear o editar un recurso de esa categoría. Es metadato de interfaz: el paquete **no** convierte esos campos en obligatorios al guardar, porque cada aplicación conserva sus propias reglas en `FormRequest`.

Se admiten estos formatos, que se devuelven normalizados automáticamente en `fields_schema`:

```php
// Compatible con categorías existentes: todos son visibles y opcionales.
'fields' => ['eyebrow', 'title', 'description']

// Sintaxis corta: mandatory indica que el front debe marcarlo como obligatorio.
'fields' => ['eyebrow:mandatory', 'title', 'description:optional']

// Recomendado para nuevas categorías: expresivo y extensible.
'fields' => [
    ['name' => 'eyebrow', 'required' => true],
    ['name' => 'title', 'required' => false],
    ['name' => 'description', 'required' => false, 'visible' => true],
]
```

Por ejemplo, `['eyebrow:mandatory', 'title']` produce:

```json
[
  {"name": "eyebrow", "required": true, "visible": true},
  {"name": "title", "required": false, "visible": true}
]
```

El front selecciona una categoría, lee `fields_schema` y crea los inputs necesarios. Su `FormRequest` debe aplicar las reglas reales de negocio para los campos requeridos.

## Acciones CRUD seguras

`AdvertisingActions` concentra crear, actualizar, sincronizar relaciones, eliminación lógica, eliminación definitiva y restauración. Solo guarda atributos definidos en `$fillable` del modelo destino. Si no llega ningún campo permitido, devuelve `false`.

```php
use Ahinest\LaravelAdvertising\Actions\AdvertisingActions;

$actions = app(AdvertisingActions::class);
$resource = $actions->createResource([
    'resource' => 'advertising/oferta.jpg', // se formatea como path
    'title' => 'Oferta de verano',
    'containers' => [1, 2],
]);

$actions->updateResource($resource, ['title' => 'Oferta nueva', 'containers' => [2]]);
$actions->delete($resource);       // eliminación lógica
$actions->restore($resource);      // obtener antes con onlyTrashed()
$actions->delete($resource, true); // eliminación definitiva
```

Para contenedores: `createContainer($input)` recibe `resources: [ids]`. Para anuncios: `createAdvertisement($input)` recibe `containers: [ids]`. Las actualizaciones sincronizan una relación solo si su clave está presente.

También están disponibles `createCategory` y `updateCategory`. Un recurso puede recibir un `UploadedFile` en `file`, `image` o `video`; el paquete lo guarda automáticamente empleando el disco y la ruta configurados.

### Respuesta de aplicación o API

Cada método de `AdvertisingActions` recibe como último parámetro `$response`: use `'application'` (valor por defecto) para obtener un objeto `AdvertisingResponse`, independiente de Blade, Inertia o Livewire; use `'api'` para recibir un `JsonResponse`.

```php
$web = $actions->createResource($input);        // AdvertisingResponse
$api = $actions->createResource($input, 'api'); // JsonResponse: success, message, data
```

Las respuestas de aplicación contienen `success`, `message`, `data` y `status`; puede usar `$web->toArray()` en la capa que prefiera. En error se devuelve estado 422. Las acciones de borrado usan 204 al completarse.

Los archivos solamente se guardan desde `createResource` y `updateResource`. Al eliminar definitivamente (`delete($resource, true)`), se borra también el archivo del disco configurado; categorías, contenedores y anuncios no manejan archivos.

### Flujo completo de relaciones

```php
$actions = app(\Ahinest\LaravelAdvertising\Actions\AdvertisingActions::class);
$resource = $actions->createResource(['category' => 1, 'image' => $request->file('image')])->data;
$container = $actions->createContainer(['title' => 'Portada', 'resources' => [$resource->id]])->data;
$advertisement = $actions->createAdvertisement(['title' => 'Inicio', 'containers' => [$container->id]])->data;

// Guarda el archivo nuevo, actualiza la base de datos y borra el archivo anterior.
$actions->updateResource($resource, ['image' => $request->file('image')]);
```

`all($type, $with)`, `find($type, $id)`, `trashed($type, $with)` y `restoreMany($type, $ids)` cubren los listados, consulta, papelera y restauración múltiple. Los tipos disponibles son `category`, `resource`, `container` y `advertisement`. Las excepciones de las acciones usan `Log::error` con `message`, `line`, `file` y `data`.

## Formateo de entrada

El paquete transforma los nombres externos antes de la asignación masiva. Por defecto acepta el vocabulario de otros proyectos:

| Entrada | Campo del paquete |
| --- | --- |
| `title` | `name` en categorías, contenedores y anuncios |
| `resource` | `path` |
| `alt_resource` | `alt` |
| `top_title` | `eyebrow` |
| `button` / `button_link` | `button_label` / `button_url` |
| `end_of_advertising` | `expires_at` |

Edite `input_map` en la configuración publicada para cambiar o añadir equivalencias. `Model::create($input)` no transforma datos por sí mismo: use `AdvertisingActions` o `CrudAction::attributes()` antes de crear directamente.

## Publicar y sustituir modelos

```bash
php artisan vendor:publish --tag=advertising-models
```

Esto crea subclases listas para editar en `App\\Models\\Advertising`. Edite `$fillable`, casts o accessors según necesite y cambie la clase correspondiente en `advertising.models` del archivo de configuración. Las relaciones y acciones usan esas clases configuradas.

## Vencimientos

```php
Schedule::command('advertising:expire')->daily();
```

El comando elimina lógicamente los recursos, contenedores y anuncios vencidos.

## Consultar recursos

`indexResources()` devuelve todos los recursos no eliminados junto a su categoría y contenedores. Admite los filtros `category`/`category_id`, `container`/`container_id` y `active`.

```php
$actions = app(\Ahinest\LaravelAdvertising\Actions\AdvertisingActions::class);

$todos = $actions->indexResources();
$activosDeCategoria = $actions->indexResources(['category' => 1, 'active' => true], 'api');

// Incluye categoría y contenedores. Devuelve 404 si no existe.
$recurso = $actions->showResource(15);
```

`showResource($id)` consulta un recurso individual no eliminado. Ambos métodos reciben `'application'` (predeterminado) o `'api'` para elegir el tipo de respuesta.

`createResource` y `updateResource` aceptan un arreglo o un `Request`/`FormRequest`. Cuando reciben un `FormRequest`, usan `$request->validated()`, manteniendo los archivos `image` y `video` ya validados.

```php
public function create(StoreResourcesRequest $request)
{
    return $this->actions->createResource($request, 'api');
}
```

Al reemplazar un archivo en una actualización, el recurso debe tener una categoría asociada (o recibirse `category`/`category_id` en la solicitud) para validar si corresponde una imagen o un video. Actualizar solo texto, fecha o contenedores no exige categoría.

Si se recibe `image` o `video` y no hay categoría válida, el paquete responde 422 con un mensaje específico. Si la categoría existe pero el tipo de archivo no corresponde (por ejemplo, imagen para `IV`), también informa el tipo esperado.

Al crear recursos, el campo recomendado es `category`. El paquete lo convierte a `category_id` antes de la asignación masiva. Si se publicó el archivo de configuración, compruebe que los alias estén presentes en `advertising.input_map.resource` y ejecute `php artisan config:clear` después de modificarlo.

`category` y `containers` no representan lo mismo: `category` se transforma en la columna `category_id`, mientras que `containers` se sincroniza en la tabla pivote de la relación muchos-a-muchos. El request nuevo debe usar `containers: [1, 2]`. Si su API usa otro nombre para la relación, cámbielo en `advertising.relation_inputs.resource_containers`; no se agrega al `input_map` porque no es una columna del recurso.

### Filtros en todos los índices

Todos los métodos `index...` e `indexTrashed...` aceptan un arreglo de filtros como primer parámetro seguido del tipo de respuesta a recibir.

```php
$actions->indexCategories(['name' => 'video'], 'api');
$actions->indexContainers(['slug' => 'portada', 'active' => true], 'api');
$actions->indexAdvertisements(['description' => 'verano', 'expires_at' => 'active']);
$actions->indexResources(['category_id' => 1, 'container' => 5, 'active' => true]);
```

Los modelos exponen scopes comunes: `name` y `description` buscan parcialmente, `slug` es exacto y `expiresAt` acepta una fecha (`YYYY-MM-DD`), `null`, `expired` o `active`. Solamente se aplican los filtros registrados para cada modelo en `advertising.index_filters`; las claves no registradas se ignoran intencionalmente.

Para habilitar un filtro de un campo agregado al modelo publicado, defina su scope y regístrelo en la configuración. Por ejemplo, para `external_id` en su modelo `App\Models\Advertising\Resource`:

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
        // Conserve los filtros existentes que quiera usar.
        'external_id' => 'externalId',
    ],
],
```

Entonces `indexResources(['external_id' => 'CRM-42'], 'api')` aplica ese scope. Si publica la configuración, agregue la entrada dentro del arreglo existente —no lo reemplace por completo— y ejecute `php artisan config:clear` si su aplicación mantiene la configuración en caché.

## CRUD y consultas de todos los modelos

Sí: el paquete permite trabajar con todos sus modelos y relaciones, no solo con los recursos. Estas son las operaciones explícitas disponibles:

| Modelo | Crear / editar | Índice / detalle | Relaciones cargadas |
| --- | --- | --- | --- |
| Categoría | `createCategory`, `updateCategory` | `indexCategories`, `showCategory` | recursos |
| Recurso | `createResource`, `updateResource` | `indexResources`, `showResource` | categoría, contenedores |
| Contenedor | `createContainer`, `updateContainer` | `indexContainers`, `showContainer` | recursos, anuncios |
| Anuncio | `createAdvertisement`, `updateAdvertisement` | `indexAdvertisements`, `showAdvertisement` | contenedores, recursos |

Para todos ellos se emplean `delete($model)`, `delete($model, true)`, `restore($model)`, `restoreMany($type, $ids)` y `trashed($type)`.

Cada modelo tiene también sus métodos específicos para eliminación: `deleteResourceById` / `deleteContainerById` / `deleteAdvertisementById` `deleteCategoryById`. Todos preservan el tipo de respuesta `'application'` o `'api'` y devuelven 404 si el registro a eliminar no existe.

Cada modelo tiene también sus métodos específicos de papelera y restauración por ID: `indexTrashedResources` / `showTrashedResource` / `restoreResourceById`, `indexTrashedContainers` / `showTrashedContainer` / `restoreContainerById`, e `indexTrashedAdvertisements` / `showTrashedAdvertisement` / `restoreAdvertisementById`. Todos preservan el tipo de respuesta `'application'` o `'api'` y devuelven 404 si el registro eliminado no existe.

Al crear o actualizar un contenedor puede enviar ambas relaciones de forma independiente: `resources: [ids]` sincroniza sus recursos y `advertisements: [ids]` sincroniza los anuncios que lo usan. Si se omite una clave, esa relación no se modifica; si se envía como arreglo vacío, se desvinculan todos los registros de esa relación.

Cuando una petición de creación o actualización incluye una relación sincronizable, la respuesta recarga e incluye esa relación con su estado final. Por ejemplo, una creación de recurso con `containers: [1, 2]` devuelve el recurso junto a `containers`.

La categoría es un CRUD completo: `name`/`title`, `code` y `fields` son editables. `fields` se guarda como arreglo JSON y define los campos de presentación que requiere cada categoría. Las categorías se eliminan lógicamente, igual que recursos, contenedores y anuncios.

`description` también es un campo editable. Después de actualizar el paquete, ejecute `php artisan migrate` para añadir la columna en instalaciones ya existentes.

### Actualizar o eliminar por identificador

`showCategory()` devuelve una respuesta, no una instancia de `Category`; por eso no debe usarse como modelo para `delete()`. Para servicios que reciben un ID, use estos métodos del paquete, que devuelven 404 si no existe:

```php
$actions->updateCategoryById($id, $request->all(), 'api');
$actions->deleteCategoryById($id, false, 'api'); // eliminación lógica
$actions->deleteCategoryById($id, true, 'api');  // eliminación definitiva
```

## Arquitectura interna

`AdvertisingActions` contiene solamente las operaciones públicas del dominio: categorías, recursos, contenedores, anuncios y sus relaciones. La infraestructura compartida —respuestas, búsquedas internas, listados genéricos, restauración, eliminación y logs— vive en `AbstractAdvertisingAction`, de la que hereda. El guardado, validación y eliminación de archivos vive en `ResourceFileService`, ya que los archivos pertenecen exclusivamente a los recursos.

## Modelos publicados y constantes

Los modelos publicados son subclases del paquete; **no reemplazan el modelo original por uno vacío**. Heredan sus relaciones, `$casts`, accessors, soft deletes y métodos. Puede añadir métodos propios directamente. Para añadir campos permitidos sin sustituir el `$fillable` base, use:

```php
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);
    $this->mergeFillable(['frequency', 'web_publish']);
}
```

El paquete utiliza `Ahinest\LaravelAdvertising\Constants\ModelName` en vez de cadenas internas. Por ejemplo: `ModelName::RESOURCE`, `ModelName::CATEGORY`, `ModelName::CONTAINER` y `ModelName::ADVERTISEMENT`.

## Idioma de las respuestas

Las respuestas del paquete siguen automÃ¡ticamente el idioma activo de Laravel (`app()->getLocale()`). Internamente cada mensaje usa una clave en inglÃ©s; por ello, cuando no existe una traducciÃ³n para el idioma activo, Laravel devuelve el mensaje en inglÃ©s como valor de respaldo.

El paquete ya incluye las traducciones espaÃ±olas y las carga de forma automÃ¡tica. Con `APP_LOCALE=es`, por ejemplo, `Category created.` se devuelve como `CategorÃ­a creada.`. Con `APP_LOCALE=en`, se devuelve `Category created.`.

Para usar cualquier otro idioma, agregue en el JSON de idioma de su aplicaciÃ³n la clave inglesa y su traducciÃ³n. Por ejemplo, en `lang/fr.json`:

```json
{
    "Category created.": "CatÃ©gorie crÃ©Ã©e.",
    "Resource created.": "Ressource crÃ©Ã©e.",
    "The request is incorrect or incomplete; review the allowed fields.": "La requÃªte est incorrecte ou incomplÃ¨te ; vÃ©rifiez les champs autorisÃ©s."
}
```

No necesita cambiar las acciones ni los controladores: tanto la respuesta `'application'` como la respuesta `'api'` usan el mismo traductor. Puede tomar las claves disponibles de `lang/es.json` del paquete y copiar solamente las que quiera traducir a `lang/{idioma}.json` de su proyecto.
