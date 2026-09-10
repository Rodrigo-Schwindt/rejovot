<?php

namespace Database\Seeders;

use App\Models\Metadata;
use Illuminate\Database\Seeder;

class MetadataSeeder extends Seeder
{
    public function run(): void
    {
        $secciones = [
            'home' => [
                'keywords' => 'rejovot, autopartes, repuestos, mayorista, santos lugares',
                'description' => 'Rejovot Autopartes: catálogo mayorista de repuestos con stock y precios actualizados.',
            ],
            'productos' => [
                'keywords' => 'catálogo, autopartes, bombas de agua, poleas, suspensión, iluminación',
                'description' => 'Buscá autopartes por marca, rubro, vehículo, código o equivalencia.',
            ],
        ];

        foreach ($secciones as $section => $data) {
            Metadata::updateOrCreate(['section' => $section], $data);
        }
    }
}
