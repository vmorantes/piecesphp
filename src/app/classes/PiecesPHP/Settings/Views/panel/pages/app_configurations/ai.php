<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var string $title
 * @var string $description
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>
<section class="module-view-container ai-view">

    <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);

        //Root no se ofrece: siempre puede (APIController::translationUserTypes()).
        $translationUserTypesOptions = array_diff_key(\PiecesPHP\UserSystem\ORM\UsersModel::getTypesUser(), [\PiecesPHP\UserSystem\ORM\UsersModel::TYPE_USER_ROOT => true]);
        $translationUserTypesSelected = \API\Controllers\APIController::translationUserTypes(get_config(\API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG));
        $keyPlaceholder = fn(string $option): string => mb_strlen((string) get_config($option)) > 0 ? __($langGroup, 'Clave guardada: vacío la conserva') : __($langGroup, 'Pega aquí la API key del proveedor');
        $tokensLimitElement = new \PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper(\PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
        $tokensLimitName = $tokensLimitElement->userSetContentName();
        $tokensLimitAction = \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::allowedRoute('actions-edit') ? (string) \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::routeName('actions-edit') : '';
    ?>

    <form action="<?= $actionURL; ?>" method="POST" class="ui form initial ai segment" data-content-form>

        <section aria-labelledby="ai-provider-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-provider-title',
                    'title' => __($langGroup, 'Proveedor y modelo'),
                ]);
            ?>
            <div class="two fields">
                <div class="field">
                    <label for="ai-model-openai"><?= __($langGroup, 'Modelo OpenAI'); ?></label>
                    <select id="ai-model-openai" name="modelOpenAI" class="ui fluid selection dropdown">
                        <?= array_to_html_options(AI_MODELS[AI_OPENAI], get_config('modelOpenAI')); ?>
                    </select>
                </div>
                <div class="field">
                    <label for="ai-key-openai"><?= __($langGroup, 'API Key OpenAI'); ?></label>
                    <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
                    <input type="text" id="ai-key-openai" name="OpenAIApiKey" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html($keyPlaceholder('OpenAIApiKey')); ?>">
                </div>
            </div>
            <div class="two fields">
                <div class="field">
                    <label for="ai-model-mistral"><?= __($langGroup, 'Modelo Mistral'); ?></label>
                    <select id="ai-model-mistral" name="modelMistral" class="ui fluid selection dropdown">
                        <?= array_to_html_options(AI_MODELS[AI_MISTRAL], get_config('modelMistral')); ?>
                    </select>
                </div>
                <div class="field">
                    <label for="ai-key-mistral"><?= __($langGroup, 'API Key Mistral'); ?></label>
                    <input type="text" id="ai-key-mistral" name="MistralAIApiKey" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html($keyPlaceholder('MistralAIApiKey')); ?>">
                </div>
            </div>
        </section>

        <div class="ui section divider"></div>

        <section aria-labelledby="ai-translation-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-translation-title',
                    'title' => __($langGroup, 'Traducción con IA'),
                ]);
                $helpController->_render('panel/built-in/content/toggle-field.php', [
                    'id' => 'ai-translation-enable',
                    'inputName' => 'translationAIEnable',
                    'label' => __($langGroup, 'Activar traducción con IA'),
                    'checked' => (bool) get_config('translationAIEnable'),
                ]);
            ?>
            <div class="two fields">
                <div class="field">
                    <label for="ai-translation-provider"><?= __($langGroup, 'IA para traducciones'); ?></label>
                    <select id="ai-translation-provider" name="translationAI" class="ui fluid selection dropdown">
                        <?= array_to_html_options(TRANSLATION_AI_LIST, get_config('translationAI')); ?>
                    </select>
                </div>
                <div class="field">
                    <label for="ai-translation-users"><?= __($langGroup, 'Quién puede traducir con IA'); ?></label>
                    <select id="ai-translation-users" name="<?= \API\Controllers\APIController::TRANSLATION_AI_USER_TYPES_CONFIG; ?>[]" class="ui fluid dropdown" multiple>
                        <?= array_to_html_options($translationUserTypesOptions, $translationUserTypesSelected, true); ?>
                    </select>
                    <div class="description"><?= __($langGroup, 'Root siempre puede.'); ?></div>
                </div>
            </div>
        </section>

        <?php
            //La barra al final del formulario (§2.3).
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
        ?>

    </form>

    <?php if ($tokensLimitAction !== ''): ?>
    <?php //Un formulario aparte, dentro no cabe: guarda en su propia ruta (GenericContentController). ?>
    <form action="<?= escape_html($tokensLimitAction); ?>" method="POST" class="ui form initial tokens-limit segment" aria-labelledby="ai-quota-title" data-content-form>
        <?php
            $helpController->_render('panel/built-in/content/section-header.php', [
                'id' => 'ai-quota-title',
                'title' => __($langGroup, 'Cuota de tokens'),
                'description' => __($langGroup, 'Solo informativo: es la cuota contratada y sirve para calcular los tokens restantes del informe. No limita el uso de la inteligencia artificial.'),
            ]);
        ?>
        <input type="hidden" name="configName" value="<?= escape_html($tokensLimitName); ?>">
        <?php foreach (\PiecesPHP\Core\Config::get_allowed_langs() as $tokensLang): ?>
        <?php $tokensMultilang = $tokensLimitElement->isTranslatable($tokensLimitName); ?>
        <?php if ($tokensMultilang || $tokensLang === \PiecesPHP\Core\Config::get_default_lang()): ?>
        <div class="two fields">
            <div class="field required">
                <label><?= __(\PiecesPHP\BuiltIn\Helpers\HelpersSystemLang::LANG_GROUP, 'Límite de tokens de IA') . ($tokensMultilang ? " ({$tokensLang})" : ''); ?></label>
                <input type="number" step="1" name="<?= escape_html($tokensLimitName); ?>[<?= $tokensLang; ?>]" value="<?= escape_html((string) $tokensLimitElement->getLangData($tokensLang, $tokensLimitName, true, '')); ?>" required>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
            ]);
        ?>
    </form>
    <?php endif; ?>

</section>
