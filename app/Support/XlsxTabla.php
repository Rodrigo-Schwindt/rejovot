<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Generador de Excel (.xlsx) para tablas largas, escrito a mano.
 *
 * El proyecto no tiene librería de Excel. Un .xlsx es un zip con unos XML: la
 * hoja se escribe a un archivo a medida que avanza y al cerrar se empaqueta,
 * así nunca se guarda el documento entero en memoria.
 *
 * Las columnas `texto` van como texto de verdad: Excel no convierte
 * «4321.0050» en un número, como sí hace al abrir un CSV.
 */
class XlsxTabla
{
    /** Índices de cellXfs en styles.xml. */
    private const ESTILO_ENCABEZADO = 1;

    private const ESTILO_TEXTO = 2;

    private const ESTILO_NUMERO = 3;

    /** Lo máximo que Excel acepta en una celda. */
    private const MAXIMO_CELDA = 32767;

    /** @var resource */
    private $fh;

    private string $rutaHoja;

    private int $filas = 0;

    /**
     * @param  array<int, array{titulo:string, ancho:float, tipo?:string}>  $columnas  tipo: texto o numero
     */
    public function __construct(
        private string $ruta,
        private string $nombreHoja,
        private array $columnas,
    ) {
        $this->rutaHoja = $this->ruta . '.hoja';
        $this->fh = fopen($this->rutaHoja, 'w');

        fwrite($this->fh, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            // El encabezado queda fijo al bajar.
            . '<sheetViews><sheetView tabSelected="1" workbookViewId="0">'
            . '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A2" sqref="A2"/>'
            . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="15"/>'
            . $this->anchos()
            . '<sheetData>');

        $this->escribirFila(array_column($this->columnas, 'titulo'), encabezado: true);
    }

    /** @param  array<int, string|float|int|null>  $valores */
    public function fila(array $valores): void
    {
        $this->escribirFila($valores);
    }

    /** Cierra la hoja y arma el .xlsx. */
    public function cerrar(): void
    {
        fwrite($this->fh, '</sheetData>'
            . '<autoFilter ref="' . $this->rango() . '"/>'
            . $this->sinAvisoDeNumeros()
            . '</worksheet>');
        fclose($this->fh);

        $zip = new ZipArchive;

        if ($zip->open($this->ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("No se pudo crear {$this->ruta}");
        }

        $zip->addFromString('[Content_Types].xml', $this->tiposDeContenido());
        $zip->addFromString('_rels/.rels', $this->relaciones());
        $zip->addFromString('xl/workbook.xml', $this->libro());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->relacionesDelLibro());
        $zip->addFromString('xl/styles.xml', $this->estilos());
        $zip->addFile($this->rutaHoja, 'xl/worksheets/sheet1.xml');
        $zip->close();

        unlink($this->rutaHoja);
    }

    private function escribirFila(array $valores, bool $encabezado = false): void
    {
        $this->filas++;
        $celdas = '';

        foreach (array_values($valores) as $i => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            $referencia = $this->letra($i) . $this->filas;
            $tipo = $this->columnas[$i]['tipo'] ?? null;

            if (! $encabezado && $tipo === 'numero') {
                $celdas .= '<c r="' . $referencia . '" s="' . self::ESTILO_NUMERO . '"><v>' . (float) $valor . '</v></c>';

                continue;
            }

            $estilo = match (true) {
                $encabezado => ' s="' . self::ESTILO_ENCABEZADO . '"',
                $tipo === 'texto' => ' s="' . self::ESTILO_TEXTO . '"',
                default => '',
            };

            $celdas .= '<c r="' . $referencia . '"' . $estilo . ' t="inlineStr"><is><t xml:space="preserve">'
                . $this->xml((string) $valor) . '</t></is></c>';
        }

        fwrite($this->fh, '<row r="' . $this->filas . '">' . $celdas . '</row>');
    }

    /** Sin los caracteres de control, que hacen que Excel rechace el archivo. */
    private function xml(string $texto): string
    {
        $limpio = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $texto);

        return htmlspecialchars(mb_substr($limpio, 0, self::MAXIMO_CELDA), ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function letra(int $indice): string
    {
        return chr(65 + $indice);
    }

    private function ultimaLetra(): string
    {
        return $this->letra(count($this->columnas) - 1);
    }

    private function rango(): string
    {
        return 'A1:' . $this->ultimaLetra() . $this->filas;
    }

    private function anchos(): string
    {
        $cols = '';

        foreach (array_values($this->columnas) as $i => $columna) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . $columna['ancho'] . '" customWidth="1"/>';
        }

        return '<cols>' . $cols . '</cols>';
    }

    /** Saca el triangulito verde de «número guardado como texto» en los códigos. */
    private function sinAvisoDeNumeros(): string
    {
        $rangos = [];

        foreach (array_values($this->columnas) as $i => $columna) {
            if (($columna['tipo'] ?? null) === 'texto') {
                $rangos[] = $this->letra($i) . '2:' . $this->letra($i) . max(2, $this->filas);
            }
        }

        return $rangos
            ? '<ignoredErrors><ignoredError sqref="' . implode(' ', $rangos) . '" numberStoredAsText="1"/></ignoredErrors>'
            : '';
    }

    private function tiposDeContenido(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function relaciones(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function libro(): string
    {
        $hoja = $this->xml($this->nombreHoja);
        // El filtro de la fila de encabezado se declara también como nombre oculto.
        $filtro = "'" . str_replace("'", "''", $hoja) . "'!\$A\$1:\$" . $this->ultimaLetra() . '$' . $this->filas;

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $hoja . '" sheetId="1" r:id="rId1"/></sheets>'
            . '<definedNames><definedName name="_xlnm._FilterDatabase" localSheetId="0" hidden="1">' . $filtro . '</definedName></definedNames>'
            . '</workbook>';
    }

    private function relacionesDelLibro(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /** Formato 49 es texto; el 4 es #,##0.00, que Excel muestra según el país. */
    private function estilos(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n"
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2">'
            . '<font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            . '</fonts>'
            . '<fills count="3">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF002B56"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="4">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}
