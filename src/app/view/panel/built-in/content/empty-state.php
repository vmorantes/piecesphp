<?php
/**
 * El estado vacío de un listado sin ningún registro (diseño de contenidos, §2.2): una frase y la acción principal, con
 * `ui placeholder segment` de Fomantic. Descartable. La tabla sin resultados de una búsqueda sigue con el texto de
 * DataTables.
 *
 * @var string $text La frase («Aún no hay noticias.»).
 * @var string $icon Icono de Fomantic, opcional.
 * @var array{text:string,url:string}|null $action
 */
$text = isset($text) && is_string($text) ? $text : '';
$icon = isset($icon) && is_string($icon) ? trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($icon))) : '';
$action = isset($action) && is_array($action) ? $action : null;
?>
<div class="ui placeholder segment">
    <div class="ui icon header">
        <?php if ($icon !== ''): ?>
        <i class="<?= escape_html($icon); ?> icon"></i>
        <?php endif; ?>
        <?= escape_html($text); ?>
    </div>
    <?php if ($action !== null): ?>
    <a class="ui button brand-color" href="<?= escape_html($action['url']); ?>"><?= escape_html($action['text']); ?></a>
    <?php endif; ?>
</div>
