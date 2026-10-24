<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * La pestaña «Accesos» de «Registros»: sus tres informes, elegidos con un menú propio dentro de la pestaña.
 *
 * @var string $langGroup
 * @var array<int,array{view:string,title:string,icon:string,url:string,active:bool}> $views
 * @var string $content El informe activo
 */
?>
<div class="ui secondary stackable menu" data-login-reports-views>
    <?php foreach ($views as $view): ?>
    <a class="<?= $view['active'] ? 'active ' : ''; ?>item" href="<?= escape_html($view['url']); ?>" data-login-reports-view="<?= escape_html($view['view']); ?>">
        <i class="<?= escape_html($view['icon']); ?> icon"></i>
        <?= escape_html($view['title']); ?>
    </a>
    <?php endforeach; ?>
</div>

<?= $content; ?>
