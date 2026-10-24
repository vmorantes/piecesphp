<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $processTableLink
 * @var string $referencesAliasesOptions
 * @var string $elapsepDaysOptions
 */

?>
<section class="module-view-container">

    <div class="limiter-content">

        <?php
        $this->helpController->_render('panel/built-in/content/page-header.php', [
            'breadcrumbs' => $breadcrumbs,
            'title' => $title,
            'description' => $description ?? '',
        ]);
        ?>

        <form class="ui form segment filters" data-approvals-filters>
            <div class="fields">
                <div class="eight wide field">
                    <label><?= __($langGroup, 'Tipo de contenido'); ?></label>
                    <select name="contentType" class="ui dropdown search">
                        <?= $referencesAliasesOptions; ?>
                        <option value="-1"><?= __($langGroup, 'Cualquiera'); ?></option>
                    </select>
                </div>
                <div class="eight wide field">
                    <label><?= __($langGroup, 'Tiempo'); ?></label>
                    <select name="elapsedDays" class="ui dropdown search">
                        <?= $elapsepDaysOptions; ?>
                        <option value="-1"><?= __($langGroup, 'Cualquiera'); ?></option>
                    </select>
                </div>
            </div>
            <button type="submit" class="ui button brand-color"><i class="filter icon"></i><?= __($langGroup, 'Filtrar'); ?></button>
            <button type="reset" class="ui basic button"><?= __($langGroup, 'Limpiar'); ?></button>
        </form>

        <div class="mirror-scroll-x all" mirror-scroll-target=".container-standard-table.all">
            <div class="mirror-scroll-x-content"></div>
        </div>

        <div class="container-standard-table all">

            <table url="<?= $processTableLink; ?>" class="ui basic table content-table all">

                <thead>

                    <tr>
                        <th><?= __($langGroup, 'Tiempo'); ?></th>
                        <th><?= __($langGroup, 'Tipo de contenido'); ?></th>
                        <th><?= __($langGroup, 'Fecha'); ?></th>
                        <th><?= __($langGroup, 'Usuario'); ?></th>
                        <th order="false" search="false" class-name="collapsing" with-container="true"><?= __($langGroup, 'Acciones'); ?></th>
                    </tr>

                </thead>

            </table>

        </div>

    </div>

</section>
