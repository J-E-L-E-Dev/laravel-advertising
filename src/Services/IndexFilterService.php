<?php

namespace Ahinest\LaravelAdvertising\Services;

use Illuminate\Database\Eloquent\Builder;

/** Aplica solamente filtros registrados en la configuración del paquete. */
class IndexFilterService
{
    /**
     * Aplica filtros como scopes locales del modelo.
     * La configuración es una lista blanca: entradas no registradas se ignoran.
     */
    public function apply(Builder $query, string $type, array $filters): Builder
    {
        foreach (config("advertising.index_filters.$type", []) as $input => $scope) {
            if (!array_key_exists($input, $filters) || $filters[$input] === '' || ($filters[$input] === null && $input !== 'expires_at')) continue;

            $scope = is_array($scope) ? ($scope['scope'] ?? null) : $scope;
            if (is_string($scope) && $scope !== '') $query->{$scope}($filters[$input]);
        }

        return $query;
    }
}
