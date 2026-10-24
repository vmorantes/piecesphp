<?php
/**
 * Una tarjeta de acción (`card-action`, _graphs-and-statistics.scss): un enlace con la misma barra que las de estadística
 * (el título), el icono y una frase, y el pie con su flecha; para lo que se hace desde un tablero. Con `not-allowed` o
 * `disabled` se ve apagada y no lleva a ningún sitio.
 *
 * @var string $url Adónde lleva; con `not-allowed` o `disabled` se ignora.
 * @var string $icon El icono de Fomantic.
 * @var string $title
 * @var string $description Opcional.
 * @var string|null $caption El texto del pie, a la izquierda de la flecha; sin él, la flecha sola.
 * @var string[] $modifiers `tall`, `horizontal-center`, `not-allowed` y `disabled`.
 */
$modifiers = isset($modifiers) && is_array($modifiers) ? array_values(array_intersect(['tall', 'horizontal-center', 'not-allowed', 'disabled'], $modifiers)) : [];
$off = array_intersect(['not-allowed', 'disabled'], $modifiers) !== [];
$url = !$off && isset($url) && is_string($url) ? $url : 'javascript:void(0)';
$icon = isset($icon) && is_string($icon) ? trim((string) preg_replace('/[^a-z0-9 -]/', '', mb_strtolower($icon))) : '';
$title = isset($title) && is_string($title) ? $title : '';
$description = isset($description) && is_string($description) ? $description : '';
$caption = isset($caption) && is_string($caption) && $caption !== '' ? $caption : null;
?>
<a class="ui segment card-action<?= $modifiers !== [] ? ' ' . implode(' ', $modifiers) : ''; ?>" href="<?= escape_html($url); ?>">
    <div class="toolbar">
        <div class="title"><?= escape_html($title); ?></div>
    </div>
    <div class="action-body">
        <div class="icon">
            <i class="<?= escape_html($icon); ?> icon"></i>
        </div>
        <?php if ($description !== ''): ?>
        <div class="description"><?= escape_html($description); ?></div>
        <?php endif; ?>
    </div>
    <div class="footer">
        <?php if ($caption !== null): ?>
        <div class="caption">
            <?= escape_html($caption); ?>
        </div>
        <?php else: ?>
        <div></div>
        <?php endif; ?>
        <div class="action-button">
            <span class="button"><i class="arrow right icon"></i></span>
        </div>
    </div>
</a>
