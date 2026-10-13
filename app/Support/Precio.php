<?php

namespace App\Support;

class Precio
{
    /** Formato de moneda argentino: $65.346,08 (negativo: -$65.346,08) */
    public static function ar(float|int|null $valor, bool $simbolo = true, int $decimales = 2): string
    {
        $valor = round((float) $valor, $decimales);
        $numero = number_format(abs($valor), $decimales, ',', '.');
        $signo = $valor < 0 ? '-' : '';

        return $signo . ($simbolo ? '$' : '') . $numero;
    }

    /** Igual que ar(), pero sin centavos: $43.000 */
    public static function arEntero(float|int|null $valor, bool $simbolo = true): string
    {
        return self::ar($valor, $simbolo, 0);
    }
}
