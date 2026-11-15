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
use PiecesPHP\AI\Providers\AIProviderCatalog;
use PiecesPHP\AI\Providers\AIProviderRegistry;
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
        //Una clave que da el archivo de claves manda (P318): el campo no se edita y lo dice.
        $fixedByFile = fn(string $option): bool => \PiecesPHP\Core\ConfigHelpers\ConfigOptions::isFixedByFile($option);
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

        <section aria-labelledby="ai-provider-title" data-ai-providers data-test-url="<?= escape_html(SettingsController::allowedRoute('integrations-ai-provider-test') ? (string) SettingsController::routeName('integrations-ai-provider-test') : ''); ?>">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-provider-title',
                    'title' => __($langGroup, 'Proveedores'),
                    'description' => __($langGroup, 'Cada proveedor con su sobrenombre y su clave; cada función elige el suyo abajo. La clave no se vuelve a enseñar.'),
                ]);
                $records = AIProviderRegistry::all();
                $savedKeys = AIProviderRegistry::keys();
                $typeOptions = array_map(fn(array $type): string => $type['name'], AIProviderCatalog::TYPES);
                $typeOptions[AIProviderCatalog::TYPE_CUSTOM] = __($langGroup, 'Personalizado');
                $legacyOrigin = fn(string $option): string => \PiecesPHP\Core\ConfigHelpers\ConfigOptions::isFixedByFile($option) ? __($langGroup, 'Del archivo de claves') : __($langGroup, 'De la configuración anterior');
                $legacyRows = [
                    AIProviderRegistry::LEGACY_OPENAI => ['name' => 'OpenAI', 'option' => 'OpenAIApiKey', 'keyId' => 'ai-key-openai', 'keyLabel' => __($langGroup, 'API Key OpenAI'), 'model' => 'modelOpenAI', 'models' => AI_MODELS[AI_OPENAI], 'clear' => 'ai-clear-openai'],
                    AIProviderRegistry::LEGACY_MISTRAL => ['name' => 'Mistral', 'option' => 'MistralAIApiKey', 'keyId' => 'ai-key-mistral', 'keyLabel' => __($langGroup, 'API Key Mistral'), 'model' => 'modelMistral', 'models' => AI_MODELS[AI_MISTRAL], 'clear' => 'ai-clear-mistral'],
                    AIProviderRegistry::LEGACY_GROQ => ['name' => 'Groq', 'option' => 'GroqAPIKey', 'keyId' => '', 'keyLabel' => '', 'model' => null, 'models' => [], 'clear' => null],
                ];
            ?>
            <?php foreach ($legacyRows as $legacyId => $legacy): ?>
            <?php $legacyExists = isset($records[$legacyId]); ?>
            <?php if (!$legacyExists && $legacy['model'] === null): ?>
            <?php continue; ?>
            <?php endif; ?>
            <?php //OpenAI y Mistral se ven siempre: la traducción usa su clave y su modelo hasta la ronda 3 del ADR 0077. ?>
            <div class="ui secondary segment" data-ai-provider-legacy="<?= escape_html($legacyId); ?>">
                <h4 class="ui header"><?= escape_html($legacy['name']); ?>
                    <div class="sub header"><?= $legacyExists ? escape_html($legacyOrigin($legacy['option'])) . ' · ' : ''; ?><?= $legacy['model'] !== null ? __($langGroup, 'La usa la traducción') : __($langGroup, 'Heredado: no se edita aquí'); ?></div>
                </h4>
                <?php if ($legacy['model'] !== null): ?>
                <div class="two fields">
                    <div class="field">
                        <label for="ai-<?= escape_html($legacy['model']); ?>"><?= escape_html(strReplaceTemplate(__($langGroup, 'Modelo %name%'), ['%name%' => $legacy['name']])); ?></label>
                        <select id="ai-<?= escape_html($legacy['model']); ?>" name="<?= escape_html($legacy['model']); ?>" class="ui fluid selection dropdown">
                            <?= array_to_html_options($legacy['models'], get_config($legacy['model'])); ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="<?= escape_html($legacy['keyId']); ?>"><?= escape_html($legacy['keyLabel']); ?></label>
                        <?php if (!$fixedByFile($legacy['option'])): ?>
                        <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
                        <input type="text" id="<?= escape_html($legacy['keyId']); ?>" name="<?= escape_html($legacy['option']); ?>" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html(mb_strlen((string) get_config($legacy['option'])) > 0 ? __($langGroup, 'Clave guardada: vacío la conserva') : __($langGroup, 'Pega aquí la API key del proveedor')); ?>">
                        <?php endif; ?>
                        <?= $clearSaved($legacy['option'], (string) $legacy['clear']); ?>
                    </div>
                </div>
                <?php else: ?>
                <p class="ui text"><?= __($langGroup, 'Solo voz a texto: no se ofrece a las funciones de chat.'); ?></p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>

            <input type="hidden" name="<?= escape_html(AIGateway::CONFIG_NAME); ?>[providers_sent]" value="1">
            <div data-ai-providers-list>
                <?php $savedProviders = array_values(array_filter($records, fn($record) => !str_starts_with($record->id(), AIProviderRegistry::LEGACY_PREFIX))); ?>
                <?php $savedEntries = SettingsController::aiProviderEntries(); ?>
                <?php foreach ($savedProviders as $position => $record): ?>
                <?php $entry = $savedEntries[$record->id()] ?? ['id' => $record->id(), 'type' => $record->type(), 'alias' => $record->alias()]; ?>
                <?php $index = (string) $position; ?>
                <?php include __DIR__ . '/ai-provider-row.php'; ?>
                <?php endforeach; ?>
            </div>
            <?php if ($savedProviders === []): ?>
            <p class="ui muted text" data-ai-providers-empty><?= __($langGroup, 'Todavía no hay proveedores guardados.'); ?></p>
            <?php endif; ?>
            <?php $entry = []; ?>
            <?php $index = '__INDEX__'; ?>
            <template data-ai-provider-template><?php include __DIR__ . '/ai-provider-row.php'; ?></template>
            <button type="button" class="ui basic button" data-ai-provider-add data-next-index="<?= count($savedProviders); ?>"><i class="plus icon" aria-hidden="true"></i><?= __($langGroup, 'Añadir proveedor'); ?></button>
        </section>

        <div class="ui section divider"></div>

        <section aria-labelledby="ai-capabilities-title">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-capabilities-title',
                    'title' => __($langGroup, 'Funciones'),
                ]);
            ?>
            <?php $firstCapability = true; ?>
            <?php foreach (AICapabilityRegistry::all() as $capabilityKey => $capability): ?>
            <?php if (!$firstCapability): ?>
            <div class="ui divider"></div>
            <?php endif; ?>
            <?php
                $firstCapability = false;
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
                    <?php else: ?>
                    <?php
                        $selection = AIGateway::providerSelectionFor($capabilityKey);
                        //Los que saben lo que pide la función: chat y, si usa herramientas, herramientas. Los heredados, primero.
                        $choices = ['' => __($langGroup, 'El de «IA para traducciones»')];
                        $suggestions = [];
                        foreach ($records as $record) {
                            if ($record->supports(AIProviderCatalog::CAPABILITY_CHAT) && (!$capability->usesTools() || $record->supports(AIProviderCatalog::CAPABILITY_TOOLS))) {
                                $choices[$record->id()] = $record->alias() . ' (' . ($record->type() === AIProviderCatalog::TYPE_CUSTOM ? __($langGroup, 'Personalizado') : AIProviderCatalog::TYPES[$record->type()]['name']) . ')';
                                $suggestions[$record->id()] = $record->models();
                            }
                        }
                    ?>
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-provider"><?= __($langGroup, 'Proveedor'); ?></label>
                        <select id="<?= escape_html($idBase); ?>-provider" name="<?= escape_html($field); ?>[provider]" class="ui fluid selection dropdown" data-ai-capability-provider<?= ($selection['provider'] ?? null) === null ? ' aria-describedby="' . escape_html($idBase) . '-provider-description"' : ''; ?>>
                            <?= array_to_html_options($choices, $selection['provider'] ?? ''); ?>
                        </select>
                        <?php if (($selection['provider'] ?? null) === null): ?>
                        <div class="description" id="<?= escape_html($idBase); ?>-provider-description"><?= __($langGroup, 'Usa el proveedor de la traducción hasta que elijas otro.'); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($capabilityKey !== 'translation'): ?>
                <div class="two fields">
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-model"><?= __($langGroup, 'Modelo'); ?></label>
                        <input type="text" id="<?= escape_html($idBase); ?>-model" name="<?= escape_html($field); ?>[model]" value="<?= escape_html($selection['model'] ?? ''); ?>" list="<?= escape_html($idBase); ?>-models" autocomplete="off" spellcheck="false" aria-describedby="<?= escape_html($idBase); ?>-model-description">
                        <datalist id="<?= escape_html($idBase); ?>-models">
                            <?php foreach (array_unique(array_merge(...array_values($suggestions + ['' => []]))) as $suggestion): ?>
                            <option value="<?= escape_html($suggestion); ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                        <div class="description" id="<?= escape_html($idBase); ?>-model-description"><?= __($langGroup, 'Obligatorio si eliges un proveedor.'); ?></div>
                    </div>
                </div>
                <?php endif; ?>
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
