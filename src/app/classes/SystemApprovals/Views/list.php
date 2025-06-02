<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $editLink
 */

?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-title">
            <div class="title"><?= $title ?></div>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="tabs-controls" style="display: none;">
            <div class="active" data-tab="all"><?= __($langGroup, 'Todas'); ?></div>
        </div>

        <br>

        <div class="ui form filters">
            <div class="fields">
                <div class="seven wide field inline">
                    <label><?= __($langGroup, 'Filtrar por'); ?></label>
                    <select name="contentType" class="ui dropdown search">
                        <?= $referencesAliasesOptions; ?>
                        <option value="-1"><?= __($langGroup, 'Cualquiera'); ?></option>
                    </select>
                </div>
                <div class="seven wide field">
                    <select name="elapsedDays" class="ui dropdown search">
                        <?= $elapsepDaysOptions; ?>
                        <option value="-1"><?= __($langGroup, 'Cualquiera'); ?></option>
                    </select>
                </div>
                <div class="two wide field">
                    <button type="submit" class="ui button blue" filtee><?= __($langGroup, 'Filtrar'); ?></button>
                </div>
            </div>
        </div>

        <br>

        <div class="ui tab tab-element active" data-tab="all">

            <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
                <div class="mirror-scroll-x-content"></div>
            </div>

            <div class="container-standard-table all">

                <table url="<?= $processTableLink; ?>" class="ui basic table all">

                    <thead>

                        <tr>
                            <th><?= __($langGroup, 'Tiempo'); ?></th>
                            <th><?= __($langGroup, 'Tipo de contenido'); ?></th>
                            <th><?= __($langGroup, 'Fecha'); ?></th>
                            <th><?= __($langGroup, 'Usuario'); ?></th>
                            <th order="false" class-name="buttons" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                        </tr>

                    </thead>

                </table>

            </div>

        </div>

    </div>

</section>