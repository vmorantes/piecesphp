<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $title
 * @var string $breadcrumbs
 * @var array<int,array{label:string,required:bool,aliases:string[]}> $columns
 * @var string[] $extensions
 * @var int $maxSizeMB
 * @var int $maxRows
 * @var string $action
 * @var string $template
 */
$escape = fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$accept = implode(',', array_map(fn($e) => '.' . $e, $extensions));
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs; ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $escape($title); ?></div>
            <div class="description">
                <?= $escape(sprintf(__($langGroup, 'Archivos %s de hasta %d MB y %d filas. Si una fila tiene errores, no se guarda ninguna.'), implode(', ', $extensions), $maxSizeMB, $maxRows)); ?>
            </div>
        </div>

        <div class="main-buttons">
            <a class="ui button brand-color alt" href="<?= $escape($template); ?>"><?= __($langGroup, 'Descargar plantilla'); ?></a>
        </div>

        <br>

        <table class="ui basic table">
            <thead>
                <tr>
                    <th><?= __($langGroup, 'Columna'); ?></th>
                    <th><?= __($langGroup, 'Obligatoria'); ?></th>
                    <th><?= __($langGroup, 'También se reconoce como'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($columns as $column): ?>
                <tr>
                    <td><?= $escape($column['label']); ?></td>
                    <td><?= $column['required'] ? __($langGroup, 'Sí') : __($langGroup, 'No'); ?></td>
                    <td><?= $escape(implode(', ', $column['aliases'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" action="<?= $escape($action); ?>" class="ui form data-transfer-import" data-transfer-import>
            <div class="container-standard-form">
                <div class="field required">
                    <label><?= __($langGroup, 'Archivo'); ?></label>
                    <input type="file" name="file" accept="<?= $escape($accept); ?>" required>
                </div>
                <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Importar'); ?></button>
            </div>
        </form>

        <br>

        <div class="data-transfer-report" data-transfer-report></div>

    </div>

</section>
