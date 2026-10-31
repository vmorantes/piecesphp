<?php
/**
 * El título de una sección dentro de un bloque (design.md §3.25.1): `h2.ui.header`, con `sub header` si lleva frase. Las
 * secciones se separan con `ui section divider`. Descartable.
 *
 * @var string $title
 * @var string $description Opcional: lo que hace la sección.
 * @var string $url Opcional: el título enlaza ahí, en otra pestaña (el archivo del que habla la sección).
 * @var string $id Opcional, para un `aria-labelledby`.
 */
$title = isset($title) && is_string($title) ? $title : '';
$description = isset($description) && is_string($description) ? $description : '';
$url = isset($url) && is_string($url) ? $url : '';
$id = isset($id) && is_string($id) ? $id : '';
?>
<h2 class="ui header"<?= $id !== '' ? ' id="' . escape_html($id) . '"' : ''; ?>>
    <?php if ($url !== ''): ?>
    <a href="<?= escape_html($url); ?>" target="_blank" rel="noopener"><?= escape_html($title); ?></a>
    <?php else: ?>
    <?= escape_html($title); ?>
    <?php endif; ?>
    <?php if ($description !== ''): ?>
    <div class="sub header"><?= escape_html($description); ?></div>
    <?php endif; ?>
</h2>
