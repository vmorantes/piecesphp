<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var array<int,array{group:string,label:string,value:string,state:string,origin:string}> $rows SystemHealth::rows()
 * @var array<string,string> $groupTitles
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
use PiecesPHP\SystemStatus\SystemHealth;

//Pintada fuera del controlador (la suite la incluye con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';
$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [];

$states = [
    SystemHealth::STATE_OK => ['green', __($langGroup, 'Bien')],
    SystemHealth::STATE_WARNING => ['orange', __($langGroup, 'Atención')],
    SystemHealth::STATE_MISSING => ['red', __($langGroup, 'Falta')],
    SystemHealth::STATE_INFO => ['grey', __($langGroup, 'Información')],
];
?>
<section class="module-view-container system-health-view" data-system-health>

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <?php foreach ($groupTitles as $group => $groupTitle): ?>
        <div class="container-standard-form" data-system-health-group="<?= escape_html($group); ?>">
            <div class="form-section-title"><?= escape_html($groupTitle); ?></div>
            <table class="ui very basic table">
                <thead>
                    <tr>
                        <th><?= __($langGroup, 'Dato'); ?></th>
                        <th><?= __($langGroup, 'Valor'); ?></th>
                        <th><?= __($langGroup, 'Origen'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                    <?php if ($row['group'] !== $group) continue; ?>
                    <?php [$color, $stateText] = $states[$row['state']] ?? ['grey', $row['state']]; ?>
                    <tr data-system-health-row>
                        <td><?= escape_html($row['label']); ?></td>
                        <td><span class="ui small <?= escape_html($color); ?> label"><?= escape_html($stateText); ?></span> <?= escape_html($row['value']); ?></td>
                        <td><span class="ui small grey text" data-system-health-origin><?= escape_html($row['origin']); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endforeach; ?>

    </div>

</section>
