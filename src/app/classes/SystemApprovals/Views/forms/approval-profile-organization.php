<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\App\Locations\LocationsLang;
use PiecesPHP\App\Locations\Mappers\CityMapper;
use PiecesPHP\App\Locations\Mappers\CountryMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\UserDataPackage;
use SystemApprovals\Mappers\SystemApprovalsMapper;
/**
 * @var SystemApprovalsMapper $approvalMapper
 * @var string $langGroup
 * @var string $action
 */
$mapper = new OrganizationMapper($approvalMapper->referenceValue);
$affiliatedInstitutions = $mapper->affiliatedInstitutions;
$affiliatedInstitutions ??= [];
//Lo que escribe un usuario se escapa al pintar: nada lo limpia al entrar (pendientes.md 394).
$escape = fn ($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
//Sin encargado se dice y no se toca nada: una vista solo mira (pendientes.md 374.6). Antes asignaba el usuario 1 y guardaba.
$administratorID = $mapper->administrator instanceof UsersModel ? $mapper->administrator->id : (is_int($mapper->administrator) ? $mapper->administrator : null);
$adminUser = $administratorID !== null ? new UserDataPackage($administratorID) : null;
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
        <div class="approval-time"><?= $approvalMapper->getTimeTag(); ?></div>

        <form method="POST" action="<?=$action;?>" class="ui form initial system-approval datasheet" data-content-form>

            <input type="hidden" name="id" value="<?=$approvalMapper->id;?>">
            <button type="submit" hidden save></button>

            <div class="ui header base-title size3"><?=__($langGroup, 'Nombre de la organización');?></div>
            <div class="ui big text base-text mark2"><?=$escape($mapper->currentLangData('name'));?></div>

            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="ui tiny header base-title"><?=__($langGroup, 'Ubicación');?></div>
            <?php $country = $mapper->country instanceof CountryMapper ? __(LocationsLang::LANG_GROUP_NAMES, $mapper->country->name) : ''; ?>
            <?php $city = $mapper->city instanceof CityMapper ? __(LocationsLang::LANG_GROUP_NAMES, $mapper->city->name) : ''; ?>
            <?php $text = implode(', ', [$country, $city]); ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?=$escape($text);?></div>

            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="ui tiny header base-title"><?=__($langGroup, 'Latitud');?></div>
            <?php $text = $mapper->latitude !== null ? (string) $mapper->latitude : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?=$escape($text);?></div>

            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="ui tiny header base-title"><?=__($langGroup, 'Longitud');?></div>
            <?php $text = $mapper->longitude !== null ? (string) $mapper->longitude : ''; ?>
            <?php $text = is_string($text) ? $text : ''; ?>
            <?php $text = mb_strlen($text) > 0 ? $text : '-'; ?>
            <div class="base-text"><?=$escape($text);?></div>

            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="ui tiny header base-title"><?=__($langGroup, 'Instituciones a las que pertenece');?></div>
            <div class="base-text"><?=!empty($affiliatedInstitutions) ? implode(', ', array_map($escape, $affiliatedInstitutions)) : '-';?></div>

            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="ui header base-title size3"><?=__($langGroup, 'Persona encargada');?></div>
            <div class="ui hidden divider base-horizontal-space"></div>
            <?php if ($adminUser === null): ?>
            <div class="base-text"><?=__($langGroup, 'Sin encargado');?></div>
            <?php else: ?>
            <div class="form-attachments-regular">
                <div data-trigger-open-link="<?=$escape($adminUser->getAvatarURL());?>" class="attach-placeholder tall">
                    <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                    <div class="ui top right attached basic green label">
                        <i class="paperclip icon"></i>
                    </div>
                    <label for="<?=$uniqueIdentifier;?>">
                        <div class="image fullsize">
                            <img src="<?=$escape($adminUser->getAvatarURL());?>">
                        </div>
                        <div class="text">
                            <div class="header">
                                <div class="title"><?=$escape($adminUser->getMapper()->getFullName());?></div>
                            </div>
                        </div>
                    </label>
                    <input type="file" accept="image/*" id="<?=$uniqueIdentifier;?>">
                </div>
            </div>
            <?php endif; ?>

            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="container-standard-form">
                <div class="ui header base-title size3"><?=__($langGroup, 'Imágenes');?></div>
                <div class="ui hidden divider base-horizontal-space"></div>
                <div class="form-attachments-regular">
                    <div data-trigger-open-link="<?=$escape($mapper->getLogoURL());?>" class="attach-placeholder tall">
                        <?php $uniqueIdentifier = "attach-id-" . uniqid(); ?>
                        <div class="ui top right attached basic green label">
                            <i class="paperclip icon"></i>
                        </div>
                        <label for="<?=$uniqueIdentifier;?>">
                            <div class="image fullsize">
                                <img src="<?=$escape($mapper->getLogoURL());?>">
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

            <div class="ui hidden divider base-horizontal-space"></div>


            <div class="ui hidden divider base-horizontal-space"></div>

            <div class="container-standard-form mw-800">
                <div class="field">
                    <label><?= __($langGroup, 'Motivo'); ?></label>
                    <textarea name="reason"></textarea>
                </div>
            </div>

            <?php
                $this->helpController->_render('panel/built-in/content/form-action-bar.php', [
                    'saveText' => __($langGroup, 'Aprobar'),
                    'saveAttributes' => ['approve-trigger' => ''],
                    'secondaryActions' => [
                        ['text' => __($langGroup, 'Rechazar'), 'type' => 'button', 'icon' => 'times', 'danger' => true, 'attributes' => ['reject-trigger' => '']],
                    ],
                ]);
            ?>

        </form>

    </div>

</section>
