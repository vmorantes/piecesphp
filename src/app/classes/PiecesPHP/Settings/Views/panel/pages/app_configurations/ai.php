<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 */
?>

<main class="ai-view">
    <section class="main-body-header">
        <div class="head">
            <h2 class="tittle"><?= __($langGroup, 'Inteligencia artificial'); ?></h2>
            <span class="sub-tittle"><?= __($langGroup, 'Integraciones'); ?></span>
        </div>
        <div class="body-card">
            <form action="<?= $actionURL; ?>" method="POST" class="ui form ai">

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
                        <?php //La clave no viaja al HTML: el campo va vacío y, vacío, al guardar la conserva. ?>
                        <input type="text" name="OpenAIApiKey" value="" autocomplete="off" placeholder="<?= mb_strlen((string) get_config('OpenAIApiKey')) > 0 ? __($langGroup, 'Hay una clave guardada: déjelo vacío para conservarla') : ''; ?>">
                    </div>

                    <div class="field">
                        <label><?= __($langGroup, 'API Key Mistral'); ?></label>
                        <input type="text" name="MistralAIApiKey" value="" autocomplete="off" placeholder="<?= mb_strlen((string) get_config('MistralAIApiKey')) > 0 ? __($langGroup, 'Hay una clave guardada: déjelo vacío para conservarla') : ''; ?>">
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

                <div class="save-button">
                    <button type="submit" class="ui button primary"><?= __($langGroup, 'Guardar'); ?></button>
                </div>

            </form>
        </div>
    </section>
</main>
