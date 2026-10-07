<?php

//Un POST no concede más que su GET: editar o crear usuarios exige la autoridad del formulario, y las publicaciones no
//activas solo se ven en su organización. Por HTTP. Siembra usuarios, organizaciones y publicaciones zz-, y los retira.

use Organizations\Mappers\OrganizationMapper;
use API\Controllers\APIController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\SessionToken;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use Publications\Controllers\PublicationsController;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;

CliActions::make('unit-tests:core/users-post-authority', function ($args) {

    echoTerminal("\e[33m[TEST:UsersPostAuthority] Un POST no concede más que su GET\e[39m");
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
    $database = (new BaseModel())->getDatabase();
    if (!$check($base !== '' && $database !== null, 'p1 hay una base HTTP para pedir y conexión a la base', $base)) {
        return $balance();
    }

    $prefijoBase = rtrim((string) parse_url($base, \PHP_URL_PATH), '/');
    $cabeceraToken = SessionToken::tokenName();
    $camino = function (string $url) use ($prefijoBase): string {
        $path = str_contains($url, '://') ? (string) parse_url($url, \PHP_URL_PATH) : $url;
        return $prefijoBase !== '' && str_starts_with($path, $prefijoBase) ? substr($path, strlen($prefijoBase)) : $path;
    };
    $pedir = function (string $path, ?string $jwt, ?array $post = null) use ($base, $cabeceraToken): array {
        $handle = curl_init();
        $opciones = [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => array_merge($jwt !== null ? ["{$cabeceraToken}: {$jwt}"] : [], ['X-Requested-With: XMLHttpRequest']),
        ];
        if ($post !== null) {
            $opciones[CURLOPT_POST] = true;
            $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
        }
        curl_setopt_array($handle, $opciones);
        $body = curl_exec($handle);
        //Sin curl_close(): deprecado desde PHP 8.5, y aquí una deprecación aborta.
        return ['status' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE), 'body' => is_string($body) ? $body : ''];
    };
    $fila = function (int $id) use ($database): array {
        $consulta = $database->prepare('SELECT username, email, firstname, status, organization, type FROM `' . UsersModel::TABLE . '` WHERE id = ?');
        $consulta->execute([$id]);
        $resultado = $consulta->fetch(\PDO::FETCH_ASSOC);
        return is_array($resultado) ? $resultado : [];
    };
    //La contraseña se comprueba, nunca se lee ni se imprime.
    $claveEs = function (int $id, string $clave): bool {
        $u = new UsersModel($id);
        return $u->id !== null && password_verify($clave, (string) $u->password);
    };

    $prefijo = 'zz-prueba-autoridad-' . bin2hex(random_bytes(3));
    $tablaUsuarios = UsersModel::TABLE;
    $tablaOrganizaciones = OrganizationMapper::TABLE;
    $tablaPublicaciones = PublicationMapper::TABLE;
    $usuarios = [];
    $claves = [];
    $organizaciones = [];
    $publicaciones = [];

    try {
        $crearUsuario = function (string $sufijo, int $tipo, ?int $organizacion) use ($prefijo, &$usuarios, &$claves): string {
            $clave = bin2hex(random_bytes(12));
            $u = new UsersModel();
            $u->username = "{$prefijo}-{$sufijo}";
            $u->email = "{$prefijo}-{$sufijo}@example.com";
            $u->password = password_hash($clave, \PASSWORD_DEFAULT);
            $u->firstname = 'Zz';
            $u->secondname = '';
            $u->firstLastname = 'Autoridad';
            $u->secondLastname = '';
            $u->type = $tipo;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            if ($organizacion !== null) {
                $u->organization = $organizacion;
            }
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            $u->save();
            $usuarios[$sufijo] = (int) $u->id;
            $claves[$sufijo] = $clave;
            return SessionToken::generateToken(['id' => (int) $u->id], null, null, false);
        };
        $global = OrganizationMapper::INITIAL_ID_GLOBAL;
        $crearUsuario('victima-root', UsersModel::TYPE_USER_ROOT, $global);
        $general = $crearUsuario('general', UsersModel::TYPE_USER_GENERAL, $global);
        $adminGeneral = $crearUsuario('admin-general', UsersModel::TYPE_USER_ADMIN_GRAL, $global);
        $ahora = date('Y-m-d H:i:s');
        foreach (['a', 'b'] as $cual) {
            $database->prepare("INSERT INTO `{$tablaOrganizaciones}` (code, name, nit, folder, createdAt, createdBy, status, meta) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")
                ->execute(['zz' . bin2hex(random_bytes(4)), "{$prefijo}-org-{$cual}", 'zz-', "{$prefijo}-org-{$cual}", $ahora, $usuarios['admin-general'], OrganizationMapper::ACTIVE, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $organizaciones[$cual] = (int) $database->lastInsertId();
        }
        //El encargado de A recibe los permisos de formulario de usuarios (index.php, PERMISSIONS_ON_ADMINISTRATOR).
        $encargadoA = $crearUsuario('encargado-a', UsersModel::TYPE_USER_ADMIN_ORG, $organizaciones['a']);
        $database->prepare("UPDATE `{$tablaOrganizaciones}` SET meta = ? WHERE id = ?")
            ->execute([json_encode(['baseLang' => 'es', 'langData' => new \stdClass, 'administrator' => $usuarios['encargado-a']]), $organizaciones['a']]);
        $crearUsuario('miembro-a', UsersModel::TYPE_USER_GENERAL, $organizaciones['a']);
        $crearUsuario('miembro-b', UsersModel::TYPE_USER_GENERAL, $organizaciones['b']);
        $comunicacionesA = $crearUsuario('comunicaciones-a', UsersModel::TYPE_USER_COMUNICACIONES, $organizaciones['a']);
        $comunicacionesSinOrganizacion = $crearUsuario('comunicaciones-sin', UsersModel::TYPE_USER_COMUNICACIONES, null);
        $crearUsuario('autor-b', UsersModel::TYPE_USER_COMUNICACIONES, $organizaciones['b']);
        $crearUsuario('sin-rol', UsersModel::TYPE_USER_GOOGLE_PLAY, $global);

        $editar = $camino(UsersController::routeName('edit-request', [], true));
        $alta = $camino(UsersController::routeName('register-request', [], true));
        $cuerpoEdicion = function (string $sufijo, array $cambios) use ($fila, $usuarios): array {
            $actual = $fila($usuarios[$sufijo]);
            return array_merge([
                'id' => $usuarios[$sufijo],
                'username' => $actual['username'],
                'email' => $actual['email'],
                'firstname' => $actual['firstname'],
                'first_lastname' => 'Autoridad',
                'status' => $actual['status'],
                'organization' => $actual['organization'],
            ], $cambios);
        };

        echoTerminal('[a] F1-a · un usuario general cambia la contraseña de un principal');
        $nueva = bin2hex(random_bytes(12));
        $r = $pedir($editar, $general, $cuerpoEdicion('victima-root', ['password' => $nueva, 'password2' => $nueva]));
        $check(!$claveEs($usuarios['victima-root'], $nueva) && $claveEs($usuarios['victima-root'], $claves['victima-root']), 'a1 la contraseña del principal NO cambia', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[b] F1-b · un usuario general cambia el nombre, el correo y el estado de un principal');
        $antes = $fila($usuarios['victima-root']);
        $r = $pedir($editar, $general, $cuerpoEdicion('victima-root', ['username' => "{$prefijo}-tomado", 'email' => "{$prefijo}-tomado@example.com", 'status' => UsersModel::STATUS_USER_INACTIVE]));
        $check($fila($usuarios['victima-root']) === $antes, 'b1 la fila del principal NO cambia', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[c] F1-c · con is_profile=yes sobre OTRO usuario');
        $antes = $fila($usuarios['victima-root']);
        $r = $pedir($editar, $general, ['id' => $usuarios['victima-root'], 'is_profile' => 'yes', 'firstname' => 'Tomado', 'status' => $antes['status'], 'organization' => $antes['organization']]);
        $check($fila($usuarios['victima-root']) === $antes, 'c1 el perfil de otro NO se edita', "HTTP {$r['status']}");
        //Con permiso de formulario tampoco: el perfil es de uno mismo, y por él no se pasan las reglas de la edición.
        $antes = $fila($usuarios['general']);
        $r = $pedir($editar, $adminGeneral, ['id' => $usuarios['general'], 'is_profile' => 'yes', 'firstname' => 'PorPerfil', 'status' => $antes['status'], 'organization' => $antes['organization']]);
        $check($fila($usuarios['general']) === $antes, 'c2 ni siquiera quien puede editarlo lo hace por su perfil', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[d] F2 · un usuario general crea un principal');
        $clave = bin2hex(random_bytes(12));
        $r = $pedir($alta, $general, ['username' => "{$prefijo}-nuevo-root", 'email' => "{$prefijo}-nuevo-root@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Autoridad', 'type' => UsersModel::TYPE_USER_ROOT, 'status' => UsersModel::STATUS_USER_ACTIVE]);
        $creado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-nuevo-root"))->fetchColumn();
        $check($creado === 0, 'd1 el principal NO se crea', "HTTP {$r['status']}, creados {$creado}");
        $r = $pedir($alta, $adminGeneral, ['username' => "{$prefijo}-nuevo-root-2", 'email' => "{$prefijo}-nuevo-root-2@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Autoridad', 'type' => UsersModel::TYPE_USER_ROOT, 'status' => UsersModel::STATUS_USER_ACTIVE]);
        $creado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-nuevo-root-2"))->fetchColumn();
        $check($creado === 0, 'd2 tampoco lo crea un administrador general: no tiene autoridad sobre el principal', "HTTP {$r['status']}, creados {$creado}");

        echoTerminal('');
        echoTerminal('[e] La organización: el encargado de A no toca usuarios de B ni los mueve');
        $antes = $fila($usuarios['miembro-b']);
        $r = $pedir($editar, $encargadoA, $cuerpoEdicion('miembro-b', ['firstname' => 'Movido', 'organization' => $organizaciones['a']]));
        $check($fila($usuarios['miembro-b']) === $antes, 'e1 un miembro de B NO se edita ni pasa a A', "HTTP {$r['status']}");
        $antes = $fila($usuarios['miembro-a']);
        $r = $pedir($editar, $encargadoA, $cuerpoEdicion('miembro-a', ['organization' => $organizaciones['b']]));
        $check($fila($usuarios['miembro-a']) === $antes, 'e2 un miembro de A NO pasa a B', "HTTP {$r['status']}");
        $r = $pedir($alta, $encargadoA, ['username' => "{$prefijo}-alta-en-b", 'email' => "{$prefijo}-alta-en-b@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Autoridad', 'type' => UsersModel::TYPE_USER_GENERAL, 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => $organizaciones['b']]);
        $creado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-alta-en-b"))->fetchColumn();
        $check($creado === 0, 'e3 el encargado de A NO da de alta en B', "HTTP {$r['status']}, creados {$creado}");

        echoTerminal('');
        echoTerminal('[i] Los estados: solo los que ofrece cada formulario (lista blanca)');
        $antes = $fila($usuarios['general']);
        $r = $pedir($editar, $adminGeneral, $cuerpoEdicion('general', ['status' => 99]));
        $check($fila($usuarios['general']) === $antes, 'i1 la edición NO acepta un estado fuera del formulario (99)', "HTTP {$r['status']}");
        $r = $pedir($alta, $adminGeneral, ['username' => "{$prefijo}-estado-raro", 'email' => "{$prefijo}-estado-raro@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Autoridad', 'type' => UsersModel::TYPE_USER_GENERAL, 'status' => 99, 'organization' => $global]);
        $creado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-estado-raro"))->fetchColumn();
        $check($creado === 0, 'i2 el alta tampoco (99)', "HTTP {$r['status']}, creados {$creado}");

        echoTerminal('');
        echoTerminal('[j] Un tipo sin rol no se edita por POST, como no se abre por GET');
        $check(!Roles::roleExists(UsersModel::TYPE_USER_GOOGLE_PLAY), 'j0 el tipo de la prueba no tiene rol');
        $antes = $fila($usuarios['sin-rol']);
        $r = $pedir($editar, $adminGeneral, $cuerpoEdicion('sin-rol', ['firstname' => 'SinRol']));
        $check($fila($usuarios['sin-rol']) === $antes, 'j1 el usuario de un tipo sin rol NO se edita', "HTTP {$r['status']}");

        echoTerminal('');
        echoTerminal('[k] El núcleo del alta: ninguna ruta apunta a él, y la API pública sí llega');
        $apuntan = array_filter(get_routes(), fn ($ruta) => is_string($ruta['controller'] ?? null) && str_ends_with((string) $ruta['controller'], ':createUserFromRequest'));
        $check(count($apuntan) === 0, 'k1 ninguna ruta apunta a createUserFromRequest', implode(', ', array_keys($apuntan)));
        //Sin organización el alta falla ANTES de guardar y de enviar correo: lo que se mide es que llega al núcleo.
        $api = $pedir($camino(APIController::routeName('users-actions', ['actionType' => 'register'], true)), null, [
            'email' => "{$prefijo}-api@mailinator.com", 'password' => 'zz-Clave-Api-1', 'passwordConfirm' => 'zz-Clave-Api-1',
            'firstName' => 'Zz', 'firstLastName' => 'Api', 'phoneCode' => '+57', 'phoneNumber' => '3000000000',
            'userType' => BaseHashEncryption::encryptBidirectionalHash((string) UsersModel::TYPE_USER_GENERAL),
        ]);
        $cuerpoApi = json_decode($api['body'], true);
        $mensajeApi = is_array($cuerpoApi) ? (string) ($cuerpoApi['message'] ?? '') : '';
        $check($mensajeApi === __(UsersController::LANG_GROUP, 'Debe seleccionar una organización.'), 'k2 sin sesión, el alta pública llega al núcleo y responde su validación', "HTTP {$api['status']}: " . mb_substr($mensajeApi, 0, 160));

        echoTerminal('');
        echoTerminal('[f] Los canarios: quien SÍ tiene autoridad, edita y crea');
        $r = $pedir($editar, $adminGeneral, $cuerpoEdicion('general', ['firstname' => 'Editado']));
        $check(($fila($usuarios['general'])['firstname'] ?? '') === 'Editado', 'f1 CANARIO: el administrador general edita a un usuario general', "HTTP {$r['status']}");
        $r = $pedir($editar, $encargadoA, $cuerpoEdicion('miembro-a', ['firstname' => 'EditadoA']));
        $check(($fila($usuarios['miembro-a'])['firstname'] ?? '') === 'EditadoA', 'f2 CANARIO: el encargado de A edita a un miembro de A', "HTTP {$r['status']}");
        $r = $pedir($alta, $encargadoA, ['username' => "{$prefijo}-alta-en-a", 'email' => "{$prefijo}-alta-en-a@example.com", 'password' => $clave, 'password2' => $clave, 'firstname' => 'Zz', 'first_lastname' => 'Autoridad', 'type' => UsersModel::TYPE_USER_GENERAL, 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => $organizaciones['a']]);
        $creado = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username = " . $database->quote("{$prefijo}-alta-en-a"))->fetchColumn();
        $check($creado === 1, 'f3 CANARIO: el encargado de A da de alta en A', "HTTP {$r['status']}, creados {$creado}");

        echoTerminal('');
        echoTerminal('[h] F3 · las publicaciones no activas, solo de la propia organización');
        $categoria = $database->query('SELECT id FROM `' . \Publications\Mappers\PublicationCategoryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        foreach (['borrador-a' => ['comunicaciones-a', PublicationMapper::DRAFT], 'borrador-b' => ['autor-b', PublicationMapper::DRAFT], 'activa-b' => ['autor-b', PublicationMapper::ACTIVE]] as $sufijo => [$autor, $estado]) {
            $database->prepare("INSERT INTO `{$tablaPublicaciones}` (title, content, seoDescription, author, category, mainImage, thumbImage, ogImage, folder, visits, publicDate, createdAt, createdBy, status, featured, meta)"
                . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, 0, ?)')
                ->execute(["{$prefijo}-{$sufijo}", 'zz-', 'zz-', $usuarios[$autor], (int) $categoria, '', '', '', "{$prefijo}-{$sufijo}", $ahora, $ahora, $usuarios[$autor], $estado, json_encode(['baseLang' => 'es', 'langData' => new \stdClass])]);
            $publicaciones[$sufijo] = (int) $database->lastInsertId();
            //El listado exige la aprobación (BaseEntityMapper::fieldsToSelect); sembrada por SQL, la publicación no la tiene.
            $database->prepare('INSERT INTO `' . SystemApprovalsMapper::TABLE . '` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES (?, ?, ?, ?, ?, ?, ?)')
                ->execute(["{$prefijo}-{$sufijo}", (string) $publicaciones[$sufijo], $tablaPublicaciones, $ahora, $ahora, $usuarios[$autor], SystemApprovalsMapper::STATUS_APPROVED]);
        }
        $todas = $camino(PublicationsController::routeName('ajax-all', [], true)) . '?status=ANY&per_page=1000';
        $deA = $pedir($todas, $comunicacionesA);
        $check($deA['status'] === 200 && str_contains($deA['body'], "{$prefijo}-borrador-a"), 'h1 CANARIO: el de A ve el borrador de A', "HTTP {$deA['status']}");
        $check(str_contains($deA['body'], "{$prefijo}-activa-b"), 'h2 CANARIO: y la activa de B, que es pública');
        $check(!str_contains($deA['body'], "{$prefijo}-borrador-b"), 'h3 NO ve el borrador de B');
        $sin = $pedir($todas, $comunicacionesSinOrganizacion);
        $check($sin['status'] === 200 && !str_contains($sin['body'], "{$prefijo}-borrador-"), 'h4 sin organización, ningún borrador', "HTTP {$sin['status']}");
        $check(str_contains($sin['body'], "{$prefijo}-activa-b"), 'h5 CANARIO: pero sí lo activo');
        $deGeneral = $pedir($todas, $adminGeneral);
        $check(str_contains($deGeneral['body'], "{$prefijo}-borrador-a") && str_contains($deGeneral['body'], "{$prefijo}-borrador-b"), 'h6 CANARIO: el administrador general ve los dos borradores');
        //La vista individual, con el slug que el propio mapper construye (el token se puede fabricar).
        $vista = fn (string $sufijo): string => $camino(PublicationsPublicController::routeName('single', ['slug' => (new PublicationMapper($publicaciones[$sufijo] ?? null))->getSlug()], true));
        $propio = $pedir($vista('borrador-a'), $comunicacionesA);
        $check($propio['status'] === 200, 'h7 CANARIO: el de A abre su borrador', "HTTP {$propio['status']}");
        $ajeno = $pedir($vista('borrador-b'), $comunicacionesA);
        $check($ajeno['status'] === 404 && !str_contains($ajeno['body'], "{$prefijo}-borrador-b"), 'h8 NO abre el borrador de B', "HTTP {$ajeno['status']}");
        $sinOrg = $pedir($vista('borrador-a'), $comunicacionesSinOrganizacion);
        $check($sinOrg['status'] === 404, 'h9 sin organización, ningún borrador', "HTTP {$sinOrg['status']}");
        $activa = $pedir($vista('activa-b'), null);
        $check($activa['status'] === 200, 'h10 CANARIO: lo activo y público lo abre cualquiera', "HTTP {$activa['status']}");
        $general2 = $pedir($vista('borrador-b'), $adminGeneral);
        $check($general2['status'] === 200, 'h11 CANARIO: el administrador general abre el borrador de B', "HTTP {$general2['status']}");
        echoTerminal('');
        echoTerminal('[g] Uno mismo, con permiso de formulario: su perfil, con su contraseña actual (al final: cambia su contraseña)');
        $propia = bin2hex(random_bytes(12));
        $perfil = ['id' => $usuarios['admin-general'], 'is_profile' => 'yes', 'firstname' => 'Propio', 'status' => UsersModel::STATUS_USER_ACTIVE, 'organization' => $global];
        $r = $pedir($editar, $adminGeneral, array_merge($perfil, ['password' => $propia, 'password2' => $propia, 'current-password' => 'no-es-la-suya']));
        $check(!$claveEs($usuarios['admin-general'], $propia), 'g1 sin su contraseña actual NO cambia la suya', "HTTP {$r['status']}");
        //El cuerpo entero de la edición, sin is_profile y sin la actual: la que se exige es la misma.
        $r = $pedir($editar, $adminGeneral, $cuerpoEdicion('admin-general', ['password' => $propia, 'password2' => $propia]));
        $check(!$claveEs($usuarios['admin-general'], $propia), 'g2 sin is_profile tampoco: la actual se exige siempre', "HTTP {$r['status']}");
        $r = $pedir($editar, $adminGeneral, array_merge($perfil, ['password' => $propia, 'password2' => $propia, 'current-password' => $claves['admin-general']]));
        $check($claveEs($usuarios['admin-general'], $propia), 'g3 CANARIO: con su contraseña actual, cambia la suya', "HTTP {$r['status']}");
        $antes = $fila($usuarios['admin-general']);
        $r = $pedir($editar, $adminGeneral, array_merge($perfil, ['status' => UsersModel::STATUS_USER_INACTIVE]));
        $check(($fila($usuarios['admin-general'])['status'] ?? null) === $antes['status'], 'g4 su perfil no le cambia el estado', "HTTP {$r['status']}");

    } finally {
        echoTerminal(' ');
        echoTerminal('[z] Limpieza');
        $usuariosCreados = $database->query("SELECT id FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchAll(\PDO::FETCH_COLUMN);
        try {
            $aprobaciones = SystemApprovalsMapper::model();
            foreach ([[$tablaPublicaciones, $publicaciones], [$tablaOrganizaciones, $organizaciones], [$tablaUsuarios, $usuariosCreados]] as [$tablaReferencia, $ids]) {
                foreach ($ids as $id) {
                    $aprobaciones->resetAll();
                    //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                    $aprobaciones->delete(new WhereSegment([
                        new WhereItem('referenceTable', WhereItem::EQUAL_OPERATOR, $tablaReferencia),
                        new WhereItem('referenceValue', WhereItem::EQUAL_OPERATOR, (string) $id, WhereItem::AND_OPERATOR),
                    ]))->execute();
                }
            }
            foreach ($publicaciones as $id) {
                $database->exec("DELETE FROM `{$tablaPublicaciones}` WHERE id = " . (int) $id);
            }
            foreach ($usuariosCreados as $id) {
                $perfiles = UserProfileMapper::model();
                $perfiles->resetAll();
                //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
                $perfiles->delete(['belongsTo' => (int) $id])->execute();
            }
            //Las claves van en los dos sentidos (el usuario a su organización, la organización a su creador): los usuarios
            //pasan a la global, caen las organizaciones y después los usuarios.
            $database->exec("UPDATE `{$tablaUsuarios}` SET organization = " . OrganizationMapper::INITIAL_ID_GLOBAL . ' WHERE username LIKE ' . $database->quote("{$prefijo}%"));
            foreach ($organizaciones as $id) {
                $database->exec("DELETE FROM `{$tablaOrganizaciones}` WHERE id = " . (int) $id);
            }
            $modeloUsuarios = UsersModel::model();
            $modeloUsuarios->resetAll();
            //RETORNO-IGNORADO: lo comprueba z1 contando lo que queda.
            $modeloUsuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        } catch (\Throwable $e) {
            $check(false, 'z0 la limpieza corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
        }
        $quedan = (int) $database->query("SELECT COUNT(*) FROM `{$tablaUsuarios}` WHERE username LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaOrganizaciones}` WHERE name LIKE " . $database->quote("{$prefijo}%"))->fetchColumn()
            + (int) $database->query("SELECT COUNT(*) FROM `{$tablaPublicaciones}` WHERE title LIKE " . $database->quote("{$prefijo}%"))->fetchColumn();
        $check($quedan === 0, 'z1 no queda ningún usuario, organización ni publicación de la prueba', "quedan {$quedan}");
    }

    return $balance();

})->setDescription('Un POST no concede más que su GET: editar o crear usuarios exige la autoridad y la organización del formulario, uno mismo solo cambia su contraseña con la actual, y las publicaciones no activas solo se ven en su organización. Por HTTP.')->setEffects([CliActions::EFFECT_DATABASE])->register();
