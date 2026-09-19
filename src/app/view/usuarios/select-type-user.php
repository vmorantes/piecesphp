<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\UsersController;
$langGroup = UsersController::LANG_GROUP;
?>


<main container-cards>
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Agregar usuario'); ?></h2>
        </div>
        <div class="body-card trasparent no-padding max-width">
            <div class="ui cards">
                <?php foreach ($types as $type) : ?>
                <a class="card" href="<?= $type['link']; ?>">
                    <div class="content">
                        <i class="list alternate outline icon"></i>
                        <div class="tittle">
                            <?= $type['text']; ?>
                        </div>
                        <p>
                            <?= strReplaceTemplate(__($langGroup, 'Agregar nuevo usuario ${type}'), [
                                '${type}' => $type['text'],
                            ]); ?>
                        </p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
