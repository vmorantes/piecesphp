<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $actionURL
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var string $indexLink
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 */
?>
<section class="module-view-container ai-view">

    <div class="limiter-content">

        <?php
        //La cabecera del diseño de contenidos (diseno/02-configuracion.md §2.1).
        $helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description,
        ]);
        ?>

        <form action="<?= $actionURL; ?>" method="POST" class="ui form initial ai" data-content-form>

            <div class="container-standard-form">

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

</section>
