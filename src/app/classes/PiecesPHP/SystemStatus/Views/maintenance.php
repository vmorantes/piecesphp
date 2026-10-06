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
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$size = fn(int $bytes): string => $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
?>
<main class="system-maintenance-view" data-system-maintenance>

    <section class="main-body-header">

        <div class="head">
            <h2 class="tittle"><?= $escape($title); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Sistema'); ?></span>
        </div>

        <div class="body-card">

        <div>

        <p><?= __($langGroup, 'Copias temporales que guarda el sistema para que el sitio cargue rápido. Si algo no se ve actualizado, bórralas aquí: se vuelven a crear solas.'); ?></p>

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
                    <td><?= __($langGroup, 'Versión de estilos y scripts'); ?></td>
                    <td data-system-maintenance-stamp><code><?= $escape($staticsStamp); ?></code></td>
                </tr>
            </tbody>
        </table>

        <?php if (count($brokenShown) > 0): ?>
        <h4 class="ui header"><?= $escape(sprintf(__($langGroup, 'Accesos rotos (los %d primeros)'), count($brokenShown))); ?></h4>
        <ul data-system-maintenance-broken-list>
            <?php foreach ($brokenShown as $path): ?>
            <li><code><?= $escape($path); ?></code></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <h4 class="ui header"><?= __($langGroup, 'Borrar una sola cosa'); ?></h4>
        <div class="main-buttons">
            <button type="button" class="ui button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="statics-stamp" data-confirm="<?= $escape(__($langGroup, '¿Seguro? Todos los visitantes volverán a descargar los estilos y los scripts.')); ?>"><?= __($langGroup, 'Forzar que todos descarguen de nuevo estilos y scripts'); ?></button>
            <button type="button" class="ui button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="webp-cache" data-confirm="<?= $escape(__($langGroup, '¿Borrar las imágenes optimizadas? Se vuelven a crear la primera vez que se pida cada una.')); ?>"><?= __($langGroup, 'Borrar imágenes optimizadas'); ?></button>
            <button type="button" class="ui button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="publications-cache" data-confirm="<?= $escape(__($langGroup, '¿Borrar los listados guardados? Se vuelven a crear al pedirse.')); ?>"><?= __($langGroup, 'Borrar listados guardados'); ?></button>
            <button type="button" class="ui button" data-system-maintenance-action="<?= $escape($cleanURL); ?>" data-system-maintenance-target="server-delegated-links" data-confirm="<?= $escape(__($langGroup, '¿Borrar todos los accesos directos, no solo los rotos? Se vuelven a crear al pedirse cada archivo.')); ?>"><?= __($langGroup, 'Borrar todos los accesos directos'); ?></button>
        </div>

        <br>

        <div class="main-buttons">
            <button type="button" class="ui button brand-color" data-system-maintenance-action="<?= $escape($brokenLinksURL); ?>" data-confirm="<?= $escape(__($langGroup, '¿Borrar los accesos directos rotos?')); ?>"><?= __($langGroup, 'Borrar accesos rotos'); ?></button>
            <button type="button" class="ui button brand-color alt" data-system-maintenance-action="<?= $escape($cacheCleanURL); ?>" data-confirm="<?= $escape(__($langGroup, '¿Borrar todas las copias temporales? El sitio irá algo más lento mientras se vuelven a crear.')); ?>"><?= __($langGroup, 'Borrar todo lo temporal'); ?></button>
        </div>

        <br>

        <div data-system-maintenance-result aria-live="polite"></div>

        </div>

        </div>

    </section>

</main>
