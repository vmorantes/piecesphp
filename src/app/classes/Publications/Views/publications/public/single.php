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

        <div class="wrapper unbounds">

            <div class="post-image">
                <img src="<?= $element->currentLangData('mainImage'); ?>" alt="<?= $element->currentLangData('title'); ?>">
            </div>

            <div class="text-center">
                <strong><?= $element->createdAtFormat(); ?></strong>
                -
                <em><?= $element->authorFullName(); ?></em>
            </div>

            <h2 class="segment-title text-center mw-1200 element-center"><?= $element->currentLangData('title'); ?></h2>

        </div>

        <div class="wrapper">
            <div class="post-content"><?= $element->currentLangData('content'); ?></div>
        </div>

    </div>

</section>
