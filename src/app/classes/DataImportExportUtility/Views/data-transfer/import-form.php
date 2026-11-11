<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var array<int,array{label:string,required:bool,aliases:string[],help:?string,example:?string}> $columns
 * @var \PiecesPHP\Core\DataTransfer\Import\ImportDefinition $definition
 * @var bool $dryRunAllowed Nivel 2 o más
 * @var string|null $partial Nivel 2 o más: el .php de formPartial(), ya resuelto dentro del proyecto
 * @var string[] $extensions
 * @var int $maxSizeMB
 * @var int $maxRows
 * @var string $action
 * @var string $template
 * @var \DataImportExportUtility\Controllers\HelperController $helpController Pinta las piezas del panel (la vista se pinta desde el módulo)
 */
$escape = fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$accept = implode(',', array_map(fn($e) => '.' . $e, $extensions));
//El formato de la plantilla sale de las extensiones aceptadas: xlsx si está (y el enlace sin format, que es el que
//importTemplate() sirve por omisión); si no, csv con format=csv. El nombre es el mismo con que la sirve.
$lowerExtensions = array_map(fn($e) => mb_strtolower((string) $e), $extensions);
$templateFormat = in_array('xlsx', $lowerExtensions, true) || !in_array('csv', $lowerExtensions, true) ? 'xlsx' : 'csv';
$templateFilename = 'plantilla-' . $definition->key() . '.' . $templateFormat;
$templateURL = $templateFormat === 'xlsx' ? $template : $template . (str_contains($template, '?') ? '&' : '?') . 'format=csv';
$templateFormatName = $templateFormat === 'xlsx' ? __($langGroup, 'Excel') : __($langGroup, 'CSV');
$limits = sprintf(__($langGroup, 'Archivos %s de hasta %d MB y %d filas. Si una fila tiene errores, no se guarda ninguna.'), implode(', ', $extensions), $maxSizeMB, $maxRows);
$description = trim($definition->description());
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => $description !== '' ? $description : $limits,
            ]);
        ?>

        <?php //Las columnas se casan por título, key o alias, normalizados; lo no declarado se ignora (ImportRunner::run()). ?>
        <div class="ui warning icon message">
            <i class="exclamation triangle icon" aria-hidden="true"></i>
            <div class="content">
                <div class="header"><?= __($langGroup, 'Mantén los títulos de la plantilla'); ?></div>
                <p><?= __($langGroup, 'Las columnas se reconocen por su título, o por los otros nombres de la tabla de abajo; el orden no importa. Si cambias un título, esa columna no se leerá.'); ?></p>
            </div>
        </div>

        <form method="POST" action="<?= $escape($action); ?>" class="ui form initial segment data-transfer-import" data-transfer-import data-content-form>

            <section aria-labelledby="import-step-template">
                <h2 class="ui header" id="import-step-template"><span class="ui flex"><span class="ui active circular label" aria-hidden="true">1</span><?= __($langGroup, 'Descarga la plantilla'); ?></span></h2>
                <div class="ui tight segments"><div class="ui secondary segment">
                    <div class="ui relaxed flex">
                        <i class="green bordered file excel icon" aria-hidden="true"></i>
                        <div class="ui vertical fitted flex">
                            <strong class="ui breaking text"><?= $escape($templateFilename); ?></strong>
                            <small class="ui muted text"><?= $escape(sprintf(__($langGroup, '%s · Llénala sin cambiar sus títulos'), $templateFormatName)); ?></small>
                        </div>
                        <a class="right floated mobile stretched ui basic labeled icon button" href="<?= $escape($templateURL); ?>" download="<?= $escape($templateFilename); ?>"><i class="download icon" aria-hidden="true"></i><?= __($langGroup, 'Descargar plantilla'); ?></a>
                    </div>
                </div></div>
            </section>

            <div class="ui section divider"></div>

            <section aria-labelledby="import-step-file">
                <h2 class="ui header" id="import-step-file"><span class="ui flex"><span class="ui active circular label" aria-hidden="true">2</span><?= __($langGroup, 'Sube la plantilla diligenciada'); ?></span></h2>
                <div class="fields">
                    <?php //El input real va oculto y SIN required: oculto y obligatorio, el navegador frena el envío sin decir nada. Lo valida import.js. ?>
                    <div class="eight wide required field" data-transfer-file-field>
                        <label for="data-transfer-import-file-name"><?= __($langGroup, 'Archivo de la plantilla'); ?></label>
                        <div class="ui action input">
                            <input type="text" id="data-transfer-import-file-name" readonly aria-required="true" aria-describedby="data-transfer-import-file-help" placeholder="<?= $escape(__($langGroup, 'Ningún archivo elegido')); ?>" data-transfer-file-name>
                            <button type="button" class="ui basic labeled icon button" data-transfer-file-browse><i class="upload icon" aria-hidden="true"></i><?= __($langGroup, 'Examinar'); ?></button>
                        </div>
                        <input type="file" id="data-transfer-import-file" name="file" accept="<?= $escape($accept); ?>" hidden tabindex="-1" data-transfer-file>
                        <div class="description" id="data-transfer-import-file-help"><?= $escape($limits); ?></div>
                    </div>
                </div>
                <?php if (isset($partial) && $partial !== null): ?>
                <?php include $partial; ?>
                <?php endif; ?>
                <?php if (isset($dryRunAllowed) && $dryRunAllowed): ?>
                <div class="field">
                    <div class="ui checkbox">
                        <input type="checkbox" name="dryRun" value="yes" id="data-transfer-import-dry-run" data-transfer-dry-run>
                        <label for="data-transfer-import-dry-run"><?= __($langGroup, 'Solo validar (no guarda nada)'); ?></label>
                    </div>
                </div>
                <?php endif; ?>
            </section>

            <?php
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Importar'),
                ]);
            ?>
        </form>

        <section class="ui segment" aria-labelledby="import-columns">
            <h2 class="ui header" id="import-columns"><?= __($langGroup, 'Las columnas de la plantilla'); ?></h2>
            <div class="ui basic fitted horizontal scrolling segment" role="region" tabindex="0" aria-label="<?= __($langGroup, 'Las columnas de la plantilla'); ?>">
            <table class="ui selectable very basic unstackable table content-table">
                <thead>
                    <tr>
                        <th><?= __($langGroup, 'Columna'); ?></th>
                        <th><?= __($langGroup, 'Obligatoria'); ?></th>
                        <th><?= __($langGroup, 'También se reconoce como'); ?></th>
                        <th><?= __($langGroup, 'Ayuda'); ?></th>
                        <th><?= __($langGroup, 'Ejemplo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($columns as $column): ?>
                    <tr>
                        <td><?= $escape($column['label']); ?></td>
                        <td><?= $column['required'] ? __($langGroup, 'Sí') : __($langGroup, 'No'); ?></td>
                        <td><?= $escape(implode(', ', $column['aliases'])); ?></td>
                        <td><?= $escape($column['help'] ?? ''); ?></td>
                        <td><?= $escape($column['example'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>

        <div class="data-transfer-report" data-transfer-report aria-live="polite"></div>

    </div>

</section>
