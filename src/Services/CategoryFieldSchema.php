<?php

namespace Ahinest\LaravelAdvertising\Services;

/** Normaliza la definición visible de campos de una categoría para clientes web o API. */
class CategoryFieldSchema
{
    /**
     * Convierte listas simples, `campo:mandatory` y objetos a un mapa uniforme.
     * Este servicio no valida ni obliga campos al guardar recursos.
     */
    public function normalize(?array $fields): array
    {
        return collect($fields ?? [])->map(function (mixed $field): array {
            if (is_array($field)) {
                return [
                    'name' => $field['name'] ?? null,
                    'required' => (bool) ($field['required'] ?? false),
                    'visible' => $field['visible'] ?? true,
                ];
            }

            [$name, $rule] = array_pad(explode(':', (string) $field, 2), 2, null);
            return [
                'name' => $name,
                'required' => $rule === 'mandatory',
                'visible' => true,
            ];
        })->filter(fn (array $field) => filled($field['name']))->values()->all();
    }
}
