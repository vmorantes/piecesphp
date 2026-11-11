<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Core\Config;
use PiecesPHP\Settings\Controllers\SettingsController;
/**
 * @var string|null $xAccount
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var string $actionURL
 * @var array<string,array{url:string,locale:string}> $shareFacts Lo que la portada emite por idioma y no se edita aquí
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$getName = fn ($name, $lang): string => \PiecesPHP\Settings\Controllers\SettingsController::seoOptionName((string) $name, (string) $lang);
//El contador avisa y no corta (sin `maxlength`): un valor guardado más largo tiene que poder editarse.
$counter = fn(string $inputId, int $max): string => '<span class="counter" data-seo-counter="' . escape_html($inputId) . '" data-max="' . $max . '" aria-live="polite"></span>';
$siteHost = mb_strtoupper((string) parse_url(baseurl(), \PHP_URL_HOST));
//La etiqueta técnica de cada campo, a la derecha de su etiqueta (vistas-referencia.md §3.21).
$tag = fn(string $name): string => ' <small class="ui muted text" data-seo-tag="' . escape_html($name) . '">' . escape_html($name) . '</small>';
?>

<section class="module-view-container seo-view">

    <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <div class="ui secondary pointing menu" aria-label="<?= __($langGroup, 'Idioma del contenido'); ?>" data-seo-tabs>
        <?php $isFirstTab = true; ?>
        <?php foreach ($SEOValues as $lang => $values) : ?>
        <a class="<?= $isFirstTab ? 'active ' : ''; ?>item" data-tab="<?= $lang; ?>"><?= __('lang', $lang); ?></a>
        <?php $isFirstTab = false; ?>
        <?php endforeach; ?>
    </div>

    <?php $isFirstTab = true; ?>
    <?php foreach ($SEOValues as $lang => $values) : ?>
    <div class="ui<?= $isFirstTab ? ' active ' : ' '; ?>tab" data-tab="<?= $lang; ?>">

        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial seo" lang="<?= $lang; ?>" data-content-form>

            <input type="hidden" name="lang" value="<?= $lang; ?>">

            <div class="ui stackable grid" data-seo-grid>

                <div class="sixteen wide tablet eleven wide computer column" data-seo-main>

                    <section class="ui segment" aria-labelledby="seo-identity-title-<?= $lang; ?>">
                        <?php
                            $helpController->_render('panel/built-in/content/section-header.php', [
                                'id' => "seo-identity-title-{$lang}",
                                'title' => __($langGroup, 'Identidad del sitio'),
                                'description' => __($langGroup, 'Nombre, descripción y créditos que se muestran en toda la plataforma.'),
                            ]);
                        ?>

                        <div class="field required">
                            <?php $property = SettingsController::SEO_OPTION_TITLE_APP_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <?php $inputId = "seo-title-{$lang}"; ?>
                            <label for="<?= $inputId; ?>"><?= __($langGroup, 'Título'); ?><?= $tag('<title>'); ?></label>
                            <input type="text" id="<?= $inputId; ?>" name="<?= $property; ?>" value="<?= escape_html($values[$propertyLang]); ?>" placeholder="<?= __($langGroup, 'Nombre'); ?>" data-seo-title required>
                            <div class="description"><span><?= __($langGroup, 'Nombre de la plataforma: se muestra en la pestaña del navegador, los encabezados y los correos.'); ?></span><?= ($counter)($inputId, 60); ?></div>
                        </div>

                        <div class="field required">
                            <?php $property = SettingsController::SEO_OPTION_DESCRIPTION_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <?php $inputId = "seo-description-{$lang}"; ?>
                            <label for="<?= $inputId; ?>"><?= __($langGroup, 'Descripción'); ?><?= $tag('meta description'); ?></label>
                            <textarea id="<?= $inputId; ?>" name="<?= $property; ?>" rows="3" placeholder="<?= __($langGroup, 'Descripción de la página.'); ?>" data-seo-description required><?= escape_html($values[$propertyLang]); ?></textarea>
                            <div class="description"><span><?= __($langGroup, 'Resumen breve del sitio que muestran los buscadores en sus resultados.'); ?></span><?= ($counter)($inputId, 160); ?></div>
                        </div>

                        <div class="field required">
                            <?php $property = SettingsController::SEO_OPTION_OWNER_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <label for="seo-owner-<?= $lang; ?>"><?= __($langGroup, 'Propietario'); ?><?= $tag('meta author'); ?></label>
                            <input type="text" id="seo-owner-<?= $lang; ?>" name="<?= $property; ?>" value="<?= escape_html($values[$propertyLang]); ?>" placeholder="<?= __($langGroup, 'Propietario'); ?>" data-seo-owner required>
                        </div>
                    </section>

                    <section class="ui segment" aria-labelledby="seo-share-title-<?= $lang; ?>" data-seo-open-graph>
                        <?php
                            $helpController->_render('panel/built-in/content/section-header.php', [
                                'id' => "seo-share-title-{$lang}",
                                'title' => __($langGroup, 'Open Graph'),
                                'description' => __($langGroup, 'Cómo se ve el enlace al compartirlo (Facebook, WhatsApp, LinkedIn y similares).'),
                            ]);
                        ?>

                        <?php //Vacío ya significa «el del sitio» (MetaTags::shareTitle()): la casilla lo dice y lo envía vacío (seo.js). ?>
                        <div class="field">
                            <?php $property = SettingsController::SEO_OPTION_SHARE_TITLE_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <?php $inputId = "seo-share-title-input-{$lang}"; ?>
                            <?php $useSite = trim((string) ($values[$propertyLang] ?? '')) === ''; ?>
                            <label for="<?= $inputId; ?>"><?= __($langGroup, 'Título para compartir'); ?><?= $tag('og:title'); ?></label>
                            <div class="ui checkbox">
                                <input type="checkbox" id="seo-use-site-title-<?= $lang; ?>" data-seo-use-site="title"<?= $useSite ? ' checked' : ''; ?>>
                                <label for="seo-use-site-title-<?= $lang; ?>"><?= __($langGroup, 'Usar el título del sitio'); ?></label>
                            </div>
                            <input type="text" id="<?= $inputId; ?>" name="<?= $property; ?>" value="<?= $escape($useSite ? ($values[($getName)(SettingsController::SEO_OPTION_TITLE_APP_ON_FORM, $lang)] ?? '') : ($values[$propertyLang] ?? '')); ?>" data-seo-share-title<?= $useSite ? ' disabled' : ''; ?>>
                            <div class="description"><span><?= __($langGroup, 'Título de la tarjeta al compartir la portada. Vacío: el título normal.'); ?></span><?= ($counter)($inputId, 60); ?></div>
                        </div>

                        <div class="field">
                            <?php $property = SettingsController::SEO_OPTION_SHARE_DESCRIPTION_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <?php $inputId = "seo-share-description-{$lang}"; ?>
                            <?php $useSite = trim((string) ($values[$propertyLang] ?? '')) === ''; ?>
                            <label for="<?= $inputId; ?>"><?= __($langGroup, 'Descripción para compartir'); ?><?= $tag('og:description'); ?></label>
                            <div class="ui checkbox">
                                <input type="checkbox" id="seo-use-site-description-<?= $lang; ?>" data-seo-use-site="description"<?= $useSite ? ' checked' : ''; ?>>
                                <label for="seo-use-site-description-<?= $lang; ?>"><?= __($langGroup, 'Usar la descripción del sitio'); ?></label>
                            </div>
                            <textarea id="<?= $inputId; ?>" name="<?= $property; ?>" rows="3" data-seo-share-description<?= $useSite ? ' disabled' : ''; ?>><?= $escape($useSite ? ($values[($getName)(SettingsController::SEO_OPTION_DESCRIPTION_ON_FORM, $lang)] ?? '') : ($values[$propertyLang] ?? '')); ?></textarea>
                            <div class="description"><span><?= __($langGroup, 'Descripción de la tarjeta al compartir. Vacía: la descripción del sitio.'); ?></span><?= ($counter)($inputId, 200); ?></div>
                        </div>

                        <div class="field">
                            <?php //image-card.php no tiene sitio junto a su título: la etiqueta técnica va encima de la tarjeta. ?>
                            <div><?= trim($tag('og:image')); ?></div>
                            <div class="ui one stackable horizontal cards">
                                <?php
                                    $property = SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM;
                                    $propertyLang = ($getName)($property, $lang);
                                    $helpController->_render('panel/built-in/content/image-card.php', [
                                        'id' => "seo-open-graph-{$lang}",
                                        'title' => __($langGroup, 'Imagen para compartir'),
                                        'description' => __($langGroup, 'Aparece en la vista previa del enlace en redes sociales y aplicaciones de mensajería.'),
                                        'hint' => __($langGroup, 'Preferiblemente sin texto pequeño · Se guarda como JPG'),
                                        'accept' => 'image/jpeg,image/png,image/webp',
                                        'image' => (string) $values[$propertyLang],
                                        'field' => 'open-graph',
                                        'width' => 1200,
                                        'height' => 630,
                                        'format' => 'image/jpeg',
                                        'quality' => 0.8,
                                        'fillColor' => 'white',
                                    ]);
                                ?>
                            </div>
                        </div>

                        <?php //Lo que la portada emite y no se edita aquí: a la vista, de solo lectura. ?>
                        <div class="field">
                            <label for="seo-site-name-<?= $lang; ?>"><?= __($langGroup, 'Nombre del sitio'); ?><?= $tag('og:site_name'); ?></label>
                            <div class="ui left icon input">
                                <i class="lock icon" aria-hidden="true"></i>
                                <input type="text" id="seo-site-name-<?= $lang; ?>" value="<?= escape_html($values[($getName)(SettingsController::SEO_OPTION_TITLE_APP_ON_FORM, $lang)] ?? ''); ?>" readonly data-seo-site-name>
                            </div>
                        </div>
                        <div class="field">
                            <label for="seo-og-url-<?= $lang; ?>"><?= __($langGroup, 'Dirección'); ?><?= $tag('og:url'); ?></label>
                            <div class="ui left icon input">
                                <i class="lock icon" aria-hidden="true"></i>
                                <input type="text" id="seo-og-url-<?= $lang; ?>" value="<?= escape_html($shareFacts[$lang]['url'] ?? ''); ?>" readonly data-seo-og-url>
                            </div>
                        </div>
                        <div class="field">
                            <label for="seo-canonical-<?= $lang; ?>"><?= __($langGroup, 'URL canónica'); ?></label>
                            <div class="ui left icon input">
                                <i class="lock icon" aria-hidden="true"></i>
                                <input type="text" id="seo-canonical-<?= $lang; ?>" value="<?= escape_html($shareFacts[$lang]['url'] ?? ''); ?>" readonly data-seo-canonical>
                            </div>
                        </div>
                        <div class="field">
                            <label for="seo-og-locale-<?= $lang; ?>"><?= __($langGroup, 'Idioma para compartir'); ?><?= $tag('og:locale'); ?></label>
                            <div class="ui left icon input">
                                <i class="lock icon" aria-hidden="true"></i>
                                <input type="text" id="seo-og-locale-<?= $lang; ?>" value="<?= escape_html($shareFacts[$lang]['locale'] ?? ''); ?>" readonly data-seo-og-locale>
                            </div>
                            <div class="description"><?= __($langGroup, 'Se cambia en config.php (og_locales).'); ?></div>
                        </div>
                        <p class="ui muted text" data-seo-og-type><?= __($langGroup, 'Tipo (og:type): website en la portada, article en las publicaciones.'); ?></p>

                        <?php if ($lang === Config::get_default_lang()): ?>
                        <div class="field">
                            <label for="seo-x-account"><?= __($langGroup, 'Cuenta de X'); ?></label>
                            <input type="text" id="seo-x-account" name="<?= SettingsController::SEO_OPTION_X_ACCOUNT_ON_FORM; ?>" value="<?= $escape($xAccount ?? ''); ?>" placeholder="@cuenta" pattern="@[A-Za-z0-9_]+">
                        </div>
                        <?php endif; ?>
                    </section>

                    <section class="ui segment" aria-labelledby="seo-meta-title-<?= $lang; ?>">
                        <?php
                            $helpController->_render('panel/built-in/content/section-header.php', [
                                'id' => "seo-meta-title-{$lang}",
                                'title' => __($langGroup, 'Metatags'),
                                'description' => __($langGroup, 'Información adicional para buscadores.'),
                            ]);
                        ?>

                        <div class="field">
                            <label for="seo-author-<?= $lang; ?>"><?= __($langGroup, 'Autor'); ?><?= $tag('meta author'); ?></label>
                            <div class="ui left icon input">
                                <i class="lock icon" aria-hidden="true"></i>
                                <input type="text" id="seo-author-<?= $lang; ?>" value="<?= escape_html($values[($getName)(SettingsController::SEO_OPTION_OWNER_ON_FORM, $lang)] ?? ''); ?>" readonly data-seo-author>
                            </div>
                            <div class="description"><?= __($langGroup, 'Sale del Propietario.'); ?></div>
                        </div>

                        <div class="field">
                            <label><?= __($langGroup, 'Palabras clave'); ?><?= $tag('meta keywords'); ?></label>
                            <?php $property = SettingsController::SEO_OPTION_KEYWORDS_ON_FORM; ?>
                            <?php $propertyLang = ($getName)($property, $lang); ?>
                            <select name="<?= $property; ?>[]" multiple class="ui dropdown multiple search selection keywords">
                                <?= $values[$propertyLang]; ?>
                            </select>
                        </div>
                    </section>

                </div>

                <aside class="sixteen wide tablet five wide computer column" data-seo-aside>
                    <?php $shareImage = (string) $values[($getName)(SettingsController::SEO_OPTION_OPEN_GRAPH_IMAGE_ON_FORM, $lang)]; ?>
                    <section class="ui sticky segment" aria-labelledby="seo-preview-title-<?= $lang; ?>" data-seo-preview>
                        <?php
                            $helpController->_render('panel/built-in/content/section-header.php', [
                                'id' => "seo-preview-title-{$lang}",
                                'title' => __($langGroup, 'Vista previa al compartir'),
                                'description' => __($langGroup, 'Referencia aproximada; cada red la muestra a su manera. Muestra el idioma de la pestaña activa.'),
                            ]);
                        ?>
                        <div class="ui fluid card" aria-hidden="true">
                            <div class="image" data-seo-preview-image>
                                <?php if ($shareImage !== ''): ?>
                                <img src="<?= escape_html(add_cache_stamp_to_url($shareImage)); ?>" alt="">
                                <?php endif; ?>
                            </div>
                            <div class="content">
                                <div class="meta"><?= escape_html($siteHost); ?></div>
                                <div class="header" data-seo-preview-title></div>
                                <div class="description ui clamped text" data-seo-preview-description></div>
                            </div>
                        </div>
                    </section>
                </aside>

            </div>

            <?php
                //La barra al final del formulario (§2.3).
                $helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Guardar'),
                    'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
                ]);
            ?>
        </form>

    </div>
    <?php $isFirstTab = false; ?>
    <?php endforeach; ?>

    <?php $helpController->_render('panel/built-in/content/image-editor.php'); ?>

</section>
