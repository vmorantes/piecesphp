<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * Una fila de proveedor de Configuración › IA: guardado, convertido de la configuración de antes (solo en memoria hasta el
 * primer guardado) o la plantilla de «Añadir proveedor», con __INDEX__.
 *
 * @var string $langGroup
 * @var array<string,mixed> $entry Lo guardado del registro, sin clave ([] en la plantilla)
 * @var string $index
 * @var array<string,string> $typeOptions
 * @var array<string,string> $periodOptions
 * @var array<string,string> $savedKeys
 * @var string $usageTemplate
 * @var string $usageQuotaTemplate
 * @var array<string,array<string,mixed>> $modelLists Por id: las listas de modelos que pinta la pantalla
 * @var callable(array<string,mixed>):string $modelsSourceText
 * @var list<string> $savedProviderIds Los ids de los registros GUARDADOS: solo ellos llevan precios
 */
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AICapabilityRegistry;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Providers\AIProviderCatalog;
use PiecesPHP\AI\Providers\AIProviderRecord;
use PiecesPHP\AI\Providers\AIProviderRegistry;
use PiecesPHP\Settings\Controllers\SettingsController;

$id = (string) ($entry['id'] ?? '');
$base = AIGateway::CONFIG_NAME . "[providers][{$index}]";
$domId = 'ai-provider-' . $index;
$type = (string) ($entry['type'] ?? 'openai');
$savedKey = $savedKeys[$id] ?? '';
$isCustom = $type === AIProviderCatalog::TYPE_CUSTOM;
$keySource = ($entry['keySource'] ?? null) === AIProviderRecord::KEY_SOURCE_FILE ? AIProviderRecord::KEY_SOURCE_FILE : AIProviderRecord::KEY_SOURCE_STORED;
$fromFile = $keySource === AIProviderRecord::KEY_SOURCE_FILE;
$keyFile = is_string($entry['keyFile'] ?? null) ? $entry['keyFile'] : '';
$keySourceOptions = [
    AIProviderRecord::KEY_SOURCE_STORED => __($langGroup, 'Escribirla aquí'),
    AIProviderRecord::KEY_SOURCE_FILE => __($langGroup, 'Del archivo de claves'),
];
$testModels = $id !== '' && isset($modelLists[$id]) ? $modelLists[$id] : null;
$testModel = is_array($entry['models'] ?? null) && $entry['models'] !== [] ? (string) end($entry['models']) : '';
//Los precios (ADR 0077 §8), solo en un registro guardado: los que ya tiene, los modelos que recuerda y los de las funciones
//que lo usan.
$isSaved = $id !== '' && in_array($id, $savedProviderIds ?? [], true);
$prices = $isSaved && is_array($entry['prices'] ?? null) ? $entry['prices'] : [];
$priceModels = array_keys($prices);
if ($isSaved) {
    foreach (is_array($entry['models'] ?? null) ? $entry['models'] : [] as $rememberedModel) {
        $priceModels[] = (string) $rememberedModel;
    }
    foreach (array_keys(AICapabilityRegistry::all()) as $capabilityKey) {
        $selection = AIGateway::effectiveSelectionFor($capabilityKey);
        if (($selection['provider'] ?? null) === $id && is_string($selection['model'] ?? null) && $selection['model'] !== '') {
            $priceModels[] = $selection['model'];
        }
    }
}
$priceModels = array_values(array_unique(array_filter($priceModels, fn(string $model): bool => $model !== '')));
$priceBase = $base . '[prices]';
$priceRow = function (string $priceIndex, ?string $model, ?array $price) use ($priceBase, $domId, $langGroup, $id): string {
    $name = escape_html("{$priceBase}[{$priceIndex}]");
    $rowId = escape_html("{$domId}-price-{$priceIndex}");
    $amount = fn(?float $value): string => $value === null ? '' : rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
    $label = fn(string $text): string => escape_html($model !== null ? "{$text} ({$model})" : $text);
    $html = '<div class="three fields" data-ai-price>';
    if ($model !== null) {
        $html .= '<div class="field"><label for="' . $rowId . '-model">' . escape_html(__($langGroup, 'Modelo')) . '</label><input type="text" id="' . $rowId . '-model" name="' . $name . '[model]" value="' . escape_html($model) . '" readonly></div>';
    } else {
        $html .= '<div class="field"><label for="' . $rowId . '-model">' . escape_html(__($langGroup, 'Modelo')) . '</label><select id="' . $rowId . '-model" name="' . $name . '[model]" class="ui fluid search selection dropdown" data-ai-model-select data-ai-model-capability="' . escape_html(AIProviderCatalog::CAPABILITY_CHAT) . '" data-ai-model-provider="' . escape_html($id) . '" data-current=""></select><input type="text" value="" autocomplete="off" spellcheck="false" aria-label="' . escape_html(__($langGroup, 'Otro modelo')) . '" placeholder="' . escape_html(__($langGroup, 'Escribe el modelo')) . '" data-ai-model-other hidden></div>';
    }
    foreach (['input' => 'Entrada', 'output' => 'Salida'] as $field => $text) {
        $html .= '<div class="field"><label for="' . $rowId . '-' . $field . '">' . escape_html(__($langGroup, $text)) . '</label><input type="number" id="' . $rowId . '-' . $field . '" name="' . $name . '[' . $field . ']" min="0" max="' . SettingsController::MAX_AI_PRICE . '" step="0.0001" inputmode="decimal" value="' . $amount(isset($price[$field]) ? (float) $price[$field] : null) . '" aria-label="' . $label(__($langGroup, $text)) . '"></div>';
    }
    return $html . '</div>';
};
?>
<div class="ui segment" data-ai-provider data-ai-provider-id="<?= escape_html($id); ?>">
    <input type="hidden" name="<?= escape_html($base); ?>[id]" value="<?= escape_html($id); ?>">
    <div class="two fields">
        <div class="field">
            <label for="<?= escape_html($domId); ?>-type"><?= __($langGroup, 'Tipo'); ?></label>
            <select id="<?= escape_html($domId); ?>-type" name="<?= escape_html($base); ?>[type]" class="ui fluid selection dropdown" data-ai-provider-type>
                <?= array_to_html_options($typeOptions, $type); ?>
            </select>
        </div>
        <div class="field required">
            <label for="<?= escape_html($domId); ?>-alias"><?= __($langGroup, 'Sobrenombre'); ?></label>
            <input type="text" id="<?= escape_html($domId); ?>-alias" name="<?= escape_html($base); ?>[alias]" value="<?= escape_html((string) ($entry['alias'] ?? '')); ?>" maxlength="80" required>
        </div>
    </div>
    <div class="two fields">
        <div class="field">
            <label for="<?= escape_html($domId); ?>-key-source"><?= __($langGroup, 'Origen de la clave'); ?></label>
            <select id="<?= escape_html($domId); ?>-key-source" name="<?= escape_html($base); ?>[keySource]" class="ui fluid selection dropdown" data-ai-key-source>
                <?= array_to_html_options($keySourceOptions, $keySource); ?>
            </select>
        </div>
        <div class="field" data-ai-key-stored<?= $fromFile ? ' hidden' : ''; ?>>
            <label for="<?= escape_html($domId); ?>-key"><?= __($langGroup, 'Clave'); ?></label>
            <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
            <input type="text" id="<?= escape_html($domId); ?>-key" name="<?= escape_html($base); ?>[key]" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html($savedKey !== '' ? strReplaceTemplate(__($langGroup, 'Guardada (…%last%)'), ['%last%' => mb_substr($savedKey, -4)]) : __($langGroup, 'Pega aquí la API key del proveedor')); ?>">
            <?php if ($savedKey !== ''): ?>
            <div class="field"><div class="ui checkbox"><input type="checkbox" id="<?= escape_html($domId); ?>-clear" name="<?= escape_html($base); ?>[clear_key]" value="1" data-clear-saved><label for="<?= escape_html($domId); ?>-clear"><?= __($langGroup, 'Quitar la guardada'); ?></label></div></div>
            <?php endif; ?>
        </div>
        <div class="field" data-ai-key-file<?= $fromFile ? '' : ' hidden'; ?>>
            <label for="<?= escape_html($domId); ?>-key-file"><?= __($langGroup, 'Nombre en el archivo'); ?></label>
            <input type="text" id="<?= escape_html($domId); ?>-key-file" name="<?= escape_html($base); ?>[keyFile]" value="<?= escape_html($keyFile); ?>" maxlength="61" autocomplete="off" spellcheck="false" aria-describedby="<?= escape_html($domId); ?>-key-file-description">
            <div class="description" id="<?= escape_html($domId); ?>-key-file-description"><?= escape_html(__($langGroup, 'El del tipo (openai, mistral o groq, solo en esos tres) o ai-<tipo>-…, por ejemplo ai-openai-marketing.')); ?></div>
            <?php if ($fromFile && $keyFile !== '' && $id !== ''): ?>
            <?php //Solo si está o no: ningún carácter de la clave. ?>
            <p class="ui text" data-ai-key-file-status><?= AIProviderRegistry::fileKey($keyFile, $type) !== '' ? __($langGroup, 'Está en el archivo') : __($langGroup, 'No está en el archivo'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="two fields" data-ai-provider-custom<?= $isCustom ? '' : ' hidden'; ?>>
        <div class="field">
            <label for="<?= escape_html($domId); ?>-base-url"><?= __($langGroup, 'Dirección base'); ?></label>
            <input type="url" id="<?= escape_html($domId); ?>-base-url" name="<?= escape_html($base); ?>[baseUrl]" value="<?= escape_html((string) ($entry['baseUrl'] ?? '')); ?>" placeholder="https://" aria-describedby="<?= escape_html($domId); ?>-base-url-description">
            <div class="description" id="<?= escape_html($domId); ?>-base-url-description"><?= __($langGroup, 'https://, o http://localhost o http://127.0.0.1 para un modelo local.'); ?></div>
        </div>
        <div class="field">
            <label for="<?= escape_html($domId); ?>-key-header"><?= __($langGroup, 'Cabecera de la clave'); ?></label>
            <input type="text" id="<?= escape_html($domId); ?>-key-header" name="<?= escape_html($base); ?>[keyHeader]" value="<?= escape_html((string) ($entry['keyHeader'] ?? '')); ?>" autocomplete="off" spellcheck="false" aria-describedby="<?= escape_html($domId); ?>-key-header-description">
            <div class="description" id="<?= escape_html($domId); ?>-key-header-description"><?= __($langGroup, 'Vacía: Authorization: Bearer.'); ?></div>
        </div>
    </div>
    <div class="three fields">
        <div class="field">
            <label for="<?= escape_html($domId); ?>-quota"><?= __($langGroup, 'Cuota (tokens)'); ?></label>
            <input type="number" id="<?= escape_html($domId); ?>-quota" name="<?= escape_html($base); ?>[quota]" min="0" step="1" value="<?= (int) ($entry['quota'] ?? 0); ?>" aria-describedby="<?= escape_html($domId); ?>-quota-description">
            <div class="description" id="<?= escape_html($domId); ?>-quota-description"><?= __($langGroup, '0 = sin límite. Suma lo de todas las funciones que lo usan.'); ?></div>
        </div>
        <div class="field">
            <label for="<?= escape_html($domId); ?>-period"><?= __($langGroup, 'Periodo'); ?></label>
            <select id="<?= escape_html($domId); ?>-period" name="<?= escape_html($base); ?>[period]" class="ui fluid selection dropdown" data-ai-period>
                <?= array_to_html_options($periodOptions, (string) ($entry['period'] ?? AICapability::PERIOD_ACCUMULATED)); ?>
            </select>
        </div>
        <div class="field" data-ai-period-length>
            <label for="<?= escape_html($domId); ?>-length">N</label>
            <input type="number" id="<?= escape_html($domId); ?>-length" name="<?= escape_html($base); ?>[periodLength]" min="1" step="1" value="<?= (int) ($entry['periodLength'] ?? 1); ?>">
        </div>
    </div>
    <?php if ($id !== '' && ($providerQuota = AIGateway::providerSettings($id)) !== null): ?>
    <?php
        try {
            $providerUsed = AIGateway::providerUsage($id);
        } catch (\Throwable $e) {
            log_exception($e);
            $providerUsed = null;
        }
        $providerUsageText = $providerUsed === null ? __($langGroup, 'El consumo no está disponible.') : strReplaceTemplate($providerQuota['quota'] > 0 ? $usageQuotaTemplate : $usageTemplate, [
            '%usage%' => (string) $providerUsed,
            '%quota%' => (string) $providerQuota['quota'],
            '%start%' => SettingsController::aiPeriodStartText((string) AIGateway::providerPeriodStart($id)),
        ]);
    ?>
    <div class="field">
        <p class="ui text" data-ai-provider-usage><?= escape_html($providerUsageText); ?></p>
    </div>
    <?php endif; ?>
    <div class="two fields">
        <div class="field">
            <label for="<?= escape_html($domId); ?>-test-model"><?= __($langGroup, 'Modelo para probar'); ?></label>
            <?php //Sin name: «Probar» lo lee de aquí y no se guarda. Las opciones las pone ai.js con las listas de la página. ?>
            <select id="<?= escape_html($domId); ?>-test-model" class="ui fluid search selection dropdown" data-ai-model-select data-ai-model-capability="<?= escape_html(AIProviderCatalog::CAPABILITY_CHAT); ?>" data-ai-model-provider="<?= escape_html($id); ?>" data-ai-provider-test-model data-current="<?= escape_html($testModel); ?>">
                <?php if ($testModel !== ''): ?>
                <option value="<?= escape_html($testModel); ?>" selected><?= escape_html($testModel); ?></option>
                <?php endif; ?>
            </select>
            <input type="text" value="" autocomplete="off" spellcheck="false" aria-label="<?= escape_html(__($langGroup, 'Otro modelo')); ?>" placeholder="<?= escape_html(__($langGroup, 'Escribe el modelo')); ?>" data-ai-model-other hidden>
        </div>
        <?php if ($id !== ''): ?>
        <div class="field">
            <label>&nbsp;</label>
            <button type="button" class="ui basic button" data-ai-provider-models-refresh><i class="sync alternate icon" aria-hidden="true"></i><?= __($langGroup, 'Actualizar modelos'); ?></button>
            <p class="ui muted text" data-ai-models-source><?= $testModels !== null ? escape_html($modelsSourceText($testModels)) : ''; ?></p>
        </div>
        <?php endif; ?>
    </div>
    <div class="field" data-ai-prices>
        <h4 class="ui header" id="<?= escape_html($domId); ?>-prices-title"><?= __($langGroup, 'Precios (USD por millón de tokens, opcional)'); ?></h4>
        <?php if ($isSaved): ?>
        <input type="hidden" name="<?= escape_html($base); ?>[prices_sent]" value="1">
        <div data-ai-prices-list>
            <?php foreach ($priceModels as $priceIndex => $priceModel): ?>
            <?= $priceRow((string) $priceIndex, $priceModel, $prices[$priceModel] ?? null); ?>
            <?php endforeach; ?>
        </div>
        <template data-ai-price-template><?= $priceRow('__PRICE__', null, null); ?></template>
        <button type="button" class="ui basic button" data-ai-price-add data-next-index="<?= count($priceModels); ?>"><i class="plus icon" aria-hidden="true"></i><?= __($langGroup, 'Añadir precio'); ?></button>
        <?php else: ?>
        <p class="ui muted text"><?= __($langGroup, 'Guarda el proveedor para ponerle precios.'); ?></p>
        <?php endif; ?>
    </div>
    <div class="field">
        <button type="button" class="ui basic button" data-ai-provider-test><i class="plug icon" aria-hidden="true"></i><?= __($langGroup, 'Probar'); ?></button>
        <button type="button" class="ui basic button" data-ai-provider-remove><i class="trash alternate outline icon" aria-hidden="true"></i><?= __($langGroup, 'Quitar'); ?></button>
        <span class="ui text" role="status" aria-live="polite" data-ai-provider-test-result></span>
    </div>
</div>
