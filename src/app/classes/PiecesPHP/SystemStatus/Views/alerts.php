<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var array<int,array{key:string,severity:string,message:string,hidden:bool,dismissible:bool,fixURL:string,fixLabel:string}> $rows
 * @var bool $isRoot Ve el botón Ocultar/Mostrar en los ocultables
 * @var string $toggleURL
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
use PiecesPHP\Core\Utilities\Helpers\RowActions;
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;

$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//Pintada fuera del controlador (las suites la incluyen con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';
$severities = [
    'danger' => ['red', __($langGroup, 'Grave')],
    'warning' => ['yellow', __($langGroup, 'Atención')],
    'info' => ['teal', __($langGroup, 'Información')],
];
?>
<section class="module-view-container system-alerts-view" data-system-alerts data-toggle-url="<?= $escape($toggleURL); ?>">

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <?php if (count($rows) === 0): ?>
    <?php
        $helpController->_render('panel/built-in/content/empty-state.php', [
            'text' => __($langGroup, 'No hay avisos activos.'),
            'icon' => 'check circle outline',
        ]);
    ?>
    <?php else: ?>
    <?php //Sin desplazamiento en escritorio; a ≤767 la tabla guarda su ancho mínimo y se desplaza dentro (el fork). ?>
    <section class="ui horizontal scrolling segment" role="region" tabindex="0" aria-label="<?= $escape($title); ?>">
        <table class="ui very basic unstackable table" data-system-alerts-table>
            <thead>
                <tr>
                    <th class="collapsing"><?= __($langGroup, 'Gravedad'); ?></th>
                    <th><?= __($langGroup, 'Mensaje'); ?></th>
                    <th class="collapsing"><?= __($langGroup, 'Estado'); ?></th>
                    <th class="collapsing"><?= __($langGroup, 'Cómo arreglarlo'); ?></th>
                    <?php if ($isRoot): ?>
                    <th class="collapsing"><?= __($langGroup, 'Acciones'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <?php [$color, $severityLabel] = $severities[$row['severity']] ?? ['teal', $row['severity']]; ?>
                <tr data-system-alert-row="<?= $escape($row['key']); ?>">
                    <td class="collapsing"><?= StatusLabel::html($severityLabel, $color); ?></td>
                    <td><?= $escape($row['message']); ?></td>
                    <td class="collapsing"><span data-system-alert-state><?= $row['hidden'] ? StatusLabel::neutral(__($langGroup, 'Oculto')) : StatusLabel::html(__($langGroup, 'Activo'), StatusLabel::POSITIVE); ?></span></td>
                    <td class="collapsing">
                        <?php if ($row['fixURL'] !== ''): ?>
                        <a class="ui basic button" href="<?= $escape($row['fixURL']); ?>"><?= $escape($row['fixLabel']); ?></a>
                        <?php endif; ?>
                    </td>
                    <?php if ($isRoot): ?>
                    <td class="collapsing">
                        <?php if ($row['dismissible']): ?>
                        <?php //El icono y el texto dicen lo que hará el botón; system-status.js los cambia al confirmar el servidor. ?>
                        <?php $toggleText = $row['hidden'] ? __($langGroup, 'Mostrar') : __($langGroup, 'Ocultar'); ?>
                        <?= RowActions::cell(RowActions::action($toggleText, '#', $row['hidden'] ? 'eye' : 'eye slash', ['data-system-alert-toggle' => '', 'data-key' => $row['key'], 'data-hidden' => $row['hidden'] ? 'yes' : 'no'])); ?>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

</section>
