<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\Controllers\UsersController;
$langGroup = UsersController::LANG_GROUP;
/**
 * @var array<int,array{link:string,text:string,description:string}> $types Los tipos sobre los que el usuario actual tiene autoridad
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

        <section class="ui four doubling stackable cards" aria-label="<?= escape_html(__($langGroup, 'Tipos de usuario')); ?>">
            <?php foreach ($types as $type) : ?>
            <a class="link statistic card" href="<?= escape_html($type['link']); ?>" aria-label="<?= escape_html($type['text'] . ': ' . $type['description']); ?>">
                <div class="content">
                    <i class="right floated chevron right icon" aria-hidden="true"></i>
                    <i class="bordered primary user plus icon" aria-hidden="true"></i>
                    <div class="header"><?= escape_html($type['text']); ?></div>
                    <div class="description"><?= escape_html($type['description']); ?></div>
                </div>
            </a>
            <?php endforeach; ?>
        </section>

    </div>

</section>
