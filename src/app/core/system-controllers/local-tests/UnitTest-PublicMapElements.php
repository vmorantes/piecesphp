<?php

//Las vistas del mapa público no revientan cuando el perfil llega incompleto: no pintan nada.
//`UserProfileMapper::objectToMapper()` devuelve null con una fila a medias, y eso era un 500. 263.7.

use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/public-map-elements', function ($args) {

    echoTerminal("\e[33m[TEST:PublicMapElements] Un perfil incompleto no tumba la página pública del mapa\e[39m");
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

    $vistas = [
        'tarjeta' => basepath('app/classes/ContentNavigationHub/Views/contents/map-elements/profile-person-card.php'),
        'punto' => basepath('app/classes/ContentNavigationHub/Views/contents/map-elements/profile-person-point.php'),
    ];

    //Pintar una vista aislada: se incluye con su `$element`, capturando la salida. Las vistas del
    //framework se sirven así (`render()` hace un include), y el `return` de una guarda las corta.
    $pintar = function (string $ruta, \stdClass $element): array {
        ob_start();
        $lanzo = null;
        try {
            (static function (string $__ruta, \stdClass $element): void {
                include $__ruta;
            })($ruta, $element);
        } catch (\Throwable $e) {
            $lanzo = get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 160);
        }
        return ['html' => (string) ob_get_clean(), 'error' => $lanzo];
    };

    //─── a · El canario: con un perfil COMPLETO las vistas sí pintan ─────────────────────────────
    echoTerminal('[a] El canario: con un perfil completo, las vistas pintan');
    //El elemento completo se saca de una FILA REAL de la base, con todas sus columnas: es
    //exactamente lo que la consulta del mapa entrega cuando el perfil está entero.
    $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
    if (!$check($database !== null, 'a0 hay conexión a la base')) {
        return $balance();
    }
    $tabla = UserProfileMapper::TABLE;
    $fila = $database->query("SELECT * FROM `{$tabla}` LIMIT 1")->fetch(\PDO::FETCH_OBJ);
    if (!$check($fila instanceof \stdClass, 'a0b hay al menos un perfil en la base para el caso bueno', 'sin perfiles no se puede probar el canario') || !$fila instanceof \stdClass) {
        return $balance();
    }
    //La consulta del mapa entrega el perfil UNIDO al usuario, así que la vista también lee
    //propiedades que no están en la tabla de perfiles. Se añaden para reproducir ese elemento.
    $fila->fullname = 'Zz Nombre De Prueba';
    $elementoCompleto = $fila;
    $mapperCompleto = UserProfileMapper::objectToMapper($elementoCompleto);
    if (!$check($mapperCompleto !== null, 'a1 CANARIO: el elemento completo SÍ da un mapper', 'si esto falla, los «no revienta» de abajo son gratis')) {
        return $balance();
    }
    foreach ($vistas as $nombre => $ruta) {
        $r = $pintar($ruta, $elementoCompleto);
        $check($r['error'] === null && trim($r['html']) !== '', "a2 la vista «{$nombre}» pinta con un perfil completo", (string) $r['error'] . ' · ' . mb_substr($r['html'], 0, 60));
    }

    //─── b · El caso que reventaba: un perfil INCOMPLETO ────────────────────────────────────────
    echoTerminal('');
    echoTerminal('[b] El caso que reventaba: una fila de perfil a medias');
    $elementoIncompleto = (object) ['id' => 1, 'belongsTo' => 1];
    $check(UserProfileMapper::objectToMapper($elementoIncompleto) === null, 'b1 el elemento incompleto da null, que es lo que reventaba');
    foreach ($vistas as $nombre => $ruta) {
        $r = $pintar($ruta, $elementoIncompleto);
        $check($r['error'] === null, "b2 la vista «{$nombre}» NO lanza con el perfil incompleto", (string) $r['error']);
        $check(trim($r['html']) === '', "b3 y no pinta nada a medias: el elemento se omite", mb_substr($r['html'], 0, 80));
    }

    return $balance();

})->setDescription('Las vistas del mapa público omiten el elemento cuando el perfil llega incompleto, en vez de reventar con un 500; y con un perfil completo siguen pintando.')->setEffects([CliActions::EFFECT_DATABASE])->register();
