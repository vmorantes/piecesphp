<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Persons\Controllers\PersonsController;
use Persons\Mappers\PersonsMapper;

/**
 * @var PersonsMapper $element
 * @var PersonsController $this
 */
/**
 * @var string $langGroup
 * @var string $backLink
 * @var string $action
 */
?>
<section class="module-view-container limit-size">

    <div class="header-options">

        <div class="main-options">

            <a href="<?= $backLink; ?>" class="ui icon button brand-color alt2" title="<?= __($langGroup, 'Regresar'); ?>">
                <i class="icon left arrow"></i>
            </a>

        </div>

        <div class="columns">

            <div class="column">

                <div class="section-title">
                    <div class="title"><?= $title; ?></div>
                    <div class="subtitle"><?= __($langGroup, 'Editar'); ?></div>
                </div>

            </div>

        </div>

    </div>

    <div class="container-standard-form">
        <?php if($manyLangs): ?>
        <div class="ui form">
            <div class="field required">
                <label><?= __($langGroup, 'Idiomas'); ?></label>
                <select required class="ui dropdown search langs">
                    <?= $allowedLangs; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
        <form method='POST' action="<?= $action; ?>" class="ui form" person-form>

            <input type="hidden" name="id" value="<?= $element->id; ?>">
            <input type="hidden" name="lang" value="<?= $lang; ?>">

            <div class="two fields">
                <div class="field">

                    <div class="field required">
                        <label><?= __($langGroup, 'Tipo de documento'); ?></label>
                        <select required name="documentType" class="ui dropdown search auto"><?= $optionsDocumentTypes; ?></select>
                    </div>
                    <br>

                    <div class="field required">
                        <label><?= __($langGroup, 'Primer nombre'); ?></label>
                        <input type="text" name="personName1" required value="<?= $element->personName1; ?>">
                    </div>
                    <br>

                    <div class="field required">
                        <label><?= __($langGroup, 'Primer apellido'); ?></label>
                        <input type="text" name="personLastName1" required value="<?= $element->personLastName1; ?>">
                    </div>
                    <br>

                </div>

                <div class="field">

                    <div class="field required">
                        <label><?= __($langGroup, 'Número de identificación'); ?></label>
                        <input type="text" name="documentNumber" required value="<?= $element->documentNumber; ?>">
                    </div>
                    <br>

                    <div class="field">
                        <label><?= __($langGroup, 'Segundo nombre'); ?></label>
                        <input type="text" name="personName2" value="<?= $element->personName2; ?>">
                    </div>
                    <br>

                    <div class="field">
                        <label><?= __($langGroup, 'Segundo apellido'); ?></label>
                        <input type="text" name="personLastName2" value="<?= $element->personLastName2; ?>">
                    </div>
                    <br>

                </div>
            </div>

            <div class="field">
                <div class="ui buttons">
                    <button type="submit" class="ui button brand-color"><?= __($langGroup, 'Guardar'); ?></button>
                    <?php if($allowDelete): ?>
                    <button type="submit" class="ui button brand-color alt2" delete-persons-button data-route="<?= $deleteRoute; ?>"><?= __($langGroup, 'Eliminar'); ?></button>
                    <?php endif; ?>
                </div>
            </div>

        </form>
    </div>

</section>
