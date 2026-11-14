<?php

//Las tres escrituras de `meta` de UsersModel (P329 a): el texto exacto del mapper, el valor de siempre, y una escritura que llega
//entre la lectura y el UPDATE no se pierde. Siembra usuarios zz- y los retira.
//@areas nucleo

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/users-meta', function ($args) {

    echoTerminal("\e[33m[TEST:UsersMeta] meta con el formato del mapper y sin pisar un cambio concurrente\e[39m");
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
    $prefijo = 'zz-prueba-meta-' . bin2hex(random_bytes(3));
    $usuarios = [];
    $crear = function (string $sufijo, int $estado) use ($prefijo, &$usuarios): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        $u->password = password_hash(bin2hex(random_bytes(12)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Meta';
        $u->secondLastname = '';
        $u->type = UsersModel::TYPE_USER_GENERAL;
        $u->status = $estado;
        $u->failedAttempts = 0;
        $u->organization = OrganizationMapper::INITIAL_ID_GLOBAL;
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $usuarios[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $metaDe = fn(int $id): ?string => ($v = $database->query("SELECT meta FROM `{$tabla}` WHERE id = {$id}")->fetchColumn()) === false || $v === null ? null : (string) $v;
    $ponerMeta = function (int $id, ?string $meta) use ($database, $tabla): void {
        //RETORNO-IGNORADO: preparación de la prueba; lo que importa lo miden los casos.
        $database->prepare("UPDATE `{$tabla}` SET meta = ? WHERE id = ?")->execute([$meta, $id]);
    };
    //El texto que daría el mapper: el criterio de todo lo de abajo.
    $comoMapper = fn(?string $texto): bool => $texto !== null && json_decode($texto) instanceof \stdClass && UsersModel::metaToStore(json_decode($texto)) === $texto;
    //Un guardado del modelo sin tocar nada: con el formato del mapper, 0 filas.
    $guardadoSinCambios = function (int $id): int {
        $u = new UsersModel($id);
        $u->update();
        return (int) $u->getModel()->getLastChangedRowsCount();
    };
    $registro = basepath('app/logs/error.log.jsonl');

    //Una lectura tras la que «otra petición» escribe en `meta` (en el formato de MySQL, como antes): la escritura en curso
    //se queda con la lectura vieja, su WHERE no casa y relee.
    $carrera = new class () extends UsersModel {
        public static int $carreras = 0;
        public static int $numero = 0;
        protected static function freshStatusAndRawMeta(int $id): ?array
        {
            $leido = parent::freshStatusAndRawMeta($id);
            if (static::$carreras > 0 && $leido !== null) {
                static::$carreras--;
                static::$numero++;
                $statement = self::model()->prepare('UPDATE `' . self::TABLE . "` SET meta = JSON_SET(COALESCE(NULLIF(meta, ''), '{}'), ?, ?) WHERE id = ?");
                //RETORNO-IGNORADO: la «otra petición»; lo comprueban los casos de c.
                $statement->execute(['$.zzConcurrente' . static::$numero, 'otra', $id]);
            }
            return $leido;
        }
    };
    $claseCarrera = get_class($carrera);

    try {
        //─── a · El texto exacto del mapper y el valor de siempre ───────────────────────────────────────
        echoTerminal('[a] Formato y valor');
        $id = $crear('a', UsersModel::STATUS_USER_ACTIVE);
        foreach (['NULL' => null, 'vacío' => '', '[]' => '[]', 'MySQL' => '{"zzPrevio": "x"}'] as $nombre => $inicial) {
            $ponerMeta($id, $inicial);
            $hecho = UsersModel::setMetaValue($id, 'zzClave', '7');
            $texto = $metaDe($id);
            $obj = json_decode((string) $texto, true);
            $check($hecho === true && $comoMapper($texto) && ($obj['zzClave'] ?? null) === '7' && ($inicial !== '{"zzPrevio": "x"}' || ($obj['zzPrevio'] ?? null) === 'x'), "a1 setMetaValue() sobre meta {$nombre}: texto del mapper, la clave como texto y el resto conservado", (string) $texto);
        }
        $check($guardadoSinCambios($id) === 0, 'a2 y un guardado del modelo sin cambios cuenta 0 filas (lo que reabría un rechazo)');
        $ponerMeta($id, '{"zzPrevio": 1}');
        $hecho = (new UsersModel())->changeStatus(UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $id);
        $texto = $metaDe($id);
        $obj = json_decode((string) $texto, true);
        $check($hecho === true && $comoMapper($texto) && ($obj[UsersModel::META_STATUS_BEFORE_BLOCK] ?? null) === UsersModel::STATUS_USER_ACTIVE && ($obj['zzPrevio'] ?? null) === 1 && UsersModel::statusBeforeBlock($id) === UsersModel::STATUS_USER_ACTIVE, 'a3 bloquear: texto del mapper, el estado de antes como número y el resto conservado', (string) $texto);
        $check($guardadoSinCambios($id) === 0, 'a4 y un guardado sin cambios cuenta 0 filas');
        $hecho = UsersModel::transitionStatusBeforeBlock($id, [UsersModel::STATUS_USER_ACTIVE], UsersModel::STATUS_USER_REJECTED);
        $texto = $metaDe($id);
        $obj = json_decode((string) $texto, true);
        $check($hecho === true && $comoMapper($texto) && ($obj[UsersModel::META_STATUS_BEFORE_BLOCK] ?? null) === UsersModel::STATUS_USER_REJECTED && UsersModel::statusBeforeBlock($id) === UsersModel::STATUS_USER_REJECTED, 'a5 la transición: texto del mapper y el estado nuevo como número', (string) $texto);
        $check($guardadoSinCambios($id) === 0, 'a6 y un guardado sin cambios cuenta 0 filas');
        $otro = $crear('a-raro', UsersModel::STATUS_USER_ACTIVE);
        $ponerMeta($otro, '[1,2]');
        $hechoMeta = UsersModel::setMetaValue($otro, 'zzClave', 'x');
        $hechoBloqueo = (new UsersModel())->changeStatus(UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $otro);
        $estado = (int) $database->query("SELECT status FROM `{$tabla}` WHERE id = {$otro}")->fetchColumn();
        $check($hechoMeta && $hechoBloqueo && $metaDe($otro) === '[1,2]' && $estado === UsersModel::STATUS_USER_ATTEMPTS_BLOCK, 'a7 un meta que no es un objeto JSON se deja como está (y el bloqueo se aplica igual), como antes');
        $bloqueado = $crear('a-desbloqueo', UsersModel::STATUS_USER_ACTIVE);
        //RETORNO-IGNORADO: preparación de la prueba; lo que importa lo miden los casos.
        $database->prepare("UPDATE `{$tabla}` SET status = ?, meta = ? WHERE id = ?")->execute([UsersModel::STATUS_USER_ATTEMPTS_BLOCK, '{"zzPrevio": 1, "' . UsersModel::META_STATUS_BEFORE_BLOCK . '": ' . UsersModel::STATUS_USER_ACTIVE . '}', $bloqueado]);
        $hecho = (new UsersModel())->unblockFromAttempts($bloqueado, UsersModel::STATUS_USER_APPROVED_PENDING);
        $texto = $metaDe($bloqueado);
        $obj = json_decode((string) $texto, true);
        $estado = (int) $database->query("SELECT status FROM `{$tabla}` WHERE id = {$bloqueado}")->fetchColumn();
        $check($hecho === true && $estado === UsersModel::STATUS_USER_ACTIVE && $comoMapper($texto) && !array_key_exists(UsersModel::META_STATUS_BEFORE_BLOCK, (array) $obj) && ($obj['zzPrevio'] ?? null) === 1, 'a8 desbloquear (desde el formato viejo): vuelve al estado guardado, texto del mapper, sin la clave y el resto conservado', (string) $texto);
        $check($guardadoSinCambios($bloqueado) === 0, 'a9 y un guardado sin cambios cuenta 0 filas');
        echoTerminal(' ');

        //─── b · Una escritura entre la lectura y el UPDATE no se pierde ────────────────────────────────
        echoTerminal('[b] Concurrencia: una escritura de meta entre la lectura y el UPDATE');
        $id = $crear('b', UsersModel::STATUS_USER_ACTIVE);
        $ponerMeta($id, null);
        $claseCarrera::$carreras = 1;
        $hecho = $claseCarrera::setMetaValue($id, 'zzMia', 'yo');
        $obj = json_decode((string) $metaDe($id), true);
        $check($hecho === true && ($obj['zzMia'] ?? null) === 'yo' && ($obj['zzConcurrente1'] ?? null) === 'otra' && $comoMapper($metaDe($id)), 'b1 setMetaValue(): el WHERE no casa, relee y guarda las dos claves (la otra no se pierde)', (string) $metaDe($id));
        $claseCarrera::$carreras = 1;
        $hecho = $carrera->changeStatus(UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $id);
        $obj = json_decode((string) $metaDe($id), true);
        $check($hecho === true && ($obj['zzConcurrente2'] ?? null) === 'otra' && ($obj[UsersModel::META_STATUS_BEFORE_BLOCK] ?? null) === UsersModel::STATUS_USER_ACTIVE && $comoMapper($metaDe($id)), 'b2 bloquear: relee y conserva la clave concurrente', (string) $metaDe($id));
        $claseCarrera::$carreras = 1;
        $hecho = $claseCarrera::transitionStatusBeforeBlock($id, [UsersModel::STATUS_USER_ACTIVE], UsersModel::STATUS_USER_REJECTED);
        $obj = json_decode((string) $metaDe($id), true);
        $check($hecho === true && ($obj['zzConcurrente3'] ?? null) === 'otra' && ($obj[UsersModel::META_STATUS_BEFORE_BLOCK] ?? null) === UsersModel::STATUS_USER_REJECTED, 'b3 la transición: relee y conserva la clave concurrente', (string) $metaDe($id));

        $id3 = $crear('b-desbloqueo', UsersModel::STATUS_USER_ACTIVE);
        //RETORNO-IGNORADO: preparación de la prueba; lo que importa lo miden los casos.
        $database->prepare("UPDATE `{$tabla}` SET status = ?, meta = ? WHERE id = ?")->execute([UsersModel::STATUS_USER_ATTEMPTS_BLOCK, '{"' . UsersModel::META_STATUS_BEFORE_BLOCK . '":' . UsersModel::STATUS_USER_ACTIVE . '}', $id3]);
        $claseCarrera::$carreras = 1;
        $antesNumero = $claseCarrera::$numero;
        $hecho = $carrera->unblockFromAttempts($id3, UsersModel::STATUS_USER_APPROVED_PENDING);
        $obj = json_decode((string) $metaDe($id3), true);
        $estado = (int) $database->query("SELECT status FROM `{$tabla}` WHERE id = {$id3}")->fetchColumn();
        $clave = 'zzConcurrente' . ($antesNumero + 1);
        $check($hecho === true && $estado === UsersModel::STATUS_USER_ACTIVE && ($obj[$clave] ?? null) === 'otra' && !array_key_exists(UsersModel::META_STATUS_BEFORE_BLOCK, (array) $obj) && $comoMapper($metaDe($id3)), 'b6 desbloquear: relee, desbloquea y conserva la clave concurrente', (string) $metaDe($id3));

        //Dos carreras seguidas: falla cerrado y se anota.
        clearstatcache(true, $registro);
        $desde = is_file($registro) ? (int) filesize($registro) : 0;
        $claseCarrera::$carreras = 2;
        $hechoMeta = $claseCarrera::setMetaValue($id, 'zzNunca', 'no');
        $obj = json_decode((string) $metaDe($id), true);
        $sinClave = !array_key_exists('zzNunca', is_array($obj) ? $obj : []);
        $id2 = $crear('b-bloqueo', UsersModel::STATUS_USER_ACTIVE);
        $claseCarrera::$carreras = 2;
        $hechoBloqueo = $carrera->changeStatus(UsersModel::STATUS_USER_ATTEMPTS_BLOCK, $id2);
        $estado = (int) $database->query("SELECT status FROM `{$tabla}` WHERE id = {$id2}")->fetchColumn();
        clearstatcache(true, $registro);
        $nuevo = is_file($registro) ? (string) file_get_contents($registro, false, null, $desde) : '';
        $check($hechoMeta === false && $sinClave && substr_count($nuevo, 'Meta no guardado en el usuario ' . $id) === 1, 'b4 setMetaValue() con dos carreras: false, no escribe su clave y queda anotado una vez');
        $check($hechoBloqueo === false && $estado === UsersModel::STATUS_USER_ACTIVE && substr_count($nuevo, 'Bloqueo no aplicado al usuario ' . $id2) === 1, 'b5 bloquear con dos carreras: false, sigue sin bloquear y queda anotado', "estado {$estado}");
        $claseCarrera::$carreras = 0;

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . basename($e->getFile()) . ':' . $e->getLine());
    } finally {
        $claseCarrera::$carreras = 0;
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

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las cuatro escrituras de meta de UsersModel (P329 a; también el desbloqueo): el texto del mapper sobre NULL, vacío, [] y el formato viejo; el valor de siempre (texto en setMetaValue, número en el estado de antes del bloqueo); un guardado sin cambios cuenta 0 filas; un meta que no es objeto se deja; una escritura concurrente no se pierde; dos seguidas, false y anotado.')->setEffects([CliActions::EFFECT_DATABASE])->register();
