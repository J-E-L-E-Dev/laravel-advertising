<?php

namespace Ahinest\LaravelAdvertising\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Acción CRUD segura con equivalencias configurables entre entradas y atributos del modelo. */
class CrudAction
{
    /** Última excepción de infraestructura producida por una operación CRUD. */
    private ?\Throwable $lastException = null;

    /** Crea un modelo y sincroniza las relaciones indicadas dentro de una transacción. */
    public function create(string $type, array $input, array $relations = []): Model|false
    {
        $this->lastException = null;
        try {
            return DB::transaction(function () use ($type, $input, $relations) {
                $class = $this->modelClass($type);
                $model = new $class();
                $data = $this->attributes($type, $model, $input);
                if ($data === []) return false;
                $model->fill($data)->save();
                $synced = $this->sync($model, $relations, $input);
                return $model->refresh()->load($synced);
            });
        } catch (\Throwable $th) {
            $this->lastException = $th;
            $this->log('Error en CrudAction::create', $th, $input);
            return false;
        }
    }

    /** Actualiza atributos permitidos y relaciones presentes dentro de una transacción. */
    public function update(string $type, Model $model, array $input, array $relations = []): Model|false
    {
        $this->lastException = null;
        try {
            return DB::transaction(function () use ($type, $model, $input, $relations) {
                $data = $this->attributes($type, $model, $input);
                $hasRelationsToSync = collect(array_keys($relations))->contains(fn (string $key) => array_key_exists($key, $input));
                if ($data === [] && !$hasRelationsToSync) return false;
                if ($data !== []) $model->fill($data)->save();
                $synced = $this->sync($model, $relations, $input);
                
                return $model->refresh()->load($synced);
            });
        } catch (\Throwable $th) {
            $this->lastException = $th;
            $this->log('Error en CrudAction::update', $th, $input);
            return false;
        }
    }

    /** Busca un modelo activo por identificador o devuelve false. */
    public function find(string $type, int|string $id): Model|false 
    { 
        $class = $this->modelClass($type);
        
        return $class::find($id) ?: false; 
    }
    /** Recupera todos los modelos activos, con relaciones opcionales. */
    public function all(string $type, array $with = []): Collection 
    { 
        $class = $this->modelClass($type); 
        
        return $class::with($with)->get(); 
    }
    /** Recupera los modelos eliminados lógicamente. */
    public function trashed(string $type, array $with = []): Collection 
    { 
        $class = $this->modelClass($type); 
        
        return $class::onlyTrashed()->with($with)->get(); 
    }
    /** Elimina lógicamente o de forma definitiva un modelo. */
    public function delete(Model $model, bool $force = false): bool
    {
        $this->lastException = null;
        try { 
            return $force ? (bool) $model->forceDelete() : (bool) $model->delete(); 
        }
        catch (\Throwable $th) { 
            $this->lastException = $th; $this->log('Error en CrudAction::delete', $th, ['id' => $model->getKey(), 'force' => $force]);
            return false; 
        }
    }
    /** Restaura un modelo que utiliza SoftDeletes. */
    public function restore(Model $model): bool
    {
        $this->lastException = null;
        try {
            return method_exists($model, 'restore') && $model->restore(); 
        }
        catch (\Throwable $th) { 
            $this->lastException = $th; $this->log('Error en CrudAction::restore', $th, ['id' => $model->getKey()]); 
            return false; 
        }
    }
    /** Restaura varios modelos eliminados; devuelve la cantidad restaurada. */
    public function restoreMany(string $type, array $ids): int
    {
        try { 
            $class = $this->modelClass($type); 
            
            return $class::onlyTrashed()->whereIn('id', $ids)->restore();
        }
        catch (\Throwable $th) { 
            $this->log('Error en CrudAction::restoreMany', $th, ['ids' => $ids]); 
            return 0; 
        }
    }

    /** Indica si el último false provino de una excepción y no de datos inválidos. */
    public function lastException(): ?\Throwable 
    { 
        return $this->lastException; 
    }

    /** Convierte alias y conserva exclusivamente atributos declarados en $fillable. */
    public function attributes(string $type, Model $model, array $input): array
    {
        $mapped = [];
        foreach ($input as $key => $value) $mapped[config("advertising.input_map.$type.$key", $key)] = $value;

        return Arr::only($mapped, $model->getFillable());
    }

    /** Resuelve la clase de modelo configurada para un tipo del dominio. */
    private function modelClass(string $type): string
    {
        $class = config("advertising.models.$type");
        if (!is_subclass_of($class, Model::class)) throw new \InvalidArgumentException("Invalid advertising model [$type].");
        
        return $class;
    }
    /** Sincroniza cada relación solo si su clave de entrada está definida. */
    private function sync(Model $model, array $relations, array $input): array
    {
        $synced = [];
        foreach ($relations as $inputKey => $relation) {
            if (array_key_exists($inputKey, $input)) {
                $model->{$relation}()->sync($input[$inputKey] ?? []);
                $synced[] = $relation;
            }
        }

        return $synced;
    }
    /** Registra una excepción con el formato estándar de Laravel. */
    private function log(string $message, \Throwable $th, array $data = []): void
    {
        Log::error($message, [
            'message' => $th->getMessage(), 
            'line' => $th->getLine(), 
            'file' => $th->getFile(), 
            'data' => $data
        ]);
    }
}
