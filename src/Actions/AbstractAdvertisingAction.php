<?php

namespace Ahinest\LaravelAdvertising\Actions;

use Ahinest\LaravelAdvertising\Responses\ResponseFactory;
use Ahinest\LaravelAdvertising\Services\IndexFilterService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Infraestructura común para las acciones del paquete.
 * Centraliza respuestas, consultas genéricas, restauración y registro de errores.
 */
abstract class AbstractAdvertisingAction
{
    public function __construct(protected readonly CrudAction $crud, protected readonly IndexFilterService $filters) {}

    /** Elimina lógicamente o definitivamente un modelo. */
    public function delete(Model $model, bool $force = false, string $response = 'application'): mixed
    {
        $deleted = $this->crud->delete($model, $force);
        if ($deleted && $force) $this->afterForceDelete($model);
        return $this->result($deleted, $force 
            ? 'Registro eliminado definitivamente.' 
            : 'Registro enviado a la papelera.', $response, 204);
    }

    /** Restaura un modelo eliminado lógicamente. */
    public function restore(Model $model, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->restore($model), 'Registro restaurado.', $response); 
    }
    /** Restaura varios registros eliminados de un tipo configurado. */
    public function restoreMany(string $type, array $ids, string $response = 'application'): mixed
    {
        $restored = $this->crud->restoreMany($type, $ids);
        return ResponseFactory::make($restored > 0, $restored 
            ? 'Registros restaurados.' 
            : 'No se encontraron registros para restaurar.', ['restored' => $restored], $restored ? 200 : 404, $response);
    }
    /** Consulta un modelo activo por ID mediante una respuesta estándar. */
    public function find(string $type, int|string $id, string $response = 'application'): mixed 
    { 
        return $this->result($this->crud->find($type, $id), 'Registro encontrado.', $response); 
    }
    /** Consulta todos los modelos activos de un tipo. */
    public function all(string $type, array $with = [], string $response = 'application'): mixed 
    { 
        return $this->indexModel($type, $with, 'Registros encontrados.', $response); 
    }
    /** Consulta todos los modelos eliminados de un tipo. */
    public function trashed(string $type, array $with = [], string $response = 'application'): mixed 
    { 
        return $this->indexTrashedModel($type, $with, 'Registros eliminados encontrados.', $response); 
    }

    /** Convierte un resultado CRUD en respuesta de aplicación o API. */
    protected function result(Model|bool $result, string $message, string $response, int $status = 200): mixed
    {
        return $result === false
            ? ResponseFactory::make(false, $this->crud->lastException() ? 'Ocurrió un error interno; revise el registro de Laravel.' : 'Solicitud incorrecta o incompleta; revise los campos permitidos.', null, $this->crud->lastException() ? 500 : 422, $response)
            : ResponseFactory::make(true, $message, $result instanceof Model ? $result : null, $status, $response);
    }
    /** Respuesta de recurso no encontrado. */
    protected function notFound(string $message, string $response): mixed 
    { 
        return ResponseFactory::make(false, $message, null, 404, $response); 
    }
    /** Convierte un arreglo, Request o FormRequest en datos de entrada para una acción. */
    protected function normalizeInput(array|Request $input): array
    {
        return $input instanceof FormRequest
            ? $input->validated()
            : ($input instanceof Request ? $input->all() : $input);
    }
    /** Índice genérico con relaciones. */
    protected function indexModel(string $type, array $with, string $message, string $response, array $filters = []): mixed
    {
        try { 
            $class = config("advertising.models.$type");
            $models = $this->filters->apply($class::query(), $type, $filters)->with($with)->orderByDesc('id')->get();
            return ResponseFactory::make(true, $message, $models, 200, $response);
        }
        catch (\Throwable $th) 
        { 
            $this->log('Error en AbstractAdvertisingAction::indexModel', $th, ['type' => $type]); 
            return ResponseFactory::make(false, 'No fue posible consultar los registros.', null, 500, $response); 
        }
    }
    /** Índice genérico de registros en papelera. */
    protected function indexTrashedModel(string $type, array $with, string $message, string $response, array $filters = []): mixed
    {
        try { 
            $class = config("advertising.models.$type");
            $models = $this->filters->apply($class::onlyTrashed(), $type, $filters)->with($with)->orderByDesc('id')->get();
            return ResponseFactory::make(true, $message, $models, 200, $response);
        }
        catch (\Throwable $th) 
        { 
            $this->log('Error en AbstractAdvertisingAction::indexTrashedModel', $th, ['type' => $type]); 
            return ResponseFactory::make(false, 'No fue posible consultar los registros en papelera.', null, 500, $response); 
        }
    }
    /** Consulta un modelo activo con relaciones. */
    protected function showModel(string $type, int|string $id, array $with, string $message, string $response): mixed
    {
        try {
            $class = config("advertising.models.$type");
            $model = $class::with($with)->find($id);
            return $model 
                ? ResponseFactory::make(true, $message, $model, 200, $response) 
                : $this->notFound('Registro no encontrado.', $response);
        } catch (\Throwable $th) 
        { 
            $this->log('Error en AbstractAdvertisingAction::showModel', $th, ['type' => $type, 'id' => $id]); 
            return ResponseFactory::make(false, 'No fue posible consultar el registro.', null, 500, $response); 
        }
    }
    /** Consulta un modelo en papelera con relaciones. */
    protected function showTrashedModel(string $type, int|string $id, array $with, string $message, string $response): mixed
    {
        try {
            $class = config("advertising.models.$type");
            $model = $class::onlyTrashed()->with($with)->find($id);
            return $model 
                ? ResponseFactory::make(true, $message, $model, 200, $response) 
                : $this->notFound('Registro no encontrado en papelera.', $response);
        } catch (\Throwable $th) 
        { 
            $this->log('Error en AbstractAdvertisingAction::showModel', $th, ['type' => $type, 'id' => $id]); 
            return ResponseFactory::make(false, 'No fue posible consultar el registro.', null, 500, $response); 
        }
    }
    /** Busca un modelo activo para una mutación interna. */
    protected function modelById(string $type, int|string $id): ?Model
    {
        try { 
            $class = config("advertising.models.$type"); 
            return $class::find($id); 
        }
        catch (\Throwable $th) { 
            $this->log('Error en AbstractAdvertisingAction::modelById', $th, ['type' => $type, 'id' => $id]); 
            return null; 
        }
    }
    /** Busca un modelo eliminado para restaurarlo o eliminarlo definitivamente. */
    protected function modelTrashedById(string $type, int|string $id): ?Model
    {
        try { 
            $class = config("advertising.models.$type"); 
            return $class::onlyTrashed()->find($id); 
        }
        catch (\Throwable $th) { 
            $this->log('Error en AbstractAdvertisingAction::modelTrashedById', $th, ['type' => $type, 'id' => $id]); 
            return null; 
        }
    }
    /** Punto de extensión para limpieza posterior a una eliminación definitiva. */
    protected function afterForceDelete(Model $model): void {}
    /** Mantiene compatibilidad con indexX('api') y admite indexX(['name' => '...'], 'api'). */
    protected function normalizeIndexArguments(array|string $filters, string $response): array
    {
        return is_string($filters) ? [[], $filters] : [$filters, $response];
    }
    /** Registra excepciones usando el formato estándar del paquete. */
    protected function log(string $message, \Throwable $th, array $data = []): void 
    { 
        Log::error($message, ['message' => $th->getMessage(), 'line' => $th->getLine(), 'file' => $th->getFile(), 'data' => $data]); 
    }
}
