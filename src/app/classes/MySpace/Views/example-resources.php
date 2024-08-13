<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * @var string $langGroup
 * @var string $editLink
 */
?>
<section class="module-view-container">

    <div class="home-hello-section-title">
        <div class="title"><?= __($langGroup, 'Hola,'); ?></div>
        <div class="subtitle"><?= $subtitle; ?></div>
    </div>

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

</section>
