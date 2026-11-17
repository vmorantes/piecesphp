<?php

//Las claves de interfaz sin inglés: cada __() y _i18n() con texto literal contra el diccionario 'en' que carga la
//aplicación (H226, G12), tokenizado y con canario; trinquete en 0. Y ninguna clave con letras descompuestas (NFD).
//@areas nucleo

use PiecesPHP\Core\Config;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/lang-missing-english', function ($args) {

    echoTerminal("\e[33m[TEST:LangMissingEnglish] Las claves de interfaz que faltan en inglés\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal(' ');
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];
    };

    $detalle = is_array($args) && in_array($args['detalle'] ?? null, ['yes', '1', 'si'], true);
    $raiz = rtrim(str_replace('\\', '/', basepath('')), '/');

    //─── El universo ────────────────────────────────────────────────────────────────────────────
    //Fuera: lo de terceros, lo generado, las pruebas y las demos de la hoja de diseño.
    $fuera = ['/vendor/', '/node_modules/', '/app/logs/', '/app/cache/', '/local-tests/', '/statics/plugins/', '/server-delegated/', '/missing-lang-messages/', '/AdminPanel/Views/panel/pages/design-sheet/', '/system-views/mailing/template_mautic.php'];
    //template_mautic.php fuerza el español y un grupo de prueba: traducirla no cambia lo que se ve.
    $archivos = function (string $carpeta, string $extension) use ($fuera): array {
        $salida = [];
        if (!is_dir($carpeta)) {
            return $salida;
        }
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS)) as $archivo) {
            if (!$archivo instanceof \SplFileInfo || !$archivo->isFile() || $archivo->getExtension() !== $extension) {
                continue;
            }
            $ruta = str_replace('\\', '/', $archivo->getPathname());
            foreach ($fuera as $excluido) {
                if (str_contains($ruta, $excluido)) {
                    continue 2;
                }
            }
            if (str_ends_with($ruta, '.min.js')) {
                continue;
            }
            $salida[] = $ruta;
        }
        sort($salida);
        return $salida;
    };

    //─── Los diccionarios que la aplicación carga ───────────────────────────────────────────────
    //Todas las *Lang::injectLang(): alguna solo inyecta al usarse (AILang) y sus claves parecerían faltar.
    $inyectadas = 0;
    foreach ($archivos("{$raiz}/app", 'php') as $ruta) {
        if (!str_ends_with($ruta, 'Lang.php')) {
            continue;
        }
        $fuente = (string) file_get_contents($ruta);
        $espacioLang = preg_match('~^namespace\s+([^;]+);~m', $fuente, $m) === 1 ? trim($m[1]) : '';
        $claseLang = preg_match('~^\s*(?:final\s+)?class\s+(\w+)~m', $fuente, $m) === 1 ? $m[1] : null;
        $fqcnLang = $claseLang !== null ? ltrim($espacioLang . '\\' . $claseLang, '\\') : null;
        if ($fqcnLang !== null && class_exists($fqcnLang) && method_exists($fqcnLang, 'injectLang')) {
            $fqcnLang::injectLang();
            $inyectadas++;
        }
    }
    $check($inyectadas >= 29, "banco: inyectados los grupos de {$inyectadas} clase(s) *Lang (las que solo se inyectan al usarse, también)");
    $traducciones = Config::get_translations();
    $tieneIngles = function (string $grupo, string $texto) use ($traducciones): bool {
        //Solo 'en': 'default' es la caída (en español), no una traducción (P309).
        foreach (['en'] as $idioma) {
            if (isset($traducciones[$idioma][$grupo]) && is_array($traducciones[$idioma][$grupo]) && array_key_exists($texto, $traducciones[$idioma][$grupo])) {
                return true;
            }
        }
        return false;
    };

    //─── Las llamadas __() de PHP, tokenizadas ──────────────────────────────────────────────────
    $literal = function (string $token): ?string {
        if (strlen($token) < 2) {
            return null;
        }
        $comilla = $token[0];
        $cuerpo = substr($token, 1, -1);
        if ($comilla === "'") {
            return str_replace(["\\'", '\\\\'], ["'", '\\'], $cuerpo);
        }
        //Con comillas dobles, solo si no interpola.
        return str_contains($cuerpo, '$') ? null : stripcslashes($cuerpo);
    };
    $llamadas = [];
    $sinResolver = 0;
    $sinGrupo = [];
    foreach ($archivos("{$raiz}/app", 'php') as $ruta) {
        $codigo = (string) file_get_contents($ruta);
        if (!str_contains($codigo, '__(')) {
            continue;
        }
        $tokens = array_values(array_filter(token_get_all($codigo), fn ($t) => !is_array($t) || !in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
        $espacio = '';
        $clase = null;
        $usos = [];
        $n = count($tokens);
        //Las variables del archivo asignadas con `=`: nombre => sus expresiones (solo se usan si hay UNA).
        $asignaciones = [];
        for ($k = 0; $k + 2 < $n; $k++) {
            if (is_array($tokens[$k]) && $tokens[$k][0] === T_VARIABLE && $tokens[$k + 1] === '=') {
                $expresion = [];
                for ($m = $k + 2; $m < $n && $tokens[$m] !== ';'; $m++) {
                    $expresion[] = $tokens[$m];
                }
                $asignaciones[$tokens[$k][1]][] = $expresion;
            }
        }
        for ($i = 0; $i < $n; $i++) {
            $t = $tokens[$i];
            if (is_array($t) && $t[0] === T_NAMESPACE && isset($tokens[$i + 1]) && is_array($tokens[$i + 1])) {
                $espacio = ltrim($tokens[$i + 1][1], '\\');
            } elseif (is_array($t) && $t[0] === T_USE && isset($tokens[$i + 1]) && is_array($tokens[$i + 1]) && in_array($tokens[$i + 1][0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED, T_STRING], true) && $clase === null) {
                $nombre = ltrim($tokens[$i + 1][1], '\\');
                $alias = (isset($tokens[$i + 2], $tokens[$i + 3]) && is_array($tokens[$i + 2]) && $tokens[$i + 2][0] === T_AS && is_array($tokens[$i + 3])) ? $tokens[$i + 3][1] : substr((string) strrchr('\\' . $nombre, '\\'), 1);
                $usos[$alias] = $nombre;
            } elseif (is_array($t) && in_array($t[0], [T_CLASS, T_TRAIT, T_ENUM, T_INTERFACE], true) && isset($tokens[$i + 1]) && is_array($tokens[$i + 1]) && $tokens[$i + 1][0] === T_STRING && !(isset($tokens[$i - 1]) && is_array($tokens[$i - 1]) && $tokens[$i - 1][0] === T_DOUBLE_COLON)) {
                $clase = ($espacio !== '' ? $espacio . '\\' : '') . $tokens[$i + 1][1];
            }
            if (!(is_array($t) && $t[0] === T_STRING && $t[1] === '__' && ($tokens[$i + 1] ?? null) === '(')) {
                continue;
            }
            if (isset($tokens[$i - 1]) && (is_array($tokens[$i - 1]) && in_array($tokens[$i - 1][0], [T_FUNCTION, T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_NULLSAFE_OBJECT_OPERATOR], true))) {
                continue;
            }
            //Primer argumento: hasta la coma de profundidad 0.
            $j = $i + 2;
            $primero = [];
            $nivel = 0;
            for (; $j < $n; $j++) {
                $u = $tokens[$j];
                if (in_array($u, ['(', '['], true)) {
                    $nivel++;
                } elseif (in_array($u, [')', ']'], true)) {
                    if ($nivel === 0) {
                        break;
                    }
                    $nivel--;
                } elseif ($u === ',' && $nivel === 0) {
                    break;
                }
                $primero[] = $u;
            }
            if (($tokens[$j] ?? null) !== ',') {
                continue;
            }
            $segundo = $tokens[$j + 1] ?? null;
            $cierre = $tokens[$j + 2] ?? null;
            if (!is_array($segundo) || $segundo[0] !== T_CONSTANT_ENCAPSED_STRING || !in_array($cierre, [')', ','], true)) {
                continue;
            }
            $texto = $literal($segundo[1]);
            if ($texto === null) {
                continue;
            }
            //El grupo: literal, constante global o constante de clase; o una variable del archivo asignada UNA vez con eso.
            $grupo = null;
            if (count($primero) === 1 && is_array($primero[0]) && $primero[0][0] === T_VARIABLE && count($asignaciones[$primero[0][1]] ?? []) === 1) {
                $primero = $asignaciones[$primero[0][1]][0];
            }
            if (count($primero) === 1 && is_array($primero[0]) && $primero[0][0] === T_CONSTANT_ENCAPSED_STRING) {
                $grupo = $literal($primero[0][1]);
            } elseif (count($primero) === 1 && is_array($primero[0]) && in_array($primero[0][0], [T_STRING, T_NAME_FULLY_QUALIFIED], true)) {
                $nombre = ltrim($primero[0][1], '\\');
                $grupo = defined($nombre) ? constant($nombre) : null;
            } elseif (count($primero) === 3 && is_array($primero[0]) && is_array($primero[1]) && $primero[1][0] === T_DOUBLE_COLON && is_array($primero[2]) && $primero[2][0] === T_STRING) {
                $quien = ltrim($primero[0][1], '\\');
                if (in_array(strtolower($quien), ['self', 'static'], true)) {
                    $fqcn = $clase;
                } elseif (strtolower($quien) === 'parent') {
                    $fqcn = $clase !== null && class_exists($clase) ? (get_parent_class($clase) ?: null) : null;
                } elseif ($primero[0][0] === T_NAME_FULLY_QUALIFIED) {
                    $fqcn = $quien;
                } else {
                    $cabeza = explode('\\', $quien)[0];
                    $fqcn = isset($usos[$cabeza]) ? $usos[$cabeza] . substr($quien, strlen($cabeza)) : ($espacio !== '' ? $espacio . '\\' . $quien : $quien);
                }
                $constante = $fqcn !== null ? "{$fqcn}::{$primero[2][1]}" : null;
                try {
                    $grupo = $constante !== null && defined($constante) ? constant($constante) : null;
                } catch (\Throwable $e) {
                    $grupo = null;
                }
            }
            if (!is_string($grupo)) {
                $sinResolver++;
                $sinGrupo[] = ['texto' => $texto, 'donde' => substr($ruta, strlen($raiz) + 1) . ':' . $segundo[2]];
                continue;
            }
            $llamadas[] = ['grupo' => $grupo, 'texto' => $texto, 'donde' => substr($ruta, strlen($raiz) + 1) . ':' . $segundo[2], 'lado' => 'php'];
        }
    }

    //─── Las llamadas _i18n() de JS, con el analizador de TypeScript ─────────────────────────────
    $js = $archivos("{$raiz}/app", 'js');
    foreach ($archivos("{$raiz}/statics/core/js", 'js') as $ruta) {
        $js[] = $ruta;
    }
    $herramienta = dirname($raiz) . '/bin/tools/i18n/llamadas-i18n.js';
    $llamadasJs = [];
    $diccionariosJs = [];
    $jsLeido = false;
    if (is_file($herramienta)) {
        $salida = [];
        $codigoSalida = 1;
        //RETORNO-IGNORADO: la salida entera va en $salida y el éxito, en $codigoSalida (los comprueba el banco del analizador de JS).
        exec('node ' . escapeshellarg($herramienta) . ' ' . implode(' ', array_map('escapeshellarg', $js)) . ' 2>&1', $salida, $codigoSalida);
        $leido = json_decode(implode("\n", $salida), true);
        if ($codigoSalida === 0 && is_array($leido)) {
            $jsLeido = true;
            $llamadasJs = $leido['llamadas'] ?? [];
            $diccionariosJs = $leido['diccionarios'] ?? [];
        }
    }
    $check($jsLeido, 'banco: el analizador de JS (bin/tools/i18n/llamadas-i18n.js, con typescript) leyó ' . count($js) . ' archivo(s)');
    $globalIngles = $diccionariosJs['global'] ?? [];
    foreach ($llamadasJs as $l) {
        $grupo = (string) $l['grupo'];
        $texto = (string) $l['texto'];
        $enArchivo = $diccionariosJs['porArchivo'][$l['archivo']] ?? [];
        //En JS el inglés llega del paquete global, de lo que el archivo registra y de los grupos de PHP que pide.
        $presente = isset($globalIngles[$grupo]) && array_key_exists($texto, $globalIngles[$grupo]) || in_array($texto, $enArchivo, true) || $tieneIngles($grupo, $texto);
        $llamadas[] = ['grupo' => $grupo, 'texto' => $texto, 'donde' => substr((string) $l['archivo'], strlen($raiz) + 1) . ':' . $l['linea'], 'lado' => 'js', 'presente' => $presente];
    }

    //─── El canario ─────────────────────────────────────────────────────────────────────────────
    //Una que está (Locations: 'Cancelar' => 'Cancel') y una que no puede estar (un texto que nadie traducirá).
    $check($tieneIngles(LOCATIONS_LANG_GROUP, 'Cancelar') && !$tieneIngles(LOCATIONS_LANG_GROUP, 'zz-canario sin traducción'), 'c1 CANARIO: el lector de diccionarios ve una clave inglesa que existe y no ve una que no existe');
    $vistas = array_filter($llamadas, fn ($l) => $l['lado'] === 'php' && $l['grupo'] === LOCATIONS_LANG_GROUP && $l['texto'] === 'Sí, eliminar');
    $check(count($vistas) > 0, 'c2 CANARIO: el tokenizador encuentra una llamada conocida (__(LOCATIONS_LANG_GROUP, \'Sí, eliminar\') en Point.php)');

    //─── Las que faltan ─────────────────────────────────────────────────────────────────────────
    $faltan = [];
    foreach ($llamadas as $l) {
        $presente = $l['lado'] === 'js' ? $l['presente'] : $tieneIngles($l['grupo'], $l['texto']);
        if (!$presente) {
            $faltan[$l['grupo'] . "\0" . $l['texto']] ??= $l;
        }
    }
    //Sin grupo resoluble: cota inferior; falta solo si el texto no tiene inglés en NINGÚN grupo.
    $textosIngleses = [];
    foreach (['en'] as $idioma) {
        foreach (is_array($traducciones[$idioma] ?? null) ? $traducciones[$idioma] : [] as $claves) {
            foreach (is_array($claves) ? array_keys($claves) : [] as $clave) {
                $textosIngleses[(string) $clave] = true;
            }
        }
    }
    foreach ($sinGrupo as $l) {
        if (!isset($textosIngleses[$l['texto']])) {
            $faltan["(sin grupo)\0" . $l['texto']] ??= ['grupo' => '(sin grupo)', 'texto' => $l['texto'], 'donde' => $l['donde'], 'lado' => 'php'];
        }
    }
    $porGrupo = [];
    foreach ($faltan as $l) {
        $porGrupo[$l['grupo']] = ($porGrupo[$l['grupo']] ?? 0) + 1;
    }
    ksort($porGrupo);
    echoTerminal('   universo: ' . count($llamadas) . ' llamada(s) con texto literal (' . count(array_filter($llamadas, fn ($l) => $l['lado'] === 'js')) . ' de JS); ' . $sinResolver . ' de PHP con grupo no resoluble sin ejecutar (variable o expresión): de esas, solo falta la que no tiene inglés en NINGÚN grupo (cota inferior)');
    foreach ($porGrupo as $grupo => $cuantas) {
        echoTerminal("   faltan en {$grupo}: {$cuantas}");
    }
    if ($detalle) {
        foreach ($faltan as $l) {
            echoTerminal("      {$l['grupo']} › {$l['texto']}  ({$l['donde']})");
        }
    }
    $check(count($faltan) === 0, 'm1 DISCRIMINANTE: ninguna clave de interfaz sin inglés (trinquete en 0)', count($faltan) . ' falta(n); detalle con detalle=yes');

    //─── Letras descompuestas ───────────────────────────────────────────────────────────────────
    $nfd = [];
    foreach ($sinGrupo as $l) {
        if (!\Normalizer::isNormalized($l['texto'], \Normalizer::FORM_C)) {
            $nfd[] = $l['donde'];
        }
    }
    foreach ($llamadas as $l) {
        if (!\Normalizer::isNormalized($l['texto'], \Normalizer::FORM_C) || !\Normalizer::isNormalized($l['grupo'], \Normalizer::FORM_C)) {
            $nfd[] = $l['donde'];
        }
    }
    foreach ($traducciones as $idioma => $grupos) {
        foreach (is_array($grupos) ? $grupos : [] as $grupo => $claves) {
            foreach (is_array($claves) ? array_keys($claves) : [] as $clave) {
                if (is_string($clave) && !\Normalizer::isNormalized($clave, \Normalizer::FORM_C)) {
                    $nfd[] = "{$idioma} › {$grupo} › {$clave}";
                }
            }
        }
    }
    $check($nfd === [], 'n1 DISCRIMINANTE: ninguna clave ni ningún literal de __() con letras descompuestas (NFD)', implode(' · ', array_slice($nfd, 0, 10)));

    return $balance();

})->setDescription('Las claves de interfaz sin inglés: cada __() y _i18n() con texto literal cuyo texto no tiene entrada inglesa en los diccionarios que la aplicación carga; y ninguna clave con letras descompuestas. Trinquete en 0. Con detalle=yes, la lista.')->setEffects([CliActions::EFFECT_NONE])->register();
