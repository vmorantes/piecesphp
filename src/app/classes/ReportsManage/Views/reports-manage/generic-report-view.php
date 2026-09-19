<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\UsersModel;
/**
 * @var string $langGroup
 * @var string $editLink
 */
$currentUser = getLoggedFrameworkUser();
?>
<div class="module-view-container">

    <div class="home-hello-section-title">
        <div class="title"><?= __($langGroup, 'Hola,'); ?></div>
        <div class="subtitle"><?= $subtitle; ?></div>
    </div>

    <div class="statistics-section one-column">

        <div class="three-columns-grid one-on-break-1">

            <div class="card-statistic tall fullw-on-break-1" data-type="researchersData">
                <div class="toolbar">
                    <div class="title big"><?= __($langGroup, 'Total de usuarios generales'); ?></div>
                    <div class="help" data-tooltip="">
                        <i class="icon help"></i>
                    </div>
                </div>
                <div class="data">
                    <div class="apex-chart"></div>
                </div>
                <div class="chart-data">
                    <div class="item primary" style="--custom-color: #3558A2;">
                        <div class="color circle"></div>
                        <div class="name"><?= __($langGroup, 'Colombia'); ?></div>
                        <div data-type="totalResearchersQtyColombia" class="value neutral right">&nbsp;0&nbsp;</div>
                    </div>
                    <div class="item secondary" style="--custom-color: #254079;">
                        <div class="color circle"></div>
                        <div class="name"><?= __($langGroup, 'Francia'); ?></div>
                        <div data-type="totalResearchersQtyFrancia" class="value neutral right">&nbsp;0&nbsp;</div>
                    </div>
                    <div class="item secondary" style="--custom-color: #7A7A7A;">
                        <div class="color circle"></div>
                        <div class="name"><?= __($langGroup, 'Otros países'); ?></div>
                        <div data-type="totalResearchersQtyOthers" class="value neutral right">&nbsp;0&nbsp;</div>
                    </div>
                </div>
                <div class="footer">
                    <div></div>
                    <div class="action-button">
                    </div>
                </div>
            </div>

            <div class="card-statistic tall fullw-on-break-1" data-type="organizationsData">
                <div class="toolbar">
                    <div class="title big"><?= __($langGroup, 'Total Organizaciones'); ?></div>
                    <div class="help" data-tooltip="">
                        <i class="icon help"></i>
                    </div>
                </div>
                <div class="data">
                    <div class="apex-chart"></div>
                </div>
                <div class="chart-data">
                    <div class="item primary" style="--custom-color: #3558A2;">
                        <div class="color circle"></div>
                        <div class="name"><?= __($langGroup, 'Colombia'); ?></div>
                        <div data-type="totalOrganizationsQtyColombia" class="value neutral right">&nbsp;0&nbsp;</div>
                    </div>
                    <div class="item secondary" style="--custom-color: #254079;">
                        <div class="color circle"></div>
                        <div class="name"><?= __($langGroup, 'Francia'); ?></div>
                        <div data-type="totalOrganizationsQtyFrancia" class="value neutral right">&nbsp;0&nbsp;</div>
                    </div>
                    <div class="item secondary" style="--custom-color: #7A7A7A;">
                        <div class="color circle"></div>
                        <div class="name"><?= __($langGroup, 'Otros países'); ?></div>
                        <div data-type="totalOrganizationsQtyOthers" class="value neutral right">&nbsp;0&nbsp;</div>
                    </div>
                </div>
                <div class="footer">
                    <div></div>
                    <div class="action-button">
                    </div>
                </div>
            </div>

            <div class="one-column-grid">

                <div class="two-columns-grid two-on-break-1">

                    <div class="card-statistic tall">
                        <div class="toolbar">
                            <div class="help" data-tooltip="">
                                <i class="icon help"></i>
                            </div>
                        </div>
                        <div class="data">
                            <div data-type="totalApplicationsCallsFundingOpportunityQty" class="number">0&nbsp;</div>
                        </div>
                        <div class="footer">
                            <div class="caption">
                                <?= __($langGroup, 'Total de oportunidades de financiación'); ?>
                            </div>
                            <div class="action-button">
                            </div>
                        </div>
                    </div>

                    <div class="card-statistic tall">
                        <div class="toolbar">
                            <div class="help" data-tooltip="">
                                <i class="icon help"></i>
                            </div>
                        </div>
                        <div class="data">
                            <div data-type="totalApplicationsCallsBilateralProjectQty" class="number">0&nbsp;</div>
                        </div>
                        <div class="footer">
                            <div class="caption">
                                <?= __($langGroup, 'Proyectos bilaterales'); ?>
                            </div>
                            <div class="action-button">
                            </div>
                        </div>
                    </div>

                    <div class="card-statistic tall">
                        <div class="toolbar">
                            <div class="help" data-tooltip="">
                                <i class="icon help"></i>
                            </div>
                        </div>
                        <div class="data">
                            <div data-type="totalRemainingTokens" class="number">0&nbsp;</div>
                        </div>
                        <div class="footer">
                            <div class="caption">
                                <?= __($langGroup, 'Tokens de IA disponibles'); ?>
                            </div>
                            <div class="action-button">
                            </div>
                        </div>
                    </div>

                    <div class="card-statistic tall">
                        <div class="toolbar">
                            <div class="help" data-tooltip="">
                                <i class="icon help"></i>
                            </div>
                        </div>
                        <div class="data">
                            <div data-type="totalPendingPublicationsQty" class="number">0&nbsp;</div>
                        </div>
                        <div class="footer two">
                            <div class="caption">
                                <?= __($langGroup, 'Publicaciones por validar'); ?>
                            </div>
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

    <?php //NOTE: Se oculta provisionlmente ?>
    <div class="statistics-section one-column" style="display: none;">

        <div class="two-columns-grid one-on-break-1">

            <div class="one-column-grid">

                <div class="card-statistic fullsize">
                    <div class="toolbar">
                        <div class="title big"><?= __($langGroup, 'Publicaciones aprobadas'); ?></div>
                        <div class="help" data-tooltip="">
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
