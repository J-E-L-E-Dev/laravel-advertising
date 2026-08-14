<?php

namespace Ahinest\LaravelAdvertising\Models;

use Ahinest\LaravelAdvertising\Models\Concerns\UsesAdvertisingTable;
use Ahinest\LaravelAdvertising\Models\Concerns\HasAdvertisingIndexScopes;
use Ahinest\LaravelAdvertising\Services\CategoryFieldSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** Categoría que define el tipo y campos disponibles para sus recursos. */
class Category extends Model
{
    use SoftDeletes, UsesAdvertisingTable, HasAdvertisingIndexScopes;

    protected $fillable = ['name', 'code', 'fields', 'description'];
    protected $casts = ['fields' => 'array'];
    protected $appends = ['fields_schema'];

    /** Devuelve la clave de tabla configurada. */
    protected function advertisingTableKey(): string { return 'categories'; }

    protected static function booted(): void
    {
        static::saving(fn (self $category) => $category->slug = Str::slug($category->name));
    }

    /** Recursos pertenecientes a esta categoría. */
    public function resources(): HasMany
    {
        return $this->hasMany(config('advertising.models.resource'), 'category_id');
    }

    /** Devuelve el esquema visible de campos para que un cliente construya su formulario. */
    public function getFieldsSchemaAttribute(): array
    {
        return app(CategoryFieldSchema::class)->normalize($this->fields);
    }
}
