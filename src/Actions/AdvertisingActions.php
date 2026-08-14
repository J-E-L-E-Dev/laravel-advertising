<?php

namespace Ahinest\LaravelAdvertising\Actions;

use Ahinest\LaravelAdvertising\Constants\ModelName;
use Ahinest\LaravelAdvertising\Models\Resource;
use Ahinest\LaravelAdvertising\Responses\ResponseFactory;
use Ahinest\LaravelAdvertising\Services\ResourceFileService;
use Ahinest\LaravelAdvertising\Services\IndexFilterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Acciones de dominio para operar categorías, recursos, contenedores y anuncios con sus relaciones. */
class AdvertisingActions extends AbstractAdvertisingAction
{
    public function __construct(CrudAction $crud, private readonly ResourceFileService $files, IndexFilterService $filters) { parent::__construct($crud, $filters); }

    /** Crea una categoría. */
    public function createCategory(array $input, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->create(ModelName::CATEGORY, $input), 'Categoría creada.', $response, 201); 
    }
    /** Actualiza una categoría existente. */
    public function updateCategory(Model $category, array $input, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->update(ModelName::CATEGORY, $category, $input), 'Categoría actualizada.', $response); 
    }
    /** Lista categorías con sus recursos. */
    public function indexCategories(array|string $filters = [], string $response = 'application'): mixed
    { 
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexModel(ModelName::CATEGORY, ['resources'], 'Categorías encontradas.', $response, $filters);
    }
    /** Lista categorías eliminadas con sus recursos. */
    public function indexTrashedCategories(array|string $filters = [], string $response = 'application'): mixed
    { 
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexTrashedModel(ModelName::CATEGORY, ['resources'], 'Categorías encontradas.', $response, $filters);
    }
    /** Muestra una categoría con sus recursos. */
    public function showCategory(int|string $id, string $response = 'application'): mixed 
    { 
        return $this->showModel(ModelName::CATEGORY, $id, ['resources'], 'Categoría encontrada.', $response); 
    }
    public function showTrashedCategory(int|string $id, string $response = 'application'): mixed 
    { 
        return $this->showTrashedModel(ModelName::CATEGORY, $id, ['resources'], 'Categoría en papelera encontrada.', $response); 
    }
    /** Actualiza una categoría por identificador. */
    public function updateCategoryById(int|string $id, array $input, string $response = 'application'): mixed 
    { 
        $model = $this->modelById(ModelName::CATEGORY, $id); return $model 
            ? $this->updateCategory($model, $input, $response) 
            : $this->notFound('Categoría no encontrada.', $response); 
    }
    /** Restaura una instancia de categoría que esté en papelera. */
    public function restoreCategory(Model $category, string $response = 'application'): mixed 
    { 
        return $this->restore($category, $response); 
    }
    /** Elimina una categoría por identificador. */
    public function deleteCategoryById(int|string $id, bool $force = false, string $response = 'application', bool $trashed = false): mixed 
    { 
        $model = $trashed 
            ? $this->modelTrashedById(ModelName::CATEGORY, $id) 
            : $this->modelById(ModelName::CATEGORY, $id);
        
        return $model 
            ? $this->delete($model, $force, $response) 
            : $this->notFound('Categoría no encontrada.', $response); 
    }
    /** Restaura una categoría por identificador. */
    public function restoreCategoryById(int|string $id, string $response = 'application'): mixed 
    { 
        $model = $this->modelTrashedById(ModelName::CATEGORY, $id); 
        
        return $model 
            ? $this->restore($model, $response) 
            : $this->notFound('Categoría en papelera no encontrada.', $response); 
    }

    /** Crea un recurso y sincroniza contenedores. */
    public function createResource(array|Request $input, string $response = 'application'): mixed
    {
        $input = $this->normalizeInput($input);
        $input = $this->normalizeResourceRelations($input);
        $input = $this->files->store($input);
        $model = $input === false 
            ? false 
            : $this->crud->create(ModelName::RESOURCE, $input, ['containers' => 'containers']);
        if ($model === false && is_array($input)) $this->files->cleanup($input);
        
        return $this->resourceResult($model, 'Recurso creado.', $response, 201);
    }
    /** Actualiza un recurso, sincroniza contenedores y reemplaza su archivo si llega uno nuevo. */
    public function updateResource(Model $resource, array|Request $input, string $response = 'application'): mixed
    {
        $input = $this->normalizeInput($input);
        $input = $this->normalizeResourceRelations($input);
        $oldPath = $resource->path;
        $oldDisk = $resource->disk ?: config('advertising.disk');
        $input = $this->files->store($input, $resource);
        $model = $input === false 
            ? false 
            : $this->crud->update(ModelName::RESOURCE, $resource, $input, ['containers' => 'containers']);
        if ($model === false && is_array($input)) $this->files->cleanup($input);
        if ($model && is_array($input) && isset($input['_advertising_uploaded_path']) && $input['path'] !== $oldPath) $this->files->remove($oldDisk, $oldPath);
        
        return $this->resourceResult($model, 'Recurso actualizado.', $response);
    }
    public function updateResourceById(int|string $id, Request $input, string $response = 'application'): mixed 
    { 
        $model = $this->modelById(ModelName::RESOURCE, $id);
        return $model 
            ? $this->updateResource($model, $input, $response) 
            : $this->notFound('Recurso no encontrado.', $response); 
    }
    /** Lista recursos con categoría y contenedores; filtra por categoría, contenedor o active. */
    public function indexResources(array|string $filters = [], string $response = 'application'): mixed
    {
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexModel(ModelName::RESOURCE, ['category', 'containers'], 'Recursos encontrados.', $response, $filters);
    }
    /** Muestra un recurso con categoría y contenedores. */
    public function showResource(int|string $id, string $response = 'application'): mixed 
    { 
        return $this->showModel(ModelName::RESOURCE, $id, ['category', 'containers'], 'Recurso encontrado.', $response); 
    }
    /** Lista recursos eliminados con su categoría y contenedores. */
    public function indexTrashedResources(array|string $filters = [], string $response = 'application'): mixed
    {
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexTrashedModel(ModelName::RESOURCE, ['category', 'containers'], 'Recursos en papelera encontrados.', $response, $filters);
    }
    /** Muestra un recurso eliminado con su categoría y contenedores. */
    public function showTrashedResource(int|string $id, string $response = 'application'): mixed
    {
        return $this->showTrashedModel(ModelName::RESOURCE, $id, ['category', 'containers'], 'Recurso en papelera encontrado.', $response);
    }
    /** Restaura un recurso eliminado por su identificador. */
    public function restoreResourceById(int|string $id, string $response = 'application'): mixed
    {
        $model = $this->modelTrashedById(ModelName::RESOURCE, $id);
        return $model ? $this->restore($model, $response) : $this->notFound('Recurso en papelera no encontrado.', $response);
    }
    /** Elimina un recurso por identificador. */
    public function deleteResourceById(int|string $id, bool $force = false, string $response = 'application', bool $trashed = false): mixed 
    { 
        $model = $trashed 
            ? $this->modelTrashedById(ModelName::RESOURCE, $id) 
            : $this->modelById(ModelName::RESOURCE, $id);
        
        return $model 
            ? $this->delete($model, $force, $response) 
            : $this->notFound('Recurso no encontrado.', $response); 
    }
    /** Crea un contenedor y sincroniza recursos o anuncios cuando las claves estén presentes. */
    public function createContainer(array $input, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->create(ModelName::CONTAINER, $input, [
            'resources' => 'resources',
            'advertisements' => 'advertisements',
        ]), 'Contenedor creado.', $response, 201); 
    }
    /** Actualiza un contenedor y sincroniza recursos o anuncios cuando las claves estén presentes. */
    public function updateContainer(Model $container, array $input, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->update(ModelName::CONTAINER, $container, $input, [
            'resources' => 'resources',
            'advertisements' => 'advertisements',
        ]), 'Contenedor actualizado.', $response); 
    }
    public function updateContainerById(int|string $id, array $input, string $response = 'application'): mixed 
    { 
        $model = $this->modelById(ModelName::CONTAINER, $id); return $model 
            ? $this->updateContainer($model, $input, $response) 
            : $this->notFound('Contenedor no encontrado.', $response); 
    }
    /** Lista contenedores con recursos y anuncios. */
    public function indexContainers(array|string $filters = [], string $response = 'application'): mixed
    { 
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexModel(ModelName::CONTAINER, ['resources', 'advertisements'], 'Contenedores encontrados.', $response, $filters);
    }
    /** Muestra un contenedor con recursos y anuncios. */
    public function showContainer(int|string $id, string $response = 'application'): mixed 
    { 
        return $this->showModel(ModelName::CONTAINER, $id, ['resources', 'advertisements'], 'Contenedor encontrado.', $response); 
    }
    /** Lista contenedores eliminados con recursos y anuncios. */
    public function indexTrashedContainers(array|string $filters = [], string $response = 'application'): mixed
    {
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexTrashedModel(ModelName::CONTAINER, ['resources', 'advertisements'], 'Contenedores en papelera encontrados.', $response, $filters);
    }
    /** Muestra un contenedor eliminado con recursos y anuncios. */
    public function showTrashedContainer(int|string $id, string $response = 'application'): mixed
    {
        return $this->showTrashedModel(ModelName::CONTAINER, $id, ['resources', 'advertisements'], 'Contenedor en papelera encontrado.', $response);
    }
    /** Restaura un contenedor eliminado por su identificador. */
    public function restoreContainerById(int|string $id, string $response = 'application'): mixed
    {
        $model = $this->modelTrashedById(ModelName::CONTAINER, $id);
        return $model ? $this->restore($model, $response) : $this->notFound('Contenedor en papelera no encontrado.', $response);
    }
    /** Elimina un contenedor por identificador. */
    public function deleteContainerById(int|string $id, bool $force = false, string $response = 'application', bool $trashed = false): mixed 
    { 
        $model = $trashed 
            ? $this->modelTrashedById(ModelName::CONTAINER, $id) 
            : $this->modelById(ModelName::CONTAINER, $id);
        
        return $model 
            ? $this->delete($model, $force, $response) 
            : $this->notFound('Contenedor no encontrado.', $response); 
    }
    /** Crea un anuncio y sincroniza contenedores. */
    public function createAdvertisement(array $input, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->create(ModelName::ADVERTISEMENT, $input, ['containers' => 'containers']), 'Anuncio creado.', $response, 201); 
    }
    /** Actualiza un anuncio y sincroniza contenedores. */
    public function updateAdvertisement(Model $advertisement, array $input, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->update(ModelName::ADVERTISEMENT, $advertisement, $input, ['containers' => 'containers']), 'Anuncio actualizado.', $response); 
    }
    /** Lista anuncios con contenedores y recursos. */
    public function indexAdvertisements(array|string $filters = [], string $response = 'application'): mixed
    { 
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexModel(ModelName::ADVERTISEMENT, ['containers.resources'], 'Anuncios encontrados.', $response, $filters);
    }
    /** Muestra un anuncio con contenedores y recursos. */
    public function showAdvertisement(int|string $id, string $response = 'application'): mixed 
    { 
        return $this->showModel(ModelName::ADVERTISEMENT, $id, ['containers.resources'], 'Anuncio encontrado.', $response); 
    }
    /** Actualiza un Anuncio por identificador. */
    public function updateAdvertisementById(int|string $id, array $input, string $response = 'application'): mixed 
    { 
        $model = $this->modelById(ModelName::ADVERTISEMENT, $id); return $model 
            ? $this->updateAdvertisement($model, $input, $response) 
            : $this->notFound('Anuncio no encontrado.', $response); 
    }
    /** Lista anuncios eliminados con contenedores y recursos. */
    public function indexTrashedAdvertisements(array|string $filters = [], string $response = 'application'): mixed
    {
        [$filters, $response] = $this->normalizeIndexArguments($filters, $response);
        return $this->indexTrashedModel(ModelName::ADVERTISEMENT, ['containers.resources'], 'Anuncios en papelera encontrados.', $response, $filters);
    }
    /** Muestra un anuncio eliminado con contenedores y recursos. */
    public function showTrashedAdvertisement(int|string $id, string $response = 'application'): mixed
    {
        return $this->showTrashedModel(ModelName::ADVERTISEMENT, $id, ['containers.resources'], 'Anuncio en papelera encontrado.', $response);
    }
    /** Restaura un anuncio eliminado por su identificador. */
    public function restoreAdvertisementById(int|string $id, string $response = 'application'): mixed
    {
        $model = $this->modelTrashedById(ModelName::ADVERTISEMENT, $id);
        return $model ? $this->restore($model, $response) : $this->notFound('Anuncio en papelera no encontrado.', $response);
    }
    /** Elimina un Anuncio por identificador. */
    public function deleteAdvertisementById(int|string $id, bool $force = false, string $response = 'application', bool $trashed = false): mixed 
    { 
        $model = $trashed 
            ? $this->modelTrashedById(ModelName::ADVERTISEMENT, $id) 
            : $this->modelById(ModelName::ADVERTISEMENT, $id);
        
        return $model 
            ? $this->delete($model, $force, $response) 
            : $this->notFound('Anuncio no encontrado.', $response); 
    }

    /** Borra el archivo cuando se elimina definitivamente un recurso. */
    protected function afterForceDelete(Model $model): void
    {
        if ($model instanceof Resource && $model->path) $this->files->remove($model->disk ?: config('advertising.disk'), $model->path);
    }

    /** Normaliza la clave configurable que sincroniza contenedores del recurso. */
    private function normalizeResourceRelations(array $input): array
    {
        $inputKey = config('advertising.relation_inputs.resource_containers', 'containers');
        if ($inputKey !== 'containers' && array_key_exists($inputKey, $input)) $input['containers'] = $input[$inputKey];
        return $input;
    }

    /** Devuelve el error específico del archivo cuando existe; de otro modo usa la respuesta CRUD común. */
    private function resourceResult(Model|bool $result, string $message, string $response, int $status = 200): mixed
    {
        if ($result === false && $this->files->failureMessage()) {
            return ResponseFactory::make(false, $this->files->failureMessage(), null, 422, $response);
        }
        return $this->result($result, $message, $response, $status);
    }
}
