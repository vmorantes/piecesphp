<?php

use App\Model\UsersModel;

 defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>"); ?>
<!DOCTYPE html>
<html lang="<?= get_config('app_lang'); ?>" dlang="<?= get_config('default_lang'); ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="cache-stamp" value="<?= get_config('cacheStamp'); ?>">
    <base href="<?= baseurl(); ?>">
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsGeneric(); ?>
    <?= \PiecesPHP\Core\Utilities\Helpers\MetaTags::getMetaTagsOpenGraph(); ?>
    <link rel="shortcut icon" href="<?= get_config('favicon-back'); ?>" type="image/x-icon">
    <?php load_font() ?>
    <?php load_css([
        'base_url' => "", 
        'custom_url' => "",
    ]) ?>
</head>

<body>

    <script>
    window.addEventListener('load', function() {
        $('.ui.form.select-data-imported-generated .ui.dropdown').dropdown()
    })
    </script>

    <form style="max-width: 600px;" class="ui form select-data-imported-generated" action="<?= get_current_url(); ?>" method="GET">
        <div class="field">
            <select name="dataSelected" class="ui dropdown" set-data>
                <?php foreach($dataOptions as $dataOptionValue => $dataOptionText): ?>
                <option value="<?= url_safe_base64_encode($dataOptionValue); ?>"><?= $dataOptionText; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <button type="submit" class="ui button green">Seleccionar</button>
        </div>
    </form>

    <div class="print-view">

        <?php foreach($dataPaginated as $page => $pageElements): ?>
        <div class="content">
            <?php foreach($pageElements as $dataElement): ?>
            <?php $checkPasswordResult = ''; ?>
            <?php if($checkPassword): ?>
            <?php $checkPasswordResult = password_verify($dataElement->password,  (new UsersModel())->getByUsername($dataElement->username)->password); ?>
            <?php $checkPasswordResult = $checkPasswordResult ? '<span class="ui check-pass tag label green">OK</span>' : '<span class="ui check-pass tag label red">KO</span>'; ?>
            <?php endif; ?>
            <div class="card">
                <div class="image">
                    <img src="statics/images/imported-generated-template/logo.png">
                </div>
                <div class="link">
                    <a href="https://domain.tld/">Link de acceso: https://domain.tld/</a>
                </div>
                <ul class="info">
                    <li>
                        <strong>Nombre:</strong> <?= $dataElement->fullname; ?>
                    </li>
                    <li>
                        <strong>Usuario:</strong> <?= $dataElement->username; ?>
                    </li>
                    <li>
                        <strong>Email:</strong> <?= $dataElement->email; ?>
                    </li>
                    <li>
                        <strong>Contraseña:</strong> <?= $dataElement->password . " " . $checkPasswordResult; ?>
                    </li>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <br>
        <?php endforeach; ?>

    </div>

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
