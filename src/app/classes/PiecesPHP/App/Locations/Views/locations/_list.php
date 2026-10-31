<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Utilities\Helpers\RowActions;

/**
 * El listado de las cuatro entidades de Ubicaciones: solo cambian sus columnas. La de acciones la pone la plantilla.
 *
 * @var \PiecesPHP\App\Locations\Controllers\HelperController $pieces Pinta las piezas del panel (la vista se pinta desde el módulo)
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $title
 * @var string $addText
 * @var string $add_link
 * @var bool $has_add_link_permissions
 * @var string $process_table
 * @var array<int,array{text:string,attributes?:array<string,string>}> $columns
 */
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $pieces->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'primaryAction' => $has_add_link_permissions ? ['text' => $addText, 'url' => $add_link, 'icon' => 'plus'] : null,
            ]);
        ?>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="ui fluid card container-standard-table all">

            <table process="<?= escape_html($process_table); ?>" class="ui selectable very basic table content-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                        <th<?= RowActions::attributes($column['attributes'] ?? []); ?>><?= escape_html($column['text']); ?></th>
                        <?php endforeach; ?>
                        <th order="false" search="false" class-name="collapsing"><?= __(LOCATIONS_LANG_GROUP, 'Acciones'); ?></th>
                    </tr>
                </thead>
            </table>

        </div>

    </div>

</section>
