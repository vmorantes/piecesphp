<?php defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");?>
<?php
use PiecesPHP\Core\Config;
$alternativesURL = Config::get_config('alternatives_url');
?>
<!-- Header -->
<header id="header">
    <span class="logo"><strong><?= get_title(true, null, false); ?></strong></span>
    <ul class="icons">
    <li><a target="_blank" href="https://www.facebook.com/#/" class="icon brands fa-facebook-f"><span class="label">Facebook</span></a></li>
        <li><a target="_blank" href="https://www.instagram.com/#/" class="icon brands fa-instagram"><span class="label">Instagram</span></a></li>
        <?php if(is_array($alternativesURL)):?>

        <?php foreach($alternativesURL as $lang => $url): ?>

        <li class="alt">
            <a href="<?= $url; ?>">
                <span class="icon" title="<?= __('lang', $lang); ?>"><i class="icon fa-language"></i></span>
                <span class="label"><?= __('lang', $lang); ?></span>
            </a>
        </li>

        <?php endforeach;?>

        <?php endif;?>
    </ul>
</header>
