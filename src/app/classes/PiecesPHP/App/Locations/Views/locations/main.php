<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\App\Locations\Controllers\City;
use PiecesPHP\App\Locations\Controllers\Country;
use PiecesPHP\App\Locations\Controllers\Point;
use PiecesPHP\App\Locations\Controllers\State;

/**
 * La portada de Ubicaciones: una tarjeta por entidad, que lleva a su listado (o a su alta, si solo se puede agregar).
 *
 * @var \PiecesPHP\App\Locations\Controllers\HelperController $pieces Pinta las piezas del panel (la vista se pinta desde el módulo)
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $title
 */
$elements = [
    [Country::class, 'globe', __(LOCATIONS_LANG_GROUP, 'Países'), __(LOCATIONS_LANG_GROUP, 'Listado de los países')],
    [State::class, 'flag', __(LOCATIONS_LANG_GROUP, 'Departamentos'), __(LOCATIONS_LANG_GROUP, 'Listado de los departamentos')],
    [City::class, 'building outline', __(LOCATIONS_LANG_GROUP, 'Ciudades'), __(LOCATIONS_LANG_GROUP, 'Listado de las ciudades')],
    [Point::class, 'map pin', __(LOCATIONS_LANG_GROUP, 'Coordenadas'), __(LOCATIONS_LANG_GROUP, 'Listado de las coordenadas')],
];
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        $pieces->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
        ]);

        ob_start();
        foreach ($elements as [$controller, $icon, $cardTitle, $cardDescription]) {
            $url = $controller::routeName('list');
            $url = $url !== '' ? $url : $controller::routeName('forms-add');
            if ($url === '') {
                continue;
            }
            $pieces->_render('panel/built-in/statistics/action-card.php', [
                'url' => $url,
                'icon' => $icon,
                'title' => $cardTitle,
                'description' => $cardDescription,
            ]);
        }
        $pieces->_render('panel/built-in/statistics/statistics-section.php', [
            'content' => (string) ob_get_clean(),
        ]);
        ?>

    </div>

</section>
