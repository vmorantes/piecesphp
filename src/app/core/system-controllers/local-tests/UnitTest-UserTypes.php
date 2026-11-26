<?php

//Los tipos de usuario (UserTypes, ADR 0078 y 0090): fábrica, alias, roles, jerarquía, perfiles con la lista vacía y
//los de fábrica ajustables. En proceso; escribe solo en una transacción que se deshace (j11-j13).
//@areas nucleo

use Directory\Controllers\AllProfilesController;
use MySpace\Controllers\Util\ProfileTasksUtilities;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserTypes;
use PiecesPHP\UserSystem\UserTypesAlert;
use ReportsManage\Queries\ReportsManageQueries;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;
use Slim\Psr7\Headers;
use SystemApprovals\Util\Packages\UsersApprovalHandler;

CliActions::make('unit-tests:core/user-types', function ($args) {

    echoTerminal("\e[33m[TEST:UserTypes] Los tipos de usuario: valores de fábrica, alias, roles, jerarquía y perfiles\e[39m");
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
    $show = fn(mixed $value): string => (string) json_encode($value, \JSON_UNESCAPED_UNICODE);

    //──── a) Los valores de fábrica, los de antes de UserTypes ──────────────────────────────────
    echoTerminal("\e[36m a) Cada CORE_* trae lo que traían las listas de UsersModel en a50e8470\e[39m");

    $before = [
        'CORE_LABELS' => [0 => 'Principal', 1 => 'Administrador general', 12 => 'Administrador de organización', 2 => 'Usuario general', 3 => 'Institucional', 4 => 'Comunicaciones'],
        'CORE_PRIORITY' => [0 => 500, 1 => 400, 12 => 1, 2 => 1, 3 => 100, 4 => 2, 50 => -50],
        'CORE_NO_AUTHORITY_OVER_SAME_TYPE' => [12],
        'CORE_NO_ORGANIZATION' => [0, 1, 50],
        'CORE_PROFILE' => [12, 2],
        'CORE_EXTERNAL_LOGIN' => [2, 50],
        'CORE_AUTO_APPROVAL' => [0, 1, 3, 4],
    ];
    $now = [
        'CORE_LABELS' => UserTypes::CORE_LABELS,
        'CORE_PRIORITY' => UserTypes::CORE_PRIORITY,
        'CORE_NO_AUTHORITY_OVER_SAME_TYPE' => UserTypes::CORE_NO_AUTHORITY_OVER_SAME_TYPE,
        'CORE_NO_ORGANIZATION' => UserTypes::CORE_NO_ORGANIZATION,
        'CORE_PROFILE' => UserTypes::CORE_PROFILE,
        'CORE_EXTERNAL_LOGIN' => UserTypes::CORE_EXTERNAL_LOGIN,
        'CORE_AUTO_APPROVAL' => UserTypes::CORE_AUTO_APPROVAL,
    ];
    foreach ($before as $constant => $value) {
        $check($now[$constant] === $value, "a {$constant} es el de antes, con su orden", $show($now[$constant]));
    }
    $check(UserTypes::PROTECTED === [0, 1] && UserTypes::RESERVED === [99, 50], 'a PROTECTED es [root, administración general] y RESERVED es [99, 50]');

    //──── b) Los alias de UsersModel ────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m b) Las listas de UsersModel son alias idénticos, hasta la 11.0.0\e[39m");

    //Por nombre, con constant(): el alias se prueba a propósito, y leerlo escrito daría su aviso de desuso.
    $aliases = [
        'TYPES_USERS' => UserTypes::CORE_LABELS,
        'TYPES_USER_PRIORITY' => UserTypes::CORE_PRIORITY,
        'TYPES_USER_DO_NOT_HAVE_AUTHORITY_OVER_SAME_TYPE' => UserTypes::CORE_NO_AUTHORITY_OVER_SAME_TYPE,
        'TYPES_USER_DONT_REQUIRE_ORGANIZATION' => UserTypes::CORE_NO_ORGANIZATION,
        'TYPES_USER_SHOULD_HAVE_PROFILE' => UserTypes::CORE_PROFILE,
        'TYPES_WITH_EXTERNAL_LOGIN' => UserTypes::CORE_EXTERNAL_LOGIN,
        'ARE_AUTO_APPROVAL' => UserTypes::CORE_AUTO_APPROVAL,
    ];
    foreach ($aliases as $alias => $coreValue) {
        $check(constant(UsersModel::class . '::' . $alias) === $coreValue, "b UsersModel::{$alias} es idéntico a su CORE_*");
    }

    //En un proyecto, user-types.php declara sus propios tipos (ADR 0078): lo cargado se guarda para d y h16, y c a f
    //miden la fábrica, que la suite siembra con un archivo vacío.
    $roles = get_config('roles');
    $registrados = is_array($roles) ? ($roles['types'] ?? null) : null;
    $segmentosReales = is_array($roles) ? ($roles['baseInitialSegmentedPermissions'] ?? null) : null;
    $rolesCargados = is_array($segmentosReales) ? UserTypes::roles($segmentosReales) : null;
    $cargados = ['labels' => UserTypes::labels(), 'errors' => UserTypes::errors()];
    UserTypes::fromArray([]);

    //──── c) Los métodos, para los siete tipos ──────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m c) codes(), labels(), priorities() y los métodos de cada tipo\e[39m");

    $check(UserTypes::codes() === [0, 1, 12, 2, 3, 4], 'c codes() son los seis con etiqueta, en su orden', $show(UserTypes::codes()));
    $check(UserTypes::labels() === $before['CORE_LABELS'], 'c labels() son las etiquetas de fábrica');
    $check(UserTypes::priorities() === $before['CORE_PRIORITY'], 'c priorities() son las de fábrica, también la del 50');
    $check(UserTypes::profileTypes() === [12, 2], 'c profileTypes() son el administrador de organización y el general');
    //Tipo: prioridad, organización, perfil, acceso externo, autoridad sobre el mismo tipo, autoaprobación, protegido.
    $table = [
        0 => [500, false, false, false, true, true, true],
        1 => [400, false, false, false, true, true, true],
        12 => [1, true, true, false, false, false, false],
        2 => [1, true, true, true, true, false, false],
        3 => [100, true, false, false, true, true, false],
        4 => [2, true, false, false, true, true, false],
        50 => [-50, false, false, true, true, false, false],
    ];
    foreach ($table as $type => $expected) {
        $got = [
            UserTypes::priority($type),
            UserTypes::requiresOrganization($type),
            UserTypes::shouldHaveProfile($type),
            UserTypes::hasExternalLogin($type),
            UserTypes::hasAuthorityOverSameType($type),
            UserTypes::isAutoApproval($type),
            UserTypes::isProtected($type),
        ];
        $check($got === $expected, "c el tipo {$type}: prioridad, organización, perfil, acceso externo, autoridad sobre el mismo tipo, autoaprobación y protegido", $show($got));
    }
    $check(UserTypes::priority(99) === null && UserTypes::requiresOrganization(99), 'c un tipo sin registrar no tiene prioridad y requiere organización');

    //──── d) Los roles ──────────────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m d) roles() construye las entradas que escribía roles.php\e[39m");

    $segments = ['generals' => ['g'], 'administratives' => ['g', 'a'], 'superiors' => ['g', 'a', 's']];
    $entry = fn(int $code, string $name, array $routes): array => ['code' => $code, 'name' => $name, 'all' => false, 'allowed_routes' => $routes];
    $expectedRoles = [
        0 => $entry(0, 'Principal', ['g', 'a', 's']),
        1 => $entry(1, 'Administrador general', ['g', 'a']),
        2 => $entry(12, 'Administrador de organización', ['g']),
        3 => $entry(2, 'Usuario general', ['g']),
        4 => $entry(3, 'Institucional', ['g']),
        5 => $entry(4, 'Comunicaciones', ['g']),
    ];
    if (!CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE']) {
        unset($expectedRoles[2]);
    }
    $check(UserTypes::roles($segments) === $expectedRoles, 'd cada tipo con etiqueta, su segmento y las claves de antes (el 50 sin rol)', $show(UserTypes::roles($segments)));
    $check($rolesCargados !== null && $registrados === $rolesCargados, 'd y roles.php registró los roles de los tipos cargados con sus segmentos reales');

    //──── e) La jerarquía por prioridad ─────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m e) getHigherPriorityTypes() no pierde los tipos con la misma prioridad\e[39m");

    $higher = [
        0 => [],
        1 => [0],
        12 => [0, 1, 3, 4],
        2 => [0, 1, 3, 4],
        3 => [0, 1],
        4 => [0, 1, 3],
        //Antes [0, 1, 2, 2, 3, 4]: el array_flip de las prioridades se comía el 12, que comparte la 1 con el 2.
        50 => [0, 1, 12, 2, 3, 4],
    ];
    foreach ($higher as $type => $expected) {
        $user = new UsersModel();
        $user->id = 999999;
        $user->type = $type;
        $got = $user->getHigherPriorityTypes();
        $check($got === $expected, "e el tipo {$type} tiene por encima " . $show($expected), $show($got));
    }
    $check((new UsersModel())->getHigherPriorityTypes() === UserTypes::codes(), 'e sin usuario, todos los tipos con etiqueta');

    //──── f) Los perfiles con la lista vacía ────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m f) La consulta de los usuarios sin perfil no lanza con la lista vacía\e[39m");

    $usersWithoutProfile = new \ReflectionMethod(ProfileTasksUtilities::class, 'usersWithoutProfile');
    $empty = null;
    $error = '';
    try {
        $empty = $usersWithoutProfile->invoke(null, []);
    } catch (\Throwable $e) {
        $error = get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200);
    }
    $check($empty === [], 'f con la lista vacía devuelve [] sin consultar', $error);
    $error = '';
    $withTypes = null;
    try {
        $withTypes = $usersWithoutProfile->invoke(null, UserTypes::profileTypes());
    } catch (\Throwable $e) {
        $error = get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200);
    }
    $check(is_array($withTypes), 'f con los tipos de perfil consulta y devuelve una lista', $error);

    //──── g) Las reglas de user-types.php, una por una ──────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m g) Cada regla de user-types.php: con un error no se aplica nada y rigen los de fábrica\e[39m");

    $own = ['id' => 5, 'label' => 'zz-Telemarketing', 'priority' => 3];
    //Regla => [contenido, un trozo del error que la nombra].
    $rules = [
        'g1 forma: clave de primer nivel desconocida' => [['tipos' => []], 'clave desconocida «tipos»'],
        'g2 forma: types no es una lista' => [['types' => ['a' => $own]], 'types tiene que ser una lista'],
        'g3 forma: retire no es una lista' => [['retire' => 5], 'retire tiene que ser una lista'],
        'g4 forma: un tipo que no es un array' => [['types' => ['zz']], 'no es un tipo'],
        'g5 id: no entero' => [['types' => [['id' => '5'] + $own]], 'id tiene que ser un entero no negativo'],
        'g6 id: negativo' => [['types' => [['id' => -1] + $own]], 'id tiene que ser un entero no negativo'],
        'g7 id: repetido' => [['types' => [$own, ['label' => 'zz-Otra'] + $own]], 'está declarado dos veces'],
        'g8 id: el 99, reservado' => [['types' => [['id' => 99] + $own]], 'del usuario del sistema'],
        'g9 protegido: priority en root' => [['types' => [['id' => 0, 'priority' => 9]]], 'el tipo 0 está protegido y solo admite label, translations, description, descriptionTranslations, creatable, profile, routes («priority»)'],
        'g10 protegido: autoApproval en la administración general' => [['types' => [['id' => 1, 'autoApproval' => false]]], 'el tipo 1 está protegido y solo admite'],
        'g11 retire: en types y en retire a la vez' => [['types' => [$own], 'retire' => [5]], 'en types y en retire a la vez'],
        'g12 retire: un protegido' => [['retire' => [0]], 'está protegido'],
        'g13 retire: un reservado' => [['retire' => [50]], 'está reservado'],
        'g14 etiqueta: vacía' => [['types' => [['label' => '  '] + $own]], 'label tiene que ser un texto no vacío'],
        'g15 etiqueta: repetida contra una de fábrica' => [['types' => [['label' => 'principal'] + $own]], 'se repite'],
        'g16 etiqueta: numérica' => [['types' => [['label' => '7'] + $own]], 'no puede ser un número'],
        'g17 prioridad: falta' => [['types' => [['id' => 5, 'label' => 'zz-Telemarketing']]], 'priority es obligatoria'],
        'g18 prioridad: 500 o más' => [['types' => [['priority' => 500] + $own]], 'tiene que ser menor que 500'],
        'g19 permissions: no es una de las tres listas' => [['types' => [['permissions' => 'todas'] + $own]], 'permissions tiene que ser'],
        'g20 routes: no es una lista de cadenas' => [['types' => [['routes' => ['admin', 3]] + $own]], 'routes tiene que ser'],
    ];
    foreach ($rules as $name => [$data, $fragment]) {
        UserTypes::fromArray($data);
        $errors = implode(' | ', UserTypes::errors());
        $check(str_contains($errors, $fragment) && UserTypes::labels() === UserTypes::CORE_LABELS && UserTypes::retired() === [], "{$name}: se rechaza y rigen los de fábrica", $errors);
    }
    UserTypes::fromArray(['types' => [['priority' => 450] + $own]]);
    $check(UserTypes::errors() === [] && str_contains(implode(' | ', UserTypes::warnings()), 'priority 450') && UserTypes::priority(5) === 450, 'g21 prioridad desde 400: se aplica, con aviso', implode(' | ', UserTypes::errors()));
    //Lo bueno de un archivo con un error tampoco se aplica: nada a medias.
    UserTypes::fromArray(['types' => [$own, ['id' => 3, 'creatable' => false]], 'retire' => [0]]);
    $check(!in_array(5, UserTypes::codes(), true) && UserTypes::isCreatable(3), 'g22 con un error, tampoco se aplica lo bueno del mismo archivo');

    //──── h) Lo que se aplica ───────────────────────────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m h) Un archivo bueno: el tipo propio, la etiqueta nueva, creatable y retire\e[39m");

    UserTypes::fromArray([
        'types' => [
            ['id' => 5, 'label' => 'zz-Telemarketing', 'translations' => ['en' => 'zz-Telemarketing EN'], 'description' => 'zz-Llama a los clientes', 'descriptionTranslations' => ['en' => 'zz-Calls the customers'], 'priority' => 3, 'routes' => ['zz-ruta']],
            ['id' => 2, 'label' => 'zz-Contenido', 'translations' => ['en' => 'zz-Content']],
            ['id' => 3, 'creatable' => false],
            ['id' => 6, 'label' => 'zz-Supervisión', 'priority' => 350, 'organization' => false, 'profile' => true, 'externalLogin' => true, 'authorityOverSameType' => false, 'autoApproval' => true, 'permissions' => 'administratives'],
        ],
        'retire' => [4, 77],
    ]);
    $check(UserTypes::errors() === [], 'h1 el archivo bueno no da errores', implode(' | ', UserTypes::errors()));
    $check(UserTypes::codes() === [0, 1, 12, 2, 3, 5, 6], 'h2 codes(): los de fábrica en su orden, sin el retirado, y los propios detrás', $show(UserTypes::codes()));
    $check(UserTypes::labels()[2] === 'zz-Contenido' && UserTypes::labels()[5] === 'zz-Telemarketing', 'h3 la etiqueta nueva del 2 y la del tipo propio');
    $check(UserTypes::priority(5) === 3 && UserTypes::priority(4) === null && UserTypes::priority(50) === -50, 'h4 priorities(): la del propio, ninguna del retirado, y la del 50 apagado');
    $got5 = [UserTypes::requiresOrganization(5), UserTypes::shouldHaveProfile(5), UserTypes::hasExternalLogin(5), UserTypes::hasAuthorityOverSameType(5), UserTypes::isAutoApproval(5), UserTypes::isCreatable(5)];
    $check($got5 === [true, false, false, true, false, true], 'h5 el tipo propio sin declarar nada más: los valores por omisión de la guía', $show($got5));
    $got6 = [UserTypes::requiresOrganization(6), UserTypes::shouldHaveProfile(6), UserTypes::hasExternalLogin(6), UserTypes::hasAuthorityOverSameType(6), UserTypes::isAutoApproval(6)];
    $check($got6 === [false, true, true, false, true] && UserTypes::profileTypes() === [12, 2, 6], 'h6 el tipo propio con todo declarado', $show($got6));
    $check(!UserTypes::isCreatable(3) && UserTypes::isCreatable(2) && !UserTypes::isCreatable(4) && !UserTypes::isCreatable(50), 'h7 isCreatable(): no el 3 (creatable false), sí el 2, no el retirado ni el 50 apagado');
    $check(UserTypes::description(5) === 'zz-Llama a los clientes' && UserTypes::description(2) === null, 'h8 description(): la declarada, o null');
    $check(UserTypes::retired() === [4, 77], 'h9 retired(): también un código propio que ya no se declara');
    $rolesH = UserTypes::roles($segments);
    $byCode = [];
    foreach ($rolesH as $role) {
        $byCode[$role['code']] = $role;
    }
    $check(!isset($byCode[4]) && ($byCode[5]['allowed_routes'] ?? null) === ['g', 'zz-ruta'] && ($byCode[6]['allowed_routes'] ?? null) === ['g', 'a'] && ($byCode[2]['name'] ?? null) === 'zz-Contenido', 'h10 roles(): sin el retirado, el propio con su segmento y sus rutas, y el 2 con su nombre nuevo', $show($byCode[5] ?? null));
    $check(array_keys($rolesH) === (CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE'] ? [0, 1, 2, 3, 4, 7, 8] : [0, 1, 3, 4, 7, 8]), 'h11 roles(): las claves de los de fábrica no se mueven y los propios van detrás', $show(array_keys($rolesH)));
    $missing = UserTypes::missingTranslations(['es', 'en', 'fr']);
    $expectedMissing = [
        'el tipo 2: la etiqueta «zz-Contenido» no tiene traducción a «fr»',
        'el tipo 5: la etiqueta «zz-Telemarketing» no tiene traducción a «fr»',
        'el tipo 5: la descripción no tiene traducción a «fr»',
        'el tipo 6: la etiqueta «zz-Supervisión» no tiene traducción a «en»',
        'el tipo 6: la etiqueta «zz-Supervisión» no tiene traducción a «fr»',
    ];
    $check($missing === $expectedMissing, 'h12 missingTranslations(): las que faltan, sin contar el español ni las etiquetas de fábrica sin tocar', $show($missing));
    $conflicts = UserTypes::moduleConflicts();
    $check(defined('PUBLICATIONS_MODULE') && PUBLICATIONS_MODULE === true ? str_contains(implode(' | ', $conflicts), 'PUBLICATIONS_MODULE') : $conflicts === [], 'h13 moduleConflicts(): retirar el 4 con Publicaciones encendido avisa', $show($conflicts));
    UserTypes::fromArray(['types' => [['id' => 50, 'label' => 'zz-Google Play']]]);
    $check(UserTypes::errors() === [] && in_array(50, UserTypes::codes(), true) && UserTypes::hasExternalLogin(50), 'h14 declarar el 50 con su etiqueta lo enciende, con lo suyo de fábrica');
    UserTypes::fromArray(['types' => [['id' => 50, 'creatable' => false]]]);
    $check(str_contains(implode(' | ', UserTypes::errors()), 'label tiene que ser'), 'h15 el 50 sin etiqueta no se enciende: es un error');

    //──── i) Los de fábrica, ajustables (ADR 0090) ─────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m i) Los de fábrica que se retiran admiten lo de uno propio; root y la administración general, solo lo que añade\e[39m");

    //Atributo => [el ajuste sobre el institucional (3), lo que devuelve su método con él]; de fábrica, el contrario.
    $adjustable = [
        'priority' => [7, fn(): mixed => UserTypes::priority(3)],
        'organization' => [false, fn(): mixed => UserTypes::requiresOrganization(3)],
        'profile' => [true, fn(): mixed => UserTypes::shouldHaveProfile(3) && in_array(3, UserTypes::profileTypes(), true)],
        'externalLogin' => [true, fn(): mixed => UserTypes::hasExternalLogin(3)],
        'authorityOverSameType' => [false, fn(): mixed => UserTypes::hasAuthorityOverSameType(3)],
        'autoApproval' => [false, fn(): mixed => UserTypes::isAutoApproval(3)],
    ];
    foreach ($adjustable as $key => [$value, $read]) {
        UserTypes::fromArray([]);
        $factoryValue = $read();
        UserTypes::fromArray(['types' => [['id' => 3, $key => $value]]]);
        $got = $read();
        $check(UserTypes::errors() === [] && $got === $value && $factoryValue !== $value, "i1 {$key} en el 3 cambia lo que devuelve su método ({$show($factoryValue)} → {$show($got)})", implode(' | ', UserTypes::errors()));
    }
    UserTypes::fromArray(['types' => [['id' => 3, 'permissions' => 'administratives', 'routes' => ['zz-ruta']]]]);
    $byCode = array_column(UserTypes::roles($segments), null, 'code');
    $check(UserTypes::errors() === [] && ($byCode[3]['allowed_routes'] ?? null) === ['g', 'a', 'zz-ruta'], 'i2 permissions y routes en el 3: su rol, con el segmento declarado y sus rutas', $show($byCode[3] ?? null));
    UserTypes::fromArray(['types' => [['id' => 2, 'priority' => 450]]]);
    $check(str_contains(implode(' | ', UserTypes::errors()), 'el tipo 2: priority tiene que ser menor que 400 en un tipo de fábrica') && UserTypes::priority(2) === 1, 'i3 priority desde 400 en uno de fábrica: error (precisión 3), y rigen los de fábrica');
    UserTypes::fromArray(['types' => [['id' => 3, 'priority' => 500]]]);
    $check(str_contains(implode(' | ', UserTypes::errors()), 'el tipo 3: priority tiene que ser menor que 500') && UserTypes::priority(3) === 100 && UserTypes::isAutoApproval(3), 'i4 priority 500 en el 3: error, y rigen los de fábrica');
    UserTypes::fromArray(['types' => [['id' => 3, 'label' => 'zz-Institucional']]]);
    $check(UserTypes::errors() === [] && UserTypes::priority(3) === 100, 'i5 sin priority, uno de fábrica conserva la suya: no es obligatoria');
    UserTypes::fromArray(['types' => [['id' => 50, 'label' => 'zz-Google Play', 'priority' => 3, 'externalLogin' => false]]]);
    $check(UserTypes::errors() === [] && UserTypes::priority(50) === 3 && !UserTypes::hasExternalLogin(50), 'i6 el 50, encendido con su etiqueta, también se ajusta', implode(' | ', UserTypes::errors()));
    UserTypes::fromArray(['types' => [['id' => 50, 'priority' => 3]]]);
    $check(str_contains(implode(' | ', UserTypes::errors()), 'label tiene que ser'), 'i7 el 50 sin etiqueta sigue sin encenderse, aunque declare otra cosa');
    //Lo que root y la administración general no admiten, uno por uno: cada uno es un error y rigen los de fábrica.
    foreach ([0, 1] as $code) {
        foreach (['priority' => 9, 'organization' => true, 'externalLogin' => true, 'authorityOverSameType' => false, 'autoApproval' => false, 'permissions' => 'generals'] as $key => $value) {
            UserTypes::fromArray(['types' => [['id' => $code, 'label' => 'zz-Protegido', $key => $value]]]);
            $check(str_contains(implode(' | ', UserTypes::errors()), "el tipo {$code} está protegido y solo admite") && UserTypes::labels() === UserTypes::CORE_LABELS && UserTypes::priority($code) === UserTypes::CORE_PRIORITY[$code], "i8 {$key} en el {$code}: error, y rigen los de fábrica (tampoco su etiqueta nueva)");
        }
    }
    UserTypes::fromArray(['types' => [['id' => 1, 'profile' => true, 'routes' => ['zz-ruta-admin']], ['id' => 0, 'creatable' => false]]]);
    $byCode = array_column(UserTypes::roles($segments), null, 'code');
    $check(UserTypes::errors() === [] && UserTypes::shouldHaveProfile(1) && ($byCode[1]['allowed_routes'] ?? null) === ['g', 'a', 'zz-ruta-admin'] && UserTypes::priority(1) === 400 && UserTypes::isAutoApproval(1) && !UserTypes::isCreatable(0), 'i9 profile y routes en el 1 se aplican (las rutas, en su rol) y root puede no crearse', implode(' | ', UserTypes::errors()) . ' ' . $show($byCode[1] ?? null));
    foreach ([['id' => 3, 'all' => true], ['id' => 1, 'all' => true], ['id' => 0, 'all' => true], ['all' => true] + $own] as $entryAll) {
        UserTypes::fromArray(['types' => [$entryAll]]);
        $rolesAll = array_column(UserTypes::roles($segments), 'all');
        $check(str_contains(implode(' | ', UserTypes::errors()), '«all»') && !in_array(true, $rolesAll, true), "i10 'all' en el tipo {$entryAll['id']}: error, y ningún rol con todas las rutas");
    }

    //El aviso de un ajuste que choca con un módulo encendido (ADR 0090 §6), con el módulo encendido y apagado.
    $moduleWarnings = new \ReflectionMethod(UserTypes::class, 'moduleWarnings');
    UserTypes::fromArray(['types' => [['id' => 12, 'organization' => false]]]);
    $on = $moduleWarnings->invoke(null, true);
    $off = $moduleWarnings->invoke(null, false);
    $check(UserTypes::errors() === [] && count($on) === 1 && str_contains($on[0], 'ORGANIZATIONS_MODULE') && $off === [], 'i11 el 12 sin organización: aviso con el módulo encendido, ninguno con él apagado', $show([$on, $off]));
    $check(ORGANIZATIONS_MODULE === true ? str_contains(implode(' | ', UserTypes::warnings()), 'el tipo 12 no pertenece') : !str_contains(implode(' | ', UserTypes::warnings()), 'el tipo 12'), 'i12 warnings() lo suma según el módulo de esta instalación');
    UserTypes::fromArray([]);
    $check($moduleWarnings->invoke(null, true) === [], 'i13 con los de fábrica (el 12 con organización), ningún aviso aunque el módulo esté encendido');

    //Quien decide la aprobación automática lo lee de UserTypes, no de una lista escrita a mano.
    $pending = new UsersModel();
    $pending->type = UsersModel::TYPE_USER_INSTITUCIONAL;
    $pending->status = UsersModel::STATUS_USER_APPROVED_PENDING;
    UserTypes::fromArray([]);
    $factoryApproval = UsersApprovalHandler::isAutoApprovalSpecificMapper($pending);
    UserTypes::fromArray(['types' => [['id' => 3, 'autoApproval' => false]]]);
    $check($factoryApproval && !UsersApprovalHandler::isAutoApprovalSpecificMapper($pending), 'i14 isAutoApprovalSpecificMapper(): un 3 pendiente con autoApproval false ya no se aprueba solo (de fábrica, sí)');
    UserTypes::fromArray(['types' => [['id' => 2, 'autoApproval' => true]]]);
    $pending->type = UsersModel::TYPE_USER_GENERAL;
    $check(UsersApprovalHandler::isAutoApprovalSpecificMapper($pending), 'i15 y un 2 pendiente con autoApproval true, sí');

    //──── j) Las precisiones 3, 4 y 6 del ADR 0090 ──────────────────────────────────────────────
    echoTerminal('');
    echoTerminal("\e[36m j) Prioridad de fábrica bajo 400, rutas de root en routes, avisos en el panel y los que siguen a los tipos con perfil\e[39m");

    foreach ([400, 499] as $priority) {
        UserTypes::fromArray(['types' => [['id' => 12, 'priority' => $priority]]]);
        $check(str_contains(implode(' | ', UserTypes::errors()), "el tipo 12: priority tiene que ser menor que 400 en un tipo de fábrica, y es {$priority}") && UserTypes::priority(12) === 1, "j1 priority {$priority} en el 12: error, y rigen los de fábrica");
    }
    UserTypes::fromArray(['types' => [['id' => 12, 'priority' => 399]]]);
    $check(UserTypes::errors() === [] && UserTypes::priority(12) === 399 && !str_contains(implode(' | ', UserTypes::warnings()), 'priority'), 'j2 priority 399 en el 12: se aplica, sin aviso de prioridad', implode(' | ', UserTypes::errors()));
    UserTypes::fromArray(['types' => [['priority' => 450] + $own]]);
    $check(UserTypes::errors() === [] && UserTypes::priority(5) === 450 && str_contains(implode(' | ', UserTypes::warnings()), 'el tipo 5: priority 450'), 'j3 priority 450 en uno propio: se aplica, con aviso (como desde el 0078)');

    //Una ruta solo de root (configurations-integrations-scripts-save: guarda los scripts del sitio) en el routes del 2.
    $rootRoute = 'configurations-integrations-scripts-save';
    $registeredRoutes = get_routes();
    $rootRoles = is_array($registeredRoutes) ? array_map('intval', array_values((array) ($registeredRoutes[$rootRoute]['roles_allowed'] ?? []))) : null;
    $check($rootRoles === [UsersModel::TYPE_USER_ROOT], "j4 CANARIO: {$rootRoute} está registrada y es solo de root", $show($rootRoles));
    UserTypes::fromArray([]);
    $check(UserTypes::rootOnlyRoutes() === [], 'j5 con los de fábrica (ningún routes), ninguna ruta de root');
    UserTypes::fromArray(['types' => [['id' => 2, 'routes' => [$rootRoute, 'users-edit-request']]]]);
    $rootOnly = UserTypes::rootOnlyRoutes();
    $check($rootOnly === ["el tipo 2 trae en routes «{$rootRoute}», que es solo de root"], 'j6 la ruta de root en el routes del 2 se lista; la normal (users-edit-request), no', $show($rootOnly));
    UserTypes::fromArray(['types' => [['id' => 1, 'routes' => [$rootRoute]]]]);
    $check(UserTypes::rootOnlyRoutes() !== [] && str_contains(UserTypes::rootOnlyRoutes()[0], 'el tipo 1'), 'j7 también en la administración general, que admite routes');
    UserTypes::fromArray(['types' => [['id' => 0, 'routes' => [$rootRoute]]]]);
    $check(UserTypes::errors() === [] && UserTypes::rootOnlyRoutes() === [], 'j7b root con una ruta suya en routes no se señala', $show(UserTypes::rootOnlyRoutes()));
    UserTypes::fromArray(['types' => [['id' => 12, 'routes' => [$rootRoute, $rootRoute]]]]);
    $check(UserTypes::rootOnlyRoutes() === (CRITICAL_CONSTANTS['ORGANIZATIONS_MODULE'] ? ["el tipo 12 trae en routes «{$rootRoute}», que es solo de root"] : []), 'j7c el 12 se señala una vez, y solo si el módulo de organizaciones le da rol', $show(UserTypes::rootOnlyRoutes()));

    //Lo que enseña Avisos del sistema: el aviso de warnings() y el crítico de las rutas de root.
    $alertsAfter = function (array $data): array {
        UserTypes::fromArray($data);
        $found = [];
        foreach ([UserTypesAlert::KEY_WARNINGS, UserTypesAlert::KEY_ROOT_ROUTES] as $key) {
            $alert = \PiecesPHP\SystemStatus\SystemAlertRegistry::get($key);
            $found[$key] = $alert === null ? 'sin registrar' : ($alert->evaluateActive() ? [$alert->severity(), $alert->message()] : null);
        }
        return $found;
    };
    $alerts = $alertsAfter(['types' => [['priority' => 450] + $own]]);
    $check(($alerts[UserTypesAlert::KEY_WARNINGS][0] ?? null) === \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_WARNING && str_contains((string) ($alerts[UserTypesAlert::KEY_WARNINGS][1] ?? ''), 'priority 450') && $alerts[UserTypesAlert::KEY_ROOT_ROUTES] === null, 'j8 UserTypesAlert enseña el aviso de warnings() como aviso, no crítico', $show($alerts));
    $alerts = $alertsAfter(['types' => [['id' => 2, 'routes' => [$rootRoute]]]]);
    $check(($alerts[UserTypesAlert::KEY_ROOT_ROUTES][0] ?? null) === \PiecesPHP\SystemStatus\SystemAlert::SEVERITY_DANGER && str_contains((string) ($alerts[UserTypesAlert::KEY_ROOT_ROUTES][1] ?? ''), $rootRoute), 'j9 y la ruta de root como crítico, con el tipo y la ruta', $show($alerts));
    $alerts = $alertsAfter([]);
    $check($alerts[UserTypesAlert::KEY_WARNINGS] === null && $alerts[UserTypesAlert::KEY_ROOT_ROUTES] === null, 'j10 con los de fábrica, ninguno de los dos', $show($alerts));

    //Los informes y el Directorio siguen a profileTypes(): un 3 con perfil, activo y aprobado, en una transacción que se deshace.
    $pdo = UsersModel::model()::getDb(Config::app_db('default')['db']);
    $previousUser = get_config('current_user');
    //RETORNO-IGNORADO: la conexión va en ERRMODE_EXCEPTION; si no pudiera abrir la transacción, lanzaría.
    $pdo->beginTransaction();
    try {
        $rootRow = $pdo->query("SELECT id FROM `" . UsersModel::TABLE . "` WHERE username = 'zz-agente-root'")->fetchColumn();
        $country = $pdo->query('SELECT id FROM `' . \PiecesPHP\App\Locations\Mappers\CountryMapper::PREFIX_TABLE . \PiecesPHP\App\Locations\Mappers\CountryMapper::TABLE . '` ORDER BY id LIMIT 1')->fetchColumn();
        $country = $country === false ? null : (int) $country;
        $pdo->prepare("INSERT INTO `" . UsersModel::TABLE . "` (organization, username, password, firstname, firstLastname, email, meta, type, status, failedAttempts, createdAt)
            SELECT organization, 'zz-agente-suite-perfil', password, 'Zzperfilsuite', 'Tipotres', 'zz-agente-suite-perfil@example.invalid', meta, ?, ?, 0, NOW() FROM `" . UsersModel::TABLE . "` WHERE username = 'zz-agente-root'")
            ->execute([UsersModel::TYPE_USER_INSTITUCIONAL, UsersModel::STATUS_USER_ACTIVE]);
        $userID = (int) $pdo->lastInsertId();
        $pdo->prepare('INSERT INTO `' . \PiecesPHP\UserSystem\Profile\UserProfileMapper::TABLE . '` (country, publicVisible, belongsTo, createdAt, createdBy) VALUES (?, 1, ?, NOW(), ?)')->execute([$country, $userID, (int) $rootRow]);
        $pdo->prepare('INSERT INTO `' . \SystemApprovals\Mappers\SystemApprovalsMapper::TABLE . "` (referenceAlias, referenceValue, referenceTable, referenceDate, createdAt, createdBy, status) VALUES ('zz-suite', ?, ?, NOW(), NOW(), ?, 'APPROVED')")
            ->execute([(string) $userID, UsersModel::TABLE, (int) $rootRow]);

        $researchers = function () use ($country): int {
            $qty = 0;
            foreach (ReportsManageQueries::getResearchersByCountry() as $row) {
                $qty += $row['country'] === $country ? $row['qty'] : 0;
            }
            return $qty;
        };
        $directory = function () use ($rootRow): int {
            set_config('current_user', (object) ['id' => (int) $rootRow]);
            getLoggedFrameworkUser(true);
            $request = new RequestRoute('GET', (new UriFactory())->createUri('http://localhost/datatables'), new Headers(['X-Requested-With' => 'XMLHttpRequest']), [], [], (new StreamFactory())->createStream(''));
            $request = $request->withQueryParams(['draw' => 1, 'start' => 0, 'length' => 10, 'columns' => array_fill(0, 3, ['searchable' => 'true']), 'search' => ['value' => 'Zzperfilsuite', 'regex' => 'false']]);
            $values = json_decode((string) (new AllProfilesController())->dataTables($request, new ResponseRoute())->getBody(), true);
            return is_array($values) ? (int) ($values['recordsFiltered'] ?? -1) : -1;
        };
        UserTypes::fromArray([]);
        $withoutResearchers = $researchers();
        $withoutDirectory = $directory();
        UserTypes::fromArray(['types' => [['id' => 3, 'profile' => true]]]);
        $withResearchers = $researchers();
        $withDirectory = $directory();
        $check($withResearchers === $withoutResearchers + 1, 'j11 los investigadores por país (ReportsManageQueries) cuentan al 3 con perfil; sin el ajuste, no', "sin {$withoutResearchers}, con {$withResearchers}");
        $check($withoutDirectory === 0 && $withDirectory === 1, 'j12 el Directorio (AllProfilesController) lista al 3 con perfil; sin el ajuste, no', "sin {$withoutDirectory}, con {$withDirectory}");
    } finally {
        if ($pdo->inTransaction()) {
            //RETORNO-IGNORADO: deshacer es lo único que queda; si falla, lanza.
            $pdo->rollBack();
        }
        set_config('current_user', $previousUser);
        getLoggedFrameworkUser(true);
    }
    $leftover = $pdo->query("SELECT COUNT(*) FROM `" . UsersModel::TABLE . "` WHERE username = 'zz-agente-suite-perfil'")->fetchColumn();
    $check((int) $leftover === 0, 'j13 la transacción se deshizo: no queda el usuario de la prueba');

    //Vuelta al archivo: lo que corra después en este proceso ve los tipos de verdad.
    UserTypes::reload();
    $check(UserTypes::errors() === $cargados['errors'] && UserTypes::labels() === $cargados['labels'], 'h16 reload(): de vuelta al archivo del proyecto, el mismo que se cargó al empezar');
    //En el framework, el archivo de serie no declara nada: sin errores y con las etiquetas de fábrica.
    if (!\Terminal\Distribution::isDistribution()) {
        $check(UserTypes::errors() === [] && UserTypes::labels() === UserTypes::CORE_LABELS, 'h17 en el framework, user-types.php es el de serie: sin errores y con las etiquetas de fábrica');
    }

    $total = $passed + $failed;
    echoTerminal(' ');
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los tipos de usuario (UserTypes): los valores de fábrica, los alias de UsersModel, los roles, la jerarquía por prioridad, los perfiles con la lista vacía, las reglas y lo que aplica user-types.php, y los tipos de fábrica ajustables (ADR 0090).')->setEffects([CliActions::EFFECT_DATABASE])->register();
