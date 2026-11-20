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
    [Country::class, __(LOCATIONS_LANG_GROUP, 'Países'), __(LOCATIONS_LANG_GROUP, 'Listado de los países')],
    [State::class, __(LOCATIONS_LANG_GROUP, 'Departamentos'), __(LOCATIONS_LANG_GROUP, 'Listado de los departamentos')],
    [City::class, __(LOCATIONS_LANG_GROUP, 'Ciudades'), __(LOCATIONS_LANG_GROUP, 'Listado de las ciudades')],
    [Point::class, __(LOCATIONS_LANG_GROUP, 'Coordenadas'), __(LOCATIONS_LANG_GROUP, 'Listado de las coordenadas')],
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
            foreach ($elements as [$controller, $cardTitle, $cardDescription]) {
                $url = $controller::routeName('list');
                $caption = __(LOCATIONS_LANG_GROUP, 'Ver el listado');
                if ($url === '') {
                    $url = $controller::routeName('forms-add');
                    $caption = __(LOCATIONS_LANG_GROUP, 'Agregar');
                }
                if ($url === '') {
                    continue;
                }
                //El acceso directo del tema (como en el Inicio): la tarjeta entera es el enlace, sin icono ni flecha.
                $pieces->_render('panel/built-in/statistics/statistic-card.php', [
                    'title' => $cardTitle,
                    'figure' => ['url' => $url, 'description' => $cardDescription],
                    'footer' => ['caption' => $caption],
                ]);
            }
            $pieces->_render('panel/built-in/statistics/statistics-section.php', [
                'content' => (string) ob_get_clean(),
            ]);
        ?>

    </div>

</section>
