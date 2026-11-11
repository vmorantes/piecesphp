<?php

//El centro por omisión de los mapas: fuera de rango no se guarda, dentro lo recibe el geovisor, y sin el permiso de las
//llaves de MapBox no se guarda. Por HTTP; repone lo que había.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/BuiltIn/
//@areas src/app/classes/PiecesPHP/Settings/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Geo\Controllers\GeoController;
use PiecesPHP\Settings\ORM\SettingsModel;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/map-default-center', function ($args) {

    echoTerminal("\e[33m[TEST:MapDefaultCenter] El centro por omisión de los mapas: rangos, permiso y lo que recibe el geovisor\e[39m");
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

    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'banco: hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $camino = function (string $url) use ($prefijoBase): string {
        $path = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $ruta, string $jwt, ?array $cuerpo = null) use ($base): array {
        $h = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ];
        if ($cuerpo !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($cuerpo);
        }
        \Terminal\TestRun::curlSetoptArray($h, $opciones);
        $body = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $nombre = GenericContentPseudoMapper::CONTENT_MAP_DEFAULT_CENTER;
    $guardar = function (string $jwt, array $centro) use ($pedir, $camino, $nombre): array {
        $r = $pedir($camino(GenericContentController::routeName('actions-edit', [], true)), $jwt, ['configName' => $nombre, $nombre => $centro]);
        $json = json_decode($r['body'], true);
        return ['status' => $r['status'], 'success' => is_array($json) && ($json['success'] ?? null) === true, 'message' => is_array($json) ? (string) ($json['message'] ?? '') : ''];
    };
    $leer = fn(): array => GenericContentPseudoMapper::mapDefaultCenter();
    //Lo que recibe el geovisor: la meta de su página, que configurations.js lee como pcsphpGlobals.mapDefaultCenter.
    $enElGeovisor = function (string $jwt) use ($pedir, $camino): ?array {
        $r = $pedir($camino(GeoController::routeName('viewer', [], true)), $jwt);
        //RETORNO-IGNORADO: sin la meta, la captura queda vacía y el caso falla.
        preg_match('#<meta name="map-default-center" value="([^"]*)">#', $r['body'], $meta);
        $centro = json_decode((string) base64_decode($meta[1] ?? ''), true);
        return is_array($centro) ? $centro : null;
    };
    $filaDelCentro = sha1(GenericContentPseudoMapper::class) . '|' . $nombre;
    $habiaFila = (new SettingsModel($filaDelCentro))->id !== null;

    $prefijo = 'zz-prueba-centro-' . bin2hex(random_bytes(3));
    $ids = [];
    $crear = function (string $sufijo, int $tipo) use ($prefijo, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = $sufijo;
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[] = (int) $u->id;
        return (int) $u->id;
    };

    $original = $leer();
    $jwtRoot = null;
    try {
        $jwtRoot = UserSessions::issueToken($crear('root', UsersModel::TYPE_USER_ROOT));
        $jwtAdmin = UserSessions::issueToken($crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL));

        echoTerminal('[r] Fuera de rango no se guarda');
        foreach ([
            'r1 una longitud de 200' => [['longitude' => 200, 'latitude' => 10, 'zoom' => 11], 'longitud'],
            'r2 una latitud de -91' => [['longitude' => -74, 'latitude' => -91, 'zoom' => 11], 'latitud'],
            'r3 un acercamiento de 23' => [['longitude' => -74, 'latitude' => 10, 'zoom' => 23], 'acercamiento'],
            'r4 un texto como longitud' => [['longitude' => 'abc', 'latitude' => 10, 'zoom' => 11], 'longitud'],
            'r5 sin acercamiento' => [['longitude' => -74, 'latitude' => 10], 'acercamiento'],
        ] as $caso => [$centro, $palabra]) {
            $r = $guardar($jwtRoot, $centro);
            $check(!$r['success'] && str_contains(mb_strtolower($r['message']), $palabra) && $leer() === $original, "{$caso}: el error lo dice («{$palabra}») y no guarda", "HTTP {$r['status']}, «{$r['message']}», guardado " . json_encode($leer()));
        }
        echoTerminal(' ');

        echoTerminal('[g] Dentro de rango, lo recibe el geovisor');
        $nuevo = ['longitude' => -75.5658, 'latitude' => 6.2442, 'zoom' => 12.5];
        $r = $guardar($jwtRoot, $nuevo);
        $check($r['success'] && $leer() === $nuevo, 'g1 root guarda un centro dentro de rango', "HTTP {$r['status']}, «{$r['message']}», guardado " . json_encode($leer()));
        $recibido = $enElGeovisor($jwtRoot);
        $check($recibido === $nuevo, 'g2 el geovisor lo recibe en su página (meta map-default-center)', (string) json_encode($recibido));
        $check($leer() !== $original, 'g3 CANARIO: el centro de la prueba no es el de antes', (string) json_encode($original));
        echoTerminal(' ');

        echoTerminal('[p] El permiso es el de las llaves de MapBox');
        $r = $guardar($jwtAdmin, ['longitude' => 1, 'latitude' => 1, 'zoom' => 1]);
        $check(!$r['success'] && $leer() === $nuevo, 'p1 un administrador general no lo cambia', "HTTP {$r['status']}, guardado " . json_encode($leer()));
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if (!$habiaFila) {
            $database->prepare('DELETE FROM `pcsphp_app_config` WHERE name = ?')->execute([$filaDelCentro]);
        } elseif ($jwtRoot !== null && $leer() !== $original) {
            $guardar($jwtRoot, $original);
        }
        $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
        foreach ($ids as $id) {
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare("DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?")->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
        }
        $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote("{$prefijo}%"))->fetchColumn();
        $filaAhora = (new SettingsModel($filaDelCentro))->id !== null;
        $check($leer() === $original && $filaAhora === $habiaFila && $quedan === 0, 'z1 el centro vuelve a como estaba y no queda ningún usuario de la prueba', 'centro ' . json_encode($leer()) . ", fila " . json_encode($filaAhora) . ", usuarios {$quedan}");
    }

    return $balance();

})->setDescription('El centro por omisión de los mapas: fuera de rango (longitud, latitud, acercamiento) no se guarda y responde por qué; dentro de rango, el geovisor lo recibe en su página; sin el permiso de las llaves de MapBox no se guarda. Por HTTP; repone lo que había.')->setEffects([CliActions::EFFECT_DATABASE])->register();
