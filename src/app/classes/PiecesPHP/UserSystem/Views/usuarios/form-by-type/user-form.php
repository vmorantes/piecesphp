<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * El formulario de un usuario: lo común de los 18 de form-by-type (`<mode>/<tipo>/form.php`), que solo dicen su modo y su
 * tipo. users-forms.js los toma por las clases del `form`; el servidor, por sus `name`.
 *
 * @var string $formMode 'create', 'edit' o 'profile'.
 * @var string $formType La carpeta del tipo: 'root', 'admin-general', 'admin-organization', 'general', 'institucional' o 'comunicaciones'.
 * @var array<string,int> $status_options Sin 'profile'.
 * @var \stdClass $edit_user Sin 'create'.
 */
$langGroup = UsersController::LANG_GROUP;
$typeConstants = [
    'root' => UsersModel::TYPE_USER_ROOT,
    'admin-general' => UsersModel::TYPE_USER_ADMIN_GRAL,
    'admin-organization' => UsersModel::TYPE_USER_ADMIN_ORG,
    'general' => UsersModel::TYPE_USER_GENERAL,
    'institucional' => UsersModel::TYPE_USER_INSTITUCIONAL,
    'comunicaciones' => UsersModel::TYPE_USER_COMUNICACIONES,
];
$isCreate = $formMode === 'create';
$isProfile = $formMode === 'profile';
//Principal y administrador general no pertenecen a una organización.
$hasOrganization = !in_array($formType, ['root', 'admin-general'], true);
$canPickOrganization = $hasOrganization && !$isProfile && OrganizationMapper::canModifyAnyOrganization(getLoggedFrameworkUserOrFail()->type);
$organizations = $canPickOrganization ? OrganizationMapper::all(false) : [];
$userValue = fn(string $property): string => $isCreate ? '' : escape_html($edit_user->$property);
$idOf = fn(string $inputName): string => "user-{$formMode}-{$inputName}";
$textField = function (string $inputName, string $property, string $label, bool $required, string $type = 'text') use ($idOf, $userValue): void {
    ?>
    <div class="<?= $required ? 'required ' : ''; ?>field">
        <label for="<?= $idOf($inputName); ?>"><?= $label; ?></label>
        <input <?= $required ? 'required ' : ''; ?>type="<?= $type; ?>" id="<?= $idOf($inputName); ?>" name="<?= $inputName; ?>" value="<?= ($userValue)($property); ?>">
    </div>
    <?php
        };
    ?>
<form class="ui form initial segment users <?= $formMode; ?> <?= $formType; ?>" action="<?= UsersController::routeName($isCreate ? 'register-request' : 'edit-request'); ?>" data-content-form>

    <?php if ($isCreate): ?>
    <input type="hidden" name="type" value="<?= $typeConstants[$formType]; ?>">
    <?php else: ?>
    <input type="hidden" name='id' value="<?= $edit_user->id; ?>">
    <?php endif; ?>
    <?php if ($isProfile): ?>
    <input type="hidden" name='status' value="<?= $edit_user->status; ?>">
    <input type="hidden" name='is_profile' value="yes">
    <?php if ($hasOrganization): ?>
    <input type="hidden" name='organization' value="<?= $edit_user->organization; ?>">
    <?php endif; ?>
    <?php elseif ($hasOrganization && !$canPickOrganization): ?>
    <input type="hidden" name="organization" value="<?= getLoggedFrameworkUserOrFail()->organization; ?>">
    <?php endif; ?>

    <?php if ($canPickOrganization): ?>
    <section aria-labelledby="user-organization-title">
        <?php if (ORGANIZATIONS_MODULE): ?>
        <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['id' => 'user-organization-title', 'title' => __($langGroup, 'Organización')]); ?>
        <?php endif; ?>
        <div class="fields">
            <div class="eight wide required field">
                <?php if (ORGANIZATIONS_MODULE): ?>
                <label><?= __($langGroup, 'Organización base'); ?></label>
                <?php endif; ?>
                <select class="ui fluid dropdown" required name="organization" <?= ORGANIZATIONS_MODULE ? '' : 'hidden'; ?>>
                    <option value="" disabled><?= __($langGroup, 'Selecciona una organización'); ?></option>
                    <?php foreach ($organizations as $organization): ?>
                    <?php $selected = $isCreate ? $organization->id == OrganizationMapper::INITIAL_ID_GLOBAL : $organization->id == $edit_user->organization; ?>
                    <option value="<?= $organization->id; ?>"<?= $selected ? ' selected' : ''; ?>><?= escape_html($organization->name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>
    <div class="ui section divider"></div>
    <?php endif; ?>

    <section aria-labelledby="user-personal-title">
        <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['id' => 'user-personal-title', 'title' => __($langGroup, 'Datos personales')]); ?>
        <div class="two fields">
            <?php ($textField)('firstname', 'firstname', __($langGroup, 'firstname'), true); ?>
            <?php ($textField)('secondname', 'secondname', __($langGroup, 'secondname'), false); ?>
        </div>
        <div class="two fields">
            <?php ($textField)('first_lastname', 'firstLastname', __($langGroup, 'first-lastname'), true); ?>
            <?php ($textField)('second_lastname', 'secondLastname', __($langGroup, 'second-lastname'), false); ?>
        </div>
    </section>

    <div class="ui section divider"></div>

    <section aria-labelledby="user-access-title">
        <?php $this->helpController->_render('panel/built-in/content/section-header.php', ['id' => 'user-access-title', 'title' => __($langGroup, 'Acceso')]); ?>
        <div class="two fields">
            <?php ($textField)('username', 'username', __($langGroup, 'user'), true); ?>
            <?php ($textField)('email', 'email', __($langGroup, 'email-standard'), true, 'email'); ?>
        </div>
        <?php if ($isProfile): ?>
        <div class="two fields">
            <?php
                $this->helpController->_render('panel/built-in/content/password-field.php', [
                    'id' => $idOf('current-password'),
                    'inputName' => 'current-password',
                    'label' => __($langGroup, 'current-password'),
                    'fieldExtra' => '<div class="description">' . __($langGroup, 'Para cambiar tu usuario, tu correo o tu contraseña, escribe tu contraseña actual.') . '</div>',
                ]);
            ?>
        </div>
        <?php endif; ?>
        <div class="two fields">
            <?php
                $this->helpController->_render('panel/built-in/content/password-field.php', [
                    'id' => $idOf('password'),
                    'inputName' => 'password',
                    'label' => __($langGroup, $isCreate ? 'password' : 'password_restored'),
                    'autocomplete' => 'new-password',
                    //Al crear es obligatoria (el servidor ya la exige); al editar, vacía no la cambia.
                    'required' => $isCreate,
                ]);
                $this->helpController->_render('panel/built-in/content/password-field.php', [
                    'id' => $idOf('password2'),
                    'inputName' => 'password2',
                    'label' => __($langGroup, 'confirm-password'),
                    'autocomplete' => 'new-password',
                    'required' => $isCreate,
                ]);
            ?>
        </div>
        <?php if (!$isProfile): ?>
        <div class="fields">
            <div class="eight wide required field">
                <label><?= __($langGroup, 'status'); ?></label>
                <select class="ui fluid dropdown" required name="status">
                    <option value=""><?= __($langGroup, 'status'); ?></option>
                    <?php foreach ($status_options as $statusName => $statusValue): ?>
                    <option value="<?= $statusValue; ?>"<?= !$isCreate && $statusValue == $edit_user->status ? ' selected' : ''; ?>><?= $statusName; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php endif; ?>
    </section>

    <?php $this->helpController->_render('panel/built-in/content/form-action-bar.php', ['saveText' => __($langGroup, 'save')]); ?>

</form>
