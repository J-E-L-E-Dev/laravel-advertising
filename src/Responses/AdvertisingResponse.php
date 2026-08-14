<?php

namespace Ahinest\LaravelAdvertising\Responses;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Resultado para la capa de aplicación. No presupone Inertia, Blade ni Livewire.
 */
class AdvertisingResponse implements Arrayable
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly mixed $data = null,
        public readonly int $status = 200,
    ) {}

    /** Convierte el resultado a un arreglo apto para una vista, evento o redirección. */
    public function toArray(): array
    {
        return ['success' => $this->success, 'message' => $this->message, 'data' => $this->data, 'status' => $this->status];
    }
}
