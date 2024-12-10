<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use MySpace\Controllers\MySpaceController;
use PiecesPHP\UserSystem\Authentication\TOTPStandard;
use PiecesPHP\UserSystem\Controllers\UserSystemFeaturesController;

/**
 * @var string $langGroup
 * @var string $editLink
 */
$currentUser = getLoggedFrameworkUser();
$totpData = $currentUser->TOTPData;
$totpSecret = $totpData->secret;
$username = $currentUser->username;
$totpManager = new TOTPStandard($totpSecret);
$totpQrURL = $totpManager->getQRCodeUrl($username, get_config('owner'));
?>
<section class="module-view-container" drag-area>

    <div class="home-hello-section-title">
        <div class="title"><?= __($langGroup, 'Hola,'); ?></div>
        <div class="subtitle"><?= $subtitle; ?></div>
    </div>

    <div class="tabs-controls">
        <div class="active" data-tab="a"><?= __($langGroup, 'Gráficos y estadísticas'); ?></div>
        <div data-tab="b"><?= __($langGroup, 'Plantillas de correo'); ?></div>
        <div data-tab="c"><?= __($langGroup, 'Bases de 2AF'); ?></div>
        <div data-tab="d"><?= __($langGroup, 'Dialog'); ?></div>
        <div data-tab="e"><?= __($langGroup, 'Survey JS Creator'); ?></div>
        <div data-tab="f"><?= __($langGroup, 'Survey JS Form'); ?></div>
    </div>

    <div class="ui tab tab-element active" data-tab="a">

        <h2><?= __($langGroup, 'Gráficos y estadísticas'); ?></h2>

        <div class="statistics-section">

            <div class="card-statistic">
                <div class="toolbar">
                    <div class="help">
                        <i class="icon help"></i>
                    </div>
                </div>
                <div class="data">
                    <div data-type="dataElement1" class="number">0&nbsp;</div>
                </div>
                <div class="footer">
                    <div class="caption">
                        <?= __($langGroup, 'Texto de ejemplo'); ?>
                    </div>
                    <div class="action-button">
                        <a class="button" href="#">
                            <i class="arrow right icon"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-statistic">
                <div class="toolbar">
                    <div class="help">
                        <i class="icon help"></i>
                    </div>
                </div>
                <div class="data">
                    <div data-type="dataElement2" class="number">0&nbsp;</div>
                </div>
                <div class="footer">
                    <div class="caption">
                        <?= __($langGroup, 'Texto de ejemplo'); ?>
                    </div>
                    <div class="action-button">
                        <a class="button" href="#">
                            <i class="arrow right icon"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-statistic">
                <div class="toolbar">
                    <div class="help">
                        <i class="icon help"></i>
                    </div>
                </div>
                <div class="data">
                    <div data-type="dataElement3" class="number">0&nbsp;</div>
                </div>
                <div class="footer">
                    <div class="caption">
                        <?= __($langGroup, 'Texto de ejemplo'); ?>
                    </div>
                    <div class="action-button">
                        <a class="button" href="#">
                            <i class="arrow right icon"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-statistic">
                <div class="toolbar">
                    <div class="help">
                        <i class="icon help"></i>
                    </div>
                </div>
                <div class="data">
                    <div data-type="dataElement4" class="number">0&nbsp;</div>
                </div>
                <div class="footer">
                    <div class="caption">
                        <?= __($langGroup, 'Texto de ejemplo'); ?>
                    </div>
                    <div class="action-button">
                        <a class="button" href="#">
                            <i class="arrow right icon"></i>
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <div class="statistics-section one-column">

            <div class="two-columns-grid one-on-break-1">

                <div class="card-statistic tall fullw-on-break-1">
                    <div class="toolbar">
                        <div class="title big"><?= __($langGroup, 'Texto de ejemplo'); ?></div>
                        <div class="help">
                            <i class="icon help"></i>
                        </div>
                    </div>
                    <div class="data">
                        <div data-type="dataElement5" class="progress-circle" style="--progress: 0;">
                            <div class="inner">
                                <div data-type="dataElement6" class="number">0%</div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-data">
                        <div class="item primary">
                            <div class="color circle"></div>
                            <div class="name"><?= __($langGroup, 'Valor A'); ?></div>
                            <div data-type="dataElement7" class="value">0&nbsp;</div>
                        </div>
                        <div class="item sencodary">
                            <div class="color circle"></div>
                            <div class="name"><?= __($langGroup, 'Valor B'); ?></div>
                            <div data-type="dataElement8" class="value">0&nbsp;</div>
                        </div>
                    </div>
                    <div class="footer">
                        <div></div>
                        <div class="action-button">
                            <a class="button" href="#">
                                <i class="arrow right icon"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="one-column-grid">

                    <div class="three-columns-grid two-on-break-1">

                        <div class="card-statistic tall">
                            <div class="toolbar">
                                <div class="help">
                                    <i class="icon help"></i>
                                </div>
                            </div>
                            <div class="data">
                                <div data-type="dataElement9" class="number">0&nbsp;</div>
                            </div>
                            <div class="footer">
                                <div class="caption">
                                    <?= __($langGroup, 'Texto de ejemplo'); ?>
                                </div>
                                <div class="action-button">
                                    <a class="button" href="#">
                                        <i class="arrow right icon"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-statistic tall">
                            <div class="toolbar">
                                <div class="help">
                                    <i class="icon help"></i>
                                </div>
                            </div>
                            <div class="data">
                                <div data-type="dataElement10" class="number">0&nbsp;</div>
                            </div>
                            <div class="footer">
                                <div class="caption">
                                    <?= __($langGroup, 'Texto de ejemplo'); ?>
                                </div>
                                <div class="action-button">
                                    <a class="button" href="#">
                                        <i class="arrow right icon"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card-statistic tall">
                            <div class="toolbar">
                                <div class="help">
                                    <i class="icon help"></i>
                                </div>
                            </div>
                            <div class="data">
                                <div data-type="dataElement11" class="number">0&nbsp;</div>
                            </div>
                            <div class="footer">
                                <div class="caption">
                                    <?= __($langGroup, 'Texto de ejemplo'); ?>
                                </div>
                                <div class="action-button">
                                    <a class="button" href="#">
                                        <i class="arrow right icon"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="one-column-grid">

                        <div class="card-statistic fullsize">
                            <div class="toolbar">
                                <div class="title big"><?= __($langGroup, 'Texto de ejemplo'); ?></div>
                                <div class="help">
                                    <i class="icon help"></i>
                                </div>
                            </div>
                            <div class="data">
                                <div id="projection-charts"></div>
                            </div>
                            <div class="footer">
                                <div></div>
                                <div class="action-button">
                                    <a class="button" href="#">
                                        <i class="arrow right icon"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="ui tab tab-element" data-tab="b">

        <h2><?= __($langGroup, 'Usuarios'); ?></h2>

        <iframe src="<?= MySpaceController::routeName('iframe-sources', ['source' => 'mail-users-template'])?>" frameborder="0" style="width: 100%; max-width: 800px; height: 800px;"></iframe>

    </div>

    <div class="ui tab tab-element" data-tab="c">

        <h2><?= __($langGroup, 'TOTP'); ?></h2>

        <ul class="mw-800">
            <li>
                <strong><?= __($langGroup, 'Secreto'); ?>:</strong>
                <span class="secret-text"><?= $totpSecret; ?></span>
            </li>
            <li>
                <strong><?= __($langGroup, 'QR URL'); ?>:</strong>
                <span class="secret-text"><?= $totpQrURL; ?></span>
            </li>
            <li>
                <strong><?= __($langGroup, 'QR'); ?>:</strong>
                <span qr-container data-value="<?= $totpQrURL; ?>"></span>
            </li>
            <li>
                <strong><?= __($langGroup, 'TOTP'); ?>:</strong>
                <span totp-code data-url="<?= UserSystemFeaturesController::routeName('get-current-totp'); ?>"></span>
            </li>
            <li>
                <strong><?= __($langGroup, 'Verificar código'); ?>:</strong>
                <form action="<?= UserSystemFeaturesController::routeName('check-totp'); ?>" class="ui form" method="POST" totp>
                    <div class="fields two">
                        <div class="field">
                            <input type="text" name="username" required readonly value="<?= $username; ?>">
                        </div>
                        <div class="field">
                            <input type="text" name="totp" required value="">
                        </div>
                    </div>
                    <div class="field">
                        <button class="ui button green" type="submit"><?= __($langGroup, 'Verificar'); ?></button>
                    </div>
                </form>
            </li>
        </ul>

    </div>

    <div class="ui tab tab-element" data-tab="d">
        <button class="ui button green" trigger-add-dialog-pcs><?= __($langGroup, 'Mostrar'); ?></button>
        <div class="ui card dialog-pcs a">
            <div class="content header" drag-area>
                <div class="right floated">
                    <i class="trash alternate outline icon" delete title="<?= __($langGroup, 'Eliminar'); ?>"></i>
                    <i class="window close outline icon" close title="<?= __($langGroup, 'Cerrar'); ?>"></i>
                </div>
                <div class="header">Lorem, ipsum dolor.</div>
            </div>
            <div class="content">
                <p>Lorem, ipsum dolor sit amet consectetur adipisicing elit. Alias veniam ad expedita distinctio, quod error voluptatem pariatur magni illo molestias, voluptatibus eius enim fugit consequatur cupiditate? Recusandae a nobis provident!</p>
            </div>
            <div class="extra content">
                <button class="ui button primary"><?= __($langGroup, 'Aceptar'); ?></button>
                <button class="ui button"><?= __($langGroup, 'Cancelar'); ?></button>
            </div>
        </div>
    </div>

    <div class="ui tab tab-element" data-tab="e">
        <iframe src="<?= MySpaceController::routeName('iframe-sources', ['source' => 'survey-js-creator'])?>" frameborder="0" style="width: 100%; max-width: 1920px; height: 800px;"></iframe>
    </div>

    <div class="ui tab tab-element" data-tab="f">
        <iframe src="<?= MySpaceController::routeName('iframe-sources', ['source' => 'survey-js-form'])?>" frameborder="0" style="width: 100%; max-width: 1920px; height: 800px;"></iframe>
    </div>

</section>
