<?php

/**
 * MailCatalog.php
 */

namespace PiecesPHP\MailCatalog;

/**
 * MailCatalog - El registro de los correos que el sitio envía (H189). Cada módulo registra los suyos dentro de su
 * interruptor; un proyecto registra los suyos igual, con register(), sin tocar el núcleo. La pantalla «Correos del
 * sitio» lo recorre.
 *
 * @package     PiecesPHP\MailCatalog
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class MailCatalog
{

    /** Lo de la página que pintar un correo puede cambiar: los recursos, el título (BaseController::__construct()), la
     * configuración del navegador y el sello de los recursos (las vistas de correo lo apagan y lo vuelven a encender). */
    const PAGE_STATE_CONFIG = ['global_assets', 'global_requireds_assets', 'as_modules_assets', 'imported_assets', 'custom_assets', 'lock_assets', 'title', 'front_configurations', 'cache_stamp_render_files'];

    /**
     * @var array<string,MailDefinition> Por clave, en el orden de registro
     */
    private static $definitions = [];

    /**
     * @var array<string,string> Por clave, el módulo que lo envía; '' es el núcleo
     */
    private static $modules = [];

    /**
     * Registra un correo DESDE el módulo que lo envía y dentro de su interruptor: un módulo apagado no registra los suyos
     * (#2446). Una clave repetida sustituye a la anterior: así un proyecto cambia la definición de uno del framework.
     *
     * @param MailDefinition $definition
     * @param string $module El nombre del módulo en español (se traduce al pintarse, con el grupo mail-catalog); vacío para
     *                       los del núcleo
     * @return void
     */
    public static function register(MailDefinition $definition, string $module = ''): void
    {
        self::$definitions[$definition->key()] = $definition;
        self::$modules[$definition->key()] = $module;
    }

    /**
     * Quita un correo del catálogo (las pruebas). Sin efecto si no estaba.
     *
     * @param string $key
     * @return void
     */
    public static function unregister(string $key): void
    {
        unset(self::$definitions[$key], self::$modules[$key]);
    }

    /**
     * @return array<string,MailDefinition>
     */
    public static function all(): array
    {
        return self::$definitions;
    }

    /**
     * Los correos por módulo, el núcleo primero (clave '') y después en el orden de registro.
     *
     * @return array<string,array<string,MailDefinition>>
     */
    public static function byModule(): array
    {
        $groups = ['' => []];
        foreach (self::$definitions as $key => $definition) {
            $groups[self::$modules[$key] ?? ''][$key] = $definition;
        }
        return array_filter($groups, fn(array $group): bool => $group !== []);
    }

    /**
     * @param string $key
     * @return MailDefinition|null
     */
    public static function get(string $key): ?MailDefinition
    {
        return self::$definitions[$key] ?? null;
    }

    /**
     * Asunto y cuerpo con los datos de muestra. Si la definición revienta, el error en vez del correo: la pantalla no cae.
     *
     * @param MailDefinition $definition
     * @return array{subject:string,body:string,error:string|null}
     */
    public static function preview(MailDefinition $definition): array
    {
        //Las vistas de correo crean su propio BaseController, que vacía los recursos y el título de la página que las pinta
        //(H89); lock_assets no basta, porque cada HelperController lo deja en false al construirse. Se guardan y se reponen.
        $saved = [];
        foreach (self::PAGE_STATE_CONFIG as $name) {
            $saved[$name] = get_config($name);
        }
        try {
            $data = $definition->sampleData();
            return ['subject' => $definition->subject($data), 'body' => $definition->render($data), 'error' => null];
        } catch (\Throwable $e) {
            return ['subject' => '', 'body' => '', 'error' => log_exception($e)];
        } finally {
            foreach ($saved as $name => $value) {
                set_config($name, $value);
            }
        }
    }
}
