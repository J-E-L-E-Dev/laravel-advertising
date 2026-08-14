<?php

namespace Ahinest\LaravelAdvertising\Models;

use Ahinest\LaravelAdvertising\Models\Concerns\UsesAdvertisingTable;
use Ahinest\LaravelAdvertising\Models\Concerns\HasAdvertisingIndexScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Capa final que presenta los contenedores de publicidad. */
class Advertisement extends Model
{
    use SoftDeletes, UsesAdvertisingTable, HasAdvertisingIndexScopes;

    protected $fillable = ['name', 'description', 'expires_at'];
    protected $casts = ['expires_at' => 'datetime'];
    /** Devuelve la clave de tabla configurada. */
    protected function advertisingTableKey(): string 
    { 
        return 'advertisements'; 
    }
    protected static function booted(): void 
    { 
        static::saving(fn (self $advertisement) => $advertisement->slug = Str::slug($advertisement->name)); 
    }
    /** Contenedores que forman el anuncio. */
    public function containers(): BelongsToMany 
    { 
        return $this->belongsToMany(config('advertising.models.container'), config('advertising.tables.advertisement_container')); 
    }
    /** Limita la consulta a anuncios no vencidos. */
    public function scopeActive($query, mixed $value = true)
    { 
        if (!filter_var($value, FILTER_VALIDATE_BOOLEAN)) return $query;
        return $query->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())); 
    }
}
