<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $breadcrumbs
 * @var array<int,array{key:string,title:string,description:string,link:string,templateXlsx:string,templateCsv:string|null,enabled:bool}> $importers
 * @var array<int,array{key:string,title:string,description:string,link:string,downloadXlsx:string|null,downloadCsv:string|null,enabled:bool}> $exporters
 * @var bool $isRoot Ve la columna «Activo» y las filas apagadas
 * @var string $toggleURL
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$toggle = function (string $kind, array $row) use ($escape, $langGroup): string {
    $id = "data-transfer-toggle-{$kind}-{$row['key']}";
    $label = sprintf(__($langGroup, 'Activo: %s'), $row['title']);
    return '<div class="ui toggle checkbox">'
        . '<input type="checkbox" id="' . $escape($id) . '" aria-label="' . $escape($label) . '" data-transfer-toggle data-kind="' . $escape($kind) . '" data-key="' . $escape($row['key']) . '"' . ($row['enabled'] ? ' checked' : '') . '>'
        . '<label for="' . $escape($id) . '"></label>'
        . '</div>';
};
?>
<section class="module-view-container" data-transfer-hub data-toggle-url="<?= $escape($toggleURL); ?>">

    <div class="breadcrumb">
        <?= $breadcrumbs; ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $escape($title); ?></div>
            <div class="description"><?= __($langGroup, 'Carga datos desde una hoja de cálculo, o descarga los del sistema con los filtros que elijas.'); ?></div>
        </div>

        <br>

        <div class="tabs-controls">
            <div class="active" data-tab="importar"><?= __($langGroup, 'Importar'); ?></div>
            <div data-tab="exportar"><?= __($langGroup, 'Exportar'); ?></div>
        </div>

        <div class="ui tab tab-element active" data-tab="importar">
            <p><?= __($langGroup, 'Descarga la plantilla, llénala y súbela. Puedes validarla antes de guardar.'); ?></p>
            <?php if (count($importers) === 0): ?>
            <p><?= __($langGroup, 'No hay importadores disponibles para tu usuario.'); ?></p>
            <?php else: ?>
            <table class="ui basic table" data-transfer-table="import">
                <thead>
                    <tr>
                        <th><?= __($langGroup, 'Entidad'); ?></th>
                        <th><?= __($langGroup, 'Descripción'); ?></th>
                        <th><?= __($langGroup, 'Acciones'); ?></th>
                        <?php if ($isRoot): ?>
                        <th><?= __($langGroup, 'Activo'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($importers as $importer): ?>
                    <?php //Apagada: gris y sin enlaces; el interruptor sigue activo para volver a encenderla. ?>
                    <?php $off = $importer['enabled'] ? '' : 'disabled'; ?>
                    <tr data-transfer-row="import-<?= $escape($importer['key']); ?>">
                        <td class="<?= $off; ?>"><?= $escape($importer['title']); ?></td>
                        <td class="<?= $off; ?>"><?= $escape($importer['description']); ?></td>
                        <td class="<?= $off; ?>">
                            <a class="ui button brand-color" href="<?= $escape($importer['link']); ?>"><?= __($langGroup, 'Importar'); ?></a>
                            <a class="ui button brand-color alt" href="<?= $escape($importer['templateXlsx']); ?>"><?= __($langGroup, 'Plantilla XLSX'); ?></a>
                            <?php if ($importer['templateCsv'] !== null): ?>
                            <a class="ui button brand-color alt" href="<?= $escape($importer['templateCsv']); ?>"><?= __($langGroup, 'Plantilla CSV'); ?></a>
                            <?php endif; ?>
                        </td>
                        <?php if ($isRoot): ?>
                        <td><?= $toggle('import', $importer); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <div class="ui tab tab-element" data-tab="exportar">
            <p><?= __($langGroup, 'Elige los filtros y el formato, y descarga el archivo.'); ?></p>
            <?php if (count($exporters) === 0): ?>
            <p><?= __($langGroup, 'No hay exportadores disponibles para tu usuario.'); ?></p>
            <?php else: ?>
            <table class="ui basic table" data-transfer-table="export">
                <thead>
                    <tr>
                        <th><?= __($langGroup, 'Entidad'); ?></th>
                        <th><?= __($langGroup, 'Descripción'); ?></th>
                        <th><?= __($langGroup, 'Acciones'); ?></th>
                        <?php if ($isRoot): ?>
                        <th><?= __($langGroup, 'Activo'); ?></th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exporters as $exporter): ?>
                    <?php $off = $exporter['enabled'] ? '' : 'disabled'; ?>
                    <tr data-transfer-row="export-<?= $escape($exporter['key']); ?>">
                        <td class="<?= $off; ?>"><?= $escape($exporter['title']); ?></td>
                        <td class="<?= $off; ?>"><?= $escape($exporter['description']); ?></td>
                        <td class="<?= $off; ?>">
                            <a class="ui button brand-color" href="<?= $escape($exporter['link']); ?>"><?= __($langGroup, 'Exportar'); ?></a>
                            <?php if ($exporter['downloadXlsx'] !== null): ?>
                            <a class="ui button brand-color alt" href="<?= $escape($exporter['downloadXlsx']); ?>">XLSX</a>
                            <?php endif; ?>
                            <?php if ($exporter['downloadCsv'] !== null): ?>
                            <a class="ui button brand-color alt" href="<?= $escape($exporter['downloadCsv']); ?>">CSV</a>
                            <?php endif; ?>
                        </td>
                        <?php if ($isRoot): ?>
                        <td><?= $toggle('export', $exporter); ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

    </div>

</section>
