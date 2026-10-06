<?php

//MetaTags escribe el título y las etiquetas del <head>: con comillas, < y & tienen que salir válidas y leerse igual.
//Sin base ni red: fija valores, pide el HTML y lo lee con un analizador de HTML. Devuelve los valores que había.

use PiecesPHP\Core\Utilities\Helpers\MetaTags;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/meta-tags', function ($args) {

    echoTerminal("\e[33m[TEST:MetaTags] El título y el Open Graph admiten comillas, < y &\e[39m");
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

    //El estado es estático: se guarda y se devuelve, para que la suite no deje su título a quien corra después.
    $clase = new \ReflectionClass(MetaTags::class);
    $previo = [];
    foreach ($clase->getProperties(\ReflectionProperty::IS_STATIC) as $propiedad) {
        $previo[$propiedad->getName()] = $propiedad->getValue();
    }

    try {
        $texto = "Café «O'Brien» & \"Hijos\" <2026>";
        $url = "https://ejemplo.test/imagen.jpg?a=1&b='x'";
        MetaTags::setTitle($texto);
        MetaTags::setSitename($texto);
        MetaTags::setOwner($texto);
        MetaTags::setKeywords($texto);
        //`setDescription()` quita etiquetas: el «<2026>» se iría, así que la descripción lleva el resto.
        $descripcion = "Café «O'Brien» & \"Hijos\" 2026";
        MetaTags::setDescription($descripcion);
        MetaTags::setImage($url);
        MetaTags::setURL($url);
        //RETORNO-IGNORADO: `MetaTags::setLocale()` devuelve void; el censo lo confunde por nombre con `setlocale()`.
        MetaTags::setLocale("es_CO'");
        MetaTags::setLocaleAlternate('en_US"');
        //RETORNO-IGNORADO: `MetaTags::setType()` devuelve void; el censo lo confunde por nombre con `settype()`.
        MetaTags::setType("web'site");
        MetaTags::setThemeColor("#fff'");

        $html = MetaTags::getMetaTagsGeneric() . MetaTags::getMetaTagsOpenGraph();

        echoTerminal('[1] Cada etiqueta es una sola etiqueta bien formada');
        $lineas = array_values(array_filter(array_map('trim', explode("\n", $html)), fn($l) => str_starts_with($l, '<meta')));
        $malas = array_values(array_filter($lineas, fn($l) => preg_match("/^<meta (name|property)='[^']*' content='[^']*' \\/>$/u", $l) !== 1));
        $check(count($lineas) === 17, '1a salen las diecisiete etiquetas meta: cuatro genéricas, nueve de Open Graph y cuatro de Twitter', (string) count($lineas));
        $check(count($malas) === 0, '1b ninguna lleva una comilla simple sin escapar dentro de su atributo', implode(' | ', $malas));
        $check(substr_count($html, '<title>') === 1 && substr_count($html, '</title>') === 1 && !str_contains($html, '<2026>'), '1c el título no abre ninguna etiqueta con su «<»');
        echoTerminal(' ');

        echoTerminal('[2] Y el texto se lee igual que se escribió');
        $documento = new \DOMDocument();
        $interno = libxml_use_internal_errors(true);
        $documento->loadHTML('<?xml encoding="UTF-8"><html><head>' . $html . '</head><body></body></html>');
        libxml_clear_errors();
        libxml_use_internal_errors($interno);
        $leido = ['title' => trim((string) ($documento->getElementsByTagName('title')->item(0)->textContent ?? ''))];
        foreach ($documento->getElementsByTagName('meta') as $meta) {
            $clave = $meta->getAttribute('name') !== '' ? $meta->getAttribute('name') : $meta->getAttribute('property');
            $leido[$clave] = $meta->getAttribute('content');
        }
        $esperado = [
            'title' => $texto,
            'author' => $texto,
            'description' => $descripcion,
            'keywords' => $texto,
            'theme-color' => "#fff'",
            'og:site_name' => $texto,
            'og:title' => $texto,
            'og:description' => $descripcion,
            'og:locale' => "es_CO'",
            'og:locale:alternate' => 'en_US"',
            'og:type' => "web'site",
            'og:image' => $url,
            'og:url' => $url,
            'og:image:alt' => $texto,
            'twitter:title' => $texto,
            'twitter:description' => $descripcion,
            'twitter:image' => $url,
        ];
        foreach ($esperado as $clave => $valor) {
            $check(($leido[$clave] ?? null) === $valor, "2 {$clave} se lee igual", var_export($leido[$clave] ?? null, true));
        }
        echoTerminal(' ');

        echoTerminal('[3] Los valores se pasan TAL CUAL: quien escape antes, escapa dos veces');
        MetaTags::setTitle('Tom &amp; Jerry');
        $check(str_contains(MetaTags::getMetaTagsGeneric(), '<title>Tom &amp;amp; Jerry</title>'), '3a un valor ya escapado sale escapado otra vez: el contrato es texto crudo');
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($previo as $nombre => $valor) {
            $clase->setStaticPropertyValue($nombre, $valor);
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('MetaTags escapa el título y todas sus etiquetas: con comillas, < y & el <head> sigue válido y el texto se lee igual.')->setEffects([CliActions::EFFECT_NONE])->register();
