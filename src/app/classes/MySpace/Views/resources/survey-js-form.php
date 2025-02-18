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
    baseurl("statics/plugins/surveyjs/survey-core/defaultV2.min.css"),
], 'css');
set_custom_assets([
    //SurveyJS Form Library resources
    baseurl("statics/plugins/surveyjs/survey-core/survey.core.min.js"),
    baseurl("statics/plugins/surveyjs/survey-js-ui/survey-js-ui.min.js"),
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
    <div id="surveyContainer"></div>

    <script>
    window.addEventListener('PiecesPHP-Configurations-And-Window-Load', function() {

        surveyJSTest()

        /**
         * @link https://surveyjs.io/survey-creator/documentation/get-started-html-css-javascript
         */
        function surveyJSTest() {
            const survey = new Survey.Model({
                "logoPosition": "right",
                "pages": [{
                    "name": "Página1",
                    "elements": [{
                            "type": "text",
                            "name": "Pregunta_1"
                        },
                        {
                            "type": "imagepicker",
                            "name": "Pregunta2",
                            "imageFit": "cover"
                        },
                        {
                            "type": "file",
                            "name": "Pregunta3"
                        },
                        {
                            "type": "checkbox",
                            "name": "Pregunta4",
                            "choices": [
                                "Item 1",
                                "Item 2",
                                "Item 3"
                            ]
                        },
                        {
                            "type": "boolean",
                            "name": "Pregunta5"
                        }
                    ]
                }]
            })
			//Localización
			const defaultLocateStrings = Survey.surveyLocalization.getLocaleStrings("en")
			const customLocaleStrings = Object.assign(defaultLocateStrings, getLangGroupData('SurveyJS'))
			Survey.setupLocale({
				localeCode: pcsphpGlobals.lang,
				strings: customLocaleStrings,
			})
			survey.locale = pcsphpGlobals.lang
            survey.render(document.getElementById("surveyContainer"))
            survey.onComplete.add(surveyComplete)
            function surveyComplete(survey) {
                survey.setValue("userId", "00000")
                saveSurveyResults(
                    "https://your-web-service.com/",
                    survey.data
                )
            }
            function saveSurveyResults(url, json) {
                console.log(json)
                alert(JSON.stringify(json))
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
