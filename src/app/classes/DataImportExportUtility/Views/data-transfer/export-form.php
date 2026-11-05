<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\DataTransfer\Export\ExportParameter;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;

/**
 * @var string $langGroup
 * @var string $title
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var ExportParameter[] $parameters
 * @var string $action
 * @var array{title:string,url:string}|null $reimport
 * @var \PiecesPHP\Core\DataTransfer\Export\ExportDefinition $definition
 * @var string|null $previewURL Nivel 2 o más
 * @var array<int,array{key:string,label:string}> $columns Nivel 2 o más: para elegir y ordenar
 * @var string|null $partial Nivel 2 o más: el .php de formPartial(), ya resuelto dentro del proyecto
 * @var array<string,array<string,mixed>>|null $presets Nivel 2 o más: los filtros guardados del usuario
 * @var string|null $presetSaveURL
 * @var string|null $presetDeleteURL
 * @var \DataImportExportUtility\Controllers\HelperController $helpController Pinta las piezas del panel (la vista se pinta desde el módulo)
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$engineLang = ImportRunner::LANG_GROUP;
?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
            $helpController->_render('panel/built-in/content/page-header.php', [
                'breadcrumbs' => $breadcrumbs,
                'title' => $title,
                'description' => __($langGroup, 'Elige el formato y descarga el archivo.'),
            ]);
        ?>

        <?php if (isset($reimport) && $reimport !== null): ?>
        <div class="ui info message visible" data-transfer-reimport>
            <?= $escape(__($langGroup, 'Este archivo se puede volver a importar con')); ?>
            <a href="<?= $escape($reimport['url']); ?>">“<?= $escape($reimport['title']); ?>”</a>.
        </div>
        <?php endif; ?>

        <form method="GET" action="<?= $escape($action); ?>" class="ui form initial data-transfer-export" data-transfer-export data-content-form>
            <div class="container-standard-form">

                <?php if (isset($presets) && $presets !== null): ?>
                <div class="field" data-transfer-presets data-save-url="<?= $escape($presetSaveURL ?? ''); ?>" data-delete-url="<?= $escape($presetDeleteURL ?? ''); ?>">
                    <label for="data-transfer-preset-select"><?= __($engineLang, 'Filtros guardados'); ?></label>
                    <select id="data-transfer-preset-select" data-transfer-preset-select>
                        <option value=""><?= __($engineLang, 'Elige uno para cargarlo'); ?></option>
                        <?php foreach ($presets as $presetName => $presetQuery): ?>
                        <option value="<?= $escape($presetName); ?>" data-query="<?= $escape(json_encode($presetQuery, \JSON_UNESCAPED_UNICODE) ?: '{}'); ?>"><?= $escape($presetName); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="ui basic button" data-transfer-preset-save><i class="save icon"></i><?= __($engineLang, 'Guardar estos filtros'); ?></button>
                    <button type="button" class="ui basic red button" data-transfer-preset-delete><i class="trash alternate icon"></i><?= __($engineLang, 'Borrar'); ?></button>
                </div>
                <?php endif; ?>

                <?php foreach ($parameters as $parameter): ?>
                <?php
                    $key = $parameter->key();
                    $default = $parameter->getDefault();
                    $requiredAttribute = $parameter->isRequired() && $parameter->type() !== ExportParameter::TYPE_DATE_RANGE ? ' required' : '';
                ?>
                <div class="field<?= $parameter->isRequired() ? ' required' : ''; ?>">
                    <label><?= $escape($parameter->label()); ?></label>

                    <?php if ($parameter->type() === ExportParameter::TYPE_TEXT): ?>
                    <input type="text" name="<?= $escape($key); ?>" maxlength="<?= ExportParameter::MAX_TEXT_LENGTH; ?>" value="<?= $escape($default); ?>"<?= $requiredAttribute; ?>>

                    <?php elseif ($parameter->type() === ExportParameter::TYPE_INTEGER): ?>
                    <input type="number" step="1" name="<?= $escape($key); ?>" value="<?= $escape($default); ?>"<?= $parameter->min() !== null ? ' min="' . $parameter->min() . '"' : ''; ?><?= $parameter->max() !== null ? ' max="' . $parameter->max() . '"' : ''; ?><?= $requiredAttribute; ?>>

                    <?php elseif ($parameter->type() === ExportParameter::TYPE_BOOLEAN): ?>
                    <select name="<?= $escape($key); ?>" class="ui dropdown"<?= $requiredAttribute; ?>>
                        <option value=""<?= $default === null ? ' selected' : ''; ?>><?= __($engineLang, 'Todos'); ?></option>
                        <option value="yes"<?= $default === true ? ' selected' : ''; ?>><?= __($engineLang, 'Sí'); ?></option>
                        <option value="no"<?= $default === false ? ' selected' : ''; ?>><?= __($engineLang, 'No'); ?></option>
                    </select>

                    <?php elseif ($parameter->type() === ExportParameter::TYPE_CHOICE): ?>
                    <select name="<?= $escape($key); ?>" class="ui dropdown"<?= $requiredAttribute; ?>>
                        <option value=""><?= __($engineLang, 'Todos'); ?></option>
                        <?php foreach ($parameter->options() as $value => $optionLabel): ?>
                        <option value="<?= $escape($value); ?>"<?= $default !== null && (string) $default === (string) $value ? ' selected' : ''; ?>><?= $escape($optionLabel); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php elseif ($parameter->type() === ExportParameter::TYPE_MULTI_CHOICE): ?>
                    <?php $selected = array_map('strval', is_array($default) ? $default : []); ?>
                    <select name="<?= $escape($key); ?>[]" class="ui fluid multiple search dropdown" multiple<?= $requiredAttribute; ?>>
                        <?php foreach ($parameter->options() as $value => $optionLabel): ?>
                        <option value="<?= $escape($value); ?>"<?= in_array((string) $value, $selected, true) ? ' selected' : ''; ?>><?= $escape($optionLabel); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <?php elseif ($parameter->type() === ExportParameter::TYPE_DATE): ?>
                    <input type="date" name="<?= $escape($key); ?>" value="<?= $escape($default instanceof \DateTimeInterface ? $default->format('Y-m-d') : ''); ?>"<?= $requiredAttribute; ?>>

                    <?php else: ?>
                    <div class="two fields">
                        <div class="field">
                            <label><?= __($engineLang, 'Desde'); ?></label>
                            <input type="date" name="<?= $escape($key . '_from'); ?>">
                        </div>
                        <div class="field">
                            <label><?= __($engineLang, 'Hasta'); ?></label>
                            <input type="date" name="<?= $escape($key . '_to'); ?>">
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($parameter->helpText() !== null): ?>
                    <small><?= $escape($parameter->helpText()); ?></small>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php if (isset($partial) && $partial !== null): ?>
                <?php include $partial; ?>
                <?php endif; ?>

                <?php if (isset($columns) && count($columns) > 0): ?>
                <div class="field" data-transfer-columns>
                    <label id="data-transfer-columns-label"><?= __($engineLang, 'Columnas'); ?></label>
                    <small><?= __($engineLang, 'Marca las que quieres y ordénalas; sin marcar ninguna, salen todas.'); ?></small>
                    <ol class="ui list" aria-labelledby="data-transfer-columns-label">
                        <?php foreach ($columns as $column): ?>
                        <li class="item" data-transfer-column>
                            <div class="ui checkbox">
                                <input type="checkbox" name="columns[]" value="<?= $escape($column['key']); ?>" id="data-transfer-column-<?= $escape($column['key']); ?>" checked>
                                <label for="data-transfer-column-<?= $escape($column['key']); ?>"><?= $escape($column['label']); ?></label>
                            </div>
                            <button type="button" class="ui icon button" data-transfer-column-up aria-label="<?= $escape(__($engineLang, 'Subir') . ' ' . $column['label']); ?>"><i class="arrow up icon"></i></button>
                            <button type="button" class="ui icon button" data-transfer-column-down aria-label="<?= $escape(__($engineLang, 'Bajar') . ' ' . $column['label']); ?>"><i class="arrow down icon"></i></button>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
                <?php endif; ?>

                <div class="field required">
                    <label><?= __($engineLang, 'Formato'); ?></label>
                    <select name="format" class="ui dropdown" required>
                        <option value="xlsx" selected>XLSX</option>
                        <option value="csv">CSV</option>
                    </select>
                </div>

            </div>

            <?php
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($engineLang, 'Descargar'),
                    'secondaryActions' => isset($previewURL) && $previewURL !== null
                        ? [['text' => __($engineLang, 'Vista previa'), 'icon' => 'eye', 'attributes' => ['data-transfer-preview' => $previewURL]]]
                        : [],
                ]);
            ?>
        </form>

        <?php if (isset($previewURL) && $previewURL !== null): ?>
        <div class="data-transfer-export-preview" data-transfer-preview-result aria-live="polite"></div>
        <?php endif; ?>

        <div class="data-transfer-export-errors" data-transfer-export-errors></div>

    </div>

</section>
