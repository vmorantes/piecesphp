<?php

/**
 * Invalidación de la caché de código de la APLICACIÓN VIVA.
 *
 * Existe porque la misma regla —«invalida antes de medir»— falló TRES veces estando
 * escrita. Cuando una regla falla tres veces el arreglo no es repetirla: es convertirla
 * en mecanismo. Esto es el mecanismo. Ver T51.
 *
 * Lo que se medió, y con qué instrumento:
 *   - `php-fpm8.5 -m` dice que Zend OPcache ESTÁ cargado. Los .ini no lo mencionan
 *     porque viene compilado en el binario: leer los conf.d de /etc/php daba la respuesta
 *     contraria y tranquilizadora.
 *   - `php-fpm8.5 -i` da validate_timestamps=On, revalidate_freq=2, file_update_protection=2.
 *   - La ventana de rancio se reprodujo 3 de 3: petición → editar → petición inmediata
 *     devuelve el código VIEJO; sin la petición previa, devuelve el nuevo.
 *
 * De ahí sale la espera: opcache no vuelve a mirar el archivo hasta que han pasado
 * `revalidate_freq` segundos desde la última comprobación, y no cachea un archivo cuya
 * mtime sea más joven que `file_update_protection`. Esperar
 * `max(revalidate_freq, file_update_protection) + 1` desde la ÚLTIMA EDICIÓN cubre las dos.
 *
 * NO se toca la configuración del sistema ni se recarga FPM: solo se espera lo que el
 * propio binario declara que hay que esperar. Si no se puede averiguar, se ABORTA.
 */

/**
 * Versión de PHP que sirve una URL base. No se adivina: se pregunta a la configuración
 * de Apache que enruta ese host. Doce masters de FPM corren en esta máquina, así que
 * mirar los procesos no distingue cuál responde.
 *
 * @throws \RuntimeException si no se puede determinar. Abortar es la respuesta correcta:
 *         medir con una ventana desconocida es peor que no medir.
 */
function pcsphp_live_php_version(string $base): string
{
    $override = getenv('PCSPHP_WEB_PHP');
    if (is_string($override) && preg_match('/^\d+\.\d+$/', $override) === 1) {
        return $override;
    }

    $host = (string) parse_url($base, PHP_URL_HOST);
    if ($host === '') {
        throw new \RuntimeException("No se pudo extraer el host de «{$base}».");
    }

    foreach (glob('/etc/apache2/sites-enabled/*.conf') ?: [] as $file) {
        $contents = (string) @file_get_contents($file);
        if ($contents === '') {
            continue;
        }
        if (preg_match('/^\s*ServerName\s+' . preg_quote($host, '/') . '\s*$/mi', $contents) !== 1) {
            continue;
        }
        if (preg_match('/php(\d+\.\d+)-fpm/', $contents, $matches) === 1) {
            return $matches[1];
        }
    }

    throw new \RuntimeException(
        "No se pudo determinar qué PHP sirve «{$host}». Declara la versión en la variable "
        . "de entorno PCSPHP_WEB_PHP (por ejemplo: PCSPHP_WEB_PHP=8.5)."
    );
}

/**
 * Ajustes de opcache del SAPI que sirve. Se le preguntan AL BINARIO, no a los .ini:
 * la extensión puede venir compilada y entonces los .ini mienten por omisión.
 *
 * @return array{enabled:bool,validate:bool,freq:int,protection:int,binary:string}
 * @throws \RuntimeException si el binario no existe o no responde.
 */
function pcsphp_live_opcache(string $version): array
{
    $binary = 'php-fpm' . $version;
    $output = [];
    $status = 0;
    exec(escapeshellcmd($binary) . ' -i 2>/dev/null', $output, $status);
    if ($status !== 0 || count($output) === 0) {
        throw new \RuntimeException("«{$binary} -i» no respondió. No se puede saber si hay caché que invalidar.");
    }

    $text = implode("\n", $output);
    $read = static function (string $key, string $default) use ($text): string {
        return preg_match('/^' . preg_quote($key, '/') . '\s*=>\s*(\S+)/m', $text, $m) === 1 ? $m[1] : $default;
    };

    if (preg_match('/^opcache\.enable\s*=>/m', $text) !== 1) {
        throw new \RuntimeException("«{$binary} -i» no declara opcache.enable. Instrumento no fiable: se aborta.");
    }

    return [
        'enabled' => mb_strtolower($read('opcache.enable', 'Off')) === 'on',
        'validate' => mb_strtolower($read('opcache.validate_timestamps', 'On')) === 'on',
        'freq' => (int) $read('opcache.revalidate_freq', '2'),
        'protection' => (int) $read('opcache.file_update_protection', '2'),
        'binary' => $binary,
    ];
}

/**
 * Segundos que hay que esperar desde la última edición para que la aplicación viva
 * recompile. Cero si no hay caché que invalidar.
 */
function pcsphp_live_wait_seconds(array $opcache): int
{
    if (!$opcache['enabled']) {
        return 0;
    }
    return max($opcache['freq'], $opcache['protection']) + 1;
}

/**
 * Invalida la caché de código de la aplicación viva. ABORTA el proceso si no puede.
 *
 * @param string[] $editedFiles Archivos recién editados. Solo se usan para informar y
 *                              para comprobar que existen: la espera no depende de ellos.
 */
function pcsphp_live_invalidate(string $base, array $editedFiles = [], bool $quiet = false): void
{
    $say = static function (string $line) use ($quiet): void {
        if (!$quiet) {
            fwrite(STDERR, $line . "\n");
        }
    };

    try {
        $version = pcsphp_live_php_version($base);
        $opcache = pcsphp_live_opcache($version);
    } catch (\RuntimeException $e) {
        fwrite(STDERR, "\n[CACHÉ VIVA] ABORTADO: " . $e->getMessage() . "\n");
        fwrite(STDERR, "Una comparación contra la web sin invalidar la caché MIENTE, y miente en la dirección tranquilizadora.\n");
        exit(1);
    }

    if (!$opcache['enabled']) {
        $say("[CACHÉ VIVA] opcache apagado en {$opcache['binary']}: nada que invalidar.");
        return;
    }

    if (!$opcache['validate']) {
        fwrite(STDERR, "\n[CACHÉ VIVA] ABORTADO: {$opcache['binary']} tiene opcache.validate_timestamps=Off.\n");
        fwrite(STDERR, "Con esa configuración NINGUNA espera invalida nada: hay que recargar FPM. No se mide.\n");
        exit(1);
    }

    foreach ($editedFiles as $file) {
        if (!is_file($file)) {
            fwrite(STDERR, "\n[CACHÉ VIVA] ABORTADO: se declaró como editado «{$file}» y no existe.\n");
            exit(1);
        }
    }

    $wait = pcsphp_live_wait_seconds($opcache);
    $say(
        "[CACHÉ VIVA] {$opcache['binary']}: opcache On, revalidate_freq={$opcache['freq']}, "
        . "file_update_protection={$opcache['protection']} -> esperando {$wait}s."
    );
    sleep($wait);
}
