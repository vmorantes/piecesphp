<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AdminPanelController;
$user = getLoggedFrameworkUser(true)->userMapper;
?>
<div class="banner-zone"></div>

<div class="ui very padded segment mw-800 b-center info-card">

    <div class="header-list">

        <h3 class="title-list subtitle small">
            <?= __(AdminPanelController::LANG_GROUP, 'Acerca de'); ?>
            <span class="subtitle">PiecesPHP v<?= APP_VERSION; ?></span>
        </h3>

    </div>

</div>

<div class="container-about-framework">

    <p>
        PiecesPHP es conjunto de componentes personalizables y reusables para el desarrollo web basado en PHP con licencia MIT (de código abierto, sin costos escondidos o adicionales para el cliente final), modular, liviano y flexible que permite “ensamblar” diferentes librerías, funcionalidades y
        plugins como su nombre “Pieces” lo indica; normalmente a este grupo de componentes se le conoce como Framework.
    </p>
    <p>
        Este framework ha venido siendo desarrollado durante 5 años por Tejido Digital y ha permitido la estandarización de procesos dentro de la organización, desarrollo de manera más ágil y rápida, disminuyendo las solicitudes de soporte (garantía) y en caso de presentarse alguna, lograr una
        solución más rápida.
    </p>

    <p>
        <strong>Cuenta con las siguientes librerías propias, con licencia MIT:</strong>
    </p>
    <ul>
        <li>Pieces PHP datastructures Ver 2.0</li>
        <li>Pieces PHP html Ver 1.1</li>
        <li>Pieces PHP database Ver 2.5</li>
        <li>Pieces PHP geojson Ver 1.1</li>
    </ul>

    <p>
        <strong>Además, incluye plugins, scripts y dependencias que son desarrollados por terceros, estas cuentan con diferentes acuerdos de licencia y están autorizados para uso comercial por parte de terceros, pero los alcances pueden variar dependiendo del proveedor, las cuales son:</strong>
    </p>
    <ul>
        <li>Ckeditor Ver. 5.0 Licencia GNU</li>
        <li>Cropper Ver. 1.36 Licencia MIT</li>
        <li>Datatables Ver. 1.13.5 Licencia MIT</li>
        <li>elfinder Ver. 2.1.57 Licencia BSD</li>
        <li>izitoast Ver. 1.4.0 Licencia APACHE</li>
        <li>jquery Ver. 3.7.0 Licencia MIT</li>
        <li>jquery-ui Ver. 1.12.1 Licencia MIT</li>
        <li>Open-layers Ver. 6.14.1 Licencia BSD</li>
        <li>Fomantic Ver 2.9.2 Licencia MIT</li>
        <li>Spectrum Ver. 1.8 Licencia MIT</li>
        <li>Slim Ver. 3.0 Licencia MIT</li>
        <li>phpmailer Ver. 6.6 Licencia GNU</li>
        <li>html2pdf Ver. 5.2 Licencia OSL 3.0</li>
        <li>monolog Ver. 1.23 Licencia MIT</li>
        <li>phpspreadsheet Ver. 1.22 Licencia MIT</li>
        <li>leclient Ver. 1.2 Licencia MIT</li>
        <li>psr log Ver. 1.1 Licencia MIT</li>
        <li>mpdf Ver. 8.0 Licencia GNU</li>
        <li>mysqldump-php Ver. 2.9 Licencia GNU</li>
        <li>scssphp Ver. 1.10 Licencia MIT</li>
        <li>spatie url Ver. 1.3 Licencia MI</li>
    </ul>
</div>
