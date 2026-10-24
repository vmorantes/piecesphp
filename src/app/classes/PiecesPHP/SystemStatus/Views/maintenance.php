<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var int $linksTotal
 * @var int $linksBroken
 * @var string[] $brokenShown
 * @var int $webpCacheBytes
 * @var int $publicationsCacheBytes
 * @var string $staticsStamp
 * @var string $brokenLinksURL
 * @var string $cacheCleanURL La ruta existente de «Limpiar caché» (configurations-system-cache-clean)
 * @var string $cleanURL La ruta de los borrados por separado (system-status-maintenance-clean)
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//Pintada fuera del controlador (las suites la incluyen con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';
$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [];
$size = fn(int $bytes): string => $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
?>
<section class="module-view-container system-maintenance-view" data-system-maintenance>

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <div class="container-standard-table">
            <table class="ui basic table">
                <tbody>
                    <tr>
                        <td><?= __($langGroup, 'Accesos directos a archivos de módulos'); ?></td>
                        <td data-system-maintenance-links><?= $escape($linksTotal); ?> · <?= __($langGroup, 'rotos'); ?>: <?= $escape($linksBroken); ?></td>
                    </tr>
                    <tr>
                        <td><?= __($langGroup, 'Imágenes optimizadas'); ?></td>
                        <td data-system-maintenance-webp><?= $escape($size($webpCacheBytes)); ?></td>
                    </tr>
                    <tr>
                        <td><?= __($langGroup, 'Listados de publicaciones guardados'); ?></td>
                        <td data-system-maintenance-publications><?= $escape($size($publicationsCacheBytes)); ?></td>
                    </tr>
                    <tr>
                        <td><?= __($langGroup, 'Versión de los archivos estáticos'); ?><br><small><?= __($langGroup, 'Estilos, scripts e imágenes de las páginas (no las imágenes que cargan las hojas de estilo, las que pone el JavaScript ni las del correo)'); ?></small></td>
                        <td data-system-maintenance-stamp><code><?= $escape($staticsStamp); ?></code></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="container-standard-form">

            <?php if (count($brokenShown) > 0): ?>
            <div class="form-section-title"><?= $escape(sprintf(__($langGroup, 'Accesos rotos (los %d primeros)'), count($brokenShown))); ?></div>
            <ul data-system-maintenance-broken-list>
                <?php foreach ($brokenShown as $path): ?>
                <li><code><?= $escape($path); ?></code></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>

            <div class="form-section-title"><?= __($langGroup, 'Borrar una sola cosa'); ?></div>
            <div class="main-buttons">
                <button type="button" class="ui basic button" data-system-maintenance-action="<?= $escape($brokenLinksURL); ?>" data-confirm="<?= $escape(__($langGroup, '¿Borrar los accesos directos rotos?')); ?>"><?= __($langGroup, 'Borrar accesos rotos'); ?></button>
                <button type="button" class="ui basic button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="statics-stamp" data-confirm="<?= $escape(__($langGroup, '¿Seguro? Todos los visitantes volverán a descargar los estilos, los scripts y las imágenes de las páginas.')); ?>"><?= __($langGroup, 'Forzar que todos descarguen de nuevo los archivos estáticos'); ?></button>
                <button type="button" class="ui basic button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="webp-cache" data-confirm="<?= $escape(__($langGroup, '¿Borrar las imágenes optimizadas? Se vuelven a crear la primera vez que se pida cada una.')); ?>"><?= __($langGroup, 'Borrar imágenes optimizadas'); ?></button>
                <button type="button" class="ui basic button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="publications-cache" data-confirm="<?= $escape(__($langGroup, '¿Borrar los listados guardados? Se vuelven a crear al pedirse.')); ?>"><?= __($langGroup, 'Borrar listados guardados'); ?></button>
                <button type="button" class="ui basic button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="server-delegated-links" data-confirm="<?= $escape(__($langGroup, '¿Borrar todos los accesos directos, no solo los rotos? Se vuelven a crear al pedirse cada archivo.')); ?>"><?= __($langGroup, 'Borrar todos los accesos directos'); ?></button>
            </div>

            <div class="main-buttons">
                <button type="button" class="ui button brand-color" data-system-maintenance-action="<?= $escape($cacheCleanURL); ?>" data-confirm="<?= $escape(__($langGroup, '¿Borrar todas las copias temporales? El sitio irá algo más lento mientras se vuelven a crear.')); ?>"><?= __($langGroup, 'Borrar todo lo temporal'); ?></button>
            </div>

            <div data-system-maintenance-result aria-live="polite"></div>

        </div>

    </div>

</section>
