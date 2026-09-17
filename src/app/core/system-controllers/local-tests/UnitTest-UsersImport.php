<?php

//Importación de usuarios sobre DataTransfer: todo o nada en transacción, prioridad de tipos y contraseñas que solo salen una vez.
//Escribe usuarios zz-prueba-imp-* y sus perfiles, y los borra en el finally: db-backup antes.

use App\Model\UsersModel;
use DataImportExportUtility\Definitions\UsersImportDefinition;
use PiecesPHP\Core\DataTransfer\Import\ImportReport;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\ArrayRowSource;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/users-import', function ($args) {

    echoTerminal("\e[33m[TEST:UsersImport] Importador de usuarios sobre DataTransfer\e[39m");
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

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-prueba-imp-{$marca}";
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');

    $usuariosDelPrefijo = function (string $patron): array {
        $modelo = UsersModel::model();
        $modelo->resetAll();
        $modelo->select()->where(new WhereSegment([WhereItem::like('username', "{$patron}%")]))->execute();
        return array_values((array) $modelo->result());
    };
    $como = function (?int $id): void {
        set_config('current_user', $id === null ? null : (object) ['id' => $id]);
        set_config('pcsphp_current_user_stored', null);
    };
    $correr = function (array $headers, array $rows): ImportReport {
        return (new ImportRunner())->run(new UsersImportDefinition(), new ArrayRowSource($headers, $rows));
    };
    $cabeceras = ['Usuario', 'correo electrónico', 'nombre', 'Primer apellido', 'Contraseña', 'id', 'zz_desconocida'];
    $fila = fn(string $sufijo, ?string $clave = null, array $extra = []) => array_merge(["{$prefijo}-{$sufijo}", "{$prefijo}-{$sufijo}@example.com", 'Zz', 'Prueba', $clave, '999999', 'x'], $extra);

    try {
        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(new WhereSegment([new WhereItem('type', WhereItem::EQUAL_OPERATOR, UsersModel::TYPE_USER_ROOT)]))->execute();
        $filasRoot = (array) $root->result();
        $idRoot = isset($filasRoot[0]->id) ? (int) $filasRoot[0]->id : null;
        $check($idRoot !== null, 'banco: hay un usuario root para importar como él');
        $como($idRoot);

        //─── 1/6 + 2/6 · Cabeceras y archivo válido ─────────────────────────────────────────────────────
        echoTerminal('[1-2/6] Cabeceras por etiqueta y alias; archivo válido de tres filas');
        $antesLogs = [];
        foreach (['error.plain.log', 'error.log.json', 'error.unique.message.log'] as $log) {
            $ruta = basepath("app/logs/{$log}");
            $antesLogs[$log] = is_file($ruta) ? (int) filesize($ruta) : 0;
        }
        $informe = $correr($cabeceras, [$fila('a', 'zz-Clave-Dada-1'), $fila('b'), $fila('c')]);
        $creados = $usuariosDelPrefijo($prefijo);
        $json = (string) json_encode($informe, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $check($informe->persisted() && count($creados) === 3, 'p1-p2 persisted y 3 usuarios creados (id y columna desconocida ignoradas)', $json);
        $porUsuario = [];
        foreach ($creados as $creado) {
            $porUsuario[$creado->username] = $creado;
        }
        $check(isset($porUsuario["{$prefijo}-a"]) && password_verify('zz-Clave-Dada-1', (string) $porUsuario["{$prefijo}-a"]->password) && (int) $porUsuario["{$prefijo}-a"]->id !== 999999, 'p2 la contraseña dada se guarda como hash verificable y el id del archivo no se usa');
        $conPerfil = count(array_filter($creados, fn($u) => UserProfileMapper::getProfile((int) $u->id) !== null));
        $check($conPerfil === 3 && count(array_filter($creados, fn($u) => (int) $u->type === UsersModel::TYPE_USER_GENERAL && (int) $u->organization === \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL)) === 3, 'p2 los 3 tienen perfil, tipo general y la organización global (root no tiene organización)');
        $artefacto = $informe->artifacts();
        $fichas = $artefacto !== null ? $artefacto->content() : '';
        $check($artefacto !== null && substr_count($fichas, 'class="card"') === 2 && str_contains($fichas, "{$prefijo}-b") && str_contains($fichas, "{$prefijo}-c") && !str_contains($fichas, "{$prefijo}-a<"), 'p2 el artefacto trae 2 fichas: las de b y c, que no traían contraseña', $artefacto !== null ? $artefacto->filename() : 'sin artefacto');
        $generadasValidas = 0;
        $generadas = [];
        foreach (['b', 'c'] as $s) {
            $usuario = "{$prefijo}-{$s}";
            if (preg_match('#<dd>' . preg_quote($usuario, '#') . '</dd><dt>[^<]*</dt><dd>([^<]+)</dd>#u', $fichas, $m) === 1) {
                $clave = htmlspecialchars_decode($m[1], ENT_QUOTES);
                $generadas[] = $clave;
                if (isset($porUsuario[$usuario]) && password_verify($clave, (string) $porUsuario[$usuario]->password)) {
                    $generadasValidas++;
                }
            }
        }
        $check($generadasValidas === 2, 'p2 cada contraseña de las fichas la acepta password_verify contra el hash guardado', "válidas {$generadasValidas}");
        $check(count($generadas) === 2 && count(array_filter($generadas, fn($c) => str_contains($json, $c))) === 0 && !str_contains($json, 'zz-Clave-Dada-1') && !str_contains($json, 'credenciales'), 'p2 el JSON del informe no contiene ninguna contraseña ni el artefacto');
        echoTerminal(' ');

        //─── 5/6 · Logs ─────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[5/6] Las contraseñas generadas no llegan a los logs');
        $enLogs = [];
        foreach ($antesLogs as $log => $tamano) {
            $ruta = basepath("app/logs/{$log}");
            $nuevo = is_file($ruta) ? (string) file_get_contents($ruta, false, null, $tamano) : '';
            foreach ($generadas as $clave) {
                if ($clave !== '' && str_contains($nuevo, $clave)) {
                    $enLogs[] = $log;
                }
            }
        }
        $check(count($generadas) === 2 && count($enLogs) === 0, 'p5 ninguna contraseña generada en error.plain.log, error.log.json ni error.unique.message.log', implode(', ', $enLogs));
        echoTerminal(' ');

        //─── 3/6 · Todo o nada ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[3/6] Todo o nada: la tercera fila la rechaza la base');
        //Un username de 280 caracteres NO falla (medido: el INSERT pasa). Lo que la base sí rechaza es la clave ajena de
        //organization; la validación la pararía antes, así que se llama a persist() directamente con filas ya parseadas.
        $parseada = fn(int $pos, string $s, ?string $org) => new \PiecesPHP\Core\DataTransfer\Import\ParsedRow($pos, ['username' => "{$prefijo}-{$s}", 'email' => "{$prefijo}-{$s}@example.com", 'firstname' => 'Zz', 'secondname' => null, 'first_lastname' => 'Prueba', 'second_lastname' => null, 'password' => null, 'type' => null, 'organization' => $org]);
        $mensaje = null;
        try {
            (new UsersImportDefinition())->persist([$parseada(1, 't1', null), $parseada(2, 't2', null), $parseada(3, 't3', '987654321')]);
        } catch (\PiecesPHP\Core\DataTransfer\Import\ImportPersistException $e) {
            $mensaje = $e->getMessage();
        }
        $check($mensaje === 'No se pudo guardar la importación; no se guardó ningún usuario.' && count($usuariosDelPrefijo("{$prefijo}-t")) === 0, 'p3 la tercera viola la clave ajena: ImportPersistException y 0 usuarios de las tres filas', var_export($mensaje, true) . ' · creados ' . count($usuariosDelPrefijo("{$prefijo}-t")));
        echoTerminal(' ');

        //─── 4/6 · Filas inválidas ──────────────────────────────────────────────────────────────────────
        echoTerminal('[4/6] Filas inválidas');
        $cabecerasCompletas = ['username', 'email', 'firstname', 'first_lastname', 'type', 'organization'];
        $base = fn(string $s, array $extra = []) => array_replace(["{$prefijo}-v{$s}", "{$prefijo}-v{$s}@example.com", 'Zz', 'Prueba', null, null], $extra);
        $informe = $correr($cabecerasCompletas, [
            $base('1'),
            $base('2', [1 => 'no-es-correo']),
            $base('3', [0 => "{$prefijo}-a"]),
            $base('4', [1 => "{$prefijo}-v1@example.com"]),
            $base('5', [4 => (string) UsersModel::TYPE_USER_ADMIN_GRAL]),
            $base('6', [5 => '987654321']),
            $base('7', [4 => 'usuario general']),
        ]);
        $errores = [];
        foreach ($informe->rowResults() as $r) {
            $errores[$r->position()] = implode(' | ', $r->errors());
        }
        $check(!$informe->persisted() && count($usuariosDelPrefijo("{$prefijo}-v")) === 0, 'p4 persisted false y 0 creados', json_encode($errores, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $check(($errores[1] ?? '') === '' && ($errores[7] ?? '') === '', 'p4 las filas 1 y 7 (tipo por nombre) son válidas');
        $check(str_contains($errores[2] ?? '', 'no es un correo válido'), 'p4 fila 2: correo mal formado');
        $check(str_contains($errores[3] ?? '', 'ya existe'), 'p4 fila 3: usuario duplicado en la base');
        $check(str_contains($errores[4] ?? '', 'se repite en la fila 1'), 'p4 fila 4: correo duplicado dentro del archivo');
        $check(str_contains($errores[5] ?? '', 'no se puede importar'), 'p4 fila 5: tipo administrador, fuera de IMPORTABLE_TYPES');
        $check(str_contains($errores[6] ?? '', 'no existe la organización'), 'p4 fila 6: organización inexistente');

        //Quien importa es un usuario general: el tipo general tiene su misma prioridad.
        $general = $porUsuario["{$prefijo}-b"] ?? null;
        $como($general !== null ? (int) $general->id : null);
        $informe = $correr($cabecerasCompletas, [$base('8', [4 => (string) UsersModel::TYPE_USER_GENERAL])]);
        $errorPrioridad = implode(' | ', ($informe->rowResults()[0] ?? null)?->errors() ?? []);
        $check($general !== null && !$informe->persisted() && str_contains($errorPrioridad, 'no tienes permiso'), 'p4 tipo con la misma prioridad que quien importa → error de fila', $errorPrioridad);
        $como($idRoot);
        echoTerminal(' ');

        //─── 6/6 · Fichas ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[6/6] Las fichas escapan');
        $html = UsersImportDefinition::credentialsSheet([['name' => 'zz"<b>', 'username' => 'zz<i>', 'password' => 'a&b']]);
        $check(str_contains($html, 'zz&quot;&lt;b&gt;') && str_contains($html, 'zz&lt;i&gt;') && str_contains($html, 'a&amp;b') && !str_contains($html, '<b>') && !str_contains($html, 'http-equiv') && !preg_match('#(src|href)\s*=#i', $html), 'p6 nombre, usuario y contraseña escapados; sin recursos externos');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $ids = array_map(fn($u) => (int) $u->id, $usuariosDelPrefijo($prefijo));
        if (count($ids) > 0) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            $perfiles->delete(new WhereSegment([new WhereItem('belongsTo', WhereItem::IN_OPERATOR, '(' . implode(',', $ids) . ')')]))->execute();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        }
        $restos = count($usuariosDelPrefijo('zz-prueba-imp-'));
        $check($restos === 0, 'z1 limpieza: 0 usuarios zz-prueba-imp-* (y sus perfiles borrados antes)', "restos {$restos}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El importador de usuarios guarda todo o nada, respeta los tipos importables y entrega las contraseñas generadas una sola vez.')->setEffects([CliActions::EFFECT_DATABASE])->register();
