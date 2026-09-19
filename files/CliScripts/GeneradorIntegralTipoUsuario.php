<?php
/**
 * GeneradorIntegralTipoUsuario.php
 *
 * Herramienta de automatización para la creación de nuevos tipos de usuario.
 * Reclica vistas, inyecta constantes en el modelo, configura roles y 
 * registra los renderizadores en el controlador de forma quirúrgica.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes
 * @copyright   2026
 */

include __DIR__ . '/../../src/app/core/psr4/PiecesPHP/Cli.php';
use PiecesPHP\Cli;

// Inicialización de la consola
$cli = new Cli($argv, [
    'addLines' => false,
    'skipArgs' => 1,
]);

// Definición de rutas base del proyecto
$basePath = realpath(__DIR__ . '/../../');
$files = [
    'model'      => "$basePath/src/app/model/UsersModel.php",
    'controller' => "$basePath/src/app/controller/UsersController.php",
    'roles'      => "$basePath/src/app/config/roles.php",
    'js_forms'   => "$basePath/src/statics/admin-area/js/users-forms.js",
    'views_dir'  => "$basePath/src/app/view/usuarios/form-by-type",
];

/**
 * Helper para capturar datos. 
 * El espacio en readline evita que el prompt se borre visualmente en algunas terminales.
 */
$getOrAsk = function($argName, $label, $default = '') use ($cli) {
    $value = $cli->getArgumentValue($argName);
    if (empty($value)) {
        $promptText = $default ? "$label [$default]: " : "$label: ";
        Cli::systemOutFormatted($promptText, ['bold' => true, 'newLine' => false]);
        $value = readline(" "); 
        $value = trim($value);
        return empty($value) ? $default : $value;
    }
    return $value;
};

// --- Interfaz de inicio ---
Cli::systemOutFormatted("┌────────────────────────────────────────────────────────┐", ['color' => 'light-cyan']);
Cli::systemOutFormatted("│         PIECES-PHP: ASISTENTE DE NUEVO TIPO            │", ['color' => 'light-cyan', 'bold' => true]);
Cli::systemOutFormatted("└────────────────────────────────────────────────────────┘", ['color' => 'light-cyan']);

// Captura de configuración
$srcSlug   = $getOrAsk('source', '📂 Slug base (origen)', 'comunicaciones');
$newSlug   = $getOrAsk('target', '📂 Nuevo Slug (ej: auditor)', 'auditor');
$newLabel  = $getOrAsk('label', '🏷️  Etiqueta visual', 'Auditor');
$newID     = (int)$getOrAsk('id', '🔢 ID numérico único', '15');
$priority  = (int)$getOrAsk('priority', '⚖️  Prioridad de autoridad', '2');

$srcConst  = "TYPE_USER_" . strtoupper(str_replace('-', '_', $srcSlug));
$newConst  = "TYPE_USER_" . strtoupper(str_replace('-', '_', $newSlug));

// 1. REPLICACIÓN DE VISTAS (Create, Edit, Profile)
Cli::systemOutFormatted("\n[1/5] Generando archivos de vista...", ['color' => 'green', 'bold' => true]);
foreach (['create', 'edit', 'profile'] as $dir) {
    $srcPath = "{$files['views_dir']}/$dir/$srcSlug";
    $dstPath = "{$files['views_dir']}/$dir/$newSlug";
    
    if (is_dir($srcPath)) {
        if (!is_dir($dstPath)) {
            mkdir($dstPath, 0755, true);
        }
        $content = file_get_contents("$srcPath/form.php");
        // Reemplaza clases CSS y constantes de tipo en el HTML/PHP de la vista
        $content = str_replace([$srcSlug, $srcConst], [$newSlug, $newConst], $content);
        file_put_contents("$dstPath/form.php", $content);
        Cli::systemOutFormatted("  ✔ Estructura creada en: $dir/$newSlug", ['color' => 'light-gray']);
    }
}

// 2. ACTUALIZACIÓN DEL MODELO (UsersModel.php)
Cli::systemOutFormatted("[2/5] Actualizando constantes en UsersModel...", ['color' => 'green', 'bold' => true]);
$model = file_get_contents($files['model']);
if (strpos($model, $newConst) === false) {
    // 2a. Inyección de constante de ID al final del bloque (después de la última const TYPE_USER_*)
    $model = preg_replace(
        '/(const TYPE_USER_\w+\s*=\s*\d+;\s*\r?\n)(\s*\r?\n\s*\/\*\*)/s',
        "$1    const $newConst = $newID;\n$2",
        $model,
        1
    );
    // 2b. Inyección en lista de auto-aprobación (al final del array, antes de ];)
    $model = preg_replace(
        '/(const ARE_AUTO_APPROVAL\s*=\s*\[.*?)(    \];)/s',
        "$1        self::$newConst,\n$2",
        $model,
        1
    );
    // 2c. Inyección en etiquetas de tipo TYPES_USERS (antes del comentario del último tipo comentado)
    $model = preg_replace(
        '/(const TYPES_USERS\s*=\s*\[.*?)(        \/\/self::TYPE_USER_COMUNICACIONES)/s',
        "$1        self::$newConst => '$newLabel',\n$2",
        $model,
        1
    );
    // 2d. Inyección de prioridad al final del array TYPES_USER_PRIORITY (antes del cierre ];)
    $model = preg_replace(
        '/(const TYPES_USER_PRIORITY\s*=\s*\[.*?)(    \];)/s',
        "$1        self::$newConst => $priority,\n$2",
        $model,
        1
    );
    // 2e. Inyección en TYPES_USER_SHOULD_HAVE_PROFILE (al final, antes del cierre ];)
    $model = preg_replace(
        '/(const TYPES_USER_SHOULD_HAVE_PROFILE\s*=\s*\[.*?)(    \];)/s',
        "$1        self::$newConst,\n$2",
        $model,
        1
    );
    
    file_put_contents($files['model'], $model);
    Cli::systemOutFormatted("  ✔ UsersModel.php actualizado correctamente.", ['color' => 'light-gray']);
}

// 3. CONFIGURACIÓN DE PERMISOS (roles.php)
Cli::systemOutFormatted("[3/5] Configurando acceso en roles.php...", ['color' => 'green', 'bold' => true]);
$roles = file_get_contents($files['roles']);
if (strpos($roles, "UsersModel::$newConst") === false) {
    $roleBlock = <<<ROLE
    [
        'code' => UsersModel::$newConst,
        'name' => UsersModel::TYPES_USERS[UsersModel::$newConst] ?? null,
        'all' => false,
        'allowed_routes' => \$permisosGenerales,
    ],
ROLE;
    
    // Inserción al final del array $config['roles']['types'], antes del cierre ];
    // Se usa .* (greedy) para avanzar hasta el último ]; del bloque, no el primero
    $roles = preg_replace(
        '/(\$config\[\'roles\'\]\[\'types\'\]\s*=\s*\[.*)(^\];)/ms',
        "$1$roleBlock\n$2",
        $roles,
        1
    );
    file_put_contents($files['roles'], $roles);
    Cli::systemOutFormatted("  ✔ Rol y permisos base registrados.", ['color' => 'light-gray']);
}

// 4. REGISTRO DE RENDERIZADORES (UsersController.php)
Cli::systemOutFormatted("[4/5] Inyectando mapeos en UsersController...", ['color' => 'green', 'bold' => true]);
$ctrl = file_get_contents($files['controller']);
$methods = [
    'formCreateByType' => 'create',
    'formEditByType'   => 'edit',
    'formProfileByType' => 'profile'
];

foreach ($methods as $methodName => $folder) {
    // Localiza el array $formsByType específico de cada función del controlador
    $pattern = "/function $methodName.*?\{.*?((\\\$formsByType\s*=\s*\[)(.*?)(\];))/s";
    
    if (preg_match($pattern, $ctrl, $matches)) {
        $fullBlock = $matches[1]; 
        
        if (strpos($fullBlock, "UsersModel::$newConst") === false) {
            // Detectar la indentación del cierre ]; para derivar la indentación de entradas
            if (preg_match('/^( +)\];/m', $fullBlock, $closeIndentMatch)) {
                $closeIndent = $closeIndentMatch[1];
                $indent = $closeIndent . '    ';
            } else {
                $indent = '                ';
                $closeIndent = '            ';
            }
            $innerIndent = $indent . '    ';

            $newEntry = "{$indent}UsersModel::$newConst => [\n{$innerIndent}'view' => 'usuarios/form-by-type/$folder/$newSlug/form',\n{$innerIndent}'data' => array_merge(\$data_form, []),\n{$indent}],";
            
            // Buscar la línea completa de cierre (indentación + ];) y poner antes
            $closePattern = '/(\r?\n)(' . preg_quote($closeIndent, '/') . '\];)/';
            $updatedBlock = preg_replace($closePattern, "$1$newEntry$1$2", $fullBlock, 1);
            
            if ($updatedBlock !== $fullBlock) {
                $ctrl = str_replace($fullBlock, $updatedBlock, $ctrl);
            }
        }
    }
}
file_put_contents($files['controller'], $ctrl);
Cli::systemOutFormatted("  ✔ Rutas de renderizado inyectadas por contexto.", ['color' => 'light-gray']);

// 5. REGISTRO EN JAVASCRIPT (users-forms.js)
Cli::systemOutFormatted("[5/5] Vinculando selectores en users-forms.js...", ['color' => 'green', 'bold' => true]);
$js = file_get_contents($files['js_forms']);

// Conversión a camelCase para las llaves del objeto JS
$jsKey = str_replace('-', '', lcfirst(ucwords($newSlug, '-')));

foreach (['create', 'edit', 'profile'] as $t) {
    // Buscar el cierre del sub-objeto correspondiente e insertar antes de él.
    // Patrón: capturar la última propiedad con coma y el cierre },
    $pattern = "/($t:\s*\{[^}]*?)(,?\s*\r?\n\t\t},)/s";
    if (preg_match($pattern, $js, $jsMatches, PREG_OFFSET_CAPTURE)) {
        $blockContent = $jsMatches[1][0];
        $closingPart = $jsMatches[2][0];
        $fullMatchOffset = $jsMatches[0][1];
        $fullMatch = $jsMatches[0][0];
        
        // Asegurarse de que la última línea tiene coma antes de añadir
        $newLine = "\n\t\t\t$jsKey: 'form.users.$t.$newSlug',";
        // Reemplazar: mantener el contenido del bloque, añadir nueva línea, y cerrar
        $replacement = $blockContent . ",{$newLine}\n\t\t},";
        $js = str_replace($fullMatch, $replacement, $js);
    }
}
file_put_contents($files['js_forms'], $js);
Cli::systemOutFormatted("  ✔ Selectores jQuery registrados.", ['color' => 'light-gray']);

// --- Finalización ---
Cli::systemOutFormatted("\n✨ Integración completada para el tipo: $newLabel", ['color' => 'light-cyan', 'bold' => true]);
Cli::systemOutFormatted("Recuerda verificar que la prioridad $priority sea la adecuada para este rol.\n", ['color' => 'white']);
