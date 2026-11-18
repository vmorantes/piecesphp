<?php

//StructuredData::extendOrganization() amplía el nodo de la organización del JSON-LD y rechaza lo que lo rompería.
//Sin base ni red: extiende, pide los nodos y mira errors(). Devuelve el estado estático que había.
//@areas nucleo

use PiecesPHP\Core\Utilities\Helpers\StructuredData;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/structured-data', function ($args) {

    echoTerminal("\e[33m[TEST:StructuredData] La organización del JSON-LD se amplía sin perder su @id\e[39m");
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

    //El estado es estático: se guarda y se devuelve, para no dejar una organización extendida a quien corra después.
    $clase = new \ReflectionClass(StructuredData::class);
    $previo = $clase->getStaticProperties();
    $imagenPrevia = get_config('open_graph_image');
    $limpiar = function () use ($clase): void {
        foreach (array_keys($clase->getStaticProperties()) as $nombre) {
            $clase->setStaticPropertyValue($nombre, []);
        }
    };
    $id = baseurl() . '#organization';
    $organizaciones = function () use ($id): array {
        $grafo = json_decode((string) preg_replace('/^.*?>(\{.*\})<\/script>.*$/s', '$1', StructuredData::get()), true);
        return array_values(array_filter(is_array($grafo) ? (array) ($grafo['@graph'] ?? []) : [], fn($nodo) => is_array($nodo) && (($nodo['@id'] ?? null) === $id || in_array($nodo['@type'] ?? null, StructuredData::ORGANIZATION_TYPES, true))));
    };

    try {
        echoTerminal('[1] Un subtipo y sus perfiles amplían el nodo de siempre');
        $limpiar();
        StructuredData::extendOrganization(['@type' => 'NGO', 'sameAs' => ['https://www.facebook.com/zz-ejemplo', 'https://www.instagram.com/zz-ejemplo']]);
        StructuredData::extendOrganization(['sameAs' => ['https://www.instagram.com/zz-ejemplo', 'https://x.com/zz-ejemplo']]);
        $nodos = $organizaciones();
        $organizacion = $nodos[0] ?? [];
        $check(count($nodos) === 1, 's1a un solo nodo de organización en el @graph', (string) json_encode($nodos));
        $check(($organizacion['@id'] ?? null) === $id && ($organizacion['@type'] ?? null) === 'NGO', 's1b con el @id de siempre y @type NGO', (string) json_encode($organizacion));
        $check(($organizacion['sameAs'] ?? null) === ['https://www.facebook.com/zz-ejemplo', 'https://www.instagram.com/zz-ejemplo', 'https://x.com/zz-ejemplo'], 's1c sameAs se une sin repetir entre llamadas', (string) json_encode($organizacion['sameAs'] ?? null));
        $check(StructuredData::errors() === [], 's1d nada rechazado', implode(' | ', StructuredData::errors()));
        StructuredData::extendOrganization(['logo' => '/zz-logo.png']);
        $check(($organizaciones()[0]['logo'] ?? null) === baseurl('zz-logo.png'), 's1e un logo relativo sale absoluto, como el de omisión', (string) json_encode($organizaciones()[0]['logo'] ?? null));
        echoTerminal(' ');

        echoTerminal('[2] @id y @context no se cambian');
        $limpiar();
        StructuredData::extendOrganization(['@id' => 'https://otro.test/#zz', '@context' => 'https://otro.test', 'name' => 'zz-Nombre']);
        $organizacion = $organizaciones()[0] ?? [];
        $check(count(StructuredData::errors()) === 2, 's2a @id y @context van a errors()', implode(' | ', StructuredData::errors()));
        $check(($organizacion['@id'] ?? null) === $id && !isset($organizacion['@context']) && ($organizacion['name'] ?? null) === 'zz-Nombre', 's2b sin aplicarse, y lo válido de la misma llamada sí', (string) json_encode($organizacion));
        $limpiar();
        StructuredData::extendOrganization(['@graph' => []]);
        $check(count(StructuredData::errors()) === 1 && !array_key_exists('@graph', $organizaciones()[0] ?? []), 's2c otra clave con @ que no es @type, rechazada', implode(' | ', StructuredData::errors()));
        echoTerminal(' ');

        echoTerminal('[3] @type solo de la lista cerrada');
        $limpiar();
        StructuredData::extendOrganization(['@type' => 'Person']);
        $check(count(StructuredData::errors()) === 1 && ($organizaciones()[0]['@type'] ?? null) === 'Organization', 's3 un @type fuera de ORGANIZATION_TYPES, rechazado: sigue Organization', implode(' | ', StructuredData::errors()));
        echoTerminal(' ');

        echoTerminal('[4] sameAs solo con URL absolutas http(s)');
        foreach (['relativa' => ['/zz-perfil'], 'javascript:' => ['javascript:void(0)'], 'texto suelto' => 'https://www.facebook.com/zz-ejemplo', 'ftp' => ['ftp://zz.test/perfil']] as $caso => $sameAs) {
            $limpiar();
            StructuredData::extendOrganization(['sameAs' => $sameAs]);
            $check(count(StructuredData::errors()) === 1 && !isset($organizaciones()[0]['sameAs']), "s4 sameAs {$caso}, rechazada", implode(' | ', StructuredData::errors()));
        }
        echoTerminal(' ');

        echoTerminal('[5] WebSite.publisher apunta a la organización');
        $limpiar();
        StructuredData::extendOrganization(['@type' => 'NGO']);
        $sitio = array_values(array_filter(StructuredData::defaultNodes(), fn($nodo) => ($nodo['@type'] ?? null) === 'WebSite'))[0] ?? [];
        $check(($sitio['publisher']['@id'] ?? null) === ($organizaciones()[0]['@id'] ?? false), 's5 WebSite.publisher.@id = el @id de la organización', (string) json_encode($sitio['publisher'] ?? null));
        echoTerminal(' ');

        echoTerminal('[6] Las claves tienen que ser texto');
        $limpiar();
        StructuredData::extendOrganization([0 => 'zz-x']);
        $check(count(StructuredData::errors()) === 1 && !array_key_exists(0, $organizaciones()[0] ?? []), 's6 una clave numérica ([0 => …]) se rechaza y no llega al nodo', implode(' | ', StructuredData::errors()) . ' · ' . (string) json_encode($organizaciones()[0] ?? null));
        echoTerminal(' ');

        echoTerminal('[7] La imagen del sitio (open_graph_image) va al nodo de la organización');
        $limpiar();
        set_config('open_graph_image', '/zz-og.png');
        $check(($organizaciones()[0]['image'] ?? null) === baseurl('zz-og.png'), 's7a una imagen relativa sale absoluta, como el logo', (string) json_encode($organizaciones()[0]['image'] ?? null));
        StructuredData::extendOrganization(['image' => 'https://zz.test/zz-propia.png']);
        $check(($organizaciones()[0]['image'] ?? null) === 'https://zz.test/zz-propia.png', 's7b la de extendOrganization() gana a la del sitio', (string) json_encode($organizaciones()[0]['image'] ?? null));
        StructuredData::extendOrganization(['image' => 'statics/zz.png']);
        $check(($organizaciones()[0]['image'] ?? null) === baseurl('statics/zz.png'), 's7d una imagen relativa de extendOrganization() sale absoluta, como el logo', (string) json_encode($organizaciones()[0]['image'] ?? null));
        foreach (['vacía' => '', 'false' => false, 'sin opción' => null] as $caso => $valor) {
            $limpiar();
            set_config('open_graph_image', $valor);
            $check(!array_key_exists('image', $organizaciones()[0] ?? []), "s7c {$caso}: el nodo va sin image", (string) json_encode($organizaciones()[0] ?? null));
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($previo as $nombre => $valor) {
            $clase->setStaticPropertyValue($nombre, $valor);
        }
        set_config('open_graph_image', $imagenPrevia === false ? null : $imagenPrevia);
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('StructuredData::extendOrganization() amplía la organización del JSON-LD sin cambiar su @id, y rechaza @id, @context, un @type fuera de la lista y un sameAs que no sea de URL http(s); la imagen del sitio va al nodo por omisión.')->setEffects([CliActions::EFFECT_NONE])->register();
