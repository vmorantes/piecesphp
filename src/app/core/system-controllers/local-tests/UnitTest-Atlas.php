<?php

//El mapa del código, R1 (ADR 0091): route_explain y symbol_uses del ejemplo del ADR, una unión falsa que no cuenta como
//localizada, la coherencia con Roles::hasPermissions() y el tiempo de una llamada. Solo lee.
//@areas nucleo

use PiecesPHP\Core\Atlas\Atlas;
use PiecesPHP\Core\Atlas\RouteDeclarations;
use PiecesPHP\Core\Atlas\TokenIndex;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/atlas', function ($args) {

    echoTerminal("\e[33m[TEST:Atlas] El mapa del código: de dónde sale el permiso de una ruta y si cuadra con el framework\e[39m");
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
    $show = fn(mixed $value): string => mb_substr((string) json_encode($value, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES), 0, 400);
    $security = SettingsController::class . '::ROLES_SECURITY_AND_AI';
    //En un clon, el proyecto puede cambiar cuántas rutas usan la constante: allí basta con una.
    $usedAsInFramework = fn(mixed $count): bool => \Terminal\Distribution::isDistribution() ? is_int($count) && $count >= 1 : $count === 5;

    try {
        //─── a · route_explain del ejemplo del ADR ──────────────────────────────────────────────────────
        echoTerminal('[a] route_explain de configurations-system-security para la administración general');
        Atlas::reset();
        $started = hrtime(true);
        $explained = Atlas::routeExplain('configurations-system-security', 1);
        $cold = (hrtime(true) - $started) / 1e6;
        $started = hrtime(true);
        Atlas::routeExplain('configurations-system-security', 1);
        $warm = (hrtime(true) - $started) / 1e6;
        echoTerminal('   tiempo: ' . number_format($cold, 1, ',', '') . ' ms en frío (con el índice), ' . number_format($warm, 1, ',', '') . ' ms en caliente');
        $roles = $explained['roles'] ?? [];
        $check(str_starts_with((string) ($explained['declaration']['at'] ?? ''), 'app/classes/PiecesPHP/Settings/Controllers/SettingsController.php:') && ($explained['declaration']['verifiedByValue'] ?? null) === true, 'a1 la declaración, en SettingsController.php y verificada por valor', $show($explained['declaration'] ?? null));
        $declared = TokenIndex::get()->constant($security);
        $check($declared !== null && ($roles['symbol'] ?? null) === $security && ($roles['value'] ?? null) === [0, 1] && $usedAsInFramework($roles['usedByRoutes'] ?? null) && ($roles['at'] ?? null) === $declared['file'] . ':' . $declared['line'], 'a2 sus roles: ROLES_SECURITY_AND_AI, [0, 1], en su línea, usada por 5 rutas', $show($roles));
        $type = $explained['types'][0] ?? [];
        $revoke = $type['changePoints']['revoke'] ?? [];
        $byConstant = array_values(array_filter($revoke, fn(array $p): bool => $p['where'] === ($roles['at'] ?? null)));
        $check(($type['granted'] ?? null) === true && count($byConstant) === 1 && $byConstant[0]['possible'] === true && $byConstant[0]['owner'] === 'código del framework'
            && str_contains((string) $byConstant[0]['onUpdate'], '--3way') && str_contains((string) $byConstant[0]['onUpdate'], 'DIFERENCIAS.md'), 'a3 revocar: editar la constante, código del framework, --3way y DIFERENCIAS.md', $show($byConstant));
        $fromUserTypes = array_values(array_filter($revoke, fn(array $p): bool => str_contains($p['what'], 'user-types.php')));
        $check(count($fromUserTypes) === 1 && $fromUserTypes[0]['possible'] === false && str_contains((string) $fromUserTypes[0]['note'], 'PROTECTED_TYPE_KEYS'), 'a4 desde user-types.php: possible false, por PROTECTED_TYPE_KEYS', $show($fromUserTypes));
        $narrow = array_column($type['changePoints']['narrow'] ?? [], 'what');
        $check(count(array_filter($narrow, fn(string $w): bool => str_contains($w, '_allowedRoute'))) === 1, 'a5 estrechar: _allowedRoute', $show($narrow));
        $check(Atlas::routeExplain('zz-no-existe') === null && isset(Atlas::routeExplain('admin', 9999)['error']), 'a6 una ruta que no existe: null; un tipo sin rol: error');
        $usersList = Atlas::routeExplain('users-list', 1);
        $ways = array_column($usersList['types'][0]['ways'] ?? [], 'way');
        $check(in_array('segmento', $ways, true), 'a7 users-list para el 1: por el segmento (administratives)', $show($ways));
        //Conceder al 1 una ruta solo de root: el punto «roles.php» va a la sección «Del clon» de SU lista (administratives).
        $grant = Atlas::routeExplain('system-status-health', 1)['types'][0]['changePoints']['grant'] ?? [];
        $toRolesFile = array_values(array_filter($grant, fn(array $p): bool => str_contains($p['what'], 'roles.php')));
        $rolesLines = file(basepath('app/config/roles.php')) ?: [];
        $from = $to = null;
        foreach ($rolesLines as $number => $text) {
            if (str_starts_with($text, '$permisosAdministrativos =')) {
                $from = $number + 1;
            } elseif (str_starts_with($text, '$permisosSuperiores =')) {
                $to = $number + 1;
            }
        }
        $grantLine = isset($toRolesFile[0]['where']) && preg_match('/:(\d+)$/', (string) $toRolesFile[0]['where'], $m) === 1 ? (int) $m[1] : null;
        $check($from !== null && $to !== null && $grantLine !== null && $grantLine > $from && $grantLine < $to && str_contains((string) ($rolesLines[$grantLine - 1] ?? ''), 'Del clon'), 'a9 conceder al 1 por roles.php: la línea «Del clon» de la lista administratives', $show([$toRolesFile, $from, $to]));
        $check(($explained['anonymous']['enters'] ?? null) === false && (Atlas::routeExplain('public-index')['anonymous']['enters'] ?? null) === true, 'a8 anonymous: sin sesión no entra a una ruta con require_login; a una pública, sí');
        echoTerminal(' ');

        //─── b · symbol_uses ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] symbol_uses de ROLES_SECURITY_AND_AI');
        $uses = Atlas::symbolUses('\\' . $security);
        $contexts = array_count_values(array_column($uses['uses'] ?? [], 'context'));
        $check(($uses['symbol'] ?? null) === $security && ($uses['value'] ?? null) === [0, 1] && str_contains((string) ($uses['declaredAt'] ?? ''), 'SettingsController.php:') && $usedAsInFramework(count($uses['routes'] ?? [])), 'b1 su declaración, su valor y las cinco rutas', $show($uses));
        $check(($contexts['declaración de ruta'] ?? 0) >= 1, 'b2 sus usos, con el contexto «declaración de ruta»', $show($contexts));
        $check(Atlas::symbolUses('Zz\\NoExiste::NADA') === null && Atlas::symbolUses('no es un símbolo') === null, 'b3 una constante que no existe o un texto que no lo es: null');
        $notLoaded = 'PiecesPHP\\UserSystem\\Views\\usuarios\\mail\\recovery_password_code';
        $check(Atlas::symbolUses($notLoaded . '::X') === null && !class_exists($notLoaded, false), 'b4 un nombre que src/app no declara como clase no se autocarga (cargar un archivo es ejecutarlo)');
        $secret = Atlas::symbolUses(\PiecesPHP\Core\Config::class . '::DEFAULT_APP_KEY');
        $check(is_array($secret) && $secret['value'] === null && str_contains((string) $secret['valueNote'], 'secreto'), 'b5 una constante con nombre de secreto no enseña su valor', $show($secret));
        echoTerminal(' ');

        //─── c · Una unión falsa no cuenta como localizada ──────────────────────────────────────────────
        echoTerminal('[c] Una declaración unida por nombre pero con otros roles: no se localiza');
        $declarations = TokenIndex::get()->routes();
        $real = null;
        $other = null;
        foreach ($declarations as $declaration) {
            if (!str_ends_with($declaration['file'], 'SettingsController.php') || !isset($declaration['arguments']['rolesAllowed'])) {
                continue;
            }
            $roleArgument = $declaration['arguments']['rolesAllowed'];
            $text = '';
            $file = TokenIndex::get()->file($declaration['file']);
            for ($i = $roleArgument['start']; $i < $roleArgument['end']; $i++) {
                $text .= $file['tokens'][$i]->text ?? '';
            }
            if (trim($text) === 'self::ROLES_SECURITY_AND_AI' && $real === null) {
                $real = $declaration;
            } elseif (trim($text) === 'self::ROLES_OS_TICKET' && $other === null) {
                $other = $declaration;
            }
        }
        $check($real !== null && $other !== null, 'c0 CANARIO: hay una declaración con ROLES_SECURITY_AND_AI y otra con ROLES_OS_TICKET en SettingsController');
        if ($real !== null && $other !== null) {
            $join = new \ReflectionMethod(RouteDeclarations::class, 'join');
            $genuine = $join->invoke(RouteDeclarations::get(), TokenIndex::get(), $real);
            $fake = $real;
            $fake['arguments']['rolesAllowed'] = $other['arguments']['rolesAllowed'];
            $faked = $join->invoke(RouteDeclarations::get(), TokenIndex::get(), $fake);
            $check(is_array($genuine) && is_array($genuine[1]), 'c1 la declaración de verdad se une', $show($genuine));
            $check(is_array($faked) && $faked[1] === null, 'c2 la misma con los roles de otra ([0] ≠ [0, 1]): unida por nombre, pero no cuadra por valor y no se localiza', $show($faked));
        }
        echoTerminal(' ');

        //─── d · La coherencia y la cobertura ───────────────────────────────────────────────────────────
        echoTerminal('[d] La coherencia con Roles::hasPermissions() y la cobertura');
        $coherence = Atlas::coherence();
        $check($coherence['cells'] > 0 && $coherence['mismatches'] === [], "d1 {$coherence['cells']} celdas (ruta × tipo con rol): el mapa concede lo mismo que Roles::hasPermissions()", $show(array_slice($coherence['mismatches'], 0, 5)));
        $coverage = RouteDeclarations::get()->coverage();
        echoTerminal("   cobertura: {$coverage['located']} de {$coverage['total']}; {$coverage['resolvedRoles']} con roles resueltos; {$coverage['withoutRoles']} sin roles; " . count($coverage['notLocated']) . ' no localizadas');
        $check($coverage['located'] + count($coverage['notLocated']) === $coverage['total'] && $coverage['total'] > 0, 'd2 localizadas y no localizadas suman todas las registradas');
        //La coherencia cae: un permiso concedido por una vía que el mapa no modela (Roles::addPermission() a mano).
        \PiecesPHP\Core\Roles::addPermission('system-status-health', 1);
        $injected = Atlas::coherence()['mismatches'];
        $check(count($injected) === 1 && $injected[0]['route'] === 'system-status-health' && $injected[0]['userType'] === 1 && $injected[0]['framework'] === true && $injected[0]['model'] === false, 'd4 con un permiso que el mapa no modela, la coherencia lo caza: system-status-health para el 1', $show($injected));
        $stats = TokenIndex::get()->stats();
        echoTerminal("   índice: {$stats['files']} archivos en " . number_format($stats['milliseconds'], 1, ',', '') . ' ms');
        $check($stats['files'] > 500, 'd3 CANARIO: el índice leyó src/app (más de 500 archivos)', (string) $stats['files']);

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getFile() . ':' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('El mapa del código, R1 (ADR 0091): route_explain y symbol_uses, una unión falsa que no se localiza, la coherencia con Roles::hasPermissions() y el tiempo de una llamada.')->setEffects([CliActions::EFFECT_NONE])->register();
