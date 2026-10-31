<?php
use PiecesPHP\Core\Menu\MenuGroup;

//Los del tema, con su nombre y `svg icon`. El suyo `mi-modulo` es una plantilla comentada en su hoja, sin .svg.
$svgIcons = ['panel', 'inicio', 'usuarios', 'contenidos', 'reportes', 'documentos', 'calendario'];
//En un MenuGroup, el icon de siempre con `svg` detrás: el menú pinta `<i class="icon inicio svg">`.
$menuItem = new MenuGroup(['name' => __($langGroup, 'Inicio'), 'icon' => 'inicio svg', 'href' => '#', 'asLink' => true]);
?>
<div class="ui horizontal list" data-svg-icons>
    <?php foreach ($svgIcons as $svgIcon): ?>
    <div class="item"><i class="<?= $svgIcon; ?> svg icon" aria-hidden="true"></i><code><?= $svgIcon; ?></code></div>
    <?php endforeach; ?>
</div>
<div class="ui secondary vertical menu">
    <a class="item" href="<?= $menuItem->getHref(); ?>"><i class="icon <?= $menuItem->getIcon(false); ?>" aria-hidden="true"></i><?= $menuItem->getName(); ?></a>
</div>
<?php //Uno propio: una línea en la hoja del proyecto, que carga el panel tras la del tema, y su .svg al lado. ?>
<p><span class="ui small grey text">statics/svg-icons/svg-icons.css</span></p>
<pre class="design-sheet-code"><code><?= escape_html((string) file_get_contents(basepath('statics/svg-icons/svg-icons.css'))); ?></code></pre>
