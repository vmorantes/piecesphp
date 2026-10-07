<?php

//Una categoría de publicaciones incompleta —sin langData en su meta— no tumba la portada: el menú público la salta.
//Por HTTP y sin sesión, como un visitante. Siembra dos categorías zz- y las retira.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Terminal\CliActions;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\PublicationsRoutes;

CliActions::make('unit-tests:core/public-menu-categories', function ($args) {

    echoTerminal("\e[33m[TEST:PublicMenuCategories] Una categoría incompleta no tumba el menú público\e[39m");
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
    $check($base !== '', 'c1 hay una base HTTP para pedir', $base);
    $check(PublicationsRoutes::ENABLE === true, 'c2 el módulo de publicaciones está activo: sin él el menú no pinta categorías');
    $database = (new BaseModel())->getDatabase();
    $check($database !== null, 'c3 hay conexión a la base');
    if ($failed > 0 || $database === null) {
        return $balance();
    }

    $pedir = function (string $path) use ($base): array {
        $handle = curl_init();
        curl_setopt_array($handle, [
            CURLOPT_URL => $base . '/' . ltrim($path, '/'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 30,
        ]);
        $cuerpo = curl_exec($handle);
        $codigo = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        return ['codigo' => $codigo, 'cuerpo' => is_string($cuerpo) ? $cuerpo : ''];
    };

    $tabla = PublicationCategoryMapper::TABLE;
    $marca = bin2hex(random_bytes(3));
    $rota = "zz-categoria-rota-{$marca}";
    $sana = "zz-categoria-sana-{$marca}";
    $idioma = Config::get_default_lang();
    $retirar = function () use ($database, $tabla, $rota, $sana): int {
        $borrar = $database->prepare("DELETE FROM `{$tabla}` WHERE name IN (?, ?)");
        $borrar->execute([$rota, $sana]);
        return $borrar->rowCount();
    };

    try {
        $sembrar = $database->prepare("INSERT INTO `{$tabla}` (name, meta) VALUES (?, ?)");
        //La sana es el canario: si el menú dejara de pintar categorías, la prueba de la rota pasaría sola.
        $sembrar->execute([$sana, json_encode(['baseLang' => $idioma, 'langData' => new \stdClass])]);
        $sembrar->execute([$rota, json_encode(['baseLang' => $idioma])]);

        $portada = $pedir('/?i18n=' . $idioma);
        $check($portada['codigo'] === 200, 'a1 la portada responde 200 con una categoría sin langData en la base', 'HTTP ' . $portada['codigo']);
        $check(str_contains($portada['cuerpo'], $sana), 'a2 CANARIO: la categoría completa sigue en el menú público');
        $check(!str_contains($portada['cuerpo'], $rota), 'a3 y la incompleta no aparece: se salta, no se inventa');
    } finally {
        $retiradas = $retirar();
    }
    $quedan = $database->prepare("SELECT COUNT(*) FROM `{$tabla}` WHERE name IN (?, ?)");
    $quedan->execute([$rota, $sana]);
    $check((int) $quedan->fetchColumn() === 0, "z las dos categorías sembradas se retiran ({$retiradas} retirada(s))");

    return $balance();

})->setDescription('Una categoría de publicaciones sin langData en su meta no tumba la portada con un 500: el menú público la salta, y las completas siguen. Por HTTP, sin sesión.')->setEffects([CliActions::EFFECT_DATABASE])->register();
