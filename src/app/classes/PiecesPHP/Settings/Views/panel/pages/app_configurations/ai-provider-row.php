<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * Una fila de proveedor guardado de Configuración › IA (o la plantilla de «Añadir proveedor», con __INDEX__).
 *
 * @var string $langGroup
 * @var array<string,mixed> $entry Lo guardado del registro, sin clave ([] en la plantilla)
 * @var string $index
 * @var array<string,string> $typeOptions
 * @var array<string,string> $periodOptions
 * @var array<string,string> $savedKeys
 * @var string $usageTemplate
 * @var string $usageQuotaTemplate
 */
use PiecesPHP\AI\AICapability;
use PiecesPHP\AI\AIGateway;
use PiecesPHP\AI\Providers\AIProviderCatalog;
use PiecesPHP\Settings\Controllers\SettingsController;

$id = (string) ($entry['id'] ?? '');
$base = AIGateway::CONFIG_NAME . "[providers][{$index}]";
$domId = 'ai-provider-' . $index;
$type = (string) ($entry['type'] ?? 'openai');
$savedKey = $savedKeys[$id] ?? '';
$isCustom = $type === AIProviderCatalog::TYPE_CUSTOM;
?>
<div class="ui segment" data-ai-provider data-ai-provider-id="<?= escape_html($id); ?>">
    <input type="hidden" name="<?= escape_html($base); ?>[id]" value="<?= escape_html($id); ?>">
    <div class="three fields">
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
        <div class="field">
            <label for="<?= escape_html($domId); ?>-key"><?= __($langGroup, 'Clave'); ?></label>
            <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
            <input type="text" id="<?= escape_html($domId); ?>-key" name="<?= escape_html($base); ?>[key]" value="" autocomplete="off" spellcheck="false" placeholder="<?= escape_html($savedKey !== '' ? strReplaceTemplate(__($langGroup, 'Guardada (…%last%)'), ['%last%' => mb_substr($savedKey, -4)]) : __($langGroup, 'Pega aquí la API key del proveedor')); ?>">
            <?php if ($savedKey !== ''): ?>
            <div class="field"><div class="ui checkbox"><input type="checkbox" id="<?= escape_html($domId); ?>-clear" name="<?= escape_html($base); ?>[clear_key]" value="1" data-clear-saved><label for="<?= escape_html($domId); ?>-clear"><?= __($langGroup, 'Quitar la guardada'); ?></label></div></div>
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
            <input type="text" id="<?= escape_html($domId); ?>-test-model" value="<?= escape_html((string) (is_array($entry['models'] ?? null) && $entry['models'] !== [] ? end($entry['models']) : '')); ?>" autocomplete="off" spellcheck="false" data-ai-provider-test-model>
        </div>
    </div>
    <div class="field">
        <button type="button" class="ui basic button" data-ai-provider-test><i class="plug icon" aria-hidden="true"></i><?= __($langGroup, 'Probar'); ?></button>
        <button type="button" class="ui basic button" data-ai-provider-remove><i class="trash alternate outline icon" aria-hidden="true"></i><?= __($langGroup, 'Quitar'); ?></button>
        <span class="ui text" role="status" aria-live="polite" data-ai-provider-test-result></span>
    </div>
</div>
