<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 * @var string $actionURL
 * @var string $deleteURL
 * @var array<int,array{field:string,name:string,image:string}> $loginCards SettingsController::loginBackgroundCards().
 */

$imagesCount = count(array_filter($loginCards, fn(array $card) => $card['image'] !== ''));
$onlyOneText = __($langGroup, 'Debe quedar al menos un fondo.');
$backgroundCard = function (array $card) use ($helpController, $actionURL, $langGroup): void {
    $helpController->_render('panel/built-in/content/image-card.php', $card + [
        'hint' => __($langGroup, 'Preferiblemente JPG o WEBP · Se guarda como JPG'),
        'accept' => 'image/*',
        'action' => $actionURL,
        'format' => 'image/jpeg',
        'quality' => 0.8,
        'fillColor' => 'white',
    ]);
};
?>
<section class="module-view-container backgrounds-view">

    <?php
        //Cada imagen se guarda al aplicar el editor: aquí no hay barra de guardar.
        $helpController->_render('panel/built-in/content/page-header.php', [
            'title' => $title,
            'description' => $description,
        ]);
    ?>

    <section class="ui segment" aria-labelledby="backgrounds-login-title">
        <?php
            $helpController->_render('panel/built-in/content/section-header.php', [
                'id' => 'backgrounds-login-title',
                'title' => __($langGroup, 'Inicio de sesión'),
                'description' => __($langGroup, 'Recomendado 1000 × 1080 px.'),
            ]);
        ?>
        <?php
            //Imágenes primero y vacías detrás (loginBackgroundCards()); backgrounds.js recarga tras subir y reordena al eliminar.
        ?>
        <div class="ui two stackable horizontal cards" data-login-backgrounds data-delete-url="<?= escape_html($deleteURL); ?>" data-title-template="<?= escape_html(__($langGroup, 'Fondo ${n}')); ?>" data-edit-template="<?= escape_html(__(\PiecesPHP\AdminPanel\Controllers\AdminPanelController::ADMIN_LANG_GROUP, 'Editar imagen: ${title}')); ?>" data-text-delete="<?= escape_html(__($langGroup, 'Eliminar')); ?>" data-text-only-one="<?= escape_html($onlyOneText); ?>" data-text-confirm="<?= escape_html(__($langGroup, '¿Eliminar este fondo? La imagen deja de mostrarse en el inicio de sesión.')); ?>" data-text-deleted="<?= escape_html(__($langGroup, 'Fondo eliminado')); ?>" data-text-deleted-detail="<?= escape_html(__($langGroup, 'La imagen ya no se muestra en el inicio de sesión.')); ?>" data-text-error="<?= escape_html(__($langGroup, 'No se pudo eliminar el fondo')); ?>">
            <?php foreach ($loginCards as $index => $loginCard): ?>
            <?php
                $backgroundNumber = $index + 1;
                ($backgroundCard)([
                    'id' => "background-{$backgroundNumber}-card",
                    'title' => strReplaceTemplate(__($langGroup, 'Fondo ${n}'), ['${n}' => $backgroundNumber]),
                    'description' => __($langGroup, 'Se muestra al azar en el panel izquierdo del inicio de sesión.'),
                    'image' => $loginCard['image'],
                    'field' => $loginCard['field'],
                    'width' => 1000,
                    'height' => 1080,
                    'deleteValue' => $loginCard['image'],
                    'deleteDisabledReason' => $imagesCount <= 1 ? $onlyOneText : '',
                ]);
            ?>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="ui segment" aria-labelledby="backgrounds-other-title">
        <?php
            $helpController->_render('panel/built-in/content/section-header.php', [
                'id' => 'backgrounds-other-title',
                'title' => __($langGroup, 'Otros fondos'),
            ]);
        ?>
        <div class="ui two stackable horizontal cards">
            <?php
                ($backgroundCard)([
                    'id' => 'background-problems-card',
                    'title' => __($langGroup, 'Fondo problemas de inicio'),
                    'description' => __($langGroup, 'Se muestra en la página de ayuda para iniciar sesión.'),
                    'image' => (string) get_config('backgoundProblems'),
                    'field' => 'problems-background',
                    'width' => 1920,
                    'height' => 1080,
                ]);
            ?>
        </div>
    </section>

    <?php $helpController->_render('panel/built-in/content/image-editor.php'); ?>

</section>
