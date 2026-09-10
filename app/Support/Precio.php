<?php

namespace App\Support;

class Precio
{
    /** Formato de moneda argentino: $65.346,08 */
    public static function ar(float|int|null $valor, bool $simbolo = true, int $decimales = 2): string
    {
        $numero = number_format((float) $valor, $decimales, ',', '.');

        return $simbolo ? '$' . $numero : $numero;
    }

    /** Igual que ar(), pero sin centavos: $43.000 */
    public static function arEntero(float|int|null $valor, bool $simbolo = true): string
    {
        return self::ar($valor, $simbolo, 0);
    }
}
