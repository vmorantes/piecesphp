<?php

//Lo que una pantalla importa antes de la cabecera llega a su pie (H261): la campana de la barra no abre lock_assets, y
//el Gestor de archivos y el alta de documentos traen su JS. Por HTTP, con un usuario zz-agente- propio que se retira.
//@areas nucleo

use Documents\Controllers\DocumentsController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\FileManager\Controllers\FileManagerController;
use PiecesPHP\Notifications\Controllers\NotificationsController;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/page-imported-assets', function ($args) {

    echoTerminal("\e[33m[TEST:PageImportedAssets] Lo importado antes de la cabecera llega al pie de la pantalla\e[39m");
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

    //──── [c] La campana, en proceso ──────────────────────────────────────────────────────
    echoTerminal('[c] listMarkup() deja lock_assets como lo encontró');
    $previo = get_config('lock_assets');
    try {
        set_config('lock_assets', true);
        //RETORNO-IGNORADO: lo que se mira es el candado después de pintar, no el marcado.
        NotificationsController::listMarkup('zz-campana-prueba', true);
        $check(get_config('lock_assets') === true, 'c1 DISCRIMINANTE: con el candado puesto, pintar la campana no lo abre');
        set_config('lock_assets', false);
        //RETORNO-IGNORADO: igual que arriba.
        NotificationsController::listMarkup('zz-campana-prueba', true);
        $check(get_config('lock_assets') === false, 'c2 y con el candado abierto, lo deja abierto');
    } finally {
        set_config('lock_assets', $previo);
    }
    echoTerminal(' ');

    //──── Por HTTP ───────────────────────────────────────────────────────────────────────
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'banco: hay una base HTTP para pedir', $base)) {
        return $balance();
    }
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $relativa = function (string $url) use ($prefijoBase): string {
        $ruta = (string) parse_url($url, \PHP_URL_PATH);
        return $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    };
    $pedir = function (string $camino, string $jwt) use ($base): array {
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($camino, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}"],
        ]);
        $cuerpo = curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE), 'body' => is_string($cuerpo) ? $cuerpo : ''];
    };
    //Los <script src> de la página, sin la cadena de consulta: lo que el navegador va a cargar.
    $scripts = function (string $cuerpo): array {
        //RETORNO-IGNORADO: lo que cuenta es $m; sin ningún script, la lista vacía hace caer el caso.
        preg_match_all('/<script\b[^>]*\bsrc=(["\'])([^"\']+)\1/i', $cuerpo, $m);
        return array_map(fn (string $src): string => (string) preg_replace('/\?.*$/', '', $src), $m[2]);
    };
    $trae = fn (array $lista, string $archivo): bool => count(array_filter($lista, fn (string $s): bool => str_ends_with($s, $archivo))) > 0;

    $usuario = 'zz-agente-recursos-' . bin2hex(random_bytes(3));
    $id = 0;
    try {
        $u = new UsersModel();
        $u->username = $usuario;
        $u->email = "{$usuario}@example.invalid";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Recursos';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_ROOT;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $id = (int) $u->id;
        $jwt = UserSessions::issueToken($id);

        echoTerminal('[g] El Gestor de archivos y su vista para el editor');
        $r = $pedir($relativa(FileManagerController::routeName('filemanager', [], true)), $jwt);
        $lista = $scripts($r['body']);
        $check($r['status'] === 200 && $trae($lista, 'elfinder.full.js') && $trae($lista, 'jquery-ui.min.js') && $trae($lista, 'file-manager.js'),
            'g1 DISCRIMINANTE: admin/filemanager/ trae elfinder.full.js y jquery-ui.min.js, además de file-manager.js', "HTTP {$r['status']}, " . json_encode(array_values(array_filter($lista, fn ($s) => preg_match('/elfinder|jquery-ui|file-manager/', $s) === 1))));
        $r = $pedir($relativa(FileManagerController::routeName('filemanager-rich-editor', [], true)), $jwt);
        $lista = $scripts($r['body']);
        $check($r['status'] === 200 && $trae($lista, 'elfinder.full.js') && $trae($lista, 'jquery-ui.min.js'),
            'g2 CANARIO: la vista para el editor (sin barra) los trae', "HTTP {$r['status']}");
        //P330: elFinder 2.1.70 usa la API de botón de la 1.11, que la 1.14 solo acepta con jQuery.uiBackCompat puesto ANTES.
        $posicion = function (array $lista, string $nombre): int {
            foreach (array_values($lista) as $i => $src) {
                if (str_contains($src, $nombre)) {
                    return $i;
                }
            }
            return -1;
        };
        $ordenes = [];
        foreach (['filemanager', 'filemanager-rich-editor'] as $pantalla) {
            $orden = $scripts($pedir($relativa(FileManagerController::routeName($pantalla, [], true)), $jwt)['body']);
            $ordenes[$pantalla] = [$posicion($orden, 'jquery-ui-backcompat.js'), $posicion($orden, 'jquery-ui.min.js'), $posicion($orden, 'jquery-ui-fomantic-restore.js')];
        }
        $check(count(array_filter($ordenes, fn ($o) => $o[0] >= 0 && $o[0] < $o[1])) === 2, 'g3 DISCRIMINANTE: en las dos pantallas, jquery-ui-backcompat.js va delante de jquery-ui.min.js', (string) json_encode($ordenes));
        //jQuery UI completo trae un accordion que pisa el de Fomantic: lo repone el archivo de DETRÁS.
        $check(count(array_filter($ordenes, fn ($o) => $o[1] >= 0 && $o[2] > $o[1])) === 2, 'g5 DISCRIMINANTE: en las dos pantallas, jquery-ui-fomantic-restore.js va detrás de jquery-ui.min.js', (string) json_encode($ordenes));
        $cabecera = (string) file_get_contents(basepath('statics/plugins/jquery-ui/jquery-ui.min.js'), false, null, 0, 40);
        $check(str_starts_with($cabecera, '/*! jQuery UI - v1.14.2'), 'g4 el jquery-ui.min.js servido es la 1.14.2 (la que pide elFinder 2.1.70)', $cabecera);
        echoTerminal(' ');

        echoTerminal('[d] El alta de documentos');
        $r = $pedir($relativa(DocumentsController::routeName('forms-add', [], true)), $jwt);
        $lista = $scripts($r['body']);
        $check($r['status'] === 200 && $trae($lista, 'SimpleUploadPlaceholder.js') && $trae($lista, 'add-form.js'),
            'd1 DISCRIMINANTE: el alta de documentos trae SimpleUploadPlaceholder.js, que su add-form.js usa al cargar', "HTTP {$r['status']}");
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if ($id > 0) {
            $database = (new BaseModel())->getDatabase();
            $referencias = $database->query("SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = '" . UsersModel::TABLE . "' AND REFERENCED_COLUMN_NAME = 'id'")->fetchAll(\PDO::FETCH_NUM);
            foreach ($referencias as [$tabla, $columna]) {
                $database->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` = ?")->execute([$id]);
            }
            $database->prepare('DELETE FROM `system_approvals_elements` WHERE referenceTable = ? AND referenceValue = ?')->execute([UsersModel::TABLE, (string) $id]);
            $database->prepare('DELETE FROM `' . UsersModel::TABLE . '` WHERE id = ?')->execute([$id]);
            $quedan = (int) $database->query('SELECT COUNT(*) FROM `' . UsersModel::TABLE . '` WHERE username LIKE ' . $database->quote('zz-agente-recursos-%'))->fetchColumn();
            $check($quedan === 0, 'z1 no queda el usuario de la prueba', "quedan {$quedan}");
        }
    }

    return $balance();

})->setDescription('Lo que una pantalla importa antes de la cabecera llega a su pie (H261): pintar la campana no abre lock_assets, y el Gestor de archivos y el alta de documentos traen su JS. Por HTTP; crea un usuario zz-agente- y lo retira.')->setEffects([CliActions::EFFECT_DATABASE])->register();
