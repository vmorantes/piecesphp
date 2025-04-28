<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use PiecesPHP\Core\ConfigHelpers\MailConfig;

/**
 * @var MailConfig $element
 */
?>

<main class="seo-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Seguridad e IA'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Descripción_Seguridad_e_IA'); ?></span>
        </div>
        <div class="body-card">
            <form action="<?= $actionURL; ?>" method="POST" class="ui form security-and-ia">

                <div class="ui dividing header"><?= __($langGroup, 'Seguridad'); ?></div>

                <div class="fields">

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="check_aud_on_auth" <?= get_config('check_aud_on_auth') ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Usar IP del usuario para encriptar el token de sesión'); ?></label>
                        </div>
                    </div>

                </div>

                <div class="ui dividing header"><?= __($langGroup, 'Inteligencia artificial'); ?></div>

                <div class="two fields">

                    <div class="field">
                        <label><?= __($langGroup, 'Modelo OpenAI'); ?></label>
                        <select name="modelOpenAI" class="ui dropdown">
                            <?= array_to_html_options(AI_MODELS[AI_OPENAI], get_config('modelOpenAI')); ?>
                        </select>
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'Modelo Mistral'); ?></label>
                        <select name="modelMistral" class="ui dropdown">
                            <?= array_to_html_options(AI_MODELS[AI_MISTRAL], get_config('modelMistral')); ?>
                        </select>
                    </div>

                </div>

                <div class="two fields">

                    <div class="field">
                        <label><?= __($langGroup, 'API Key OpenAI'); ?></label>
                        <input type="text" name="OpenAIApiKey" value="<?= get_config('OpenAIApiKey'); ?>">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'API Key Mistral'); ?></label>
                        <input type="text" name="MistralAIApiKey" value="<?= get_config('MistralAIApiKey'); ?>">
                    </div>

                </div>

                <div class="two fields">
                    <div class="field">
                        <label><?= __($langGroup, 'IA para traducciones'); ?></label>
                        <select name="translationAI" class="ui dropdown">
                            <?= array_to_html_options(TRANSLATION_AI_LIST, get_config('translationAI')); ?>
                        </select>
                    </div>

                    <div class="field">
                        <div class="ui toggle checkbox">
                            <input type="checkbox" name="translationAIEnable" <?= get_config('translationAIEnable') ? 'checked' : ''; ?>>
                            <label><?= __($langGroup, 'Activar traducción con IA'); ?></label>
                        </div>
                    </div>
                </div>

                <div style="width: 100%; text-align: end;">
                    <button type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

                <div class="divider"></div>

            </form>
        </div>
    </section>
</main>
