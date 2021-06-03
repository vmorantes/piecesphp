<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\UsersModel;
use Publications\PublicationsLang;
$user = new UsersModel(get_config('current_user')->id);
?>
<div class="banner-zone"></div>

<div class="ui very padded segment mw-800 b-center info-card">

    <div class="header-list">

        <h3 class="title-list subtitle small">
            <?= __('general', 'Bienvenido(a)'); ?>
            <span class="subtitle"><?= $user->getFullName(); ?></span>
        </h3>

    </div>

</div>

<div class="container-standard-options ui cards">

    <?php if(mb_strlen($publicationsListLink) > 0):?>
    <div class="ui card option">

        <div class="content">

            <div class="image-container">
                <div class="icon">
                    <i class="icon newspaper outline"></i>
                </div>
            </div>

            <div class="header"><?= __(PublicationsLang::LANG_GROUP, 'Publicaciones'); ?></div>

        </div>

        <div class="extra content">
            <a href="<?= $publicationsListLink; ?>" class="ui button green fluid"><?= __(PublicationsLang::LANG_GROUP, 'Ver'); ?></a>
        </div>

    </div>
    <?php endif;?>

</div>
