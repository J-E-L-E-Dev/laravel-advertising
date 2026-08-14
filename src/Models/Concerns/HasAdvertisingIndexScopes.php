<?php

namespace Ahinest\LaravelAdvertising\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Scopes de consulta reutilizables para los atributos comunes del dominio publicitario. */
trait HasAdvertisingIndexScopes
{
    /** Filtra parcialmente por nombre cuando la tabla posee la columna. */
    public function scopeName(Builder $query, string $value): Builder
    {
        return $this->likeColumn($query, 'name', $value);
    }

    /** Filtra por slug exacto cuando la tabla posee la columna. */
    public function scopeSlug(Builder $query, string $value): Builder
    {
        return $this->hasColumn('slug') ? $query->where('slug', $value) : $query;
    }

    /** Filtra parcialmente por descripción cuando la tabla posee la columna. */
    public function scopeDescription(Builder $query, string $value): Builder
    {
        return $this->likeColumn($query, 'description', $value);
    }

    /** Filtra vencimientos por fecha, `null`, `expired` o `active`. */
    public function scopeExpiresAt(Builder $query, mixed $value): Builder
    {
        if (!$this->hasColumn('expires_at')) return $query;
        if ($value === null || $value === 'null') return $query->whereNull('expires_at');
        if ($value === 'expired') return $query->where('expires_at', '<=', now());
        if ($value === 'active' && method_exists($this, 'scopeActive')) return $this->scopeActive($query);

        return $query->whereDate('expires_at', $value);
    }

    /** Aplica un LIKE protegido a una columna que exista. */
    private function likeColumn(Builder $query, string $column, string $value): Builder
    {
        return $this->hasColumn($column) ? $query->where($column, 'like', '%'.$value.'%') : $query;
    }

    /** Evita errores si un modelo no contiene uno de los atributos comunes. */
    private function hasColumn(string $column): bool
    {
        return $this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $column);
    }
}
