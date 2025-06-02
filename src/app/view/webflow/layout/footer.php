<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Controller\PublicAreaController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Menu\MenuItem;
$menusItems = [
    new MenuGroup([
        'name' => 'Inicio',
        'visible' => PublicAreaController::allowedRoute('index'),
        'asLink' => true,
        'href' => PublicAreaController::routeName('index'),
    ]),
];
?>
<?php if (!isset($noFooterSection) || $noFooterSection === false): ?>
<!-- CODE -->
<?php endif; ?>
<?php load_js([
        'base_url' => "",
        'custom_url' => "",
        'attr' => [
            'test-attr' => 'yes',
        ],
        'attrApplyTo' => [
            'test-attr' => [
                '.*configurations\.js$',
            ],
        ],
])?>
</body>

</html>