<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>


<div container-cards>

    <div class="ui cards">

        <?php foreach ($types as $type): ?>
        <div class="card">
            <div class="content">
                <div class="header">
                    <?=$type['text'];?>
                </div>
            </div>
            <div class="extra content">
                <a href="<?= $type['link'];?>" class="ui blue button"><?= __('usersModule', 'Agregar'); ?></a>
            </div>
        </div>
        <?php endforeach;?>

    </div>

</div>

<style>
[container-cards] {
    max-width: 700px;
}
</style>
