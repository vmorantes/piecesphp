<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

/**
 * @var string $langGroup
 * @var string $title
 * @var string $description
 * @var array<string|int,string|array{url:string}> $breadcrumbs
 * @var \PiecesPHP\AdminPanel\Controllers\HelperController $helpController El que pinta las piezas del panel.
 * @var string $actionURL
 */
$backgrounds = get_config('backgrounds');
$backgrounds = is_array($backgrounds) ? $backgrounds : (is_object($backgrounds) ? (array) $backgrounds : []);
//Por su nombre y nunca por su posición: el servidor guarda la tarjeta N en `bg<N>`, y la lista puede no empezar en bg1.
$backgroundOf = function (int $number) use ($backgrounds): string {
    foreach ($backgrounds as $background) {
        if (is_string($background) && str_contains($background, "bg{$number}")) {
            return $background;
        }
    }
    return '';
};
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
        <div class="ui two stackable horizontal cards">
            <?php for ($backgroundNumber = 1; $backgroundNumber <= 5; $backgroundNumber++): ?>
            <?php
                ($backgroundCard)([
                    'id' => "background-{$backgroundNumber}-card",
                    'title' => strReplaceTemplate(__($langGroup, 'Fondo ${n}'), ['${n}' => $backgroundNumber]),
                    'description' => __($langGroup, 'Se muestra al azar en el panel izquierdo del inicio de sesión.'),
                    'image' => ($backgroundOf)($backgroundNumber),
                    'field' => "background-{$backgroundNumber}",
                    'width' => 1000,
                    'height' => 1080,
                ]);
            ?>
            <?php endfor; ?>
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
