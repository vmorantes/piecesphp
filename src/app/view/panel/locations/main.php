<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\Core\Roles;
$type_user = getLoggedFrameworkUser()->type;
$elements = [
    [
        'title' => __(LOCATIONS_LANG_GROUP, 'Países'),
        'description' => __(LOCATIONS_LANG_GROUP, 'Listado de los países'),
		'image'=> '',
        'icon' => '<i class="globe icon"></i>',
        'route_list' => 'locations-countries-list',
        'route_add' => 'locations-countries-forms-add',
        'has_list_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_list, $type_user);
        },
        'has_add_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_add, $type_user);
        },
    ],
    [
        'title' => __(LOCATIONS_LANG_GROUP, 'Departamentos'),
        'description' => __(LOCATIONS_LANG_GROUP, 'Listado de los departamentos'),
		'image'=> '',
        'icon' => '<i class="flag icon"></i>',
        'route_list' => 'locations-states-list',
        'route_add' => 'locations-states-forms-add',
        'has_list_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_list, $type_user);
        },
        'has_add_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_add, $type_user);
        },
    ],
    [
        'title' => __(LOCATIONS_LANG_GROUP, 'Ciudades'),
        'description' => __(LOCATIONS_LANG_GROUP, 'Listado de las ciudades'),
		'image'=> '',
        'icon' => '<i class="building outline icon"></i>',
        'route_list' => 'locations-cities-list',
        'route_add' => 'locations-cities-forms-add',
        'has_list_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_list, $type_user);
        },
        'has_add_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_add, $type_user);
        },
    ],
    [
        'title' => __(LOCATIONS_LANG_GROUP, 'Localidades'),
        'description' => __(LOCATIONS_LANG_GROUP, 'Listado de las localidades'),
		'image'=> '',
        'icon' => '<i class="map marker alternate icon"></i>',
        'route_list' => 'locations-points-list',
        'route_add' => 'locations-points-forms-add',
        'has_list_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_list, $type_user);
        },
        'has_add_permission' => function ($stdClass) use ($type_user) {
            return Roles::hasPermissions($stdClass->route_add, $type_user);
        },
    ],
];
/**
 * @var \stdClass[] $elements
 */
$elements = array_map(function ($e) {
    return (object) $e;
}, $elements);
$title = __(ADMIN_MENU_LANG_GROUP, 'Ubicaciones');
?>

<section class="module-view-container">

    <?php if(isset($breadcrumbs)): ?>
    <div class="breadcrumb">
        <?= $breadcrumbs ?>
    </div>
    <?php endif; ?>

    <div class="limiter-content">

        <div class="section-title">
            <?php if(isset($title) && is_string($title) && mb_strlen(trim($title)) > 0): ?>
            <div class="title"><?= $title; ?></div>
            <?php endif; ?>
            <?php if(isset($description) && is_string($description) && mb_strlen(trim($description)) > 0): ?>
            <div class="description"><?= $description; ?></div>
            <?php endif; ?>
        </div>

        <br>

        <div class="ui cards">

            <?php foreach ($elements as $element): ?>
            <?php if (($element->has_list_permission)($element) || ($element->has_add_permission)($element)): ?>
            <div class="card">
                <?php if (isset($element->icon) && mb_strlen($element->image) > 0): ?>
                <div class="image">
                    <img src="<?= $element->image; ?>">
                </div>
                <?php elseif(isset($element->icon) && mb_strlen($element->icon) > 0): ?>
                <div class="image icon">
                    <?= $element->icon; ?>
                </div>
                <?php endif;?>
                <div class="content">
                    <div class="header">
                        <?=$element->title;?>
                    </div>
                    <div class="meta">
                        <?= $element->description; ?>
                    </div>
                </div>
                <div class="extra content">
                    <div class="buttons">
                        <?php if (($element->has_list_permission)($element)): ?>
                        <a href="<?=get_route($element->route_list);?>" class="ui brand-color alt2 button"><?= __(LOCATIONS_LANG_GROUP, 'Ver'); ?></a>
                        <?php endif;?>
                        <?php if (($element->has_add_permission)($element)): ?>
                        <a href="<?=get_route($element->route_add);?>" class="ui brand-color button"><?= __(LOCATIONS_LANG_GROUP, 'Agregar'); ?></a>
                        <?php endif;?>
                    </div>
                </div>
            </div>
            <?php endif;?>
            <?php endforeach;?>

        </div>

    </div>

</section>
<style>
.ui.cards .card .extra.content .buttons {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    column-gap: 6px;
    row-gap: 6px;
}

.ui.cards .card .extra.content .buttons .ui.button {
    width: 128px;
    max-width: 100%;
    margin: 0px;
    flex-grow: 1;
}

.ui.cards .card>.image.icon {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
    height: 200px;
}

.ui.cards .card>.image.icon i.icon {
    margin: 0;
    padding: 0;
    font-size: 90px;
    color: var(--main-brand-color);
}
</style>
