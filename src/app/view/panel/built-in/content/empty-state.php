<?php
/**
 * El estado vacío de un listado sin ningún registro (diseño de contenidos, §2.2): una frase y la acción principal, con
 * `ui placeholder segment` de Fomantic. Descartable. La tabla sin resultados de una búsqueda sigue con el texto de
 * DataTables.
 *
 * @var string $text La frase («Aún no hay noticias.»).
 * @var string $description Una segunda frase, opcional: qué pasará o qué hacer.
 * @var string $icon Icono de Fomantic, opcional.
 * @var array{text:string,url:string,icon?:string}|null $action Con `icon`, `ui primary labeled icon button`.
 */
$text = isset($text) && is_string($text) ? $text : '';
$description = isset($description) && is_string($description) ? $description : '';
$cleanIcon = fn($icon) => is_string($icon) ? trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($icon))) : '';
$icon = $cleanIcon($icon ?? null);
$action = isset($action) && is_array($action) ? $action : null;
$actionIcon = $cleanIcon($action['icon'] ?? null);
?>
<div class="ui placeholder segment">
    <div class="ui icon header">
        <?php if ($icon !== ''): ?>
        <i class="<?= escape_html($icon); ?> icon" aria-hidden="true"></i>
        <?php endif; ?>
        <?= escape_html($text); ?>
        <?php if ($description !== ''): ?>
        <div class="sub header"><?= escape_html($description); ?></div>
        <?php endif; ?>
    </div>
    <?php if ($action !== null): ?>
    <div class="inline">
        <a class="ui primary<?= $actionIcon !== '' ? ' labeled icon' : ''; ?> button" href="<?= escape_html($action['url']); ?>"><?php if ($actionIcon !== ''): ?><i class="<?= escape_html($actionIcon); ?> icon" aria-hidden="true"></i><?php endif; ?><?= escape_html($action['text']); ?></a>
    </div>
    <?php endif; ?>
</div>
