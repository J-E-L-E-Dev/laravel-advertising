<?php

namespace Ahinest\LaravelAdvertising\Services;

use Illuminate\Support\Collection;

/** Consulta la publicidad visible y procesa vencimientos del paquete. */
class AdvertisingManager
{
    /** Devuelve los recursos activos correspondientes al slug de un anuncio. */
    public function resourcesFor(string $slug): Collection
    {
        $class = config('advertising.models.advertisement');
        $advertisement = $class::query()->active()->where('slug', $slug)
            ->with(['containers' => fn ($query) => $query->active()->with(['resources' => fn ($query) => $query->active()->with('category')])])
            ->firstOrFail();

        return $advertisement->containers->pluck('resources')->flatten()->unique('id')->values();
    }

    /** Elimina lógicamente todos los recursos, contenedores y anuncios vencidos. */
    public function expire(): array
    {
        $classes = [
            config('advertising.models.resource'),
            config('advertising.models.container'),
            config('advertising.models.advertisement'),
        ];
        $result = [];
        foreach ($classes as $class) {
            $result[$class] = $class::query()->whereNotNull('expires_at')->where('expires_at', '<=', now())->delete();
        }
        return $result;
    }
}
