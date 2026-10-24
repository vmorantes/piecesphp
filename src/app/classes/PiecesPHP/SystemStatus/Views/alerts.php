<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var array<int,array{key:string,severity:string,message:string,hidden:bool,dismissible:bool,fixURL:string,fixLabel:string}> $rows
 * @var bool $isRoot Ve el interruptor Ocultar/Mostrar en los ocultables
 * @var string $toggleURL
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
$severities = [
    'danger' => ['red', __($langGroup, 'Grave')],
    'warning' => ['orange', __($langGroup, 'Atención')],
    'info' => ['blue', __($langGroup, 'Información')],
];
?>
<section class="module-view-container system-alerts-view" data-system-alerts data-toggle-url="<?= $escape($toggleURL); ?>">

    <div class="limiter-content">

        <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
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
        <div class="container-standard-table">
        <table class="ui basic table" data-system-alerts-table>
            <thead>
                <tr>
                    <th><?= __($langGroup, 'Gravedad'); ?></th>
                    <th><?= __($langGroup, 'Mensaje'); ?></th>
                    <th><?= __($langGroup, 'Estado'); ?></th>
                    <th><?= __($langGroup, 'Cómo arreglarlo'); ?></th>
                    <?php if ($isRoot): ?>
                    <th><?= __($langGroup, 'Ocultar'); ?></th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <?php [$color, $severityLabel] = $severities[$row['severity']] ?? ['grey', $row['severity']]; ?>
                <tr data-system-alert-row="<?= $escape($row['key']); ?>">
                    <td><span class="ui <?= $escape($color); ?> label"><?= $escape($severityLabel); ?></span></td>
                    <td><?= $escape($row['message']); ?></td>
                    <td data-system-alert-state><?= $row['hidden'] ? __($langGroup, 'Oculto') : __($langGroup, 'Activo'); ?></td>
                    <td>
                        <?php if ($row['fixURL'] !== ''): ?>
                        <a class="ui small basic button" href="<?= $escape($row['fixURL']); ?>"><?= $escape($row['fixLabel']); ?></a>
                        <?php endif; ?>
                    </td>
                    <?php if ($isRoot): ?>
                    <td>
                        <?php if ($row['dismissible']): ?>
                        <div class="ui toggle checkbox">
                            <input type="checkbox" id="system-alert-<?= $escape($row['key']); ?>" aria-label="<?= $escape(__($langGroup, 'Ocultar') . ': ' . $row['key']); ?>" data-system-alert-toggle data-key="<?= $escape($row['key']); ?>"<?= $row['hidden'] ? ' checked' : ''; ?>>
                            <label for="system-alert-<?= $escape($row['key']); ?>"></label>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>

    </div>

</section>
