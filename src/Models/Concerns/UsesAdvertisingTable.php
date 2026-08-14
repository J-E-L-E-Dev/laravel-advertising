<?php

namespace Ahinest\LaravelAdvertising\Models\Concerns;

trait UsesAdvertisingTable
{
    abstract protected function advertisingTableKey(): string;

    public function getTable(): string
    {
        return config('advertising.tables.'.$this->advertisingTableKey());
    }
}
