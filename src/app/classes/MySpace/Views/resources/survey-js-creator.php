<?php
set_config('global_assets', [
    'js' => [],
    'css' => [],
    'font' => [],
]);
set_config('custom_assets', [
    'js' => [],
    'css' => [],
    'font' => [],
]);
set_config('default_assets', [
    "css" => [],
    "js" => [],
    "plugins" => [],
]);
set_config('global_requireds_assets', [
    'css' => [],
    'js' => [],
    'font' => [],
]);
set_config('imported_assets', []);
set_config('lock_assets', false);
set_custom_assets([
    //SurveyJS Form Library resources
    "https://unpkg.com/survey-core/defaultV2.min.css",
    //Survey Creator resources
    "https://unpkg.com/survey-creator-core/survey-creator-core.min.css",
], 'css');
set_custom_assets([
    //SurveyJS Form Library resources
    "https://unpkg.com/survey-core/survey.core.min.js",
    "https://unpkg.com/survey-js-ui/survey-js-ui.min.js",
    //(Optional) Predefined theme configurations
    "https://unpkg.com/survey-core/themes/index.min.js",
    //Survey Creator resources
    "https://unpkg.com/survey-creator-core/survey-creator-core.min.js",
    "https://unpkg.com/survey-creator-js/survey-creator-js.min.js",
    "https://unpkg.com/survey-creator-core/survey-creator-core.i18n.min.js",
], 'js');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SurveyJS</title>
    <?php load_font() ?>
    <?php load_css([
        'base_url' => "", 
        'custom_url' => "",
    ]) ?>
</head>

<body>
    <div id="surveyCreator" style="height: 100vh;"></div>

    <script>
    window.addEventListener('load', function() {

        surveyJSTest()

        /**
         * @link https://surveyjs.io/survey-creator/documentation/get-started-html-css-javascript
         */
        function surveyJSTest() {

            /** Idiomas**/
            const defaultLang = "es"
            const langsNames = {
                es: 'español',
                en: 'inglés',
                de: 'alemán',
            }
            const supportedLangs = [
                "es",
                "en",
                "de",
            ]

            Survey.surveyLocalization.currentLocale = defaultLang
            Survey.surveyLocalization.defaultLocaleValue = defaultLang
            Survey.surveyLocalization.localeNames = langsNames
            Survey.surveyLocalization.supportedLocales = supportedLangs
            SurveyCreator.editorLocalization.currentLocale = Survey.surveyLocalization.currentLocale
            SurveyCreator.editorLocalization.defaultLocaleValue = Survey.surveyLocalization.defaultLocaleValue

            //Instanciación
            const creatorOptions = {
                showLogicTab: true,
                isAutoSave: true,
                showTranslationTab: true,
            }
            const creator = new SurveyCreator.SurveyCreator(creatorOptions)
            creator.render(document.getElementById("surveyCreator"))

            //Escuchador de cambios
            creator.saveSurveyFunc = (currentChangeNumber, callback) => {
                console.log({
                    JSON: creator.JSON,
                    text: creator.text,
                })
                saveSurveyJson(
                    "https://your-web-service.com/",
                    creator.JSON,
                    currentChangeNumber,
                    callback
                )
            }

            console.log({
                Survey: Survey.surveyLocalization,
                SurveyCreator: SurveyCreator.editorLocalization,
            })
            
            //Comunicación con backend callback debe recibir en 2do argumento false o true en función del resultado en backend
            function saveSurveyJson(url, json, currentChangeNumber, callback) {
                callback(currentChangeNumber, true)
            }
        }
    })
    </script>
    <?php load_js([
        'base_url' => "",
        'custom_url' => "",
        'attr' => [
            'test-attr' => 'yes',
        ],
        'attrApplyTo' => [
            'test-attr' => [
                '.*configurations\.js$',
            ],
        ],
    ]) ?>
</body>

</html>
