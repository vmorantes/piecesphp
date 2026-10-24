<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Controllers\UsersController;
$langGroup = UsersController::LANG_GROUP;
/**
 * @var array<int,array{link:string,text:string}> $types Los tipos sobre los que el usuario actual tiene autoridad
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $title
 * @var string $description
 */
?>
<section class="module-view-container select-type-user-view">

    <div class="limiter-content">

        <?php
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <div class="ui three doubling stackable cards">
            <?php foreach ($types as $type) : ?>
            <a class="card" href="<?= escape_html($type['link']); ?>">
                <div class="center aligned content">
                    <i class="huge grey user plus icon"></i>
                </div>
                <div class="content">
                    <div class="header"><?= escape_html($type['text']); ?></div>
                    <div class="meta"><?= escape_html(strReplaceTemplate(__($langGroup, 'Agregar nuevo usuario ${type}'), ['${type}' => $type['text']])); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

    </div>

</section>
