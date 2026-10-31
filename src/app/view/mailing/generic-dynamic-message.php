<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
/**
 * Un correo con un título y una lista de «etiqueta: valor» (un formulario de contacto, un aviso a quien administra), dentro
 * de la plantilla base de los correos. Todo se escapa aquí: quien llama pasa el texto tal cual, aunque lo haya escrito un
 * usuario. Los saltos de línea de un valor se conservan.
 *
 * @var string $title Sin título no se pinta el <h1>.
 * @var array<string,scalar> $message Etiqueta => valor.
 */
use PiecesPHP\Core\BaseController;

$escape = fn($value): string => htmlspecialchars(is_scalar($value) ? (string) $value : '', ENT_QUOTES, 'UTF-8');
$title = isset($title) && is_string($title) ? $title : '';
$message = isset($message) && is_array($message) ? $message : [];
$baseController = new BaseController();

$text = $title !== '' ? '<h1>' . $escape($title) . '</h1>' : '';
$text .= '<p>';
foreach ($message as $label => $value) {
    $text .= '<strong>' . $escape($label) . ':</strong> ' . nl2br($escape($value)) . '<br>';
}
$text .= '</p>';

//Sin la marca de versión en las direcciones: el correo se lee fuera del sitio.
set_config('cache_stamp_render_files', false);
$baseController->render('mailing/template_base', [
    'text' => $text,
    'langGroup' => MAILING_GENERAL_LANG_GROUP,
]);
set_config('cache_stamp_render_files', true);
