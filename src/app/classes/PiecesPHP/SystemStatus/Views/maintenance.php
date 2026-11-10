<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string[] $brokenShown Los primeros accesos rotos (de la fila de los accesos directos)
 * @var array<string,\PiecesPHP\SystemStatus\CleanTarget> $cleanTargets Los botones: las limpiezas que puede pulsar quien mira
 * @var array<string,\PiecesPHP\SystemStatus\CleanTarget> $cleanRows Las filas: las limpiezas con tamaño
 * @var array<string,mixed> $maintenanceStatus Las claves de estado de las filas, calculadas una vez (maintenanceStatus())
 * @var string $brokenLinksURL
 * @var string $cacheCleanURL La ruta existente de «Limpiar caché» (configurations-system-cache-clean)
 * @var string $cleanURL La ruta de los borrados por separado (system-status-maintenance-clean)
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
use PiecesPHP\SystemStatus\CleanTarget;

$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
//Pintada fuera del controlador (las suites la incluyen con sus propias variables): la cabecera, con lo que haya.
$helpController = isset($helpController) && $helpController instanceof \PiecesPHP\AdminPanel\Controllers\HelperController ? $helpController : new \PiecesPHP\AdminPanel\Controllers\HelperController();
$description = isset($description) && is_string($description) ? $description : '';
$size = fn(int $bytes): string => $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB';
$brokenShown = isset($brokenShown) && is_array($brokenShown) ? $brokenShown : [];
$cleanTargets = isset($cleanTargets) && is_array($cleanTargets) ? $cleanTargets : \PiecesPHP\SystemStatus\CleanTargetRegistry::all();
$cleanRows = isset($cleanRows) && is_array($cleanRows) ? $cleanRows : \PiecesPHP\SystemStatus\CleanTargetRegistry::withRows();
$maintenanceStatus = isset($maintenanceStatus) && is_array($maintenanceStatus) ? $maintenanceStatus : null;
$linksStatus = $maintenanceStatus ?? \PiecesPHP\SystemStatus\Controllers\SystemStatusController::linksStatus();
//El valor de una fila, con su unidad: lo mismo que pinta system-status.js al refrescar.
$value = function (string $unit, array $keys, array $status) use ($escape, $size, $langGroup): string {
    $first = $status[$keys[0]] ?? '';
    if ($unit === CleanTarget::UNIT_BYTES) {
        return $escape($size((int) $first));
    }
    if ($unit === CleanTarget::UNIT_STAMP) {
        return '<code>' . $escape($first) . '</code>';
    }
    return isset($keys[1]) ? $escape(sprintf(__($langGroup, '%s de %s'), $first, $status[$keys[1]] ?? '')) : $escape($first);
};
//Las filas, en su orden: la fija de los accesos rotos (su ruta y su respuesta son otras), las del registro y, al final, los
//destinos sin fila que quien mira puede pulsar. `button` es null si no puede pulsarlo.
$rows = [[
    'id' => 'system-maintenance-broken-links-name',
    'icon' => 'unlink',
    'name' => __($langGroup, 'Accesos rotos'),
    'hint' => __($langGroup, 'Accesos directos que apuntan a un archivo que ya no existe'),
    'value' => ['unit' => CleanTarget::UNIT_NUMBER, 'keys' => ['linksBroken', 'linksTotal'], 'status' => $linksStatus, 'target' => ''],
    'button' => ['text' => __($langGroup, 'Vaciar'), 'title' => __($langGroup, 'Borrar accesos rotos'), 'url' => $brokenLinksURL, 'target' => null, 'confirm' => __($langGroup, '¿Borrar los accesos directos rotos?')],
]];
foreach ($cleanRows as $row) {
    $target = $cleanTargets[$row->key()] ?? null;
    $rows[] = [
        'id' => 'system-maintenance-name-' . $row->key(),
        'icon' => $row->rowIcon(),
        'name' => $row->rowLabel(),
        'hint' => $row->rowHint(),
        'value' => ['unit' => $row->rowUnit(), 'keys' => $row->rowKeys(), 'status' => $maintenanceStatus ?? $row->status(), 'target' => $row->key()],
        'button' => $target === null ? null : ['text' => $row->rowUnit() === CleanTarget::UNIT_STAMP ? __($langGroup, 'Renovar') : __($langGroup, 'Vaciar'), 'title' => $target->label(), 'url' => $cleanURL, 'target' => $target->key(), 'confirm' => $target->confirmation()],
    ];
}
foreach ($cleanTargets as $target) {
    if (!$target->hasRow()) {
        $rows[] = [
            'id' => 'system-maintenance-name-' . $target->key(),
            'icon' => 'archive',
            'name' => $target->label(),
            'hint' => '',
            'value' => null,
            'button' => ['text' => __($langGroup, 'Vaciar'), 'title' => $target->label(), 'url' => $cleanURL, 'target' => $target->key(), 'confirm' => $target->confirmation()],
        ];
    }
}
?>
<section class="module-view-container system-maintenance-view" data-system-maintenance>

    <?php
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <section class="ui segment" aria-label="<?= $escape($title); ?>">

        <div class="ui very relaxed divided list system-maintenance-caches" role="list">
            <?php foreach ($rows as $row): ?>
            <div class="item system-maintenance-cache" role="listitem">
                <div class="ui stackable very relaxed flex">
                    <div class="fluid ui nowrap very relaxed flex">
                        <i class="primary tinted bordered <?= $escape($row['icon']); ?> icon" aria-hidden="true"></i>
                        <div class="fluid content">
                            <div class="header" id="<?= $escape($row['id']); ?>"><?= $escape($row['name']); ?></div>
                            <?php if ($row['hint'] !== ''): ?>
                            <div class="description"><span class="ui small muted text"><?= $escape($row['hint']); ?></span></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="ui stackable very relaxed flex">
                        <?php if ($row['value'] !== null): ?>
                        <?php //Una marca (un hash de 40 caracteres) va en una línea con puntos suspensivos y entera en title: partida dejaba un carácter solo. ?>
                        <?php $isStamp = $row['value']['unit'] === CleanTarget::UNIT_STAMP; ?>
                        <strong class="ui tabular<?= $isStamp ? ' truncated' : ''; ?> text"<?= $isStamp ? ' title="' . $escape((string) ($row['value']['status'][$row['value']['keys'][0]] ?? '')) . '"' : ''; ?> data-system-maintenance-row="<?= $escape($row['value']['target']); ?>" data-unit="<?= $escape($row['value']['unit']); ?>" data-keys="<?= $escape(implode(',', $row['value']['keys'])); ?>"><?= $value($row['value']['unit'], $row['value']['keys'], $row['value']['status']); ?></strong>
                        <?php endif; ?>
                        <?php if ($row['button'] !== null): ?>
                        <button type="button" class="ui basic button" title="<?= $escape($row['button']['title']); ?>" aria-describedby="<?= $escape($row['id']); ?>" data-system-maintenance-action="<?= $escape($row['button']['url']); ?>"<?php if ($row['button']['target'] !== null): ?> data-system-maintenance-target="<?= $escape($row['button']['target']); ?>"<?php endif; ?> data-confirm="<?= $escape($row['button']['confirm']); ?>"><?= $escape($row['button']['text']); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (count($brokenShown) > 0): ?>
        <h2 class="ui header"><?= $escape(sprintf(__($langGroup, 'Accesos rotos (los %d primeros)'), count($brokenShown))); ?></h2>
        <ul data-system-maintenance-broken-list>
            <?php foreach ($brokenShown as $path): ?>
            <li><code><?= $escape($path); ?></code></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="ui section divider"></div>

        <?php
            $helpController->_render('panel/built-in/content/main-action.php', [
                'title' => __($langGroup, 'Borrar todo lo temporal'),
                'description' => __($langGroup, 'Vacía todas las cachés de una vez. El sitio irá algo más lento mientras se vuelven a crear.'),
                'buttonText' => __($langGroup, 'Borrar todo lo temporal'),
                'attributes' => [
                    'data-system-maintenance-action' => $cacheCleanURL,
                    'data-confirm' => __($langGroup, '¿Borrar todas las copias temporales? El sitio irá algo más lento mientras se vuelven a crear.'),
                ],
            ]);
        ?>

        <div data-system-maintenance-result aria-live="polite"></div>

    </section>

</section>
