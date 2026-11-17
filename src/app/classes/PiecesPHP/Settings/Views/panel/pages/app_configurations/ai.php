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
        $tokensLimitElement = new \PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper(\PiecesPHP\BuiltIn\Helpers\Mappers\GenericContentPseudoMapper::CONTENT_TOKENS_LIMIT);
        $tokensLimitName = $tokensLimitElement->userSetContentName();
        $tokensLimitAction = \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::allowedRoute('actions-edit') ? (string) \PiecesPHP\BuiltIn\Helpers\Controllers\GenericContentController::routeName('actions-edit') : '';
        //La línea del consumo: el JS la rehace tras «Reiniciar el contador» con las mismas plantillas.
        $usageTemplate = __($langGroup, 'Consumo de este periodo: %usage% tokens, desde %start%.');
        $usageQuotaTemplate = __($langGroup, 'Consumo de este periodo: %usage% tokens de %quota%, desde %start%.');
    ?>

    <form action="<?= $actionURL; ?>" method="POST" class="ui form initial ai segment" data-content-form>

        <?php //Tres pestañas dentro del formulario: un solo «Guardar» debajo guarda lo de las tres (ai.js salta a la del campo que falla). ?>
        <div class="ui secondary pointing menu tabs-controls content-tabs" data-ai-tabs>
            <a class="active item" data-tab="general"><i class="power off icon" aria-hidden="true"></i><?= __($langGroup, 'General'); ?></a>
            <a class="item" data-tab="proveedores"><i class="plug icon" aria-hidden="true"></i><?= __($langGroup, 'Proveedores'); ?></a>
            <a class="item" data-tab="funciones"><i class="th list icon" aria-hidden="true"></i><?= __($langGroup, 'Funciones'); ?></a>
        </div>

        <div class="ui active tab" data-tab="general">
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
        </div>

        <div class="ui tab" data-tab="proveedores">
        <?php
            $records = AIProviderRegistry::all();
            $pendingConverted = AIProviderRegistry::convertedPending();
            //Las claves escritas: las guardadas y, sin la conversión escrita, las de antes de los convertidos (solo sus 4 últimos).
            $savedKeys = AIProviderRegistry::keys() + array_filter(array_map(fn(array $converted): string => $converted['key'], $pendingConverted), fn(string $key): bool => $key !== '');
            $typeOptions = array_map(fn(array $type): string => $type['name'], AIProviderCatalog::TYPES);
            $typeOptions[AIProviderCatalog::TYPE_CUSTOM] = __($langGroup, 'Personalizado');
            //Las listas de modelos de cada registro, para el desplegable de cada función y de «Probar» (ai.js, sin pedir nada).
            $modelLists = [];
            foreach ($records as $record) {
                $chatModels = AIGateway::providerModels($record, AIProviderCatalog::CAPABILITY_CHAT);
                $modelLists[$record->id()] = [
                    'chat' => $chatModels['models'],
                    'speech' => AIGateway::providerModels($record, AIProviderCatalog::CAPABILITY_SPEECH)['models'],
                    'remembered' => $record->models(),
                    'source' => $chatModels['source'],
                    'fetchedAt' => $chatModels['fetchedAt'],
                    'fallbackMeasured' => (string) (AIProviderCatalog::get($record->type())['fallbackMeasured'] ?? ''),
                ];
            }
            $providerListTemplate = __($langGroup, 'Lista del proveedor, actualizada el %fecha%');
            $fallbackListTemplate = __($langGroup, 'Lista de respaldo (documentación del %fecha%)');
            $modelsSourceText = fn(array $list): string => $list['source'] === 'provider'
                ? strReplaceTemplate($providerListTemplate, ['%fecha%' => SettingsController::aiPeriodStartText((string) $list['fetchedAt'])])
                : strReplaceTemplate($fallbackListTemplate, ['%fecha%' => SettingsController::aiPeriodStartText($list['fallbackMeasured'] . ' 00:00:00')]);
            $modelsURL = SettingsController::allowedRoute('integrations-ai-provider-models') ? (string) SettingsController::routeName('integrations-ai-provider-models') : '';
        ?>
        <section aria-labelledby="ai-provider-title" data-ai-providers data-test-url="<?= escape_html(SettingsController::allowedRoute('integrations-ai-provider-test') ? (string) SettingsController::routeName('integrations-ai-provider-test') : ''); ?>" data-models-url="<?= escape_html($modelsURL); ?>" data-ai-models="<?= escape_html((string) json_encode($modelLists, \JSON_UNESCAPED_UNICODE)); ?>" data-other-text="<?= escape_html(__($langGroup, 'Otro…')); ?>" data-provider-list-template="<?= escape_html($providerListTemplate); ?>">
            <?php
                $helpController->_render('panel/built-in/content/section-header.php', [
                    'id' => 'ai-provider-title',
                    'title' => __($langGroup, 'Proveedores'),
                    'description' => __($langGroup, 'Cada proveedor con su sobrenombre y de dónde sale su clave; cada función elige el suyo abajo. La clave escrita no se vuelve a enseñar.'),
                ]);
            ?>

            <input type="hidden" name="<?= escape_html(AIGateway::CONFIG_NAME); ?>[providers_sent]" value="1">
            <?php //Solo este formulario escribe la conversión: uno de una versión anterior no manda converted_sent. ?>
            <input type="hidden" name="<?= escape_html(AIGateway::CONFIG_NAME); ?>[converted_sent]" value="1">
            <div data-ai-providers-list>
                <?php //Los convertidos de la configuración de antes salen como filas normales: guardar los escribe. ?>
                <?php $savedProviders = array_values($records); ?>
                <?php $savedEntries = SettingsController::aiProviderEntries() + array_map(fn(array $converted): array => $converted['entry'], $pendingConverted); ?>
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
        </div>

        <div class="ui tab" data-tab="funciones">
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
                </div>
                <?php
                    //La selección EFECTIVA: sin la conversión escrita, la de antes ya rellena (guardar la deja explícita).
                    $selection = AIGateway::effectiveSelectionFor($capabilityKey);
                    //Los que saben lo que pide la función: chat y, si usa herramientas, herramientas.
                    $isSpeech = $capability->providerType() === AICapability::PROVIDER_SPEECH;
                    $choices = ['' => __($langGroup, 'Ninguno (la función no está disponible)')];
                    foreach ($records as $record) {
                        $fits = $isSpeech ? $record->supports(AIProviderCatalog::CAPABILITY_SPEECH) : $record->supports(AIProviderCatalog::CAPABILITY_CHAT) && (!$capability->usesTools() || $record->supports(AIProviderCatalog::CAPABILITY_TOOLS));
                        if ($fits) {
                            $choices[$record->id()] = $record->alias() . ' (' . ($record->type() === AIProviderCatalog::TYPE_CUSTOM ? __($langGroup, 'Personalizado') : AIProviderCatalog::TYPES[$record->type()]['name']) . ')';
                        }
                    }
                    $currentModel = (string) ($selection['model'] ?? '');
                ?>
                <div class="two fields">
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-provider"><?= __($langGroup, 'Proveedor'); ?></label>
                        <select id="<?= escape_html($idBase); ?>-provider" name="<?= escape_html($field); ?>[provider]" class="ui fluid selection dropdown" data-ai-capability-provider>
                            <?= array_to_html_options($choices, $selection['provider'] ?? ''); ?>
                        </select>
                    </div>
                    <div class="field">
                        <label for="<?= escape_html($idBase); ?>-model"><?= __($langGroup, 'Modelo'); ?></label>
                        <?php //Las opciones las pone ai.js con las listas de la página; «Otro…» enseña el campo de texto. ?>
                        <select id="<?= escape_html($idBase); ?>-model" name="<?= escape_html($field); ?>[model]" class="ui fluid search selection dropdown" data-ai-model-select data-ai-model-capability="<?= escape_html($isSpeech ? AIProviderCatalog::CAPABILITY_SPEECH : AIProviderCatalog::CAPABILITY_CHAT); ?>" data-ai-model-provider="<?= escape_html((string) ($selection['provider'] ?? '')); ?>" data-current="<?= escape_html($currentModel); ?>" aria-describedby="<?= escape_html($idBase); ?>-model-description">
                            <?php if ($currentModel !== ''): ?>
                            <option value="<?= escape_html($currentModel); ?>" selected><?= escape_html($currentModel); ?></option>
                            <?php endif; ?>
                        </select>
                        <input type="text" value="" autocomplete="off" spellcheck="false" aria-label="<?= escape_html(__($langGroup, 'Otro modelo')); ?>" placeholder="<?= escape_html(__($langGroup, 'Escribe el modelo')); ?>" data-ai-model-other hidden>
                        <div class="description" id="<?= escape_html($idBase); ?>-model-description"><?= __($langGroup, 'Obligatorio si eliges un proveedor.'); ?></div>
                    </div>
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
        </div>

        <?php
            //La barra al final del formulario (§2.3), fuera de las pestañas: siempre a la vista.
            $helpController->_render('panel/built-in/content/form-action-bar.php', [
                'saveText' => __($langGroup, 'Guardar'),
                'secondaryActions' => [['text' => __($langGroup, 'Cancelar'), 'url' => $indexLink, 'icon' => 'times']],
            ]);
        ?>

    </form>

    <?php if ($tokensLimitAction !== ''): ?>
    <?php //Un formulario aparte, dentro no cabe: guarda en su propia ruta (GenericContentController). ?>
    <?php //Solo con «General» activa (ai.js lo enseña u oculta al cambiar de pestaña). ?>
    <form action="<?= escape_html($tokensLimitAction); ?>" method="POST" class="ui form initial tokens-limit segment" aria-labelledby="ai-quota-title" data-content-form data-ai-quota>
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
