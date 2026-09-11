<?php

namespace App\Support;

/**
 * Generador de PDF para tablas largas, escrito a mano.
 *
 * El proyecto no tiene librería de PDF y dompdf no aguanta una tabla de
 * decenas de miles de filas. Acá se arma el PDF directamente: una fuente
 * estándar (Helvetica, no hace falta incrustarla) y una línea de texto por
 * celda. Se escribe al archivo a medida que avanza, así nunca se guarda el
 * documento entero en memoria.
 */
class PdfTabla
{
    /** A4 apaisado, en puntos. */
    private const ANCHO = 842.0;

    private const ALTO = 595.0;

    private const MARGEN = 20.0;

    private const CUERPO = 7.0;

    private const ALTO_FILA = 10.5;

    /** @var resource */
    private $fh;

    /** Posición en bytes de cada objeto, para la tabla xref del final. */
    private array $offsets = [];

    private int $bytes = 0;

    private int $proximoObjeto = 5;

    /** Objetos de página, en orden. */
    private array $paginas = [];

    private array $contenido = [];

    private int $numeroPagina = 0;

    private float $y = 0;

    /**
     * @param  array<int, array{titulo:string, ancho:float, alineacion?:string}>  $columnas
     */
    public function __construct(
        private string $ruta,
        private string $titulo,
        private string $subtitulo,
        private array $columnas,
    ) {
        $this->fh = fopen($this->ruta, 'w');
        $this->escribir("%PDF-1.4\n");
        // Marca de binario: algunos visores la piden para no tratarlo como texto.
        $this->escribir("%\xE2\xE3\xCF\xD3\n");

        $this->objeto(3, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->objeto(4, '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');

        $this->nuevaPagina();
    }

    /** @param  array<int, string>  $celdas */
    public function fila(array $celdas): void
    {
        if ($this->y < self::MARGEN + self::ALTO_FILA) {
            $this->cerrarPagina();
            $this->nuevaPagina();
        }

        $x = self::MARGEN;

        foreach ($this->columnas as $i => $columna) {
            $this->celda($celdas[$i] ?? '', $x, $columna, self::CUERPO, 'F1');
            $x += $columna['ancho'];
        }

        $this->y -= self::ALTO_FILA;
    }

    /** Cierra el documento y devuelve la cantidad de páginas. */
    public function cerrar(): int
    {
        $this->cerrarPagina();

        $kids = implode(' ', array_map(fn (int $id) => "{$id} 0 R", $this->paginas));

        $this->objeto(1, '<< /Type /Catalog /Pages 2 0 R >>');
        $this->objeto(2, '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($this->paginas)
            . ' /MediaBox [0 0 ' . self::ANCHO . ' ' . self::ALTO . '] >>');

        $this->tablaXref();

        fclose($this->fh);

        return count($this->paginas);
    }

    /* ------------------------------------------------------------------ */

    private function nuevaPagina(): void
    {
        $this->numeroPagina++;
        $this->contenido = [];
        $this->y = self::ALTO - self::MARGEN;

        $this->texto($this->titulo, self::MARGEN, $this->y, 12, 'F2');
        $this->y -= 14;

        $this->texto($this->subtitulo, self::MARGEN, $this->y, 8, 'F1');
        $this->texto('Página ' . $this->numeroPagina, self::ANCHO - self::MARGEN - 50, $this->y, 8, 'F1');
        $this->y -= 16;

        $x = self::MARGEN;

        foreach ($this->columnas as $columna) {
            $this->celda($columna['titulo'], $x, $columna, 7.5, 'F2');
            $x += $columna['ancho'];
        }

        $this->y -= 4;
        $this->linea(self::MARGEN, $this->y, self::ANCHO - self::MARGEN, $this->y);
        $this->y -= self::ALTO_FILA;
    }

    private function cerrarPagina(): void
    {
        // Sin comprimir, una lista de 40 mil filas pasa de los 15 MB.
        $flujo = gzcompress(implode(PHP_EOL, $this->contenido), 6);

        $idContenido = $this->proximoObjeto++;
        $this->objeto($idContenido, '<< /Length ' . strlen($flujo) . ' /Filter /FlateDecode >>', $flujo);

        $idPagina = $this->proximoObjeto++;
        $this->objeto($idPagina, '<< /Type /Page /Parent 2 0 R'
            . ' /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >>'
            . ' /Contents ' . $idContenido . ' 0 R >>');

        $this->paginas[] = $idPagina;
    }

    /** @param  array{titulo:string, ancho:float, alineacion?:string}  $columna */
    private function celda(string $valor, float $x, array $columna, float $tamano, string $fuente): void
    {
        $valor = $this->recortar($valor, $columna['ancho'] - 4, $tamano);

        if (($columna['alineacion'] ?? 'izquierda') === 'derecha') {
            $x += $columna['ancho'] - 4 - $this->ancho($valor, $tamano);
        }

        $this->texto($valor, $x, $this->y, $tamano, $fuente);
    }

    private function texto(string $valor, float $x, float $y, float $tamano, string $fuente): void
    {
        $this->contenido[] = sprintf(
            'BT /%s %.1f Tf %.2f %.2f Td (%s) Tj ET',
            $fuente,
            $tamano,
            $x,
            $y,
            $this->escapar($valor),
        );
    }

    private function linea(float $x1, float $y1, float $x2, float $y2): void
    {
        $this->contenido[] = sprintf('0.7 w %.2f %.2f m %.2f %.2f l S', $x1, $y1, $x2, $y2);
    }

    /**
     * Ancho aproximado del texto. Helvetica: los dígitos miden 556/1000 del
     * cuerpo y las mayúsculas rondan 700, que es lo que más hay acá.
     */
    private function ancho(string $valor, float $tamano): float
    {
        $total = 0.0;

        foreach (str_split($valor) as $caracter) {
            $total += match (true) {
                $caracter === ' ' => 278,
                ctype_digit($caracter) => 556,
                in_array($caracter, ['.', ',', ':', ';', '|', 'i', 'l', 'j'], true) => 250,
                ctype_upper($caracter) => 690,
                default => 520,
            };
        }

        return $total / 1000 * $tamano;
    }

    private function recortar(string $valor, float $ancho, float $tamano): string
    {
        $valor = trim(preg_replace('/\s+/u', ' ', $valor) ?? '');

        if ($this->ancho($valor, $tamano) <= $ancho) {
            return $valor;
        }

        while ($valor !== '' && $this->ancho($valor . '…', $tamano) > $ancho) {
            $valor = mb_substr($valor, 0, mb_strlen($valor) - 1);
        }

        return $valor . '…';
    }

    /** Las fuentes estándar usan WinAnsi, no UTF-8. */
    private function escapar(string $valor): string
    {
        $valor = mb_convert_encoding($valor, 'Windows-1252', 'UTF-8');

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $valor);
    }

    private function objeto(int $id, string $diccionario, ?string $flujo = null): void
    {
        $this->offsets[$id] = $this->bytes;

        $this->escribir("{$id} 0 obj\n{$diccionario}\n");

        if ($flujo !== null) {
            $this->escribir("stream\n{$flujo}\nendstream\n");
        }

        $this->escribir("endobj\n");
    }

    private function tablaXref(): void
    {
        $total = max(array_keys($this->offsets)) + 1;
        $inicio = $this->bytes;

        $xref = "xref\n0 {$total}\n0000000000 65535 f \n";

        for ($i = 1; $i < $total; $i++) {
            $xref .= sprintf("%010d 00000 n \n", $this->offsets[$i] ?? 0);
        }

        $this->escribir($xref);
        $this->escribir("trailer\n<< /Size {$total} /Root 1 0 R >>\nstartxref\n{$inicio}\n%%EOF\n");
    }

    private function escribir(string $texto): void
    {
        fwrite($this->fh, $texto);
        $this->bytes += strlen($texto);
    }
}
