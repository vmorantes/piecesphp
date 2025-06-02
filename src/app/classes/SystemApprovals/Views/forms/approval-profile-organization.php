<?php
    defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

    use App\Locations\LocationsLang;
    use App\Model\UsersModel;
    use Organizations\Mappers\OrganizationMapper;
    use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
    use PiecesPHP\UserSystem\Profile\SubMappers\OrganizationPreviousExperiencesMapper;
    use PiecesPHP\UserSystem\Profile\SubMappers\PreviousExperiencesMapper;
    use PiecesPHP\UserSystem\UserDataPackage;
    use SystemApprovals\Mappers\SystemApprovalsMapper;
    /**
     * @var SystemApprovalsMapper $approvalMapper
     * @var string $langGroup
     * @var string $action
     */
    $mapper = new OrganizationMapper($approvalMapper->referenceValue);
    $researchAreas = is_array($mapper->interestResearhAreas) ? $mapper->interestResearhAreas : [];
    $researchAreas = !empty($researchAreas) ? implode(', ', array_map(fn($e) => $e->currentLangData('areaName'), $researchAreas)) : '-';
    $affiliatedInstitutions = $mapper->affiliatedInstitutions;
    $affiliatedInstitutions = $affiliatedInstitutions !== null ? $affiliatedInstitutions : [];
    /**
     * @var OrganizationPreviousExperiencesMapper[]
     */
    $previousExperiences = OrganizationPreviousExperiencesMapper::allBy('profile', $mapper->id);
    //Si no tiene admin se asigna el root
    if ($mapper->administrator->id == null) {
        $mapper->administrator = new UsersModel(1);
        $mapper->update(false);
    }
    $adminUser = new UserDataPackage($mapper->administrator->id);
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?=$breadcrumbs?>
    </div>

    <div class="limiter-content">

        <div class="section-topbar">
            <div class="section-title">
                <div class="title"><?=$title?></div>
                <?php if (isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
                <div class="description"><?=$description;?></div>
                <?php endif; ?>
                <br>
                <?=$approvalMapper->getTimeTag();?>
            </div>
            <div class="actions">
            </div>
        </div>

        <br>

        <form method="POST" action="<?=$action;?>" class="ui form system-approval datasheet">

            <div class="container-standard-form mw-800">
                <div class="field">
                    <label><?=__($langGroup, 'Motivo');?></label>
                    <textarea name="reason"></textarea>
                </div>
                <div class="field global-clearfix">
                    <div class="ui right floated buttons">
                        <button type="submit" class="ui button brand-color" approve-trigger><?=__($langGroup, 'Aprobar');?></button>
                        <button type="submit" class="ui red button" reject-trigger><?=__($langGroup, 'Rechazar');?></button>
                    </div>
                </div>
            </div>
            <br>

            <input type="hidden" name="id" value="<?=$approvalMapper->id;?>">
            <button type="submit" style="display: none;" save></button>

            <div class="base-title size3"><?=__($langGroup, 'Nombre de la organización');?></div>
            <div class="base-text mark2"><?=$mapper->currentLangData('name');?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Ubicación');?></div>
            <?php $country = $mapper->country !== null ? __(LocationsLang::LANG_GROUP_NAMES, $mapper->country->name) : ''; ?>
            <?php $city = $mapper->city !== null ? __(LocationsLang::LANG_GROUP_NAMES, $mapper->city->name) : ''; ?>
            <?php $text = implode(', ', [$country, $city]); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?=$text;?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Latitud');?></div>
            <?php $text = $mapper->latitude !== null ? (string) $mapper->latitude : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?=$text;?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Longitud');?></div>
            <?php $text = $mapper->latitude !== null ? (string) $mapper->latitude : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?=$text;?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Instituciones a las que pertenece');?></div>
            <div class="base-text"><?=!empty($affiliatedInstitutions) ? implode(', ', $affiliatedInstitutions) : '-';?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Áreas de investigación de interés');?></div>
            <div class="base-text"><?=$researchAreas;?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title size3"><?=__($langGroup, 'Persona encargada');?></div>
            <div class="base-horizontal-space"></div>
            <div class="form-attachments-regular">
                <div data-trigger-open-link="<?=$adminUser->getAvatarURL();?>" class="attach-placeholder tall">
                    <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                    <div class="ui top right attached label green">
                        <i class="paperclip icon"></i>
                    </div>
                    <label for="<?=$uniqueIdentifier;?>">
                        <div class="image fullsize">
                            <img src="<?=$adminUser->getAvatarURL();?>">
                        </div>
                        <div class="text">
                            <div class="header">
                                <div class="title"><?=$adminUser->getMapper()->getFullName();?></div>
                            </div>
                        </div>
                    </label>
                    <input type="file" accept="image/*" id="<?=$uniqueIdentifier;?>">
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <div class="container-standard-form">
                <div class="base-title size3"><?=__($langGroup, 'Imágenes');?></div>
                <div class="base-horizontal-space"></div>
                <div class="form-attachments-regular">
                    <div data-trigger-open-link="<?=$mapper->getLogoURL();?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?=$uniqueIdentifier;?>">
                            <div class="image fullsize">
                                <img src="<?=$mapper->getLogoURL();?>">
                            </div>
                            <div class="text">
                                <div class="header">
                                    <div class="title"><?=__($langGroup, 'Imagen de organización');?></div>
                                </div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?=$uniqueIdentifier;?>">
                    </div>
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <?php if (!empty($previousExperiences)): ?>

            <div class="base-title size4"><?=__($langGroup, 'Experiencias');?></div>

            <div class="base-horizontal-space"></div>

            <?php foreach ($previousExperiences as $experienceRecord): ?>
            <?php
                $experience = new PreviousExperiencesMapper($experienceRecord->id);
                $researchAreasExperience = array_map(fn($e) => (new InterestResearchAreasMapper($e))->currentLangData('areaName'), $experience->researchAreas);
                $institutionsExperience = array_map(fn($e) => is_string($e) ? trim($e) : null, $experience->institutionsParticipated);
                $institutionsExperience = array_filter(
                    $experience->institutionsParticipated,
                    fn($e) => is_string($e) && mb_strlen($e) > 0
                );
            ?>

            <div class="base-title size2"><?=__($langGroup, 'Nombre de la experiencia');?></div>
            <div class="base-text mark2"><?=$experience->currentLangData('experienceName');?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Tipo de experiencia');?></div>
            <div class="base-text"><?=$experience->experienceTypeDisplayText();?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Ubicación');?></div>
            <div class="base-text"><?=__(LocationsLang::LANG_GROUP_NAMES, $experience->country->name);?>, <?=__(LocationsLang::LANG_GROUP_NAMES, $experience->city->name);?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Fecha inicial');?></div>
            <div class="base-text"><?=$experience->startDateFormat('%e %1 %B %1 Y', ['%1' => 'de']);?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Fecha final');?></div>
            <div class="base-text"><?=$experience->endDateFormat('%e %1 %B %1 Y', ['%1' => 'de']);?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Descripción');?></div>
            <div class="base-text"><?=$experience->currentLangData('description');?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Actores implicados');?></div>
            <div class="base-text"><?=!empty($institutionsExperience) ? implode(', ', $institutionsExperience) : '-';?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?=__($langGroup, 'Área de investigación');?></div>
            <div class="base-text"><?=!empty($researchAreasExperience) ? implode(', ', $researchAreasExperience) : '-';?></div>

            <div class="base-horizontal-divider"></div>
            <?php endforeach; ?>

            <?php endif; ?>

        </form>

    </div>

</section>