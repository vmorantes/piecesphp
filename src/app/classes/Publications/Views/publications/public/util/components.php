<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Validation\Validator;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

$slugsElements = array_key_exists('slugs', $_GET) ? (
    is_array($_GET['slugs']) ? $_GET['slugs'] : [$_GET['slugs']]
) : [];
$slugsElements = array_filter(array_map(fn($e) => Validator::isString($e) ? (string) $e : null, $slugsElements), fn($e) => Validator::isString($e));
//Tope: cada slug es una consulta y la ruta es pública. El cliente (listing.js, perPage: 5) manda como mucho 5.
$slugsElements = array_slice(array_values($slugsElements), 0, 10);
$idsElements = array_map(fn($e) => PublicationMapper::extractIDFromSlug("segment-" . $e), $slugsElements);
$idsElements = array_filter($idsElements, fn($e) => Validator::isInteger($e));
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
?>

<components name='publications'>

    <?php foreach($idsElements as $idElement): ?>
    <?php
        $element = new PublicationMapper($idElement);
        if($element->id == null) continue;
        $singleURL = PublicationsPublicController::routeName('single', ['slug' => $element->getSlug()]);
        $excerptTitle = $element->excerptTitle(100);
        $excerptTitle = mb_strpos($excerptTitle, '...') !== false ? $excerptTitle : $excerptTitle . '...';
        $excerptContent = $element->excerpt(300);
        $excerptContent = mb_strpos($excerptContent, '...') !== false ? $excerptContent : $excerptContent . '...';
    ?>

    <component data-slug="<?= $escape($element->preferSlug); ?>" name="special-main-item">
        <a class="item" href="<?= $escape($singleURL); ?>">
            <div class="ui large image">
                <img src="<?= $escape($element->currentLangData('thumbImage')); ?>" alt="<?= $escape($element->currentLangData('title')); ?>" loading="lazy">
            </div>
            <div class="content">
                <div class="header"><?= $escape($element->currentLangData('title')); ?></div>
                <div class="meta">
                    <span><?= $escape($element->authorFullName()); ?></span>
                </div>
                <div class="description">
                    <?php //Texto sacado del editor con strip_tags: trae sus entidades, que se decodifican antes de escapar una sola vez. ?>
                    <?= escape_html(html_entity_decode($excerptContent, ENT_QUOTES | ENT_HTML5, 'UTF-8')); ?>
                </div>
                <div class="extra">
                    <?= $escape($element->publicDateFormat()); ?>
                </div>
            </div>
        </a>
    </component>

    <component data-slug="<?= $escape($element->preferSlug); ?>" name="special-secondary-item">
        <a class="ui card" href="<?= $escape($singleURL); ?>">
            <div class="image">
                <img src="<?= $escape($element->currentLangData('thumbImage')); ?>" alt="<?= $escape($element->currentLangData('title')); ?>" loading="lazy">
            </div>
            <div class="content">
                <div class="header"><?= $escape($element->publicDateFormat()); ?></div>
                <div class="meta">
                    <span><?= $escape($element->authorFullName()); ?></span>
                </div>
                <div class="description"><?= $escape($excerptTitle); ?></div>
            </div>
        </a>
    </component>
    <?php endforeach; ?>

    <component name="special-block-container">
        <div class="ui cards centered"></div>
    </component>

</components>