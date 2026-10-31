<?php

//«Mi organización»: 404 o 403, nunca 500; root guarda la de la URL; el perfil público es de la organización.
//Por HTTP. Crea un principal, usuarios y organizaciones, zz-, y los retira.
//@areas src/app/classes/Organizations/
//@areas src/app/classes/SystemApprovals/

use Organizations\Controllers\MyOrganizationProfileController;
use Organizations\Controllers\OrganizationProfileController;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\StateMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Authentication\UserSessions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/my-organization-profile', function ($args) {

    echoTerminal("\e[33m[TEST:MyOrganizationProfile] «Mi organización» responde 404 o 403, nunca 500\e[39m");
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

    //`base_url` en el terminal es `http://localhost`: no sirve. Igual que la pantalla de respaldos.
    $base = (string) (getenv('PCSPHP_WALK_BASE') ?: '');
    $proyecto = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    if ($base === '') {
        $matrix = is_file("{$proyecto}/files/dev/permissions-matrix.json") ? json_decode((string) file_get_contents("{$proyecto}/files/dev/permissions-matrix.json"), true) : null;
        $base = is_array($matrix) ? (string) ($matrix['medido']['base'] ?? '') : '';
    }
    $base = rtrim($base, '/');
    if (!$check($base !== '', 'c1 hay una base HTTP para pedir', $base)) {
        return $balance();
    }

    $camino = function (string $sufijo, array $parametros = [], string $controlador = MyOrganizationProfileController::class): string {
        $url = $controlador::routeName($sufijo, $parametros, true);
        return '/' . ltrim(str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url, '/');
    };
    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $prefijoBase, $cabeceraToken): array {
        $path = $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
        $handle = \Terminal\TestRun::curlInit();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [],
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        \Terminal\TestRun::curlSetoptArray($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };

    $prefijo = 'zz-prueba-mi-org-' . bin2hex(random_bytes(3));
    $ids = [];
    $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo, &$ids): int {
        $u = new UsersModel();
        $u->username = "{$prefijo}-{$sufijo}";
        $u->email = "{$prefijo}-{$sufijo}@example.com";
        //Nadie entra con contraseña: el token lo fabrica la prueba.
        $u->password = password_hash(bin2hex(random_bytes(16)), \PASSWORD_DEFAULT);
        $u->firstname = 'Zz';
        $u->secondname = '';
        $u->firstLastname = 'Mi Organización';
        $u->secondLastname = '';
        $u->type = $tipo;
        $u->status = UsersModel::STATUS_USER_ACTIVE;
        $u->failedAttempts = 0;
        //Sin organización se deja el campo sin asignar: la columna admite null y es su valor de partida.
        if ($organizacion !== null) {
            $u->organization = $organizacion;
        }
        $u->createdAt = new \DateTime();
        $u->modifiedAt = $u->createdAt;
        $u->save();
        $ids[$sufijo] = (int) $u->id;
        return (int) $u->id;
    };
    $organizaciones = [];
    //Completa según profileIsComplete(): con una ciudad y un país que existan, porque la ficha pinta sus nombres.
    $crearOrganizacion = function (string $sufijo, ?int $encargado) use ($prefijo, &$organizaciones): OrganizationMapper {
        $database = (new BaseModel())->getDatabase();
        $cities = CityMapper::PREFIX_TABLE . CityMapper::TABLE;
        $states = StateMapper::PREFIX_TABLE . StateMapper::TABLE;
        $lugar = $database !== null ? $database->query("SELECT c.id AS city, s.country AS country FROM `{$cities}` c INNER JOIN `{$states}` s ON s.id = c.state ORDER BY c.id LIMIT 1")->fetch(\PDO::FETCH_ASSOC) : false;
        $o = new OrganizationMapper();
        $o->name = "{$prefijo}-org-{$sufijo}";
        $o->nit = "{$prefijo}-nit-{$sufijo}";
        $o->folder = "{$prefijo}-{$sufijo}";
        $o->status = OrganizationMapper::ACTIVE;
        $o->activitySector = 'Sector de prueba';
        $o->informativeEmail = "{$prefijo}-org-{$sufijo}@example.com";
        //Un archivo versionado como logo: nadie lo sustituye, y la limpieza borra la fila sin pasar por el controlador.
        $o->logo = 'statics/images/default-avatar-org.jpg';
        $o->country = is_array($lugar) ? (int) $lugar['country'] : null;
        $o->city = is_array($lugar) ? (int) $lugar['city'] : null;
        $o->latitude = 4.6;
        $o->longitude = -74.1;
        if ($encargado !== null) {
            $o->administrator = $encargado;
        }
        $o->save();
        $organizaciones[$sufijo] = (int) $o->id;
        return new OrganizationMapper((int) $o->id);
    };
    $nombreEnBase = function (int $id): string {
        return (string) (new OrganizationMapper($id))->name;
    };

    try {
        $root = UserSessions::issueToken($crearUsuario('root', UsersModel::TYPE_USER_ROOT, OrganizationMapper::INITIAL_ID_GLOBAL));
        $general = UserSessions::issueToken($crearUsuario('general', UsersModel::TYPE_USER_GENERAL, null));
        $adminSinOrganizacion = UserSessions::issueToken($crearUsuario('admin-sin-org', UsersModel::TYPE_USER_ADMIN_GRAL, null));
        $inexistente = 2147483000;

        //─── a · Con privilegios superiores ───────────────────────────────────────────────────────
        echoTerminal('[a] El principal: la organización sale de la URL');
        $canario = $pedir($camino('my-organization-profile', ['organizationID' => OrganizationMapper::INITIAL_ID_GLOBAL]), $root);
        $check($canario['status'] === 200, 'a1 CANARIO: la organización global responde 200', "HTTP {$canario['status']}");
        $noExiste = $pedir($camino('my-organization-profile', ['organizationID' => $inexistente]), $root);
        $check($noExiste['status'] === 404, 'a2 una organización que no existe responde 404', "HTTP {$noExiste['status']}");
        //Sin organización en la URL abre la propia (pendientes.md 374.5): el formulario guarda en la global, la suya.
        $sinParametro = $pedir($camino('my-organization-profile'), $root);
        $guardaEnLaPropia = str_contains($sinParametro['body'], $camino('actions-save-profile', ['organizationID' => OrganizationMapper::INITIAL_ID_GLOBAL]));
        $check($sinParametro['status'] === 200 && $guardaEnLaPropia, 'a3 sin organización en la URL abre la suya: 200, y el formulario guarda en su organización', "HTTP {$sinParametro['status']}");
        $guardar = $pedir($camino('actions-save-profile', ['organizationID' => $inexistente]), $root, ['name' => 'zz-no-se-guarda']);
        $check($guardar['status'] === 404, 'a4 guardar una organización que no existe responde 404', "HTTP {$guardar['status']}");
        $encargado = $pedir($camino('actions-change-administrator', ['organizationID' => $inexistente]), $root, ['newUserAdminID' => $ids['root']]);
        $check($encargado['status'] !== 500, 'a5 cambiar el encargado de una que no existe no responde 500', "HTTP {$encargado['status']}");
        $sinPropia = $pedir($camino('my-organization-profile'), $adminSinOrganizacion);
        $check($sinPropia['status'] === 404, 'a6 un administrador general SIN organización, sin indicar ninguna, 404: no hay ninguna que abrir', "HTTP {$sinPropia['status']}");

        //─── b · Un usuario sin organización ──────────────────────────────────────────────────────
        echoTerminal('');
        echoTerminal('[b] Un usuario general sin organización');
        $vista = $pedir($camino('my-organization-profile'), $general);
        $check($vista['status'] === 403, 'b1 su «mi organización» responde 403', "HTTP {$vista['status']}");
        $guardarSin = $pedir($camino('actions-save-profile'), $general, ['name' => 'zz-no-se-guarda']);
        $check($guardarSin['status'] === 403, 'b2 y guardar, 403', "HTTP {$guardarSin['status']}");
        $encargadoSin = $pedir($camino('actions-change-administrator'), $general, ['newUserAdminID' => $ids['general']]);
        $check($encargadoSin['status'] === 403, 'b3 y cambiar el encargado, 403', "HTTP {$encargadoSin['status']}");

        //─── c · El encargado es de OTRA organización (#1433 H1) ──────────────────────────────────
        echoTerminal('');
        echoTerminal('[c] Con privilegios superiores, la organización es la de la URL aunque su encargado sea de otra');
        $otra = $crearOrganizacion('b', null);
        $encargadoDeOtra = $crearUsuario('encargado', UsersModel::TYPE_USER_ADMIN_ORG, (int) $otra->id);
        $ajena = $crearOrganizacion('a', $encargadoDeOtra);
        $check($ajena->id !== null && $otra->id !== null && $ajena->profileIsComplete(), 'c0 CANARIO: las dos organizaciones existen y la de la URL está completa');
        $vistaAjena = $pedir($camino('my-organization-profile', ['organizationID' => $ajena->id]), $root);
        $check($vistaAjena['status'] === 200 && str_contains($vistaAjena['body'], (string) $ajena->name) && !str_contains($vistaAjena['body'], (string) $otra->name), 'c1 root ve en el formulario los datos de la organización de la URL, no los de la del encargado', "HTTP {$vistaAjena['status']}");
        $nuevoNombre = "{$prefijo}-org-a-guardada";
        $guardarAjena = $pedir($camino('actions-save-profile', ['organizationID' => $ajena->id]), $root, ['name' => $nuevoNombre]);
        $respuesta = json_decode($guardarAjena['body'], true);
        $check($guardarAjena['status'] === 200 && is_array($respuesta) && ($respuesta['success'] ?? false) === true, 'c2 root guarda el perfil de una organización cuyo encargado es de otra', "HTTP {$guardarAjena['status']} · " . mb_substr($guardarAjena['body'], 0, 200));
        $check($nombreEnBase((int) $ajena->id) === $nuevoNombre && $nombreEnBase((int) $otra->id) === (string) $otra->name, 'c3 y se guarda EN la de la URL, sin tocar la del encargado', $nombreEnBase((int) $ajena->id) . ' / ' . $nombreEnBase((int) $otra->id));
        $miembro = UserSessions::issueToken($crearUsuario('admin-org', UsersModel::TYPE_USER_ADMIN_ORG, (int) $ajena->id));
        $guardarMiembro = $pedir($camino('actions-save-profile', ['organizationID' => $ajena->id]), $miembro, ['name' => 'zz-no-se-guarda']);
        $check($guardarMiembro['status'] === 403, 'c4 un administrador de organización que no es su encargado no la guarda: 403', "HTTP {$guardarMiembro['status']}");
        $check($nombreEnBase((int) $ajena->id) === $nuevoNombre, 'c5 y el nombre sigue siendo el que guardó root');
        //El encargado de su propia organización: el camino de siempre, que desde la 8.0.0 no guardaba (#1447).
        $propia = $crearOrganizacion('propia', null);
        $encargadoPropio = $crearUsuario('encargado-propio', UsersModel::TYPE_USER_ADMIN_ORG, (int) $propia->id);
        $propiaModelo = new OrganizationMapper((int) $propia->id);
        $propiaModelo->administrator = $encargadoPropio;
        $propiaModelo->update();
        $tokenPropio = UserSessions::issueToken($encargadoPropio);
        $nombrePropio = (string) $propia->name;
        $cambiado = "{$prefijo}-org-propia-cambiada";
        $guardarPropia = $pedir($camino('actions-save-profile'), $tokenPropio, ['name' => $cambiado]);
        $respuestaPropia = json_decode($guardarPropia['body'], true);
        $check($guardarPropia['status'] === 200 && is_array($respuestaPropia) && ($respuestaPropia['success'] ?? false) === true && $nombreEnBase((int) $propia->id) === $cambiado, 'c6 el encargado guarda su propia organización, y el valor cambia', "HTTP {$guardarPropia['status']} · " . mb_substr($guardarPropia['body'], 0, 200));
        $devolver = $pedir($camino('actions-save-profile'), $tokenPropio, ['name' => $nombrePropio]);
        $check($devolver['status'] === 200 && $nombreEnBase((int) $propia->id) === $nombrePropio, 'c7 y devuelto, vuelve a su valor', "HTTP {$devolver['status']}");

        //─── d · El perfil público es de la organización (#1433 H2) ───────────────────────────────
        echoTerminal('');
        echoTerminal('[d] El perfil público de una organización');
        $publico = $pedir($camino('profile', ['organizationID' => $ajena->id], OrganizationProfileController::class), $root);
        $check($publico['status'] === 200 && str_contains($publico['body'], (string) $ajena->informativeEmail), 'd1 su contacto es el correo informativo de la organización', "HTTP {$publico['status']}");
        $check(!str_contains($publico['body'], "{$prefijo}-encargado@example.com"), 'd2 y no el correo de su encargado');
        $sinEncargado = $crearOrganizacion('sin-encargado', null);
        $publicoSin = $pedir($camino('profile', ['organizationID' => $sinEncargado->id], OrganizationProfileController::class), $root);
        $check($publicoSin['status'] === 200 && str_contains($publicoSin['body'], (string) $sinEncargado->informativeEmail), 'd3 una organización sin encargado, visible y completa, responde 200 con su perfil', "HTTP {$publicoSin['status']}");

        //─── e · Editar una organización recién creada ─────────────────────────────
        echoTerminal('');
        echoTerminal('[e] Sin usuarios, el encargado no se exige; con usuarios, sí');
        $editar = function (OrganizationMapper $organizacion) use ($camino): string {
            return $camino('forms-edit', ['id' => $organizacion->id, 'lang' => \PiecesPHP\Core\Config::get_default_lang()], \Organizations\Controllers\OrganizationsController::class);
        };
        $selectEncargado = function (string $html): string {
            return preg_match('/<select[^>]*name="administrator"[^>]*>/', $html, $coincidencia) === 1 ? $coincidencia[0] : '';
        };
        $vacia = $crearOrganizacion('sin-usuarios', null);
        $sinUsuarios = $pedir($editar($vacia), $root);
        $campoVacio = $selectEncargado($sinUsuarios['body']);
        $check($sinUsuarios['status'] === 200 && $campoVacio !== '' && !str_contains($campoVacio, 'required'), 'e1 sin usuarios, el select de encargado no lleva required', "HTTP {$sinUsuarios['status']}: {$campoVacio}");
        $guardarVacia = $pedir($camino('actions-edit', [], \Organizations\Controllers\OrganizationsController::class), $root, ['id' => $vacia->id, 'lang' => \PiecesPHP\Core\Config::get_default_lang(), 'name' => "{$prefijo}-org-sin-usuarios", 'administrator' => '']);
        $respuesta = json_decode($guardarVacia['body'], true);
        $check(is_array($respuesta) && ($respuesta['success'] ?? false) === true, 'e2 y se guarda con el encargado vacío', "HTTP {$guardarVacia['status']}: " . mb_substr($guardarVacia['body'], 0, 200));
        $conUsuario = $crearOrganizacion('con-usuario', null);
        $crearUsuario('encargable', UsersModel::TYPE_USER_ADMIN_ORG, (int) $conUsuario->id);
        $campoLleno = $selectEncargado($pedir($editar($conUsuario), $root)['body']);
        $check(str_contains($campoLleno, 'required'), 'e3 RECHAZO: con un usuario que puede serlo, el select sigue siendo obligatorio', $campoLleno);
        $inventado = $pedir($camino('actions-edit', [], \Organizations\Controllers\OrganizationsController::class), $root, ['id' => $vacia->id, 'lang' => \PiecesPHP\Core\Config::get_default_lang(), 'name' => "{$prefijo}-org-sin-usuarios", 'administrator' => $inexistente]);
        $rechazo = json_decode($inventado['body'], true);
        $check(!is_array($rechazo) || ($rechazo['success'] ?? false) !== true, 'e4 RECHAZO: un encargado que no existe no se guarda', mb_substr($inventado['body'], 0, 200));
    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ($ids as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, UsersModel::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => $id])->execute();
            }
            //Quien guarda queda en `modifiedBy` de la organización, con clave foránea al usuario: se suelta antes.
            $soltar = OrganizationMapper::model();
            $soltar->resetAll();
            //RETORNO-IGNORADO: si no soltara, el borrado de usuarios lanzaría y lo diría z0.
            $soltar->update(['modifiedBy' => null])->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
            foreach ($organizaciones as $id) {
                $aprobaciones->resetAll();
                //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
                $aprobaciones->delete(new WhereSegment([
                    new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, OrganizationMapper::TABLE),
                    new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                ]))->execute();
            }
            $organizacionesModelo = OrganizationMapper::model();
            $organizacionesModelo->resetAll();
            //RETORNO-IGNORADO: lo comprueba z2 contando lo que queda.
            $organizacionesModelo->delete(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $usuarios = UsersModel::model();
        $usuarios->resetAll();
        $usuarios->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        $check(count((array) $usuarios->result()) === 0, 'z1 no queda ningún usuario de la prueba');
        $restos = OrganizationMapper::model();
        $restos->resetAll();
        $restos->select()->where(new WhereSegment([WhereItem::like('name', "{$prefijo}%")]))->execute();
        $check(count((array) $restos->result()) === 0, 'z2 no queda ninguna organización de la prueba');
    }

    return $balance();

})->setDescription('«Mi organización»: una organización que no existe responde 404 y un usuario sin organización, 403; nunca 500. Root guarda la de la URL aunque su encargado sea de otra, y el perfil público enseña el contacto de la organización. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
