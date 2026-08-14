<?php

namespace Ahinest\LaravelAdvertising\Database\Seeders;

use Illuminate\Database\Seeder;

/** Siembra las tres categorías base compatibles con el proyecto de origen. */
class AdvertisingCategorySeeder extends Seeder
{
    /** Crea o actualiza las categorías IB, IE e IV sin duplicarlas. */
    public function run(): void
    {
        $model = config('advertising.models.category');
        foreach ([
            [
                'name' => 'Imagen solo descripción',
                'code' => 'IB',
                'fields' => ['description']
            ],
            [
                'name' => 'Imagen con enlace',
                'code' => 'IE',
                'fields' => [
                    'eyebrow',
                    'title',
                    'description',
                    'button_label',
                    'button_url'
                ]
            ],
            [
                'name' => 'Video publicitario',
                'code' => 'IV',
                'fields' => ['title']
            ],
        ] as $category) {
            $record = $model::withTrashed()->updateOrCreate(['code' => $category['code']], $category);
            if ($record->trashed()) $record->restore();
        }
    }
}
