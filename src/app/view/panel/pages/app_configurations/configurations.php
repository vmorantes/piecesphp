<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Controller\AppConfigController;

$langGroup = AppConfigController::LANG_GROUP;
$isFirstTitle = true;
$isFirstItem = true;

$view = $tabsItems['general'];
$title = $tabsTitles['general'];
?>

<main class="colors-view">

    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, $title); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Personalización de Plataforma'); ?></span>
        </div>
        <div class="body-card trasparent no-padding">
            <?= $view; ?>
        </div>
    </section>

</main>

<script>
    window.addEventListener('load', function(e) {

        //Inicializaciones generales
        $('.ui.top.menu .item').tab()
        $('.ui.checkbox').checkbox()
        $('.ui.dropdown.additions')
            .dropdown({
                allowAdditions: true
            })

    })
</script>
<style>
    .ui.form {
        max-width: 800px;
    }
</style>
