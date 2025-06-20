<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use App\Locations\LocationsLang;
use App\Model\UsersModel;
use ApplicationCalls\Mappers\ApplicationCallsMapper;
use ApplicationCalls\Util\AttachmentPackage;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Config;
use PiecesPHP\UserSystem\Profile\SubMappers\InterestResearchAreasMapper;
use PiecesPHP\UserSystem\Profile\SubMappers\PreviousExperiencesMapper;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Mappers\SystemApprovalsMapper;
/**
 * @var SystemApprovalsMapper $approvalMapper
 * @var string $langGroup
 * @var string $action
 */
$userPackage = new UserDataPackage($approvalMapper->referenceValue);
$mapper = $userPackage->getMapper();
$profileMapper = $userPackage->profile;
$organizationMapper = $userPackage->organizationMapper;
$researchAreas = is_array($profileMapper->interestResearhAreas) ? $profileMapper->interestResearhAreas : [];
$researchAreas = !empty($researchAreas) ? implode(', ', array_map(fn($e) => $e->currentLangData('areaName'), $researchAreas)) : '-';
$affiliatedInstitutions = $profileMapper->affiliatedInstitutions;
$affiliatedInstitutions = $affiliatedInstitutions !== null ? $affiliatedInstitutions : [];
/**
 * @var PreviousExperiencesMapper[]
 */
$previousExperiences = PreviousExperiencesMapper::allBy('profile', $profileMapper->id);

$organizationText = $organizationMapper->currentLangData('name');
$userTypeText = UsersModel::getTypeUserName($userPackage->type);
$isBaseOrganization = $organizationMapper->id == OrganizationMapper::INITIAL_ID_GLOBAL;
if($isBaseOrganization){
    $organizationText = __($langGroup, 'N/A');
}
if($userPackage->type == UsersModel::TYPE_USER_GENERAL){
    if($isBaseOrganization){
        $userTypeText = __($langGroup, 'Usuario independiente');
    }
}
?>
<section class="module-view-container">

    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>

    <div class="limiter-content">

        <div class="section-topbar">
            <div class="section-title">
                <div class="title"><?= $title ?></div>
                <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
                <div class="description"><?= $description; ?></div>
                <?php endif; ?>
                <br>
                <?= $approvalMapper->getTimeTag(); ?>
            </div>
            <div class="actions">
            </div>
        </div>

        <br>

        <form method="POST" action="<?= $action; ?>" class="ui form system-approval datasheet">

            <div class="container-standard-form mw-800">
                <div class="field">
                    <label><?= __($langGroup, 'Motivo'); ?></label>
                    <textarea name="reason"></textarea>
                </div>
                <div class="field global-clearfix">
                    <div class="ui right floated buttons">
                        <button type="submit" class="ui button brand-color" approve-trigger><?= __($langGroup, 'Aprobar'); ?></button>
                        <button type="submit" class="ui red button" reject-trigger><?= __($langGroup, 'Rechazar'); ?></button>
                    </div>
                </div>
            </div>
            <br>

            <input type="hidden" name="id" value="<?= $approvalMapper->id; ?>">
            <button type="submit" style="display: none;" save></button>

            <div class="base-title size2"><?= __($langGroup, 'Nombres'); ?></div>
            <div class="base-text mark"><?= $mapper->getNames(); ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title size2"><?= __($langGroup, 'Apellidos'); ?></div>
            <div class="base-text mark"><?= $mapper->getLastNames(); ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title size2"><?= __($langGroup, 'Organización'); ?></div>
            <div class="base-text mark"><?= $organizationText; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Tipo de actor'); ?></div>
            <div class="base-text"><?= $userTypeText; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Usuario'); ?></div>
            <div class="base-text"><?= $mapper->username; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Correo electrónico'); ?></div>
            <div class="base-text"><?= $mapper->email; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Teléfono'); ?></div>
            <?php $text = $profileMapper->getPhone(); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'LinkedIn'); ?></div>
            <?php $text = $profileMapper->getLinkedinLink(); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Página web'); ?></div>
            <?php $text = $profileMapper->getWebsiteLink(); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Nacionalidad'); ?></div>
            <?php $text = $profileMapper->currentLangData('nationality'); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Título o posición'); ?></div>
            <?php $text = $profileMapper->currentLangData('jobPosition'); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'País'); ?></div>
            <?php $text = $profileMapper->country !== null ? __(LocationsLang::LANG_GROUP_NAMES, $profileMapper->country->name) : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Ciudad'); ?></div>
            <?php $text = $profileMapper->city !== null ? __(LocationsLang::LANG_GROUP_NAMES, $profileMapper->city->name) : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Latitud'); ?></div>
            <?php $text = $profileMapper->latitude !== null ? (string) $profileMapper->latitude : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Longitud'); ?></div>
            <?php $text = $profileMapper->latitude !== null ? (string) $profileMapper->latitude : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?= $text; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Áreas de investigación de interés'); ?></div>
            <div class="base-text"><?= $researchAreas; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Instituciones a las que pertenece'); ?></div>
            <div class="base-text"><?= !empty($affiliatedInstitutions) ? implode(', ', $affiliatedInstitutions) : '-'; ?></div>

            <div class="base-horizontal-space"></div>

            <div class="container-standard-form">
                <div class="base-title size3"><?= __($langGroup, 'Imágenes'); ?></div>
                <div class="base-horizontal-space"></div>
                <div class="form-attachments-regular">
                    <div data-trigger-open-link="<?= $userPackage->getAvatarURL(); ?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached label green">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?= $uniqueIdentifier; ?>">
                            <div class="image fullsize">
                                <img src="<?= $userPackage->getAvatarURL(); ?>">
                            </div>
                            <div class="text">
                                <div class="header">
                                    <div class="title"><?= __($langGroup, 'Imagen de perfil'); ?></div>
                                </div>
                            </div>
                        </label>
                        <input type="file" accept="image/*" id="<?= $uniqueIdentifier; ?>">
                    </div>
                </div>
            </div>

            <div class="base-horizontal-space"></div>

            <?php if(!empty($previousExperiences)): ?>

            <div class="base-title size4"><?= __($langGroup, 'Experiencias'); ?></div>

            <div class="base-horizontal-space"></div>

            <?php foreach($previousExperiences as $experienceRecord): ?>
            <?php
                $experience = new PreviousExperiencesMapper($experienceRecord->id);
                $researchAreasExperience = array_map(fn($e) => (new InterestResearchAreasMapper($e))->currentLangData('areaName'), $experience->researchAreas);
                $institutionsExperience = array_map(fn($e) => is_string($e) ? trim($e) : null, $experience->institutionsParticipated);
                $institutionsExperience = array_filter(
                    $experience->institutionsParticipated, 
                    fn($e) => is_string($e) && mb_strlen($e) > 0
                );
            ?>

            <div class="base-title size2"><?= __($langGroup, 'Nombre de la experiencia'); ?></div>
            <div class="base-text mark2"><?= $experience->currentLangData('experienceName'); ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Tipo de experiencia'); ?></div>
            <div class="base-text"><?= $experience->experienceTypeDisplayText(); ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Ubicación'); ?></div>
            <div class="base-text"><?= __(LocationsLang::LANG_GROUP_NAMES, $experience->country->name); ?>, <?= __(LocationsLang::LANG_GROUP_NAMES, $experience->city->name); ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Fecha inicial'); ?></div>
            <div class="base-text"><?= $experience->startDateFormat('%e %1 %B %1 Y', ['%1' => __(LANG_GROUP, 'de')]); ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Fecha final'); ?></div>
            <div class="base-text"><?= $experience->endDateFormat('%e %1 %B %1 Y', ['%1' => __(LANG_GROUP, 'de')]); ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Descripción'); ?></div>
            <div class="base-text"><?= $experience->currentLangData('description'); ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Actores implicados'); ?></div>
            <div class="base-text"><?= !empty($institutionsExperience) ? implode(', ', $institutionsExperience) : '-'; ?></div>
            <div class="base-horizontal-space"></div>

            <div class="base-title"><?= __($langGroup, 'Área de investigación'); ?></div>
            <div class="base-text"><?= !empty($researchAreasExperience) ? implode(', ', $researchAreasExperience) : '-'; ?></div>

            <div class="base-horizontal-divider"></div>
            <?php endforeach; ?>

            <?php endif; ?>

        </form>

    </div>

</section>
