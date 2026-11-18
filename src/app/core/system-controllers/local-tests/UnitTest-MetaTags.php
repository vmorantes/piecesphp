<?php

//MetaTags escribe el título y las etiquetas del <head>: con comillas, < y & tienen que salir válidas y leerse igual.
//Sin base ni red: fija valores, pide el HTML y lo lee con un analizador de HTML. Devuelve los valores que había.
//@areas nucleo

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
    $configPrevia = [];
    foreach (['title', 'lang_by_cookie', 'alternatives_url', 'x_account', 'owner', 'title_app', 'description', 'keywords', 'meta_theme_color', 'share_description'] as $nombre) {
        $configPrevia[$nombre] = \PiecesPHP\Core\Config::get_config($nombre);
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
        echoTerminal(' ');

        $porOmision = $clase->getDefaultProperties();
        $limpiar = function () use ($clase, $porOmision): void {
            foreach (array_keys($clase->getStaticProperties()) as $nombre) {
                $clase->setStaticPropertyValue($nombre, $porOmision[$nombre] ?? null);
            }
        };
        $estatica = fn(string $nombre): mixed => $clase->getStaticPropertyValue($nombre);

        echoTerminal('[4] Leer el título antes de set_title() no lo congela');
        $limpiar();
        $antes = MetaTags::getTitle();
        set_title('zz-Nuevo');
        $html = MetaTags::getMetaTagsGeneric();
        $check(!str_contains($antes, 'zz-Nuevo') && str_contains($html, '<title>zz-Nuevo'), 'm1 getTitle() y después set_title(): el <title> lleva el nuevo', $antes . ' | ' . (string) preg_replace('/^.*(<title>[^<]*<\/title>).*$/s', '$1', $html));
        echoTerminal(' ');

        echoTerminal('[5] languageURLs() antes de set_title() tampoco');
        $limpiar();
        set_config('title', $configPrevia['title']);
        MetaTags::languageURLs();
        set_title('zz-Nuevo-2');
        $html = MetaTags::getMetaTagsGeneric();
        $check(str_contains($html, '<title>zz-Nuevo-2'), 'm2 languageURLs() y después set_title(): el <title> lleva el nuevo', (string) preg_replace('/^.*(<title>[^<]*<\/title>).*$/s', '$1', $html));
        echoTerminal(' ');

        echoTerminal('[6] Cada lector: lo fijado con su setter y, sin él, la omisión de initialValues()');
        set_config('title', $configPrevia['title']);
        $lectores = [
            'owner' => 'getOwner', 'sitename' => 'getSitename', 'title' => 'getTitle', 'description' => 'getDescription',
            'keywords' => 'getKeywords', 'image' => 'getImage', 'url' => 'getURL', 'themeColor' => 'getThemeColor',
            'locale' => 'getLocale', 'localeAlternate' => 'getLocaleAlternate', 'type' => 'getType', 'robots' => 'getRobots',
            'imageAlt' => 'getImageAlt',
        ];
        $limpiar();
        $leidos = [];
        foreach ($lectores as $propiedad => $lector) {
            $leidos[$propiedad] = MetaTags::$lector();
        }
        $leidos['shareTitle'] = MetaTags::getShareTitle();
        $leidos['shareDescription'] = MetaTags::getShareDescription();
        $intactas = array_keys(array_filter($lectores, fn($lector, $propiedad) => $estatica($propiedad) !== null, \ARRAY_FILTER_USE_BOTH));
        $check($intactas === [], 'm3a leer no fija nada: las propiedades siguen sin valor', implode(', ', $intactas));
        $iniciar = new \ReflectionMethod(MetaTags::class, 'initialValues');
        $iniciar->invoke(null);
        foreach ($lectores as $propiedad => $lector) {
            $check($leidos[$propiedad] === $estatica($propiedad), "m3b {$lector}() sin setter = la omisión de initialValues()", var_export($leidos[$propiedad], true) . ' / ' . var_export($estatica($propiedad), true));
        }
        $check($leidos['shareTitle'] === (new \ReflectionMethod(MetaTags::class, 'shareTitle'))->invoke(null), 'm3b getShareTitle() sin setter = el título para compartir efectivo', $leidos['shareTitle']);
        $check($leidos['shareDescription'] === (new \ReflectionMethod(MetaTags::class, 'shareDescription'))->invoke(null), 'm3b getShareDescription() sin setter = la descripción para compartir efectiva', $leidos['shareDescription']);
        $limpiar();
        $fijados = [
            'owner' => 'zz-Dueño', 'sitename' => 'zz-Sitio', 'title' => 'zz-Título', 'description' => 'zz-Descripción',
            'keywords' => 'zz-a,zz-b', 'image' => 'https://ejemplo.test/zz.jpg', 'url' => 'https://ejemplo.test/zz/', 'themeColor' => '#123456',
            'locale' => 'zz_ZZ', 'localeAlternate' => 'yy_YY', 'type' => 'article', 'robots' => 'noindex', 'imageAlt' => 'zz-Alternativo',
        ];
        foreach ($fijados as $propiedad => $valor) {
            $setter = 's' . mb_substr($lectores[$propiedad], 1);
            MetaTags::$setter($valor);
        }
        MetaTags::setShareTitle('zz-Compartir');
        foreach ($lectores as $propiedad => $lector) {
            $check(MetaTags::$lector() === $fijados[$propiedad], "m3c {$lector}() = lo fijado con su setter", var_export(MetaTags::$lector(), true));
        }
        $check(MetaTags::getShareTitle() === 'zz-Compartir' && MetaTags::getShareDescription() === 'zz-Descripción', 'm3c getShareTitle() y getShareDescription(): lo fijado manda', MetaTags::getShareTitle() . ' | ' . MetaTags::getShareDescription());
        echoTerminal(' ');

        echoTerminal('[7] Con valores fijos, el HTML es idéntico al de antes de los lectores');
        //Indexable, el dueño, el sitio, la descripción, las palabras, el color y el tipo salen de su omisión; con noindex,
        //de sus setters. Así las dos caras quedan fijadas.
        $fijarReferencia = function (?string $robots) use ($limpiar): string {
            $limpiar();
            set_config('lang_by_cookie', false);
            set_config('alternatives_url', ['zz' => 'https://ejemplo.test/zz/pagina/']);
            set_config('x_account', '@zz_cuenta');
            set_config('owner', 'zz-Dueño');
            set_config('title_app', 'zz-Sitio');
            set_config('description', 'zz-Descripción fija');
            set_config('keywords', ['zz-a', 'zz-b']);
            set_config('meta_theme_color', ' #123456 ');
            set_config('share_description', false);
            MetaTags::setTitle('zz-Título fijo');
            MetaTags::setImage('https://ejemplo.test/imagen.jpg');
            MetaTags::setURL('https://ejemplo.test/pagina/');
            //RETORNO-IGNORADO: `MetaTags::setLocale()` devuelve void; el censo lo confunde por nombre con `setlocale()`.
            MetaTags::setLocale('es_CO');
            MetaTags::setLocaleAlternate('en_US');
            if ($robots !== null) {
                MetaTags::setSitename('zz-Sitio');
                MetaTags::setOwner('zz-Dueño');
                MetaTags::setKeywords('zz-a,zz-b');
                MetaTags::setDescription('zz-Descripción fija');
                MetaTags::setThemeColor('#123456');
                //RETORNO-IGNORADO: `MetaTags::setType()` devuelve void; el censo lo confunde por nombre con `settype()`.
                MetaTags::setType('article');
                MetaTags::setRobots($robots);
            }
            return MetaTags::getMetaTagsGeneric() . MetaTags::getMetaTagsOpenGraph();
        };
        //Las cadenas salen del código anterior a los lectores; solo el idioma servido es del entorno.
        $lang = \PiecesPHP\Core\Config::get_lang();
        $xDefault = $lang === \PiecesPHP\Core\Config::get_default_lang() ? "\t<link rel='alternate' hreflang='x-default' href='https://ejemplo.test/pagina/' />\r\n" : '';
        $referenciaIndexable = "\r\n\t<!-- Meta tags basic -->\r\n\t<title>zz-Título fijo</title>\r\n\r\n\t<meta name='author' content='zz-Dueño' />\r\n\t<meta name='description' content='zz-Descripción fija' />\r\n\t<meta name='keywords' content='zz-a,zz-b' />\r\n\t<meta name='theme-color' content='#123456' />\r\n\t<link rel='canonical' href='https://ejemplo.test/pagina/' />\r\n\t<link rel='alternate' hreflang='zz' href='https://ejemplo.test/zz/pagina/' />\r\n\t<link rel='alternate' hreflang='{$lang}' href='https://ejemplo.test/pagina/' />\r\n" . $xDefault . "\t<!-- Close Meta tags basic -->\r\n\r\n\t<!-- Open Graph Tags -->\r\n\t<meta property='og:site_name' content='zz-Sitio' />\r\n\t<meta property='og:title' content='zz-Título fijo' />\r\n\t<meta property='og:description' content='zz-Descripción fija' />\r\n\t<meta property='og:locale' content='es_CO' />\r\n\t<meta property='og:locale:alternate' content='en_US' />\r\n\t<meta property='og:type' content='website' />\r\n\t<meta property='og:image' content='https://ejemplo.test/imagen.jpg' />\r\n\t<meta property='og:image:alt' content='zz-Título fijo' />\r\n\t<meta property='og:url' content='https://ejemplo.test/pagina/' />\r\n\t<meta name='twitter:card' content='summary_large_image' />\r\n\t<meta name='twitter:title' content='zz-Título fijo' />\r\n\t<meta name='twitter:description' content='zz-Descripción fija' />\r\n\t<meta name='twitter:image' content='https://ejemplo.test/imagen.jpg' />\r\n\t<meta name='twitter:site' content='@zz_cuenta' />\r\n\t<!-- Close Open Graph Tags -->\r\n";
        $referenciaNoindex = "\r\n\t<!-- Meta tags basic -->\r\n\t<title>zz-Título fijo</title>\r\n\r\n\t<meta name='author' content='zz-Dueño' />\r\n\t<meta name='description' content='zz-Descripción fija' />\r\n\t<meta name='keywords' content='zz-a,zz-b' />\r\n\t<meta name='theme-color' content='#123456' />\r\n\t<meta name='robots' content='noindex, nofollow' />\r\n\t<!-- Close Meta tags basic -->\r\n\r\n\t<!-- Open Graph Tags -->\r\n\t<meta property='og:site_name' content='zz-Sitio' />\r\n\t<meta property='og:title' content='zz-Título fijo' />\r\n\t<meta property='og:description' content='zz-Descripción fija' />\r\n\t<meta property='og:locale' content='es_CO' />\r\n\t<meta property='og:locale:alternate' content='en_US' />\r\n\t<meta property='og:type' content='article' />\r\n\t<meta property='og:image' content='https://ejemplo.test/imagen.jpg' />\r\n\t<meta property='og:image:alt' content='zz-Título fijo' />\r\n\t<meta property='og:url' content='https://ejemplo.test/pagina/' />\r\n\t<meta name='twitter:card' content='summary_large_image' />\r\n\t<meta name='twitter:title' content='zz-Título fijo' />\r\n\t<meta name='twitter:description' content='zz-Descripción fija' />\r\n\t<meta name='twitter:image' content='https://ejemplo.test/imagen.jpg' />\r\n\t<meta name='twitter:site' content='@zz_cuenta' />\r\n\t<!-- Close Open Graph Tags -->\r\n";
        $html = $fijarReferencia(null);
        $check($html === $referenciaIndexable, 'm4a indexable: genéricas, canónica, idiomas, Open Graph y Twitter idénticos', var_export($html, true));
        $html = $fijarReferencia('noindex, nofollow');
        $check($html === $referenciaNoindex, 'm4b con noindex: sin canónica ni idiomas, idéntico', var_export($html, true));
        echoTerminal(' ');

        echoTerminal('[8] Una configuración que no es texto no tumba a los lectores');
        foreach (['null' => null, 'un número' => 123] as $caso => $valor) {
            $limpiar();
            set_config('owner', $valor);
            set_config('meta_theme_color', $valor);
            try {
                $ownerValue = MetaTags::getOwner();
                $themeColorValue = MetaTags::getThemeColor();
                $check($ownerValue === '' && $themeColorValue === null, "m5 owner y meta_theme_color con {$caso}: getOwner() da '' y getThemeColor() null", var_export([$ownerValue, $themeColorValue], true));
            } catch (\TypeError $error) {
                $check(false, "m5 owner y meta_theme_color con {$caso}: getOwner() da '' y getThemeColor() null", 'TypeError: ' . $error->getMessage());
            }
        }
    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        foreach ($previo as $nombre => $valor) {
            $clase->setStaticPropertyValue($nombre, $valor);
        }
        foreach ($configPrevia as $nombre => $valor) {
            set_config($nombre, $valor);
        }
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0 && $total > 0, 'message' => "{$passed}/{$total}"];

})->setDescription('MetaTags escapa el título y todas sus etiquetas: con comillas, < y & el <head> sigue válido y el texto se lee igual.')->setEffects([CliActions::EFFECT_NONE])->register();
