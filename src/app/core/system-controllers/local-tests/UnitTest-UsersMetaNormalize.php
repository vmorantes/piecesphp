<?php

//users-meta-normalize (P329 a), solo sobre sus usuarios zz- y con una carpeta de volcados temporal propia (nunca dumps/):
//simula sin escribir, sin volcado reciente rechaza, con él reescribe solo el formato viejo, y una segunda pasada da 0.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Tasks\UsersMetaNormalizeTask;

CliActions::make('unit-tests:core/users-meta-normalize', function ($args) {

    echoTerminal("\e[33m[TEST:UsersMetaNormalize] la normalización del meta guardado con el formato viejo\e[39m");
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

    $database = (new BaseModel())->getDatabase();
    if (!$check($database !== null, 'b0 hay conexión a la base')) {
        return ['success' => false, 'message' => 'sin conexión'];
    }
    $tabla = UsersModel::TABLE;
    $prefijo = 'zz-prueba-normaliza-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $crear = function (string $sufijo, ?string $meta) use ($database, $tabla, $prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Normaliza';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        //RETORNO-IGNORADO: preparación de la prueba; lo que importa lo miden los casos.
        $database->prepare("UPDATE `{$tabla}` SET meta = ? WHERE id = ?")->execute([$meta, (int) $u->id]);
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $metaDe = fn(int $id): ?string => ($v = $database->query("SELECT meta FROM `{$tabla}` WHERE id = {$id}")->fetchColumn()) === false || $v === null ? null : (string) $v;
    $volcados = rtrim(sys_get_temp_dir(), '/') . '/zz-normaliza-volcados-' . bin2hex(random_bytes(6));
    $previa = UsersMetaNormalizeTask::swapForTesting($volcados);

    try {
        $viejo = $crear('viejo', '{"zzA": "x", "zzB": 2}');
        $viejo2 = $crear('viejo2', '{"theme_mode": "dark"}');
        $bien = $crear('bien', '{"zzA":"x"}');
        $raro = $crear('raro', '[1,2]');
        $vacio = $crear('vacio', null);
        $ids = array_values($usuarios);
        $antes = array_combine($ids, array_map($metaDe, $ids));

        //─── c1 · Simular ───────────────────────────────────────────────────────────────────────────────
        $cuenta = UsersMetaNormalizeTask::analyze($ids);
        $check($cuenta['total'] === 5 && $cuenta['toRewrite'] === 2 && $cuenta['alreadyOk'] === 1 && $cuenta['notObject'] === 1 && $cuenta['empty'] === 1 && $cuenta['ids'] === [$viejo, $viejo2], 'c1 simular cuenta 2 a reescribir, 1 bien, 1 que no es objeto y 1 vacío', (string) json_encode($cuenta));
        $check(array_combine($ids, array_map($metaDe, $ids)) === $antes, 'c2 y simular no escribe nada');

        //─── c3 · Sin volcado reciente ──────────────────────────────────────────────────────────────────
        $creada = mkdir($volcados, 0775, true);
        $rechazo = UsersMetaNormalizeTask::apply(time(), $ids);
        $check($creada && $rechazo['refused'] !== null && $rechazo['rewritten'] === 0 && array_combine($ids, array_map($metaDe, $ids)) === $antes, 'c3 apply sin un volcado de la última hora: rechaza y no escribe', (string) json_encode($rechazo, \JSON_UNESCAPED_UNICODE));

        //─── c4 · Con volcado ───────────────────────────────────────────────────────────────────────────
        $tocado = touch($volcados . '/zz-ahora.sql');
        $hecho = UsersMetaNormalizeTask::apply(time(), $ids);
        $check($tocado && $hecho['refused'] === null && $hecho['rewritten'] === 2 && $hecho['failed'] === 0, 'c4 con volcado: 2 reescritos, 0 fallidos', (string) json_encode($hecho));
        $check($metaDe($viejo) === '{"zzA":"x","zzB":2}' && $metaDe($viejo2) === '{"theme_mode":"dark"}', 'c5 los del formato viejo quedan con el del mapper, con los mismos valores (el número sigue siendo número)', (string) $metaDe($viejo));
        $check($metaDe($bien) === $antes[$bien] && $metaDe($raro) === '[1,2]' && $metaDe($vacio) === null, 'c6 el que ya estaba bien, el que no es objeto y el vacío no se tocan');
        $u = new UsersModel($viejo);
        $u->update();
        $check((int) $u->getModel()->getLastChangedRowsCount() === 0, 'c7 tras normalizar, un guardado del modelo sin cambios cuenta 0 filas');

        //─── c8 · Segunda pasada ────────────────────────────────────────────────────────────────────────
        $segunda = UsersMetaNormalizeTask::analyze($ids);
        $otraVez = UsersMetaNormalizeTask::apply(time(), $ids);
        $check($segunda['toRewrite'] === 0 && $otraVez['rewritten'] === 0 && $otraVez['failed'] === 0, 'c8 una segunda pasada encuentra 0', (string) json_encode([$segunda['toRewrite'], $otraVez]));

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        UsersMetaNormalizeTask::swapForTesting($previa);
        //RETORNO-IGNORADO: limpieza; lo comprueba z2 mirando que la carpeta ya no existe.
        @unlink($volcados . '/zz-ahora.sql');
        //RETORNO-IGNORADO: limpieza; lo comprueba z2 mirando que la carpeta ya no existe.
        @rmdir($volcados);
        try {
            foreach ($usuarios as $id) {
                $aprobaciones = SystemApprovalsMapper::model();
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tabla),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            $modelo = UsersModel::model();
            $modelo->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modelo->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
        }
    }
    $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tabla}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
    $check($quedan === 0, 'z1 no queda ningún usuario de la prueba', "quedan {$quedan}");
    $check(!is_dir($volcados) && UsersMetaNormalizeTask::dumpsDirectory() !== $volcados, 'z2 la carpeta temporal se borra y la del producto vuelve');

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('users-meta-normalize (P329 a), sobre usuarios zz- propios: simula sin escribir, sin volcado reciente rechaza, con él reescribe solo el formato viejo (mismos valores) sin tocar lo bien, lo vacío ni lo que no es objeto, y una segunda pasada encuentra 0.')->setEffects([CliActions::EFFECT_DATABASE])->register();
