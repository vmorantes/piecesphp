<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var array<int,array{group:string,label:string,value:string,state:string,origin:string}> $rows SystemHealth::rows()
 * @var array<string,string> $groupTitles
 * @var string $description
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
use PiecesPHP\Core\Utilities\Helpers\StatusLabel;
use PiecesPHP\SystemStatus\SystemHealth;

//Pintada fuera del controlador (la suite la incluye con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';

$states = [
    SystemHealth::STATE_OK => [StatusLabel::POSITIVE, __($langGroup, 'Bien')],
    SystemHealth::STATE_WARNING => [StatusLabel::WARNING, __($langGroup, 'Atención')],
    SystemHealth::STATE_MISSING => [StatusLabel::NEGATIVE, __($langGroup, 'Falta')],
    SystemHealth::STATE_INFO => [StatusLabel::INFO, __($langGroup, 'Información')],
];
//Lo que pide atención: avisos y faltas, de todos los grupos.
$attentionRows = array_values(array_filter($rows, fn(array $row): bool => in_array($row['state'], [SystemHealth::STATE_WARNING, SystemHealth::STATE_MISSING], true)));
?>
<section class="module-view-container system-health-view" data-system-health>

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <?php //Primero lo que pide atención, de todos los grupos; después, un grupo por pestaña (P319). ?>
    <div class="ui secondary pointing menu" aria-label="<?= escape_html(__($langGroup, 'Grupos de la salud de la instalación')); ?>" data-system-health-tabs>
        <a class="active item" data-tab="system-health-summary"><?= __($langGroup, 'Resumen'); ?></a>
        <?php foreach ($groupTitles as $group => $groupTitle): ?>
        <a class="item" data-tab="system-health-<?= escape_html(preg_replace('/[^a-z0-9-]/', '-', mb_strtolower((string) $group))); ?>"><?= escape_html($groupTitle); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="ui active tab" data-tab="system-health-summary">
    <section class="ui segment" aria-labelledby="system-health-summary-title" data-system-health-summary>
        <h2 class="ui header" id="system-health-summary-title"><?= __($langGroup, 'Resumen'); ?></h2>
        <?php if ($attentionRows === []): ?>
        <div class="ui positive message" data-system-health-all-ok><?= __($langGroup, 'Todo en orden.'); ?></div>
        <?php else: ?>
        <div class="ui basic fitted horizontal scrolling mirrored segment" role="region" tabindex="0" aria-label="<?= escape_html(__($langGroup, 'Resumen')); ?>">
        <table class="ui very basic unstackable table" aria-label="<?= escape_html(__($langGroup, 'Resumen')); ?>">
            <thead>
                <tr>
                    <th scope="col" class="three wide"><?= __($langGroup, 'Grupo'); ?></th>
                    <th scope="col" class="three wide"><?= __($langGroup, 'Dato'); ?></th>
                    <th scope="col" class="two wide"><?= __($langGroup, 'Estado'); ?></th>
                    <th scope="col" class="four wide"><?= __($langGroup, 'Valor'); ?></th>
                    <th scope="col" class="right aligned"><?= __($langGroup, 'Origen'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attentionRows as $row): ?>
                <?php [$color, $stateText] = $states[$row['state']] ?? [StatusLabel::INFO, $row['state']]; ?>
                <tr data-system-health-summary-row>
                    <td><?= escape_html($groupTitles[$row['group']] ?? $row['group']); ?></td>
                    <td><?= escape_html($row['label']); ?></td>
                    <td><?= StatusLabel::html($stateText, $color); ?></td>
                    <?php //Un hash de commit (40 caracteres) se parte dejando un carácter solo: va en una línea con puntos y entero en title. ?>
                    <?php if (preg_match('/^[0-9a-f]{40}$/', $row['value']) === 1): ?>
                    <td class="truncated" title="<?= escape_html($row['value']); ?>"><?= escape_html($row['value']); ?></td>
                    <?php else: ?>
                    <td><?= escape_html($row['value']); ?></td>
                    <?php endif; ?>
                    <td class="right aligned"><span class="ui muted text" data-system-health-origin><?= escape_html($row['origin']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </section>
    </div>

    <?php foreach ($groupTitles as $group => $groupTitle): ?>
    <?php $groupId = 'system-health-' . preg_replace('/[^a-z0-9-]/', '-', mb_strtolower((string) $group)); ?>
    <div class="ui tab" data-tab="<?= escape_html($groupId); ?>">
    <section class="ui segment" aria-labelledby="<?= escape_html($groupId); ?>" data-system-health-group="<?= escape_html($group); ?>">
        <h2 class="ui header" id="<?= escape_html($groupId); ?>"><?= escape_html($groupTitle); ?></h2>
        <div class="ui basic fitted horizontal scrolling mirrored segment" role="region" tabindex="0" aria-label="<?= escape_html($groupTitle); ?>">
        <table class="ui very basic unstackable table" aria-label="<?= escape_html($groupTitle); ?>">
            <thead>
                <tr>
                    <th scope="col" class="four wide"><?= __($langGroup, 'Dato'); ?></th>
                    <th scope="col" class="three wide"><?= __($langGroup, 'Estado'); ?></th>
                    <th scope="col" class="four wide"><?= __($langGroup, 'Valor'); ?></th>
                    <th scope="col" class="right aligned"><?= __($langGroup, 'Origen'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <?php if ($row['group'] !== $group) continue; ?>
                <?php [$color, $stateText] = $states[$row['state']] ?? [StatusLabel::INFO, $row['state']]; ?>
                <tr data-system-health-row>
                    <td><?= escape_html($row['label']); ?></td>
                    <td><?= StatusLabel::html($stateText, $color); ?></td>
                    <?php //Un hash de commit (40 caracteres) se parte dejando un carácter solo: va en una línea con puntos y entero en title. ?>
                    <?php if (preg_match('/^[0-9a-f]{40}$/', $row['value']) === 1): ?>
                    <td class="truncated" title="<?= escape_html($row['value']); ?>"><?= escape_html($row['value']); ?></td>
                    <?php else: ?>
                    <td><?= escape_html($row['value']); ?></td>
                    <?php endif; ?>
                    <td class="right aligned"><span class="ui muted text" data-system-health-origin><?= escape_html($row['origin']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </section>
    </div>
    <?php endforeach; ?>

</section>
