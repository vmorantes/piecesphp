<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Validation\Validator;
use Publications\Controllers\PublicationsPublicController;
use Publications\Mappers\PublicationMapper;

$slugsElements = array_key_exists('slugs', $_GET) ? (
    is_array($_GET['slugs']) ? $_GET['slugs'] : [$_GET['slugs']]
) : [];
$slugsElements = array_filter(array_map(fn($e) => Validator::isString($e) ? (string) $e : null, $slugsElements), fn($e) => Validator::isString($e));
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

    <component data-slug="<?= $element->preferSlug; ?>" name="special-main-item">
        <a class="item" href="<?= $singleURL; ?>">
            <div class="ui large image">
                <img src="<?= $escape($element->currentLangData('thumbImage')); ?>" alt="<?= $escape($element->currentLangData('title')); ?>" loading="lazy">
            </div>
            <div class="content">
                <div class="header"><?= $escape($element->currentLangData('title')); ?></div>
                <div class="meta">
                    <span><?= $escape($element->authorFullName()); ?></span>
                </div>
                <div class="description">
                    <?= $excerptContent; ?>
                </div>
                <div class="extra">
                    <?= $element->publicDateFormat(); ?>
                </div>
            </div>
        </a>
    </component>

    <component data-slug="<?= $element->preferSlug; ?>" name="special-secondary-item">
        <a class="ui card" href="<?= $singleURL; ?>">
            <div class="image">
                <img src="<?= $escape($element->currentLangData('thumbImage')); ?>" alt="<?= $escape($element->currentLangData('title')); ?>" loading="lazy">
            </div>
            <div class="content">
                <div class="header"><?= $element->publicDateFormat(); ?></div>
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