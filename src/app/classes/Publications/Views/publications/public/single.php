<?php

use Publications\Mappers\PublicationMapper;

defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var PublicationMapper $element
 */;
$langGroup;
$element;
?>
<section class="body">

    <div class="content">

        <div class="wrapper">

            <div class="post-image">
                <img src="<?= $element->currentLangData('mainImage'); ?>" alt="<?= $element->currentLangData('title'); ?>">
            </div>

        </div>

        <div class="wrapper no-padding-top-mobile">

            <h2 class="segment-title text-center"><?= $element->currentLangData('title'); ?></h2>
            <p><small><?= $element->createdAt->format('d-m-Y'); ?></small></p>

            <div class="post-content"><?= $element->currentLangData('content'); ?></div>

        </div>

    </div>

</section>
