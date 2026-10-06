<?php

/**
 * Distribution.php
 */

namespace Terminal;

/**
 * Distribution - Si esta instalación es la distribución pública, y qué comprobación no aplica en ella.
 *
 * Existe por el ADR 0049: el repositorio público se genera con `bin/make-distribution` y **no lleva**
 * nuestras líneas base (`files/dev/`), los censos e instrumentos de `bin/` que se quedan, los artefactos
 * de PHPStan ni la capa C del andamiaje. Una comprobación que los necesita fallaría en cada clon por algo
 * que no es suyo, y una verificación que falla sin culpa se apaga el primer día.
 *
 * **La única forma de saber que se está en la distribución es su marca**, `.piecesphp-distribution` en la
 * raíz, que escribe `bin/make-distribution`. Sin ella, lo que falta es un fallo, como siempre (LEY 18). Con
 * ella, la comprobación lo dice en su línea `[NO APLICA EN LA DISTRIBUCIÓN]`: nunca en silencio.
 *
 * @package     Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class Distribution
{

    /**
     * La marca, relativa a la raíz del repositorio.
     *
     * @var string
     */
    const MARK_FILE = '.piecesphp-distribution';

    /**
     * El prefijo de la línea que reemplaza a una comprobación que no aplica.
     *
     * @var string
     */
    const NOT_APPLICABLE = '[NO APLICA EN LA DISTRIBUCIÓN]';

    /**
     * Cuántas comprobaciones han dicho que no aplican en este proceso: el resumen final tiene que contarlas.
     *
     * @var int
     */
    protected static int $announced = 0;

    /**
     * La raíz del repositorio (no `src/`).
     *
     * @return string
     */
    public static function root(): string
    {
        return dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    }

    /**
     * @param string|null $root La raíz que se mira; por defecto, la de esta instalación.
     * @return bool
     */
    public static function isDistribution(?string $root = null): bool
    {
        return is_file(($root ?? self::root()) . '/' . self::MARK_FILE);
    }

    /**
     * Lo que falta de una lista de rutas relativas a la raíz.
     *
     * @param string[] $required
     * @param string|null $root
     * @return string[]
     */
    public static function missing(array $required, ?string $root = null): array
    {
        $root = $root ?? self::root();
        return array_values(array_filter($required, fn (string $relative): bool => !file_exists($root . '/' . $relative)));
    }

    /**
     * La línea que imprime una comprobación que no aplica, con lo que falta y por qué no viaja.
     *
     * @param string $check El número o el nombre de la comprobación.
     * @param string[] $missing
     * @return string
     */
    public static function notApplicableLine(string $check, array $missing): string
    {
        $reasons = [];
        foreach ($missing as $relative) {
            $reasons[self::reason($relative)][] = $relative;
        }
        $parts = [];
        foreach ($reasons as $reason => $paths) {
            $parts[] = implode(', ', $paths) . (count($paths) > 1 ? ', que no viajan' : ', que no viaja') . ($reason !== '' ? ' (' . $reason . ')' : '');
        }
        return self::NOT_APPLICABLE . ' ' . $check . ': ' . implode('; ', $parts);
    }

    /**
     * Si una comprobación aplica aquí. En la distribución, sin lo que necesita, imprime su línea y NO aplica;
     * en el repositorio de desarrollo aplica siempre, y lo que falte lo dirá ella como fallo.
     *
     * @param string $check
     * @param string[] $required
     * @return bool
     */
    public static function applies(string $check, array $required): bool
    {
        if (!self::isDistribution()) {
            return true;
        }
        $missing = self::missing($required);
        if (count($missing) === 0) {
            return true;
        }
        self::announce(self::notApplicableLine($check, $missing));
        return false;
    }

    /**
     * Imprime una línea de «no aplica» y la cuenta.
     *
     * @param string $line
     * @return void
     */
    public static function announce(string $line): void
    {
        self::$announced++;
        echoTerminal("\e[33m" . $line . "\e[39m");
    }

    /**
     * @return int
     */
    public static function announcedCount(): int
    {
        return self::$announced;
    }

    /**
     * @param string $relative
     * @return string
     */
    protected static function reason(string $relative): string
    {
        if (str_starts_with($relative, 'PHPStanResult')) {
            return 'ADR 0049 §5';
        }
        if (str_starts_with($relative, 'bin/')) {
            return 'ADR 0049 §6';
        }
        if (str_starts_with($relative, '.agents/')) {
            return 'capa C, ADR 0049 §3';
        }
        return 'ADR 0049, bin/distribution-exclude.txt';
    }

}
