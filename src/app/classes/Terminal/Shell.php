<?php

/**
 * Shell.php
 */

namespace Terminal;

/**
 * Shell - Si este PHP deja lanzar procesos (exec(), proc_open()). Un servidor puede desactivarlas con disable_functions
 * (Hestia, en producción): sin esta pregunta, la primera llamada es un error fatal y la tarea no llega a su resumen (H508).
 *
 * Vive aquí porque la usan varias tareas de bin/cli (verify-integrity, gates, clone-compare): cada una decide qué deja de
 * aplicar sin ella.
 *
 * @package     Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class Shell
{

    /**
     * La etiqueta de lo que no aplica sin exec() o proc_open() (no es la de la distribución: aquí falta la función).
     */
    const NOT_APPLICABLE = '[NO APLICA]';

    /**
     * Si la función existe y no está en disable_functions.
     *
     * @param string $function exec, proc_open…
     * @return bool
     */
    public static function available(string $function = 'exec'): bool
    {
        if (!function_exists($function)) {
            return false;
        }
        $disabled = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));
        return !in_array(strtolower($function), $disabled, true);
    }

    /**
     * Si se pueden lanzar procesos por cualquiera de las tres vías que usan las tareas (exec(), shell_exec(), proc_open()):
     * una tarea que use una sola no puede saber cuál le falta a otra comprobación suya.
     *
     * @return bool
     */
    public static function processes(): bool
    {
        return self::available('exec') && self::available('shell_exec') && self::available('proc_open');
    }

    /**
     * El motivo cuando falta alguna de las tres.
     *
     * @return string
     */
    public static function processesReason(): string
    {
        $missing = array_values(array_filter(['exec', 'shell_exec', 'proc_open'], fn(string $function): bool => !self::available($function)));
        return 'necesita lanzar procesos y este PHP tiene desactivada ' . implode(', ', array_map(fn(string $f): string => "{$f}()", $missing)) . ' (disable_functions)';
    }

    /**
     * Por qué no aplica lo que la necesita, para su línea NO APLICA.
     *
     * @param string $function
     * @return string
     */
    public static function reason(string $function = 'exec'): string
    {
        return "necesita {$function}(), que este PHP tiene desactivada (disable_functions)";
    }
}
