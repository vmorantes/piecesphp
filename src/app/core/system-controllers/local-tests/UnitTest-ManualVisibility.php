<?php

//Quién ve cada capítulo del manual (ADR 0072, JDY) y cada sección o subsección marcada con `<!-- ve: … -->` (H182, H198).
//Sin red: índice y capítulos SINTÉTICOS, generados por bin/manual en src/app/cache/, y el usuario actual en memoria.
//@areas src/app/classes/MySpace/

use MySpace\Controllers\MySpaceController;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Roles;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;

$cliTaskName = 'unit-tests';
$cliTaskFlag = 'core/manual-visibility';
$cliTaskDescription = 'El manual: root ve todo, un general ve lo de todos y no lo administrativo, `ve` separa un capítulo mixto, una sección o subsección marcada se oculta a quien no puede abrir su pantalla, cada `##` lleva su id y un capítulo largo sale en plegables';

CliActions::make("{$cliTaskName}:{$cliTaskFlag}", function ($args) {

    echoTerminal('[TEST:ManualVisibility] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;

    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
        }
        if ($detail !== null) {
            echoTerminal("      - {$detail}");
        }
        return $condition;
    };

    //La administración que el general no puede, y una ruta que sí puede (la edición de su organización).
    $soloAdministracion = 'organizations-admin-list';
    $laPuedenTodos = 'organizations-admin-forms-edit';
    $lang = Config::get_lang();
    $capitulo = fn(string $slug, $rutas, ?array $ve = null): array => ['slug' => $slug, 'titulo' => $slug, 'rutas' => $rutas]
         + ($ve !== null ? ['ve' => $ve] : []) + ['idioma' => $lang, 'origen' => 'framework', 'fecha' => '2026-11-04'];
    $indice = [
        $capitulo('zz-10-todos', 'todos'),
        $capitulo('zz-20-administracion', [$soloAdministracion]),
        $capitulo('zz-30-mixto-con-ve', [$soloAdministracion, $laPuedenTodos], [$soloAdministracion]),
    ];
    $sinVe = [$capitulo('zz-30-mixto-sin-ve', [$soloAdministracion, $laPuedenTodos])];

    //──── 0. Lo que la prueba da por hecho ────────────────────────────────────────────────────
    echoTerminal('[0/4] Las premisas: los permisos de las rutas y los usuarios de la base');
    $general = UsersModel::TYPE_USER_GENERAL;
    $check(!Roles::hasPermissions($soloAdministracion, $general) && Roles::hasPermissions($laPuedenTodos, $general), "p1 el general no puede {$soloAdministracion} y sí {$laPuedenTodos}");
    $check(Roles::hasPermissions($soloAdministracion, UsersModel::TYPE_USER_ROOT), "p2 root puede {$soloAdministracion}");
    $database = (new BaseModel())->getDatabase();
    $tabla = UsersModel::TABLE;
    $unoDe = function (int $tipo) use ($database, $tabla): int {
        $id = $database !== null ? $database->query("SELECT id FROM `{$tabla}` WHERE type = {$tipo} AND status = " . UsersModel::STATUS_USER_ACTIVE . ' ORDER BY id LIMIT 1')->fetchColumn() : false;
        return $id === false ? 0 : (int) $id;
    };
    $idRoot = $unoDe(UsersModel::TYPE_USER_ROOT);
    $idGeneral = $unoDe($general);
    if (!$check($idRoot > 0 && $idGeneral > 0, 'p3 hay un root y un general activos en la base (solo se leen)', "root {$idRoot}, general {$idGeneral}")) {
        return ['success' => false, 'message' => 'Sin un root y un general activos la suite no puede mirar nada.'];
    }
    echoTerminal(' ');

    //──── 1. Quién ve qué ─────────────────────────────────────────────────────────────────────
    echoTerminal('[1/4] Lo que devuelve manualChapters() con el índice sintético');
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');
    $previoPendientes = get_config('manual_for_pending');
    $comoUsuario = function (int $id, ?array $indice): array {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
        return array_column(MySpaceController::manualChapters($indice), 'slug');
    };
    try {
        //Una cuenta sin aprobar no ve nada con manual_for_pending en false: aquí no se mide eso.
        set_config('manual_for_pending', true);

        $root = $comoUsuario($idRoot, $indice);
        $check($root === ['zz-10-todos', 'zz-20-administracion', 'zz-30-mixto-con-ve'], 'v1 root ve los tres', json_encode($root) ?: '');

        $vistos = $comoUsuario($idGeneral, $indice);
        $check(in_array('zz-10-todos', $vistos, true), 'v2 el general ve el capítulo de todos', json_encode($vistos) ?: '');
        $check(!in_array('zz-20-administracion', $vistos, true), 'v3 DISCRIMINANTE: el general NO ve el de administración (cae si se quita el filtro de permiso)');
        $check(!in_array('zz-30-mixto-con-ve', $vistos, true), 'v4 DISCRIMINANTE: el general NO ve el mixto con `ve` (cae si se ignora `ve`)');

        $mixto = $comoUsuario($idGeneral, $sinVe);
        $check($mixto === ['zz-30-mixto-sin-ve'], 'v5 sin `ve`, el mixto lo ve el general: deciden todas sus rutas', json_encode($mixto) ?: '');

        $real = $comoUsuario($idRoot, null);
        $check(count($real) > 3 && array_filter($real, fn(string $slug): bool => str_starts_with($slug, 'zz-')) === [], 'v6 sin índice, el real: el sintético no se queda pegado', count($real) . ' capítulo(s)');
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        set_config('manual_for_pending', $previoPendientes);
    }
    echoTerminal(' ');

    //──── 2. Las secciones ────────────────────────────────────────────────────────────────────
    echoTerminal('[2/4] Las secciones marcadas, con la envoltura que escribe bin/manual (H182)');
    //La envoltura sale de lo GENERADO: si bin/manual deja de escribirla, el capítulo sintético se pinta sin condición y
    //los DISCRIMINANTES caen.
    $abre = null;
    $vistas = glob(__DIR__ . '/../../../classes/MySpace/Views/manual/*/*.php') ?: [];
    foreach ($vistas as $vista) {
        if (preg_match('/^(<\?php if \(.*manualSectionVisible\()\[[^\]]*\](\)\): \?>)$/m', (string) file_get_contents($vista), $envoltura) === 1) {
            $abre = [$envoltura[1], $envoltura[2]];
            break;
        }
    }
    $check($abre !== null, 's0 CANARIO: lo generado por bin/manual trae secciones envueltas', count($vistas) . ' vista(s) miradas');
    $noRegistrada = 'zz-ruta-que-no-esta-registrada';
    $seccion = function (string $marca, ?array $rutas) use ($abre): string {
        $html = "<h2 class=\"ui header\">{$marca}</h2>\n<p>{$marca}</p>";
        if ($rutas === null || $abre === null) {
            return $html;
        }
        $lista = '[' . implode(', ', array_map(fn(string $r): string => "'" . $r . "'", $rutas)) . ']';
        return "{$abre[0]}{$lista}{$abre[1]}\n{$html}\n<?php endif; ?>";
    };
    $sintetico = '<p>zz-intro</p>' . "\n" . $seccion('zz-seccion-administracion', [$soloAdministracion]) . "\n"
        . $seccion('zz-seccion-no-registrada', [$noRegistrada]) . "\n" . $seccion('zz-seccion-sin-marcador', null);
    $pinta = function (int $id) use ($sintetico): string {
        set_config('current_user', (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
        ob_start();
        eval('?>' . $sintetico);
        return (string) ob_get_clean();
    };
    try {
        $check(!array_key_exists($noRegistrada, get_routes()), "s1 CANARIO: «{$noRegistrada}» no está registrada");
        $deRoot = $pinta($idRoot);
        $deGeneral = $pinta($idGeneral);
        $check(str_contains($deRoot, 'zz-seccion-administracion'), 's2 root ve la sección de una pantalla que puede abrir');
        $check(!str_contains($deGeneral, 'zz-seccion-administracion'), 's3 DISCRIMINANTE: el general NO ve la sección de una pantalla que no puede abrir');
        $check(!str_contains($deRoot, 'zz-seccion-no-registrada') && !str_contains($deGeneral, 'zz-seccion-no-registrada'),
            's4 DISCRIMINANTE: la sección de una ruta no registrada no la ve nadie, tampoco root');
        $check(str_contains($deRoot, 'zz-seccion-sin-marcador') && str_contains($deGeneral, 'zz-seccion-sin-marcador') && str_contains($deGeneral, 'zz-intro'),
            's5 la sección sin marcador la ven los dos, como el texto de antes');
        set_config('current_user', null);
        set_config('pcsphp_current_user_stored', null);
        $check(!MySpaceController::manualSectionVisible([$soloAdministracion, $laPuedenTodos]), 's6 sin usuario, ninguna sección marcada se ve');
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
    }
    echoTerminal(' ');

    //──── 3. Subsecciones, ids y plegables, con bin/manual ───────────────────────────────────────
    echoTerminal('[3/4] Lo que genera bin/manual con fuentes sintéticas: subsecciones, ids, índice, plegables y errores (H198)');
    $carpeta = basepath('app/cache/zz-manual-' . getmypid() . '-' . bin2hex(random_bytes(3)));
    $manual = dirname(rtrim(basepath(), '/')) . '/bin/manual';
    $genera = function (string $fuentes, bool $comprobar) use ($carpeta, $manual): array {
        $entorno = array_merge(getenv(), ['PCSPHP_MANUAL_FUENTES' => $fuentes, 'PCSPHP_MANUAL_VISTAS' => "{$carpeta}/vistas", 'PCSPHP_MANUAL_IMAGENES' => "{$carpeta}/imagenes"]);
        $proceso = proc_open(array_merge([$manual], $comprobar ? ['--check'] : []), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $tubos, null, $entorno);
        if (!is_resource($proceso)) {
            return [-1, ''];
        }
        $salida = (string) stream_get_contents($tubos[1]) . (string) stream_get_contents($tubos[2]);
        return [proc_close($proceso), $salida];
    };
    $capituloMd = fn(string $cuerpo): string => "---\ntitulo: zz capítulo\nrutas: [{$soloAdministracion}, {$laPuedenTodos}]\n---\n# zz capítulo\n\n{$cuerpo}\n";
    $fuentes = [
        'buenas' => [
            'zz-50-subsecciones' => $capituloMd("Intro zz-intro.\n\n## Una sección\nTexto zz-seccion-abierta.\n\n### Sub marcada\n<!-- ve: {$soloAdministracion} -->\nTexto zz-sub-administracion.\n\n### Sub libre\nTexto zz-sub-libre.\n\n## Una sección\n<!-- ve: {$laPuedenTodos} -->\nTexto zz-seccion-dos.\n\n### Dentro\n<!-- ve: {$soloAdministracion} -->\nTexto zz-dentro-de-una-que-ve.\n\n## Administración\n<!-- ve: {$soloAdministracion} -->\nTexto zz-seccion-administracion.\n\n### Para todos\n<!-- ve: {$laPuedenTodos} -->\nTexto zz-dentro-de-una-que-no-ve."),
            //Un `###` marcado antes del primer `##` y el primer `##` oculto al general: va a la introducción, y abre el JS.
            'zz-60-plegables' => $capituloMd("### Previa\n<!-- ve: {$soloAdministracion} -->\nTexto zz-previa.\n\n## Parte 1\n<!-- ve: {$soloAdministracion} -->\nTexto zz-parte-1.\n\n" . implode("\n\n", array_map(fn(int $n): string => "## Parte {$n}\nTexto zz-parte-{$n}.", range(2, 5)))),
        ],
        'tras-cuatro' => ['zz-70-mal' => $capituloMd("## Una\n\n#### Cuatro\n<!-- ve: {$soloAdministracion} -->\nTexto.")],
        'otra-linea' => ['zz-70-mal' => $capituloMd("## Una\nTexto.\n<!-- ve: {$soloAdministracion} -->")],
    ];
    $escritos = [];
    foreach ($fuentes as $grupo => $capitulos) {
        $directorio = "{$carpeta}/{$grupo}/framework/es";
        $escritos[] = mkdir($directorio, 0775, true);
        foreach ($capitulos as $slug => $texto) {
            $escritos[] = file_put_contents("{$directorio}/{$slug}.md", $texto) !== false;
        }
    }
    try {
        $check(!in_array(false, $escritos, true), 'g0 las fuentes sintéticas se escribieron en src/app/cache/');
        [$codigo, $salida] = $genera("{$carpeta}/buenas", false);
        $check($codigo === 0, 'g1 bin/manual genera los capítulos sintéticos', trim($salida));
        $vista = (string) @file_get_contents("{$carpeta}/vistas/es/zz-50-subsecciones.php");
        $indiceGenerado = is_file("{$carpeta}/vistas/index.php") ? require "{$carpeta}/vistas/index.php" : [];
        $secciones = [];
        foreach (is_array($indiceGenerado) ? $indiceGenerado : [] as $entrada) {
            if (is_array($entrada) && ($entrada['slug'] ?? null) === 'zz-50-subsecciones') {
                $secciones = $entrada['secciones'] ?? [];
            }
        }
        $check(str_contains($vista, 'id="una-seccion"') && str_contains($vista, 'id="una-seccion-2"')
            && $secciones === [['id' => 'una-seccion', 'titulo' => 'Una sección'], ['id' => 'una-seccion-2', 'titulo' => 'Una sección', 've' => [$laPuedenTodos]], ['id' => 'administracion', 'titulo' => 'Administración', 've' => [$soloAdministracion]]],
            'i1 cada `##` lleva su id, único en el capítulo, y el índice los lista con su `ve`', json_encode($secciones, JSON_UNESCAPED_UNICODE) ?: '');
        $pintaVista = function (int $id) use ($vista): string {
            set_config('current_user', (object) ['id' => $id]);
            set_config('pcsphp_current_user_stored', null);
            ob_start();
            eval('?>' . $vista);
            return (string) ob_get_clean();
        };
        $deRoot = $pintaVista($idRoot);
        $deGeneral = $pintaVista($idGeneral);
        $check(str_contains($deRoot, 'zz-sub-administracion'), 'u1 root ve la subsección de una pantalla que puede abrir');
        $check(!str_contains($deGeneral, 'zz-sub-administracion'), 'u2 DISCRIMINANTE: el general NO ve la subsección marcada (cae si bin/manual no la envuelve)');
        $check(str_contains($deGeneral, 'zz-sub-libre') && str_contains($deGeneral, 'zz-seccion-abierta') && str_contains($deGeneral, 'zz-seccion-dos'),
            'u3 el general ve la subsección libre, la sección sin marcar y la que puede abrir');
        $check(str_contains($deRoot, 'zz-dentro-de-una-que-ve') && !str_contains($deGeneral, 'zz-dentro-de-una-que-ve'),
            'u4 DISCRIMINANTE: dentro de una sección que ve, el general NO ve la subsección que no puede abrir');
        $check(str_contains($deRoot, 'zz-dentro-de-una-que-no-ve') && !str_contains($deGeneral, 'zz-dentro-de-una-que-no-ve') && !str_contains($deGeneral, 'zz-seccion-administracion'),
            'u5 DISCRIMINANTE: dentro de una sección que no ve, tampoco la subsección que sí podría (rigen las dos)');
        //El índice del capítulo, con el filtro del controlador: lo que no ve en el cuerpo, tampoco en el índice.
        $comoSecciones = array_map(fn(array $s): array => ['id' => $s['id'], 'titulo' => $s['titulo'], 've' => $s['ve'] ?? []], is_array($secciones) ? $secciones : []);
        set_config('current_user', (object) ['id' => $idGeneral]);
        set_config('pcsphp_current_user_stored', null);
        $indiceGeneral = array_column(MySpaceController::manualVisibleSections($comoSecciones), 'id');
        set_config('current_user', (object) ['id' => $idRoot]);
        set_config('pcsphp_current_user_stored', null);
        $indiceRoot = array_column(MySpaceController::manualVisibleSections($comoSecciones), 'id');
        $check($indiceGeneral === ['una-seccion', 'una-seccion-2'] && $indiceRoot === ['una-seccion', 'una-seccion-2', 'administracion'],
            'x1 DISCRIMINANTE: el índice del capítulo no lista al general la sección que no ve; a root, sí', json_encode([$indiceGeneral, $indiceRoot]) ?: '');
        $plegable = (string) @file_get_contents("{$carpeta}/vistas/es/zz-60-plegables.php");
        $check(str_contains($plegable, 'data-section-accordion') && substr_count($plegable, 'class="title"') === 4 && substr_count($plegable, 'class="active title"') === 1
            && str_contains($plegable, '<h2 class="active title" id="parte-1">') && !str_contains($vista, 'data-section-accordion'),
            'a1 con cinco `##`, plegables con el primero abierto; con dos, títulos');
        $plegableGeneral = (function () use ($plegable, $idGeneral): string {
            set_config('current_user', (object) ['id' => $idGeneral]);
            set_config('pcsphp_current_user_stored', null);
            ob_start();
            eval('?>' . $plegable);
            return (string) ob_get_clean();
        })();
        $check(!str_contains($plegableGeneral, 'zz-parte-1') && !str_contains($plegableGeneral, 'id="parte-1"') && !str_contains($plegableGeneral, 'zz-previa')
            && str_contains($plegableGeneral, 'zz-parte-2') && str_contains($plegable, 'zz-previa'),
            'a2 DISCRIMINANTE: un plegable oculto no está en el HTML, tampoco un `###` marcado antes del primer `##`');
        [$codigo, $salida] = $genera("{$carpeta}/tras-cuatro", true);
        $check($codigo === 1 && str_contains($salida, 'no está en la línea siguiente a un `##` o un `###`'), 'e1 un marcador tras un `####` es un error', trim($salida));
        [$codigo, $salida] = $genera("{$carpeta}/otra-linea", true);
        $check($codigo === 1 && str_contains($salida, 'no está en la línea siguiente a un `##` o un `###`'), 'e2 un marcador en otra línea es un error', trim($salida));
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        //Solo lo que cuelga de la carpeta propia.
        $quedan = 0;
        if (is_dir($carpeta) && str_contains($carpeta, '/app/cache/zz-manual-')) {
            $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($carpeta, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($items as $item) {
                if ($item instanceof \SplFileInfo) {
                    $quedan += ($item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname())) ? 0 : 1;
                }
            }
            $quedan += rmdir($carpeta) ? 0 : 1;
        }
        $check($quedan === 0 && !is_dir($carpeta), 'z1 las fuentes y lo generado se borraron', "{$quedan} sin borrar");
    }
    echoTerminal(' ');

    //──── Balance ───────────────────────────────────────────────────────────────────────────────
    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:ManualVisibility] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0,
        'message' => $failed === 0
            ? "Cada capítulo del manual lo ve quien debe ({$passed} comprobaciones)."
            : "{$failed} comprobaciones fallaron.",
    ];

})->setDescription($cliTaskDescription)->setEffects([CliActions::EFFECT_FILES])->register();
