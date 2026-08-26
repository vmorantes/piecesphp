<?php

/**
 * LA LISTA DE RUTAS QUE NO SE PIDEN, LEÍDA DE UN SOLO SITIO.
 *
 * Estaba copiada en `bin/walk-routes` y en `bin/walk-attribute`. Las dos copias todavía
 * coincidían en los 17 patrones, pero el COMENTARIO que explicaba por qué se mira también
 * la URL solo viajó a una: la razón ya había divergido antes que el dato.
 *
 * Lo que cuesta que diverja no es ruido. Un recorredor que pida una ruta de escritura
 * ESCRIBE creyendo que solo lee, se lo atribuye a una ruta de lectura, y deja inservible
 * la foto de la que depende E3. Por eso esta es la primera de las cinco listas de la
 * LEY 11 que se cierra. Ver T73.
 *
 * Aquí vive la FUNCIÓN de comparación; en `files/dev/forbidden-routes.json`, los patrones.
 * Cualquier herramienta futura —en PHP o no— puede leer ese JSON.
 */

if (!function_exists('pcsphp_forbidden_patterns')) {

    /**
     * @param string $root Raíz del repositorio.
     * @return array<int, string>
     */
    function pcsphp_forbidden_patterns(string $root): array
    {
        $path = $root . '/files/dev/forbidden-routes.json';

        //SIN LISTA NO SE RECORRE. Seguir sin ella significa pedirlo TODO, escrituras incluidas.
        if (!is_file($path)) {
            fwrite(STDERR, "No existe {$path}: sin la lista de rutas prohibidas el recorrido pediría también las de escritura.\n");
            exit(1);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        $patterns = is_array($decoded) ? ($decoded['patterns'] ?? null) : null;

        if (!is_array($patterns) || count($patterns) === 0) {
            fwrite(STDERR, "{$path} no declara `patterns` o está vacío. El recorrido NO empieza.\n");
            exit(1);
        }

        return array_values(array_map('strval', $patterns));
    }

    /**
     * Las excepciones declaradas, que GANAN sobre los patrones.
     *
     * La comparación es por subcadena, así que un patrón de escritura atrapa rutas de lectura:
     * `-add` casa con `-forms-add`. Sin esta puerta, afinar los patrones obligaría a convertirlos
     * en expresiones regulares, y una lista de expresiones regulares es una lista que nadie
     * relee. Ver T100.
     *
     * @param string $root Raíz del repositorio.
     * @return array<int, string>
     */
    function pcsphp_forbidden_allowed(string $root): array
    {
        $path = $root . '/files/dev/forbidden-routes.json';
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $allowed = is_array($decoded) ? ($decoded['allow'] ?? []) : [];
        return is_array($allowed) ? array_map('strval', array_keys($allowed)) : [];
    }

    /**
     * Devuelve el patrón que veta la ruta, o null si se puede pedir.
     *
     * Se miran el nombre Y la url: un nombre puede no seguir la convención y la url sí.
     *
     * @param array<int, string> $patterns
     * @param array<int, string> $allowed Excepciones declaradas; ganan sobre los patrones.
     */
    function pcsphp_forbidden_match(string $name, string $url, array $patterns, array $allowed = []): ?string
    {
        $haystack = mb_strtolower($name . ' ' . $url);

        //La excepción se mira PRIMERO: si no, el patrón que la motiva la vetaría igual.
        foreach ($allowed as $exception) {
            if ($exception !== '' && mb_strpos($haystack, mb_strtolower($exception)) !== false) {
                return null;
            }
        }

        foreach ($patterns as $needle) {
            if (mb_strpos($haystack, $needle) !== false) {
                return $needle;
            }
        }

        return null;
    }

}
