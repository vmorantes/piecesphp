<?php

//La selección por área de gates (ADR 0062): qué suites corren según lo que cambió. Sobre GatesTask::selectByArea(), que
//es pura: ni git ni la base.

use PiecesPHP\Terminal\CliActions;
use Terminal\Tasks\GatesTask;

CliActions::make('unit-tests:core/gates-area-selection', function ($args) {

    echoTerminal("\e[33m[TEST:GatesAreaSelection] gates con changed-since corre solo las suites del área tocada, y ante la duda todas\e[39m");
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

    //Cuatro suites de mentira: una de News/, una de Newsletter/, una sin área y una con un área archivo.
    $dir = 'src/app/core/system-controllers/local-tests/';
    $areas = [
        'zz-news' => ['src/app/classes/News/'],
        'zz-newsletter' => ['src/app/classes/Newsletter/'],
        'zz-sin-area' => [],
        'zz-archivo' => ['src/app/classes/Organizations/Controllers/OrganizationsController.php'],
    ];
    $files = [
        'zz-news' => $dir . 'UnitTest-ZzNews.php',
        'zz-newsletter' => $dir . 'UnitTest-ZzNewsletter.php',
        'zz-sin-area' => $dir . 'UnitTest-ZzSinArea.php',
        'zz-archivo' => $dir . 'UnitTest-ZzArchivo.php',
    ];
    $select = fn(array $changed): array => GatesTask::selectByArea($areas, $files, $changed);
    $describe = fn(array $selected): string => json_encode($selected, JSON_UNESCAPED_UNICODE) ?: '';

    echoTerminal('[a] Qué corre');

    $news = $select(['src/app/classes/News/Controllers/NewsController.php']);
    //Y al revés: con un prefijo de texto sin la barra, «News» casaría con «Newsletter/…».
    $newsletter = $select(['src/app/classes/Newsletter/Controllers/NewsletterController.php']);
    $check(($news['zz-news'] ?? null) === 'area' && !isset($news['zz-newsletter']) && !isset($newsletter['zz-news']),
        'a1 un cambio en News/ elige la suite de News/ y NO la de Newsletter/, ni uno en Newsletter/ la de News/', $describe($news) . ' · ' . $describe($newsletter));
    $check(($news['zz-sin-area'] ?? null) === 'sin-area', 'a2 la suite sin @areas corre siempre', $describe($news));

    $core = $select(['src/app/core/AppHelpers.php']);
    $check(count($core) === count($areas) && array_unique(array_values($core)) === ['nucleo'], 'a3 un cambio en src/app/core/ las corre todas, por núcleo', $describe($core));

    $userSystem = $select(['src/app/classes/PiecesPHP/UserSystem/ORM/UsersModel.php']);
    $check(count($userSystem) === count($areas) && array_unique(array_values($userSystem)) === ['nucleo'], 'a4 un cambio en PiecesPHP/UserSystem/ (fuera de la lista) las corre todas', $describe($userSystem));

    $docs = $select(['.agents/x.md', 'CHANGELOG.md']);
    $check(array_keys($docs) === ['zz-sin-area'], 'a5 solo documentación: solo las sin @areas', $describe($docs));

    $ownFile = $select([$files['zz-newsletter']]);
    $check(($ownFile['zz-newsletter'] ?? null) === 'su-archivo' && !isset($ownFile['zz-news']), 'a6 cambia el archivo de una suite: esa suite (su archivo), y no todas', $describe($ownFile));

    $sameFile = $select(['src/app/classes/Organizations/Controllers/OrganizationsController.php']);
    $nearFile = $select(['src/app/classes/Organizations/Controllers/OrganizationsControllerZz.php']);
    $check(($sameFile['zz-archivo'] ?? null) === 'area' && !isset($nearFile['zz-archivo']), 'a7 un área archivo casa por igualdad, no por prefijo', $describe($nearFile));

    $nothing = $select([]);
    $check(array_keys($nothing) === ['zz-sin-area'], 'a8 sin cambios: solo las sin @areas', $describe($nothing));

    echoTerminal(' ');
    echoTerminal('[b] gates areas: qué tocó un cambio y de qué módulo es cada ruta');

    $enNews = GatesTask::areasFor(['src/app/classes/News/Views/zz.php']);
    $check($enNews === ['core' => null, 'modules' => ['src/app/classes/News/']], 'b1 un archivo de News/: el módulo News/ (no Newsletter/) y sin núcleo', $describe($enNews));
    $enCore = GatesTask::areasFor(['src/app/classes/News/Views/zz.php', 'src/statics/core/js/zz.js']);
    $check($enCore['core'] === 'src/statics/core/js/zz.js', 'b2 uno de src/statics/core/: ese es el núcleo', $describe($enCore));
    $enDocs = GatesTask::areasFor(['.agents/zz.md']);
    $check($enDocs === ['core' => null, 'modules' => []], 'b3 uno de .agents/: ni módulo ni núcleo', $describe($enDocs));
    $enSuite = GatesTask::areasFor([$files['zz-news']], [$files['zz-news']]);
    $check($enSuite['core'] === null, 'b4 el archivo de una suite no es núcleo', $describe($enSuite));

    echoTerminal(' ');
    echoTerminal('[c] gates areas aparta los artefactos de instrumentos, y un artefacto no es núcleo (ADR 0065)');

    $split = GatesTask::pageAffectingFiles(['files/dev/x.json', 'PHPStanResult.Summary.txt', 'src/app/classes/News/Views/zz.php', 'bin/zz', 'src/zz/PHPStanResult.txt', 'CHANGELOG.md']);
    $check($split['ignored'] === ['files/dev/x.json', 'PHPStanResult.Summary.txt'], 'c1 files/dev/ y PHPStanResult.* de la raíz van a ignored', $describe($split));
    $check($split['kept'] === ['src/app/classes/News/Views/zz.php', 'bin/zz', 'src/zz/PHPStanResult.txt', 'CHANGELOG.md'], 'c2 src/, bin/, un PHPStanResult en una subcarpeta y CHANGELOG.md se quedan', $describe($split));
    $onlyInstruments = GatesTask::areasFor(GatesTask::pageAffectingFiles(['files/dev/x.json', 'PHPStanResult.Summary.txt'])['kept']);
    $check($onlyInstruments === ['core' => null, 'modules' => []], 'c3 con solo artefactos, areasFor() sobre lo que queda: sin núcleo ni módulos', $describe($onlyInstruments));
    $check(GatesTask::areasFor(['PHPStanResult.Summary.txt'])['core'] === null, 'c4 sin apartar, el artefacto tampoco es núcleo');

    echoTerminal(' ');
    echoTerminal('[s] Un artefacto de instrumento elige solo las suites que lo leen (ADR 0065)');

    //Lo que cada suite de mentira lee, como lo calcularía suiteArtifactReads() de su archivo. maintenance-mode es real:
    //declara route-inventory.json en SUITE_DISTRIBUTION_REQUIREMENTS.
    $areasS = $areas + ['zz-lee-matriz' => ['src/app/classes/Forms/'], 'zz-files-dev' => ['src/app/classes/Forms/'], 'unit-tests:core/maintenance-mode' => ['src/app/classes/Forms/']];
    $filesS = $files + ['zz-lee-matriz' => $dir . 'UnitTest-ZzLeeMatriz.php', 'zz-files-dev' => $dir . 'UnitTest-ZzFilesDev.php', 'unit-tests:core/maintenance-mode' => $dir . 'UnitTest-MaintenanceMode.php'];
    $reads = [
        'zz-lee-matriz' => ['names' => ['permissions-matrix.json'], 'devUnnamed' => false],
        'zz-files-dev' => ['names' => [], 'devUnnamed' => true],
    ];
    $selectS = fn(array $changed): array => GatesTask::selectByArea($areasS, $filesS, $changed, $reads);

    $s1 = $selectS(['files/dev/permissions-matrix.json']);
    $check(($s1['zz-lee-matriz'] ?? null) === 'artefacto files/dev/permissions-matrix.json' && !isset($s1['zz-news']) && !in_array('nucleo', $s1, true),
        's1 un artefacto que lee una suite: elige esa y NO todas', $describe($s1));
    $s2 = $selectS(['PHPStanResult.Summary.baseline.txt']);
    $check(array_keys($s2) === ['zz-sin-area'], 's2 un artefacto que no lee nadie: ninguna, salvo las sin @areas', $describe($s2));
    $s3 = $selectS(['files/dev/scss-census.json']);
    $check(($s3['zz-files-dev'] ?? null) === 'artefacto files/dev/scss-census.json' && !isset($s3['zz-lee-matriz']),
        's3 la que nombra files/dev sin nombre de archivo entra con cualquier files/dev/', $describe($s3));
    $s4 = $selectS(['files/dev/route-inventory.json']);
    $check(($s4['unit-tests:core/maintenance-mode'] ?? null) === 'artefacto files/dev/route-inventory.json',
        's4 un artefacto declarado en SUITE_DISTRIBUTION_REQUIREMENTS elige su suite', $describe($s4));
    $s5 = $selectS(['files/dev/permissions-matrix.json', 'src/app/core/AppHelpers.php']);
    $check(count($s5) === count($areasS) && array_unique(array_values($s5)) === ['nucleo'], 's5 CANARIO: un artefacto MÁS un cambio de núcleo, todas', $describe($s5));

    $routes = get_routes();
    $repoRoot = rtrim(str_replace('\\', '/', basepath('..')), '/');
    $modules = GatesTask::routeModules(is_array($routes) ? $routes : [], $repoRoot);
    $check(($modules['publications-admin-list'] ?? 'falta') === 'src/app/classes/Publications/', 'b5 publications-admin-list es de Publications/', (string) ($modules['publications-admin-list'] ?? 'falta'));
    //AdminPanelController vive en PiecesPHP/AdminPanel/, que no es un módulo de la lista.
    $check(array_key_exists('admin', $modules) && $modules['admin'] === null, 'b6 admin no cae en ningún módulo: null', json_encode($modules['admin'] ?? 'falta') ?: '');

    return $balance();

})->setDescription('La selección por área de gates (ADR 0062 y 0065), sobre GatesTask::selectByArea(): News/ no elige Newsletter/, lo sin @areas corre siempre, el núcleo y lo que no está en la lista de módulos las corre todas, la documentación no elige ninguna, el archivo de una suite la elige a ella, un área archivo casa por igualdad y un artefacto de instrumento elige solo las suites que lo leen. Sin base ni git.')->setEffects([CliActions::EFFECT_NONE])->register();
