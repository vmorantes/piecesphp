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
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\UserSystem\ORM\UsersModel;
?>
<section class="module-view-container ai-view">

    <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);

        //Root no se ofrece: siempre puede (AIGateway::denialReason()).
        $userTypesOptions = array_diff_key(UsersModel::getTypesUser(), [UsersModel::TYPE_USER_ROOT => true]);
        $periodOptions = [
            AICapability::PERIOD_ACCUMULATED => __($langGroup, 'Acumulado'),
            AICapability::PERIOD_MONTHLY => __($langGroup, 'Mensual'),
            AICapability::PERIOD_EVERY_DAYS => __($langGroup, 'Cada N días'),
            AICapability::PERIOD_EVERY_MONTHS => __($langGroup, 'Cada N meses'),
        ];
        $resetURL = SettingsController::allowedRoute('integrations-ai-usage-reset') ? (string) SettingsController::routeName('integrations-ai-usage-reset') : '';
        //La que define el archivo de claves, sin placeholder: está deshabilitada y su texto lo dice (P318).
        $keyPlaceholder = fn(string $option): string => \PiecesPHP\Core\ConfigHelpers\ConfigOptions::isFixedByFile($option) ? '' : (mb_strlen((string) get_config($option)) > 0 ? __($langGroup, 'Clave guardada: vacío la conserva') : __($langGroup, 'Pega aquí la API key del proveedor'));
        //Una clave que da el archivo de claves manda (P318): el campo no se edita y lo dice.
        $fixedByFile = fn(string $option): bool => \PiecesPHP\Core\ConfigHelpers\ConfigOptions::isFixedByFile($option);
        $keyAttributes = fn(string $option): string => $fixedByFile($option) ? ' disabled aria-describedby="ai-fixed-' . escape_html($option) . '"' : '';
        //«Quitar la guardada» (P317): solo si hay una guardada; marcada, se guarda vacía. La del archivo, en su lugar, el aviso.
        $clearSaved = fn(string $option, string $id): string => $fixedByFile($option)
            ? '<p class="ui muted text" id="ai-fixed-' . escape_html($option) . '" data-fixed-by-file>' . escape_html(__($langGroup, 'La define el archivo de claves.')) . '</p>'
            : (mb_strlen((string) get_config($option)) > 0
                ? '<div class="field"><div class="ui checkbox"><input type="checkbox" id="' . $id . '" name="clear_' . $option . '" value="1" data-clear-saved><label for="' . $id . '">' . escape_html(__($langGroup, 'Quitar la guardada')) . '</label></div></div>'
                : '');
        $tokensLimitElement = new \PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper(\PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
        $tokensLimitName = $tokensLimitElement->userSetContentName();
        $tokensLimitAction = \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::allowedRoute('actions-edit') ? (string) \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::routeName('actions-edit') : '';
        //La línea del consumo: el JS la rehace tras «Reiniciar el contador» con las mismas plantillas.
        $usageTemplate = __($langGroup, 'Consumo de este periodo: %usage% tokens, desde %start%.');
        $usageQuotaTemplate = __($langGroup, 'Consumo de este periodo: %usage% tokens de %quota%, desde %start%.');
    ?>

    <form action="<?= $actionURL; ?>" method="POST" class="ui form initial ai segment" data-content-form>

        <section aria-labelledby="ai-center-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-center-title',
                    'title' => __($langGroup, 'La inteligencia artificial'),
                ]);
                $helpController->_render('panel/built-in/content/toggle-field.php', [
                    'id' => 'ai-center-enabled',
                    'inputName' => AIGateway::CONFIG_NAME . '[enabled]',
                    'label' => __($langGroup, 'IA activada'),
                    'description' => __($langGroup, 'Apagada, ninguna función de IA llama a un proveedor.'),
                    'checked' => AIGateway::isEnabled(),
                    'attributes' => ['data-ai-toggle' => ''],
                ]);
            ?>
        </section>

        <div class="ui section divider"></div>

        <section aria-labelledby="ai-provider-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-provider-title',
                    'title' => __($langGroup, 'Proveedores y claves'),
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
                    <input type="text" id="ai-key-openai" name="OpenAIApiKey" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html($keyPlaceholder('OpenAIApiKey')); ?>"<?= $keyAttributes('OpenAIApiKey'); ?>>
                    <?= $clearSaved('OpenAIApiKey', 'ai-clear-openai'); ?>
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
                    <input type="text" id="ai-key-mistral" name="MistralAIApiKey" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html($keyPlaceholder('MistralAIApiKey')); ?>"<?= $keyAttributes('MistralAIApiKey'); ?>>
                    <?= $clearSaved('MistralAIApiKey', 'ai-clear-mistral'); ?>
                </div>
            </div>
        </section>

        <div class="ui section divider"></div>

        <section aria-labelledby="ai-capabilities-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-capabilities-title',
                    'title' => __($langGroup, 'Funciones'),
                ]);
            ?>
            <?php foreach (AICapabilityRegistry::all() as $capabilityKey => $capability): ?>
            <?php
                $settings = AIGateway::settingsFor($capabilityKey);
                $field = AIGateway::CONFIG_NAME . '[capabilities][' . $capabilityKey . ']';
                $idBase = 'ai-capability-' . $capabilityKey;
                $periodStart = AIGateway::periodStart($capabilityKey);
                try {
                    $usage = AIGateway::usage($capabilityKey);
                } catch (\Throwable $e) {
                    log_exception($e);
                    $usage = null;
                }
                $usageText = $usage === null
                    ? __($langGroup, 'El consumo no está disponible.')
                    : strReplaceTemplate($settings['quota'] > 0 ? $usageQuotaTemplate : $usageTemplate, [
                    '%usage%' => (string) $usage,
                    '%quota%' => (string) $settings['quota'],
                    '%start%' => SettingsController::aiPeriodStartText($periodStart),
                ]);
            ?>
            <section class="ai-capability" aria-labelledby="<?= escape_html($idBase); ?>-title" data-ai-capability="<?= escape_html($capabilityKey); ?>">
                <h3 class="ui header" id="<?= escape_html($idBase); ?>-title"><?= escape_html($capability->name()); ?></h3>
                <?php
                    $helpController->_render('panel/built-in/content/toggle-field.php', [
                        'id' => $idBase . '-enabled',
                        'inputName' => $field . '[enabled]',
                        'label' => __($langGroup, 'Activada'),
                        'checked' => $settings['enabled'],
                        'attributes' => ['data-ai-toggle' => ''],
                    ]);
                ?>
                <div class="two fields">
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-users"><?= __($langGroup, 'Quién la usa'); ?></label>
                        <select id="<?= escape_html($idBase); ?>-users" name="<?= escape_html($field); ?>[userTypes][]" class="ui fluid dropdown" multiple data-ai-user-types>
                            <?= array_to_html_options($userTypesOptions, $settings['userTypes'], true); ?>
                        </select>
                        <div class="description"><?= __($langGroup, 'Root siempre puede.'); ?></div>
                    </div>
                    <?php if ($capabilityKey === 'translation'): ?>
                    <div class="field">
                        <label for="ai-translation-provider"><?= __($langGroup, 'IA para traducciones'); ?></label>
                        <select id="ai-translation-provider" name="translationAI" class="ui fluid selection dropdown">
                            <?= array_to_html_options(TRANSLATION_AI_LIST, get_config('translationAI')); ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="three fields">
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-quota"><?= __($langGroup, 'Cuota (tokens)'); ?></label>
                        <input type="number" id="<?= escape_html($idBase); ?>-quota" name="<?= escape_html($field); ?>[quota]" min="0" step="1" value="<?= (int) $settings['quota']; ?>" aria-describedby="<?= escape_html($idBase); ?>-quota-description">
                        <div class="description" id="<?= escape_html($idBase); ?>-quota-description"><?= __($langGroup, '0 = sin límite'); ?></div>
                    </div>
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-period"><?= __($langGroup, 'Periodo'); ?></label>
                        <select id="<?= escape_html($idBase); ?>-period" name="<?= escape_html($field); ?>[period]" class="ui fluid selection dropdown" data-ai-period>
                            <?= array_to_html_options($periodOptions, $settings['period']); ?>
                        </select>
                    </div>
                    <div class="field" data-ai-period-length>
                        <label for="<?= escape_html($idBase); ?>-length">N</label>
                        <input type="number" id="<?= escape_html($idBase); ?>-length" name="<?= escape_html($field); ?>[periodLength]" min="1" step="1" value="<?= (int) $settings['periodLength']; ?>">
                    </div>
                </div>
                <div class="field">
                    <p class="ui text" data-ai-usage data-template="<?= escape_html($usageTemplate); ?>" data-template-quota="<?= escape_html($usageQuotaTemplate); ?>" data-quota="<?= (int) $settings['quota']; ?>"><?= escape_html($usageText); ?></p>
                    <?php if ($resetURL !== ''): ?>
                    <button type="button" class="ui basic button" data-ai-usage-reset="<?= escape_html($capabilityKey); ?>" data-url="<?= escape_html($resetURL); ?>" data-confirm-message="<?= escape_html(strReplaceTemplate(__($langGroup, '¿Reiniciar el contador de «%s»? Lo gastado hasta ahora se conserva, pero la cuota vuelve a contar desde cero.'), ['%s' => $capability->name()])); ?>">
                        <i class="redo icon" aria-hidden="true"></i><?= __($langGroup, 'Reiniciar el contador'); ?>
                    </button>
                    <?php endif; ?>
                </div>
            </section>
            <?php endforeach; ?>
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
                'description' => __($langGroup, 'Solo para el informe de consumo: no limita. La cuota que limita cada función está arriba, en «Funciones».'),
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
