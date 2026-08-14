<?php

namespace Ahinest\LaravelAdvertising\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/** Guarda, valida y elimina exclusivamente los archivos de recursos publicitarios. */
class ResourceFileService
{
    /** Motivo de la última validación fallida de archivo, si existe. */
    private ?string $failureMessage = null;

    /** Normaliza y guarda file, image o video; devuelve false cuando no es válido. */
    public function store(array $input, ?Model $resource = null): array|false
    {
        $this->failureMessage = null;
        $file = $input['file'] ?? $input['image'] ?? $input['video'] ?? null;
        if (!$file instanceof UploadedFile) return $input;
        $category = $this->category($input, $resource);
        if (!$category) {
            $this->failureMessage = 'Se detectó un archivo image o video, pero el recurso no tiene una categoría válida. Envíe category o category_id y revise la documentación.';
            return false;
        }
        if (!$this->isValidFileForCategory($file, $category->code)) {
            $this->failureMessage = $category->code === 'IV'
                ? 'La categoría del recurso requiere un video con una extensión permitida.'
                : 'La categoría del recurso requiere una imagen válida.';
            return false;
        }
        $disk = $input['disk'] ?? config('advertising.disk');
        try {
            $input['path'] = $file->store(config('advertising.path'), $disk);
            $input['disk'] = $disk;
            $input['size'] = $file->getSize();
            $input['_advertising_uploaded_path'] = true;
            return $input;
        } catch (\Throwable $th) { $this->failureMessage = 'No fue posible almacenar el archivo publicitario.'; $this->log('Error en ResourceFileService::store', $th, $input); return false; }
    }
    /** Confirma que la categoría exista, aun si se recibe una ruta sin archivo. */
    public function hasCategory(array $input, ?Model $resource = null): bool 
    { 
        return (bool) $this->category($input, $resource); 
    }
    /** Indica si la entrada trae un archivo que requiere validar categoría y formato. */
    public function hasUpload(array $input): bool
    {
        return ($input['file'] ?? $input['image'] ?? $input['video'] ?? null) instanceof UploadedFile;
    }
    /** Devuelve el mensaje específico de la última validación de archivo fallida. */
    public function failureMessage(): ?string { return $this->failureMessage; }
    /** Limpia un archivo creado para una operación que finalmente falló. */
    public function cleanup(array $input): void
    {
        if (isset($input['_advertising_uploaded_path'], $input['path'])) $this->remove($input['disk'] ?? config('advertising.disk'), $input['path']);
    }
    /** Elimina un archivo del disco y registra cualquier fallo. */
    public function remove(string $disk, string $path): bool
    {
        try {
            $deleted = Storage::disk($disk)->delete($path);
            if (!$deleted) Log::error('Error en ResourceFileService::remove', ['message' => 'No fue posible eliminar el archivo.', 'line' => null, 'file' => $path, 'data' => ['disk' => $disk, 'path' => $path]]);
            return $deleted;
        } catch (\Throwable $th) 
        { 
            $this->log('Error en ResourceFileService::remove', $th, ['disk' => $disk, 'path' => $path]); 
            return false; 
        }
    }
    /** Obtiene la categoría indicada o la previamente asociada al recurso. */
    private function category(array $input, ?Model $resource): ?Model 
    { 
        $class = config('advertising.models.category'); $id = $input['category_id'] ?? $input['category'] ?? $resource?->category_id; return $id ? $class::find($id) : null; 
    }
    /** Acepta imágenes para IB/IE y videos configurados para IV. */
    private function isValidFileForCategory(UploadedFile $file, string $code): bool
    {
        $mime = (string) $file->getMimeType();
        return $code === 'IV'
            ? str_starts_with($mime, 'video/') && in_array(strtolower($file->getClientOriginalExtension()), config('advertising.video_extensions'), true)
            : in_array($code, ['IB', 'IE'], true) && str_starts_with($mime, 'image/');
    }
    /** Registra una excepción de manejo de archivos. */
    private function log(string $message, \Throwable $th, array $data = []): void 
    { 
        Log::error($message, ['message' => $th->getMessage(), 'line' => $th->getLine(), 'file' => $th->getFile(), 'data' => $data]); 
    }
}
