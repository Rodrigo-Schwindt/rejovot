<?php

namespace App\Services\Catalogo;

use App\Contracts\CatalogoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Str;

/**
 * Implementación provisoria con datos hardcodeados.
 * Se reemplaza por la implementación de Odoo sin tocar las vistas.
 */
class CatalogoDemo implements CatalogoRepository
{
    public function ofertas(): array
    {
        return [
            [
                'codigo'     => 'BS009.0868',
                'nombre'     => 'VMG BOMBA AGUA ELÉCTRICA AUXILIAR MERCEDEZ BENZ SPRINTER',
                'descuento'  => 18,
                'precio_ant' => 76877.74,
                'precio'     => 63039.75,
            ],
            [
                'codigo'     => 'PO507.0868',
                'nombre'     => 'VMG POLEA VISCOSA BLAZER 4.3 V6 C/ROSCA',
                'descuento'  => 12,
                'precio_ant' => 85951.76,
                'precio'     => 75637.55,
            ],
            [
                'codigo'     => 'BA564.0868',
                'nombre'     => 'VMG BOMBA AGUA DAEWOO ESPERO 1.5 / LANOS',
                'descuento'  => 25,
                'precio_ant' => 58402.41,
                'precio'     => 43801.81,
            ],
        ];
    }

    public function clientes(): array
    {
        return [
            ['id' => 1, 'nombre' => 'AUTOPARTES DEL OESTE S.R.L.', 'lista' => 'Mayorista'],
            ['id' => 2, 'nombre' => 'REPUESTOS SAN MARTÍN', 'lista' => 'Mayorista'],
            ['id' => 3, 'nombre' => 'CASA CENTRAL - MOSTRADOR', 'lista' => 'Minorista'],
            ['id' => 4, 'nombre' => 'DISTRIBUIDORA LA PLATA', 'lista' => 'Distribuidor'],
        ];
    }

    /** El demo no tiene relacionados: los carga Odoo. */
    public function relacionados(string $codigo): array
    {
        return [];
    }

    public function filtros(): array
    {
        return [
            'marcas' => ['VMG', 'GACRI', 'OMER', 'CAUPLAS', 'BAIML'],
            'rubros' => ['Refrigeración', 'Suspensión', 'Ventilación', 'Mangueras', 'Iluminación', 'Eléctricos', 'Correas'],
            'tipos' => ['Bomba de agua', 'Polea', 'Soporte', 'Pala de ventilador', 'Manguera', 'Faro', 'Variador', 'Correa'],
            'marcas_vehiculo' => ['Mercedes Benz', 'Chevrolet', 'Daewoo', 'Peugeot', 'Citroën', 'Fiat', 'Alfa Romeo'],
            'modelos' => ['Sprinter', 'Blazer', 'Espero', 'Lanos', '306', 'Partner', 'Classic', 'Ducato', 'Saxo', '307', '504', '505'],
            'versiones' => ['1.4 Nafta', '1.5', '2.0 L', '2.2 OM651', '4.3 V6'],
            'anios' => ['2026', '2025', '2024', '2020', '2015', '2010', '2009', '2005', '2000'],
        ];
    }

    public function vehiculos(): array
    {
        return [
            'ACURA' => ['INTEGRA', 'LEGEND'],
            'AEOLUS' => ['AX7', 'S30'],
            'ALFA ROMEO' => ['145', '146', '155', '156'],
            'ASIA MOTORS' => ['TOWNER', 'ROCSTA'],
            'ASTRA' => ['HD7', 'HD9'],
            'AUDI' => ['A3', 'A4', 'Q5'],
            'AUTO AR' => ['MODELO 1', 'MODELO 2'],
            'BAIC' => ['X25', 'X35'],
            'BMW' => ['SERIE 1', 'SERIE 3', 'X1'],
            'CHANGAN' => ['CS35', 'CS75'],
            'CHEVROLET' => ['BLAZER', 'CLASSIC', 'S10'],
            'CITROËN' => ['SAXO', 'BERLINGO'],
            'DAEWOO' => ['ESPERO', 'LANOS'],
            'FIAT' => ['DUCATO', 'PALIO'],
            'MERCEDES BENZ' => ['SPRINTER', 'VITO'],
            'PEUGEOT' => ['306', '307', '504', '505', 'PARTNER'],
        ];
    }

    public function paginados(array $filtros = [], int $porPagina = 20): LengthAwarePaginator
    {
        $todos = collect($this->productos($filtros, 1000));
        $pagina = LengthAwarePaginator::resolveCurrentPage();

        return new Paginator(
            $todos->forPage($pagina, $porPagina)->values()->all(),
            $todos->count(),
            $porPagina,
            $pagina,
            ['path' => LengthAwarePaginator::resolveCurrentPath()],
        );
    }

    public function productos(array $filtros = [], int $limite = 100): array
    {
        $productos = $this->dataset();

        // Búsqueda por vehículo: marca y modelo del árbol de aplicaciones.
        foreach (['vehiculo_marca' => 'marca', 'vehiculo_modelo' => 'modelo'] as $filtro => $campo) {
            $valor = $filtros[$filtro] ?? '';

            if ($valor === '' || $valor === null) {
                continue;
            }

            $productos = array_values(array_filter($productos, function (array $p) use ($campo, $valor) {
                foreach ($p['vehiculos'] ?? [] as $vehiculo) {
                    if (Str::lower($vehiculo[$campo]) === Str::lower((string) $valor)) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $texto = trim((string) ($filtros['q'] ?? ''));
        if ($texto !== '') {
            $productos = array_values(array_filter($productos, function (array $p) use ($texto) {
                return Str::contains(Str::lower($p['codigo'] . ' ' . $p['nombre'] . ' ' . $p['marca'] . ' ' . $p['rubro']), Str::lower($texto));
            }));
        }

        foreach (['marca', 'rubro', 'tipo', 'marca_vehiculo', 'modelo', 'version', 'anio'] as $campo) {
            $valor = $filtros[$campo] ?? '';
            if ($valor === '' || $valor === null) {
                continue;
            }

            $productos = array_values(array_filter($productos, function (array $p) use ($campo, $valor) {
                $contenido = $p[$campo] ?? ($p['aplicaciones_texto'] ?? '');

                return is_array($contenido)
                    ? in_array($valor, $contenido, true)
                    : Str::contains(Str::lower((string) $contenido), Str::lower((string) $valor));
            }));
        }

        if (! empty($filtros['solo_ofertas'])) {
            $productos = array_values(array_filter($productos, fn (array $p) => $p['oferta']));
        }

        return array_slice($productos, 0, $limite);
    }

    public function detalle(string $codigo): ?array
    {
        foreach ($this->dataset() as $producto) {
            if ($producto['codigo'] === $codigo) {
                return $producto;
            }
        }

        return null;
    }

    /** Completa el árbol de vehículos de cada producto. */
    private function dataset(): array
    {
        return array_map(function (array $producto) {
            $producto['vehiculos'] ??= [[
                'marca' => $producto['marca_vehiculo'],
                'modelo' => $producto['modelo'],
            ]];

            return $producto;
        }, $this->datasetCrudo());
    }

    /** @return array<int, array<string, mixed>> */
    private function datasetCrudo(): array
    {
        return [
            [
                'codigo' => 'BS009.0868',
                'nombre' => 'VMG BOMBA AGUA ELECTRICA AUXILIAR MERCEDEZ BENZ SPRINTER',
                'descripcion' => 'VMG BOMBA AGUA ELECTRICA AUXILIAR MERCEDEZ BENZ SPRINTER 311/411 315/415/515 416 2.2 OM651',
                'marca' => 'VMG',
                'rubro' => 'Refrigeración',
                'tipo' => 'Bomba de agua',
                'marca_vehiculo' => 'Mercedes Benz',
                'modelo' => 'Sprinter',
                'version' => '2.2 OM651',
                'anio' => '2015',
                'oem' => 'A6512000101',
                'costo' => 65346.08,
                'lista' => 76877.74,
                'precio_venta' => 68613.38,
                'markup' => 5,
                'stock' => 'rojo',
                'oferta' => true,
                'aplicaciones' => [
                    'Alfa romeo 145 2.0 L (AR67204 Nafta)',
                    'Alfa romeo 145 2.0 L (AR67204 Nafta)',
                    'Alfa romeo 145 2.0 L (AR67204 Nafta)',
                    'Alfa romeo 145 2.0 L (AR67204 Nafta)',
                ],
                'atributos' => [
                    'Línea' => 'Sincrónica',
                    'Modelo' => 'XS',
                    'Cantidad de dientes' => '130',
                    'Ancho' => '15mm',
                    'Perfil' => '40mm',
                ],
            ],
            [
                'codigo' => 'PO507.0868',
                'nombre' => 'VMG POLEA VISCOSA BLAZER 4.3 V6 C/ROSCA/',
                'descripcion' => 'VMG POLEA VISCOSA CHEVROLET BLAZER / S10 4.3 V6 CON ROSCA',
                'marca' => 'VMG',
                'rubro' => 'Ventilación',
                'tipo' => 'Polea',
                'marca_vehiculo' => 'Chevrolet',
                'modelo' => 'Blazer',
                'version' => '4.3 V6',
                'anio' => '2005',
                'oem' => '93242341',
                'costo' => 73059.00,
                'lista' => 85951.76,
                'precio_venta' => 116894.39,
                'markup' => 5,
                'stock' => 'amarillo',
                'oferta' => true,
                'aplicaciones' => [
                    'Chevrolet Blazer 4.3 V6 (Nafta)',
                    'Chevrolet S10 4.3 V6 (Nafta)',
                ],
                'atributos' => [
                    'Línea' => 'Viscosa',
                    'Diámetro' => '145mm',
                    'Rosca' => 'Sí',
                ],
            ],
            [
                'codigo' => 'BA564.0868',
                'nombre' => 'VMG BOMBA AGUA DAEWOO ESPERO 1.5 / LANOS',
                'descripcion' => 'VMG BOMBA DE AGUA DAEWOO ESPERO 1.5 / LANOS 1.5 8V',
                'marca' => 'VMG',
                'rubro' => 'Refrigeración',
                'tipo' => 'Bomba de agua',
                'marca_vehiculo' => 'Daewoo',
                'modelo' => 'Espero',
                'version' => '1.5',
                'anio' => '2000',
                'oem' => '96351969',
                'costo' => 49642.05,
                'lista' => 58402.41,
                'precio_venta' => 79427.28,
                'markup' => 5,
                'stock' => 'verde',
                'oferta' => true,
                'aplicaciones' => [
                    'Daewoo Espero 1.5 (Nafta)',
                    'Daewoo Lanos 1.5 (Nafta)',
                ],
                'atributos' => [
                    'Línea' => 'Standard',
                    'Cantidad de álabes' => '6',
                    'Kit junta' => 'Incluye',
                ],
            ],
            [
                'codigo' => '60079.0347',
                'nombre' => 'GACRI SOPORTE AMORT. DELANTERO PEUGEOT 306/ PATNER',
                'descripcion' => 'GACRI SOPORTE DE AMORTIGUADOR DELANTERO PEUGEOT 306 / PARTNER',
                'marca' => 'GACRI',
                'rubro' => 'Suspensión',
                'tipo' => 'Soporte',
                'marca_vehiculo' => 'Peugeot',
                'modelo' => '306',
                'version' => '1.4 Nafta',
                'anio' => '2009',
                'oem' => '503472',
                'costo' => 19542.37,
                'lista' => 22991.02,
                'precio_venta' => 31267.79,
                'markup' => 5,
                'stock' => 'verde',
                'oferta' => false,
                'aplicaciones' => [
                    'Peugeot 306 1.4 (Nafta)',
                    'Peugeot Partner 1.4 (Nafta)',
                ],
                'atributos' => [
                    'Posición' => 'Delantero',
                    'Rodamiento' => 'Incluye',
                ],
            ],
            [
                'codigo' => '3363.0562',
                'nombre' => 'OMER PALA CHEVROLET CLASSIC 1.4 NAFTA 09> FIAT DUCATO 10>',
                'descripcion' => 'OMER PALA DE VENTILADOR CHEVROLET CLASSIC 1.4 NAFTA 2009 EN ADELANTE / FIAT DUCATO 2010 EN ADELANTE',
                'marca' => 'OMER',
                'rubro' => 'Ventilación',
                'tipo' => 'Pala de ventilador',
                'marca_vehiculo' => 'Chevrolet',
                'modelo' => 'Classic',
                'version' => '1.4 Nafta',
                'anio' => '2009',
                'oem' => '52458963',
                'costo' => 13209.29,
                'lista' => 15540.34,
                'precio_venta' => 21134.86,
                'markup' => 5,
                'stock' => 'verde',
                'oferta' => false,
                'aplicaciones' => [
                    'Chevrolet Classic 1.4 (Nafta)',
                    'Fiat Ducato 2.3 (Diesel)',
                ],
                'atributos' => [
                    'Aspas' => '7',
                    'Diámetro' => '340mm',
                ],
            ],
            [
                'codigo' => '9518.1050',
                'nombre' => 'CAUPLAS MANGUERA CALEFACCION PEUG 504 505 =YACO 969',
                'descripcion' => 'CAUPLAS MANGUERA DE CALEFACCIÓN PEUGEOT 504 / 505 EQUIVALENTE YACO 969',
                'marca' => 'CAUPLAS',
                'rubro' => 'Mangueras',
                'tipo' => 'Manguera',
                'marca_vehiculo' => 'Peugeot',
                'modelo' => '504',
                'version' => '2.0 L',
                'anio' => '2000',
                'oem' => 'YACO 969',
                'costo' => 5238.54,
                'lista' => 6162.99,
                'precio_venta' => 8381.67,
                'markup' => 5,
                'stock' => 'rojo',
                'oferta' => false,
                'aplicaciones' => [
                    'Peugeot 504 2.0 (Nafta)',
                    'Peugeot 505 2.0 (Nafta)',
                ],
                'atributos' => [
                    'Material' => 'Caucho EPDM',
                    'Largo' => '520mm',
                ],
            ],
            [
                'codigo' => '1017C.0090',
                'nombre' => 'BAIML FARO POSICION DELIMITADOR UNIPOLAR CRISTAL',
                'descripcion' => 'BAIML FARO DE POSICIÓN DELIMITADOR UNIPOLAR CRISTAL 12/24V',
                'marca' => 'BAIML',
                'rubro' => 'Iluminación',
                'tipo' => 'Faro',
                'marca_vehiculo' => 'Universal',
                'modelo' => 'Universal',
                'version' => '',
                'anio' => '2026',
                'oem' => '1017C',
                'costo' => 2558.85,
                'lista' => 3010.41,
                'precio_venta' => 4094.16,
                'markup' => 5,
                'stock' => 'rojo',
                'oferta' => false,
                'aplicaciones' => [
                    'Uso universal camión / acoplado',
                ],
                'atributos' => [
                    'Tensión' => '12/24V',
                    'Color' => 'Cristal',
                ],
            ],
            [
                'codigo' => 'VDV0010.0562',
                'nombre' => 'OMER VARIADOR DE VELOCIDAD CITROEN SAXO PEUGEOT 307',
                'descripcion' => 'OMER VARIADOR DE VELOCIDAD (RESISTENCIA) CITROËN SAXO / PEUGEOT 307',
                'marca' => 'OMER',
                'rubro' => 'Eléctricos',
                'tipo' => 'Variador',
                'marca_vehiculo' => 'Citroën',
                'modelo' => 'Saxo',
                'version' => '1.4 Nafta',
                'anio' => '2005',
                'oem' => '6441L2',
                'costo' => 49576.78,
                'lista' => 58325.62,
                'precio_venta' => 79322.84,
                'markup' => 5,
                'stock' => 'verde',
                'oferta' => false,
                'aplicaciones' => [
                    'Citroën Saxo 1.4 (Nafta)',
                    'Peugeot 307 1.6 (Nafta)',
                ],
                'atributos' => [
                    'Conector' => '4 vías',
                    'Tensión' => '12V',
                ],
            ],
            [
                'codigo' => '1017LC.0090',
                'nombre' => 'BAIML LENTES REPUESTO (ACRILICO) CRISTAL',
                'descripcion' => 'BAIML LENTE DE REPUESTO EN ACRÍLICO CRISTAL PARA FARO DELIMITADOR',
                'marca' => 'BAIML',
                'rubro' => 'Iluminación',
                'tipo' => 'Faro',
                'marca_vehiculo' => 'Universal',
                'modelo' => 'Universal',
                'version' => '',
                'anio' => '2026',
                'oem' => '1017LC',
                'costo' => 411.46,
                'lista' => 484.07,
                'precio_venta' => 658.34,
                'markup' => 5,
                'stock' => 'rojo',
                'oferta' => false,
                'aplicaciones' => [
                    'Repuesto faro BAIML 1017C',
                ],
                'atributos' => [
                    'Material' => 'Acrílico',
                    'Color' => 'Cristal',
                ],
            ],
            [
                'codigo' => '40130X15XS',
                'nombre' => '40130X15XS',
                'descripcion' => '40130x15XS/5619XS ALFA/DOBLE DIENTES.',
                'marca' => 'VMG',
                'rubro' => 'Correas',
                'tipo' => 'Correa',
                'marca_vehiculo' => 'Acura',
                'modelo' => 'Integra',
                'version' => '1.8',
                'anio' => '2000',
                'oem' => '5619XS',
                'costo' => 28450.10,
                'lista' => 33470.70,
                'precio_venta' => 29872.61,
                'markup' => 5,
                'stock' => 'verde',
                'oferta' => false,
                'vehiculos' => [
                    ['marca' => 'ACURA', 'modelo' => 'INTEGRA'],
                    ['marca' => 'ACURA', 'modelo' => 'LEGEND'],
                    ['marca' => 'ALFA ROMEO', 'modelo' => '145'],
                ],
                'aplicaciones' => [
                    'Acura Integra 1.8 (Nafta)',
                    'Alfa romeo 145 2.0 L (AR67204 Nafta)',
                ],
                'atributos' => [
                    'Línea' => 'Sincrónica',
                    'Modelo' => 'XS',
                    'Cantidad de dientes' => '130',
                    'Ancho' => '15mm',
                    'Perfil' => '40mm',
                ],
            ],
            [
                'codigo' => '76149X25.4',
                'nombre' => '76149X25.4',
                'descripcion' => '76149x25.4T139',
                'marca' => 'VMG',
                'rubro' => 'Correas',
                'tipo' => 'Correa',
                'marca_vehiculo' => 'Acura',
                'modelo' => 'Integra',
                'version' => '1.8',
                'anio' => '2000',
                'oem' => 'T139',
                'costo' => 31980.55,
                'lista' => 37624.18,
                'precio_venta' => 33579.58,
                'markup' => 5,
                'stock' => 'amarillo',
                'oferta' => false,
                'vehiculos' => [
                    ['marca' => 'ACURA', 'modelo' => 'INTEGRA'],
                    ['marca' => 'ACURA', 'modelo' => 'LEGEND'],
                ],
                'aplicaciones' => [
                    'Acura Integra 1.8 (Nafta)',
                    'Acura Legend 3.2 (Nafta)',
                ],
                'atributos' => [
                    'Línea' => 'Sincrónica',
                    'Cantidad de dientes' => '139',
                    'Ancho' => '25,4mm',
                ],
            ],
            [
                'codigo' => '76154X25.4XS',
                'nombre' => '76154X25.4XS',
                'descripcion' => '76154x25.4XS/5641XS/T1602',
                'marca' => 'VMG',
                'rubro' => 'Correas',
                'tipo' => 'Correa',
                'marca_vehiculo' => 'Acura',
                'modelo' => 'Legend',
                'version' => '3.2',
                'anio' => '2000',
                'oem' => '5641XS',
                'costo' => 34120.90,
                'lista' => 40142.24,
                'precio_venta' => 35826.95,
                'markup' => 5,
                'stock' => 'verde',
                'oferta' => false,
                'vehiculos' => [
                    ['marca' => 'ACURA', 'modelo' => 'INTEGRA'],
                    ['marca' => 'ACURA', 'modelo' => 'LEGEND'],
                ],
                'aplicaciones' => [
                    'Acura Legend 3.2 (Nafta)',
                    'Acura Integra 1.8 (Nafta)',
                ],
                'atributos' => [
                    'Línea' => 'Sincrónica',
                    'Cantidad de dientes' => '154',
                    'Ancho' => '25,4mm',
                ],
            ],
        ];
    }
}
