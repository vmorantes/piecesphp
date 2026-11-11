<?php

//El límite de tokens de IA es de root (GenericContentController::TOKENS_LIMIT_PERMISSION): un administrador general que
//lo envía no lo cambia, y root sí. Por HTTP; el valor se lee antes y después y se repone por el mismo camino.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/PiecesPHP/BuiltIn/

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController;
use PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;

CliActions::make('unit-tests:core/tokens-limit-permission', function ($args) {

    echoTerminal("\e[33m[TEST:TokensLimitPermission] El límite de tokens de IA solo lo guarda root\e[39m");
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
    $ruta = (string) parse_url(GenericContentController::routeName('actions-edit', [], true), \PHP_URL_PATH);
    $ruta = $prefijoBase !== '' && str_starts_with($ruta, $prefijoBase) ? substr($ruta, strlen($prefijoBase)) : $ruta;
    $guardar = function (string $jwt, int $valor, bool $suelto = false) use ($base, $ruta): int {
        $h = \Terminal\TestRun::curlInit();
        \Terminal\TestRun::curlSetoptArray($h, [
            CURLOPT_URL => $base . '/' . ltrim($ruta, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['configName' => GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT, GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT => $suelto ? $valor : [Config::get_default_lang() => $valor]]),
            CURLOPT_HTTPHEADER => [SessionToken::headerName() . ": {$jwt}", 'X-Requested-With: XMLHttpRequest'],
        ]);
        curl_exec($h);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return (int) curl_getinfo($h, CURLINFO_RESPONSE_CODE);
    };
    $leer = function (): string {
        $mapper = new GenericContentPseudoMapper(GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
        return (string) $mapper->getLangData(Config::get_default_lang(), GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT, true, '');
    };

    $prefijo = 'zz-prueba-tokens-' . bin2hex(random_bytes(3));
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
        $jwtAdmin = UserSessions::issueToken($crear('admin', UsersModel::TYPE_USER_ADMIN_GRAL));
        $jwtRoot = UserSessions::issueToken($crear('root', UsersModel::TYPE_USER_ROOT));
        $check($original !== '', 'banco: el límite actual se lee', $original);
        $intento = ((int) $original) + 7;

        $estado = $guardar($jwtAdmin, $intento);
        $check($leer() === $original, 'a1 un administrador general que envía tokensLimit no lo cambia', "HTTP {$estado}, antes {$original}, después " . $leer());

        $estado = $guardar($jwtRoot, $intento);
        $check($leer() === (string) $intento, 'a2 CANARIO: root sí lo cambia', "HTTP {$estado}, después " . $leer());

        $estado = $guardar($jwtRoot, $intento + 1, true);
        $check($estado === 400 && $leer() === (string) $intento, 'a3 un entero suelto, sin idioma, da 400 y no cambia nada (antes, 500)', "HTTP {$estado}, después " . $leer());
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        if ($jwtRoot !== null && $original !== '' && $leer() !== $original) {
            $guardar($jwtRoot, (int) $original);
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
        $check($leer() === $original && $quedan === 0, 'z1 el límite vuelve a su valor y no queda ningún usuario de la prueba', "límite " . $leer() . ", usuarios {$quedan}");
    }

    return $balance();

})->setDescription('El límite de tokens de IA solo lo guarda root: un administrador general que lo envía no lo cambia, y root sí. Por HTTP; repone el valor.')->setEffects([CliActions::EFFECT_DATABASE])->register();
