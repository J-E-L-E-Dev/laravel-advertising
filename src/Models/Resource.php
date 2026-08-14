<?php

namespace Ahinest\LaravelAdvertising\Models;

use Ahinest\LaravelAdvertising\Models\Concerns\UsesAdvertisingTable;
use Ahinest\LaravelAdvertising\Models\Concerns\HasAdvertisingIndexScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/** Recurso publicitario, que puede ser una imagen, un video u otro archivo. */
class Resource extends Model
{
    use SoftDeletes, UsesAdvertisingTable, HasAdvertisingIndexScopes;

    protected $fillable = ['category_id', 'path', 'disk', 'size', 'alt', 'duration', 'width', 'height', 'eyebrow', 'title', 'description', 'button_label', 'button_url', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
    protected $appends = ['url'];

    /** Devuelve la clave de tabla configurada. */
    protected function advertisingTableKey(): string { return 'resources'; }

    /** Categoría del recurso. */
    public function category(): BelongsTo 
    { 
        return $this->belongsTo(config('advertising.models.category'), 'category_id'); 
    }
    /** Contenedores donde se presenta el recurso. */
    public function containers(): BelongsToMany 
    { 
        return $this->belongsToMany(config('advertising.models.container'), config('advertising.tables.container_resource')); 
    }
    /** Genera la URL pública del archivo almacenado. */
    public function getUrlAttribute(): ?string 
    { 
        if (!$this->path) {
            return null;
        }

        $disk = $this->disk ?: config('advertising.disk', 'public');

        if ($disk === 'public') {
            return asset(Storage::url($this->path));
        }

        return Storage::disk($disk)->url($this->path);
    }
    /** Limita la consulta a recursos no vencidos. */
    public function scopeActive($query, mixed $value = true)
    { 
        if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) return $query;
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())); 
    }
    /** Limita los recursos a una categoría. */
    public function scopeCategory($query, int|string $id)
    {
        return $query->where('category_id', $id);
    }
    /** Limita los recursos a los asociados con un contenedor. */
    public function scopeContainer($query, int|string $id)
    {
        return $query->whereHas('containers', fn ($containers) => $containers->whereKey($id));
    }
}
