<?php

/**
 * MailDefinition.php
 */

namespace PiecesPHP\MailCatalog;

/**
 * MailDefinition - Un correo que el sitio envía: qué es, cuándo sale, a quién, su asunto y su cuerpo. El envío real y la
 * vista previa del catálogo construyen el asunto y el cuerpo con ESTA clase, con los mismos datos: no hay otra copia.
 * Un proyecto declara los suyos con una subclase y MailCatalog::register().
 *
 * @package     PiecesPHP\MailCatalog
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
abstract class MailDefinition
{

    /**
     * Única en el catálogo, en kebab-case.
     *
     * @return string
     */
    abstract public function key(): string;

    /**
     * El nombre del correo, ya traducido.
     *
     * @return string
     */
    abstract public function name(): string;

    /**
     * Cuándo sale, ya traducido.
     *
     * @return string
     */
    abstract public function trigger(): string;

    /**
     * A quién va, ya traducido.
     *
     * @return string
     */
    abstract public function recipients(): string;

    /**
     * El asunto con esos datos.
     *
     * @param array<string,mixed> $data
     * @return string
     * @throws \Throwable Si una traducción o un dato fallan: el envío lo trata como cualquier fallo antes de enviar
     */
    abstract public function subject(array $data): string;

    /**
     * Datos inventados para la vista previa y la prueba: nunca de la base.
     *
     * @return array<string,mixed>
     */
    abstract public function sampleData(): array;

    /**
     * El cuerpo HTML con esos datos: el mismo camino que el envío real.
     *
     * @param array<string,mixed> $data
     * @return string
     * @throws \Throwable Si la vista falla: el envío lo trata como cualquier fallo antes de enviar
     */
    abstract public function render(array $data): string;

    /**
     * Un dato de texto, o el de por defecto si no viene o no es texto.
     *
     * @param array<string,mixed> $data
     * @param string $name
     * @param string $default
     * @return string
     */
    protected static function text(array $data, string $name, string $default = ''): string
    {
        $value = $data[$name] ?? null;
        return is_string($value) || is_int($value) || is_float($value) ? (string) $value : $default;
    }

    /**
     * Quién pinta la vista: el controlador que pasa el envío real en `renderer` (el mismo de antes, con sus variables
     * globales), o uno nuevo para la vista previa.
     *
     * @param array<string,mixed> $data
     * @param callable():\PiecesPHP\Core\BaseController $make
     * @return \PiecesPHP\Core\BaseController
     */
    protected static function renderer(array $data, callable $make): \PiecesPHP\Core\BaseController
    {
        $renderer = $data['renderer'] ?? null;
        //Un controlador nuevo vacía los recursos de la página (H89): la vista previa lo bloquea (MailCatalog::preview()).
        return $renderer instanceof \PiecesPHP\Core\BaseController ? $renderer : $make();
    }

    /**
     * Un pintor ligero de vistas para la vista previa: un HelperController (que no vacía los recursos de la página)
     * apuntado a esa carpeta, sin construir el controlador del envío, cuyo constructor puede consumir los avisos de la
     * sesión o tocar el usuario de la petición. La salida es la misma: las vistas de correo solo usan sus variables.
     *
     * @param string|null $viewDir La carpeta de vistas; null para las globales (src/app/view)
     * @return \PiecesPHP\Core\BaseController
     */
    protected static function viewRenderer(?string $viewDir = null): \PiecesPHP\Core\BaseController
    {
        $renderer = new \PiecesPHP\Tokens\Controllers\HelperController(null, []);
        return $viewDir !== null ? $renderer->setInstanceViewDir($viewDir) : $renderer;
    }

    /**
     * La carpeta de vistas del sistema de usuarios, donde viven sus correos.
     *
     * @return string
     */
    protected static function userSystemViews(): string
    {
        return dirname(__DIR__) . '/UserSystem/Views/';
    }

    /**
     * El asunto que daban set_title() y get_title(true) en los envíos de aprobaciones, sin tocar el título de la petición:
     * «título de la app - texto», o el texto solo si coinciden. La misma comparación laxa que get_title().
     *
     * @param string $text No vacío
     * @return string
     */
    protected static function titledSubject(string $text): string
    {
        $titleApp = get_config('title_app');
        return $titleApp != $text ? (is_scalar($titleApp) ? (string) $titleApp : '') . ' - ' . $text : $text;
    }
}
