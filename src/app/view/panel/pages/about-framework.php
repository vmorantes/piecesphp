<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\AdminPanelController;
$user = getLoggedFrameworkUser(true)->userMapper;
$langGroup = 'about-framework';
?>
<main class="about-framework">

    <div class="page-content">
        <div class="titles">
            <h2><?= __($langGroup, 'Acerca de'); ?></h2>
            <span> <?= get_config('title_app'); ?> <?= __($langGroup, 'desarrollada en:'); ?></span>
        </div>

        <div class="main-card">
            <div class="head">
                <div class="information">
                    <img src="<?= base_url('statics/images/framework/main_logo.svg') ?>" alt="">

                    <div class="data-grup">
                        <div class="data">
                            <small><?= __($langGroup, 'Última versión'); ?></small>
                            <span>v<?= APP_VERSION; ?></span>
                        </div>
                        <div class="data">
                            <small><?= __($langGroup, 'Fecha'); ?></small>
                            <span><?= (new \DateTime(APP_VERSION_DATE))->format('m/Y'); ?></span>
                        </div>
                    </div>

                    <div class="git-button">
                        <a target="_blank" href="https://bitbucket.org/piecesphp/piecesphp/src">BitBucket</a>
                    </div>

                </div>
                <div class="decorated">
                    <img src="<?= base_url('statics/images/framework/animated_square.gif') ?>" alt="">
                </div>
            </div>

            <article class="description">
                <div class="decored-tittle">
                    <div class="tittle">
                        <h3><?= __($langGroup, 'Descripción'); ?></h3>
                    </div>
                </div>
                <p>
                    <?= __($langGroup, 'Es conjunto de componentes personalizables y reusables para el desarrollo web basado en PHP con licencia MIT (de código abierto, sin costos escondidos o adicionales para el cliente final), modular, liviano y flexible que permite “ensamblar” diferentes librerías, funcionalidades y plugins como su nombre “Pieces” lo indica; normalmente a este grupo de componentes se le conoce como Framework.'); ?>
                </p>

                <p>
                    <?= __($langGroup, 'Este framework ha venido siendo desarrollado durante 5 años por Tejido Digital y ha permitido la estandarización de procesos dentro de la organización, desarrollo de manera más ágil y rápida, disminuyendo las solicitudes de soporte (garantía) y en caso de presentarse alguna, lograr una solución más rápida.'); ?>
                </p>
            </article>

            <article class="own-bookstores">
                <div class="decored-tittle">
                    <div class="tittle">
                        <h3><?= __($langGroup, 'Librerías propias'); ?></h3>
                        <span><?= __($langGroup, 'con licencia MIT'); ?></span>
                    </div>
                </div>

                <div class="libraries">
                    <ul>
                        <li>Pieces PHP datastructures Ver 2.0</li>
                        <li>Pieces PHP html Ver 1.1</li>
                    </ul>
                    <ul>
                        <li>Pieces PHP database Ver 2.5</li>
                        <li>Pieces PHP geojson Ver 1.1</li>
                    </ul>
                </div>

            </article>

            <article class="dependencies">
                <div class="decored-tittle">
                    <div class="tittle">
                        <h3><?= __($langGroup, 'Plugins, scripts y dependencias'); ?></h3>
                        <span><?= __($langGroup, 'Desarrollados por terceros'); ?></span>
                    </div>
                </div>
                <p>
                    <?= __($langGroup, 'Estas cuentan con diferentes acuerdos de licencia y están autorizados para uso comercial por parte de terceros, pero los alcances pueden variar dependiendo del proveedor, las cuales son:'); ?>
                </p>
                <ul>
                    <li>Ckeditor Ver. 5.0 Licencia GNU</li>
                    <li>Cropper Ver. 1.6.1 Licencia MIT</li>
                    <li>Datatables Ver. 1.13.5 Licencia MIT</li>
                    <li>elfinder Ver. 2.1.57 Licencia BSD</li>
                    <li>izitoast Ver. 1.4.0 Licencia APACHE</li>
                    <li>jquery Ver. 3.7.0 Licencia MIT</li>
                    <li>jquery-ui Ver. 1.12.1 Licencia MIT</li>
                    <li style="display: none;">Open-layers Ver. 6.14.1 Licencia BSD</li>
                    <li>Fomantic Ver 2.9.4 Licencia MIT</li>
                    <li>ApexCharts Ver. 3.50.0 Licencia MIT</li>
                    <li>QRCodeJS Licencia MIT</li>
                    <li>MapBox JS Ver. 3.4.0 Licencia Mapbox Web SDK</li>
                    <li>Spectrum Ver. 1.8 Licencia MIT</li>
                    <li>slim/slim Ver. 4.* Licencia MIT</li>
                    <li>slim/psr7 Ver. 1.6.* Licencia MIT</li>
                    <li>phpmailer Ver. 6.* Licencia GNU</li>
                    <li>phpspreadsheet Ver. 1.29.* Licencia MIT</li>
                    <li>mpdf Ver. 8.2.* Licencia GNU</li>
                    <li>mysqldump-php Ver. 2.12.* Licencia GNU</li>
                    <li>scssphp Ver. 1.12.* Licencia MIT</li>
                    <li>spatie/url Ver. 1.3.* Licencia MIT</li>
                    <li>microsoft/azure-storage-blob Ver. 1.5.* Licencia MIT</li>
                    <li>pragmarx/google2fa Ver. 8.0.* Licencia MIT</li>
                </ul>
            </article>
        </div>

        <section class="footer">
            <img src="<?= base_url('statics/images/framework/developer_by_logo.png'); ?>">
            <span><?= date('Y') ?></span>
        </section>

    </div>
</main>