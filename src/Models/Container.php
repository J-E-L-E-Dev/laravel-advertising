<?php

namespace Ahinest\LaravelAdvertising\Models;

use Ahinest\LaravelAdvertising\Models\Concerns\UsesAdvertisingTable;
use Ahinest\LaravelAdvertising\Models\Concerns\HasAdvertisingIndexScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Agrupa recursos que pueden reutilizarse en varios anuncios. */
class Container extends Model
{
    use SoftDeletes, UsesAdvertisingTable, HasAdvertisingIndexScopes;

    protected $fillable = ['name', 'description', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
    /** Devuelve la clave de tabla configurada. */
    protected function advertisingTableKey(): string 
    { 
        return 'containers'; 
    }
    protected static function booted(): void 
    { 
        static::saving(fn (self $container) => $container->slug = Str::slug($container->name)); 
    }
    /** Recursos asociados al contenedor. */
    public function resources(): BelongsToMany 
    { 
        return $this->belongsToMany(config('advertising.models.resource'), config('advertising.tables.container_resource')); 
    }
    /** Anuncios que utilizan este contenedor. */
    public function advertisements(): BelongsToMany 
    { 
        return $this->belongsToMany(config('advertising.models.advertisement'), config('advertising.tables.advertisement_container')); 
    }
    /** Limita la consulta a contenedores no vencidos. */
    public function scopeActive($query, mixed $value = true)
    { 
        if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) return $query;
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())); 
    }
}
