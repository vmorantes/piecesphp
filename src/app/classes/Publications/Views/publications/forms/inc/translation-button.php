<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * La fila de «Traducir» de la edición de una publicación en otro idioma (ADR 0077): quien la incluye decide lo propio del
 * sitio (idioma, ruta); aquí, oculta no pinta nada (ni la fila), y en su límite, el aviso a lo ancho y el botón desactivado.
 *
 * @var string $langGroup
 * @var array{state:string,message:?string,renewsAt:?string} $aiTranslation AIGateway::availability('translation')
 * @var string $baseLang
 * @var string $langCode
 */
use PiecesPHP\AI\AIGateway;

$aiTranslationLimit = $aiTranslation['state'] === AIGateway::AVAILABILITY_LIMIT;
if ($aiTranslation['state'] === AIGateway::AVAILABILITY_HIDDEN) {
    return;
}
?>
<div class="ui stackable grid" data-ai-translation-row>
    <div class="row">
        <?php if ($aiTranslationLimit): ?>
        <div class="twelve wide column">
            <div class="ui small warning message" id="publication-translation-limit" data-ai-limit-message><?= escape_html((string) $aiTranslation['message']); ?></div>
        </div>
        <?php endif; ?>
        <div class="<?= $aiTranslationLimit ? 'four' : 'sixteen'; ?> wide right aligned middle aligned column">
            <button type="button" class="ui right labeled icon primary button<?= $aiTranslationLimit ? ' disabled' : ''; ?>" do-translation data-ai-availability="<?= escape_html($aiTranslation['state']); ?>" from-lang="<?= escape_html($baseLang); ?>" to-lang="<?= escape_html($langCode); ?>"<?= $aiTranslationLimit ? ' disabled aria-describedby="publication-translation-limit"' : ''; ?>>
                <?= __($langGroup, 'Traducir'); ?><i class="language icon"></i>
            </button>
        </div>
    </div>
</div>
