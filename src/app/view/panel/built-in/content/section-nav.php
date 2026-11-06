<?php
/**
 * La navegación de una página larga por secciones (H200): en escritorio, un menú vertical que se queda fijo al desplazar
 * (`ui sticky` del tema dentro de su rejilla, bajo la barra superior); con la rejilla apilada, fijo arriba con
 * position: sticky; por debajo de escritorio, un desplegable de salto en lugar del menú. Va SOLA en la primera columna de
 * un `ui stackable grid` (la columna es la que se fija al apilarse); el contenido va en la siguiente.
 *
 * Un elemento lleva a otra página (`href`) o a un ancla de esta (`anchor`, el id sin «#»). Con anclas, el que se ve se marca
 * al desplazar y el desplegable lo sigue. Un nivel de hijos: un elemento con `children` pinta su título y, debajo, sus hijos;
 * sin `href`, el título es solo un rótulo. Las secciones pueden ir plegadas en un `ui accordion` con `data-section-accordion`,
 * cada `title` con el id del ancla: el JS lo inicia y un ancla abre su elemento antes de llegar a él.
 * El JS es contentSectionNav() (admin-area/js/main.js), por `data-section-nav`; el CSS, en admin_app_base.scss.
 *
 * @var string $label El nombre del menú para un lector de pantalla (aria-label).
 * @var string $jumpLabel El del desplegable de salto (por omisión, el del menú).
 * @var array<int,array{label:string,href?:string,anchor?:string,active?:bool,children?:array<int,array{label:string,href?:string,anchor?:string,active?:bool}>}> $items
 *      `active` marca la página actual (aria-current="page"); en un ancla lo pone el JS al desplazar.
 */
$label = isset($label) && is_string($label) ? $label : '';
$jumpLabel = isset($jumpLabel) && is_string($jumpLabel) && $jumpLabel !== '' ? $jumpLabel : $label;
$items = isset($items) && is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
$textOf = fn (array $item): string => isset($item['label']) && is_string($item['label']) ? $item['label'] : '';
$anchorOf = fn (array $item): ?string => isset($item['anchor']) && is_string($item['anchor']) && $item['anchor'] !== '' ? $item['anchor'] : null;
$urlOf = fn (array $item): ?string => $anchorOf($item) !== null ? '#' . $anchorOf($item) : (isset($item['href']) && is_string($item['href']) && $item['href'] !== '' ? $item['href'] : null);
$activeOf = fn (array $item): bool => ($item['active'] ?? false) === true;
$childrenOf = fn (array $item): array => isset($item['children']) && is_array($item['children']) ? array_values(array_filter($item['children'], 'is_array')) : [];
//Un enlace del menú: el ancla lleva su gancho para que el JS lo marque; la página actual, su aria-current.
$link = function (array $item, string $class) use ($textOf, $anchorOf, $urlOf, $activeOf): string {
    $url = $urlOf($item);
    $anchor = $anchorOf($item);
    $active = $activeOf($item);
    //Sin destino, un rótulo: un href vacío recargaría la página.
    if ($url === null) {
        return '<div class="' . ($active ? 'active ' : '') . $class . '">' . escape_html($textOf($item)) . '</div>';
    }
    $attributes = ' href="' . escape_html((string) $url) . '"'
        . ($anchor !== null ? ' data-section-nav-anchor="' . escape_html($anchor) . '"' : '')
        . ($active && $anchor === null ? ' aria-current="page"' : '');
    return '<a class="' . ($active ? 'active ' : '') . $class . '"' . $attributes . '>' . escape_html($textOf($item)) . '</a>';
};
$option = fn (array $item): string => $urlOf($item) === null ? '' : '<option value="' . escape_html((string) $urlOf($item)) . '"' . ($activeOf($item) ? ' selected' : '') . '>' . escape_html($textOf($item)) . '</option>';
?>
<div class="ui sticky section-nav" data-section-nav>
    <nav class="ui vertical fluid menu section-nav-menu" aria-label="<?= escape_html($label); ?>">
        <?php foreach ($items as $item): ?>
        <?php $children = $childrenOf($item); ?>
        <?php if ($children === []): ?>
        <?= $link($item, 'item'); ?>
        <?php else: ?>
        <div class="item">
            <?php if ($urlOf($item) !== null): ?>
            <?= $link($item, 'header'); ?>
            <?php else: ?>
            <div class="header"><?= escape_html($textOf($item)); ?></div>
            <?php endif; ?>
            <div class="menu">
                <?php foreach ($children as $child): ?>
                <?= $link($child, 'item'); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
    </nav>
    <div class="field section-nav-jump">
        <select class="ui fluid dropdown" data-section-nav-jump aria-label="<?= escape_html($jumpLabel); ?>">
            <?php foreach ($items as $item): ?>
            <?php $children = $childrenOf($item); ?>
            <?php if ($children === []): ?>
            <?= $option($item); ?>
            <?php else: ?>
            <optgroup label="<?= escape_html($textOf($item)); ?>">
                <?= $option($item); ?>
                <?php foreach ($children as $child): ?>
                <?= $option($child); ?>
                <?php endforeach; ?>
            </optgroup>
            <?php endif; ?>
            <?php endforeach; ?>
        </select>
    </div>
</div>
