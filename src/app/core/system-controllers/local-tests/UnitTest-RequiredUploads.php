<?php

//El servidor exige los archivos que el formulario promete obligatorios. Ver el bloque BV.

use Organizations\Controllers\OrganizationsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\Terminal\CliActions;

/**
 * Las guardas de archivo obligatorio de un archivo, con la etiqueta que cada una nombra.
 *
 * NO se mide la vecindad. La primera versión miraba los componentes que seguían a cada
 * `handlerUpload()`, y las ventanas de dos llamadas contiguas se solapaban: la de `ogImage`, que es
 * opcional, alcanzaba las guardas de las dos anteriores y salía acusada. Lo que se mide es lo que
 * importa: **cuántas guardas hay y qué campo nombra cada una**, que no depende del orden.
 *
 * @param string $codigo Código PHP completo.
 * @return array<int,string> La etiqueta de cada guarda, o `$variable` si es dinámica.
 */
function guardasDeArchivoObligatorio(string $codigo): array
{
    $tokens = @token_get_all($codigo);
    if (!is_array($tokens)) {
        return [];
    }

    $planos = [];
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)) {
                continue;
            }
            $planos[] = $token[1];
        } else {
            $planos[] = $token;
        }
    }

    $etiquetas = [];
    $total = count($planos);
    for ($i = 0; $i < $total; $i++) {
        if (mb_strpos((string) $planos[$i], 'Falta un archivo obligatorio') === false) {
            continue;
        }
        //La etiqueta es el argumento que sigue al mensaje dentro del mismo sprintf(). Se busca el
        //primer literal entre comillas o la primera variable después del mensaje.
        $etiqueta = '(no se encontró)';
        for ($j = $i + 1; $j < min($i + 14, $total); $j++) {
            $texto = (string) $planos[$j];
            if (preg_match('/^[\'"].+[\'"]$/', $texto)) {
                $etiqueta = trim($texto, "\'\"");
                break;
            }
            if (mb_substr($texto, 0, 1) === '$') {
                $etiqueta = $texto;
                break;
            }
        }
        $etiquetas[] = $etiqueta;
    }

    return $etiquetas;
}

CliActions::make('unit-tests:core/required-uploads', function ($args) {

    echoTerminal("\e[33m[TEST:RequiredUploads] El servidor exige los archivos que el formulario promete\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    //Los tres campos que el formulario marca obligatorios, con la etiqueta que ve la persona.
    $obligatorios = [
        'app/classes/Publications/Controllers/PublicationsController.php' => [
            'mainImage' => 'Imagen principal',
            'thumbImage' => 'Imagen miniatura',
        ],
        'app/classes/Documents/Controllers/DocumentsController.php' => [
            'document' => 'Documento',
        ],
    ];
    //Y los cinco que son opcionales de verdad: para ellos la cadena vacía significa «sin valor», que
    //es lo que las vistas ya leen. Si aquí apareciera una guarda, se habría roto algo legítimo.
    $opcionales = ['ogImage', 'logo', 'desktopImage', 'mobileImage', 'documentImage'];
    $todos = array_merge(array_keys($obligatorios), [
        'app/classes/Organizations/Controllers/OrganizationsController.php',
        'app/classes/PiecesPHP/BuiltIn/Banner/Controllers/BuiltInBannerController.php',
    ]);

    //──── 1. Las guardas que hay, y qué campo nombra cada una ───────────────────────────────────
    echoTerminal('[1/7] cada campo obligatorio tiene su guarda, y nombra al campo');
    $esperadas = [
        'app/classes/Publications/Controllers/PublicationsController.php' => ['Imagen miniatura', 'Imagen principal'],
        'app/classes/Documents/Controllers/DocumentsController.php' => ['Documento'],
        //En Organizations la etiqueta llega del llamador, así que la guarda nombra la variable: es
        //justo lo que permite que el alta por API no la exija.
        'app/classes/Organizations/Controllers/OrganizationsController.php' => ['$requiredRutLabel'],
        //Banner no tiene ninguna: sus dos campos son opcionales.
        'app/classes/PiecesPHP/BuiltIn/Banner/Controllers/BuiltInBannerController.php' => [],
    ];
    $ilegibles = [];
    $desviadas = [];
    $totalGuardas = 0;
    foreach ($esperadas as $ruta => $etiquetasEsperadas) {
        $codigo = @file_get_contents(basepath($ruta));
        if (!is_string($codigo) || $codigo === '') {
            $ilegibles[] = $ruta;
            continue;
        }
        $vistas = guardasDeArchivoObligatorio($codigo);
        sort($vistas);
        $totalGuardas += count($vistas);
        if ($vistas !== $etiquetasEsperadas) {
            $desviadas[] = basename($ruta) . ': ' . (count($vistas) === 0 ? 'ninguna' : implode(' + ', $vistas))
                . ' (se esperaba ' . (count($etiquetasEsperadas) === 0 ? 'ninguna' : implode(' + ', $etiquetasEsperadas)) . ')';
        }
    }
    $check(count($ilegibles) === 0, 'los cuatro controladores se pudieron leer', implode(', ', $ilegibles));
    //EL CANARIO, con umbral UNO: dice «el instrumento ve», no «hay cuatro». Un cero sería un verde
    //falso, y ya pasó: `basepath()` resuelve contra src/, no contra la raíz del repositorio.
    $check($totalGuardas >= 1, 'el censo ve las guardas que dice vigilar', "vistas: {$totalGuardas}");
    $check(count($desviadas) === 0,
        'cada controlador tiene exactamente sus guardas, con el campo nombrado',
        implode(' · ', $desviadas));
    echoTerminal(' ');

    //──── 2. Ningún campo opcional tiene guarda ─────────────────────────────────────────────────
    echoTerminal('[2/7] los cinco campos opcionales no se han vuelto obligatorios');
    //Se mide por las etiquetas: las cuatro que hay son las de los campos obligatorios, y ninguna
    //nombra a un opcional. Una guarda nueva para un opcional cambiaría esta cuenta.
    $check($totalGuardas === 4, 'hay exactamente cuatro guardas en el árbol, ni una más',
        "encontradas: {$totalGuardas}");
    echoTerminal(' ');

    //──── 3. Las cinco copias del subidor siguen siendo cinco ───────────────────────────────────
    echoTerminal('[3/7] las cinco copias de handlerUpload() y su reparto de parámetros');
    //No es trivia: la comprobación vive en la LLAMADA precisamente porque hay cinco copias distintas
    //y una de ellas declara un séptimo parámetro. Si alguien toca una firma, esto tiene que avisar.
    $declaraciones = 0;
    $conSeptimo = [];
    foreach ($todos as $ruta) {
        $codigo = (string) @file_get_contents(basepath($ruta));
        $declaraciones += preg_match_all('/function\s+handlerUpload\s*\(([^)]*)\)/', $codigo, $coincidencias);
        foreach ($coincidencias[1] ?? [] as $firma) {
            if (substr_count($firma, '$') >= 7) {
                $conSeptimo[] = basename($ruta);
            }
        }
    }
    $codigoAyudante = (string) @file_get_contents(basepath('app/classes/PiecesPHP/BuiltIn/Helpers/Controllers/GenericContentController.php'));
    $declaraciones += preg_match_all('/function\s+handlerUpload\s*\(/', $codigoAyudante);
    $check($declaraciones === 5, 'siguen siendo cinco declaraciones', "encontradas: {$declaraciones}");
    $check($conSeptimo === ['PublicationsController.php'],
        'y solo la de Publications declara un séptimo parámetro',
        'con séptimo: ' . implode(', ', $conSeptimo));
    echoTerminal(' ');

    //──── 4. El alta real de organizaciones: rechaza y NO crea ──────────────────────────────────
    echoTerminal('[4/7] createOrganization() con el RUT exigido y sin archivo: rechaza y no crea');
    $filesPrevios = $_FILES;
    $_FILES = [];       //Es el estado en el que el formulario no adjuntó nada.
    $pdo = OrganizationMapper::model()::getDb(Config::app_db('default')['db']);
    $cuenta = function () use ($pdo): int {
        $consulta = $pdo->query('SELECT COUNT(*) AS total FROM ' . OrganizationMapper::TABLE);
        $fila = $consulta === false ? null : $consulta->fetch(\PDO::FETCH_ASSOC);
        return is_array($fila) ? (int) $fila['total'] : -1;
    };
    $pdo->beginTransaction();
    try {

        $antes = $cuenta();
        $errorConEtiqueta = null;
        try {
            OrganizationsController::createOrganization([
                'name' => 'zz-prueba-required-uploads-' . uniqid(),
                'nit' => 'zz-prueba-' . uniqid(),
                'requiredRutLabel' => 'RUT',
            ]);
        } catch (\Throwable $e) {
            $errorConEtiqueta = get_class($e) . ': ' . $e->getMessage();
        }
        $despues = $cuenta();
        $check($errorConEtiqueta !== null && mb_strpos($errorConEtiqueta, 'RUT') !== false,
            'el alta se rechaza nombrando el RUT', (string) $errorConEtiqueta);
        //Lo que de verdad importa: que no quede una organización sin su RUT.
        $check($antes >= 0 && $antes === $despues, 'y NO se creó ninguna organización',
            "antes: {$antes} · después: {$despues}");
        echoTerminal(' ');

        //──── 5. Sin la clave, el alta por API se comporta igual que antes ───────────────────────
        echoTerminal('[5/7] sin `requiredRutLabel`, el alta no se queja del archivo que falta');
        $errorSinEtiqueta = null;
        try {
            OrganizationsController::createOrganization([
                'name' => 'zz-prueba-sin-etiqueta-' . uniqid(),
                'nit' => 'zz-prueba-' . uniqid(),
            ]);
        } catch (\Throwable $e) {
            $errorSinEtiqueta = get_class($e) . ': ' . $e->getMessage();
        }
        $check($errorSinEtiqueta === null
            || mb_strpos((string) $errorSinEtiqueta, 'Falta un archivo obligatorio') === false,
            'la vía de la API no exige archivos, que por ahí nunca hay',
            (string) $errorSinEtiqueta);

    } finally {
        //Todo lo de esta suite se deshace: no queda ni una fila de prueba en la base.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $_FILES = $filesPrevios;
    }
    $check($cuenta() === $antes, 'la transacción se revirtió: la tabla queda como estaba',
        "ahora: {$cuenta()} · al empezar: {$antes}");
    echoTerminal(' ');

    //──── 6. La rama de edición sigue conservando el archivo ────────────────────────────────────
    echoTerminal('[6/7] la rama de edición no se tocó: sigue guardando solo si llega algo');
    $codigoPublicaciones = (string) @file_get_contents(basepath('app/classes/Publications/Controllers/PublicationsController.php'));
    //Su guarda de edición es `if (mb_strlen($mainImage) > 0) { setLangData(...) }`, que lleva ahí
    //desde antes y es la que hace legítimo editar sin volver a subir.
    $check(mb_strpos($codigoPublicaciones, 'if (mb_strlen($mainImage) > 0) {') !== false,
        'la guarda de la edición de publicaciones sigue en pie');
    echoTerminal(' ');

    //──── 7. Y el mensaje es uno solo, con su sitio en el idioma ────────────────────────────────
    echoTerminal('[7/7] el mensaje sale de __() y nombra el campo, no la clave del formulario');
    $sinTraducir = [];
    foreach ($todos as $ruta) {
        $codigo = (string) @file_get_contents(basepath($ruta));
        if (mb_strpos($codigo, 'Falta un archivo obligatorio') === false) {
            continue;
        }
        if (!preg_match('/__\(\s*self::LANG_GROUP\s*,\s*\'Falta un archivo obligatorio: %s\.\'\s*\)/', $codigo)) {
            $sinTraducir[] = basename($ruta);
        }
    }
    $check(count($sinTraducir) === 0, 'los tres pasan el mensaje por __() con su grupo',
        implode(', ', $sinTraducir));

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los tres campos que el formulario marca obligatorios se exigen en el servidor, y los opcionales no.')->setEffects([CliActions::EFFECT_DATABASE])->register();
