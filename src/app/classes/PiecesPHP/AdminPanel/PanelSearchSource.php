<?php

/**
 * PanelSearchSource.php
 */

namespace PiecesPHP\AdminPanel;

use MySpace\Controllers\MySpaceController;
use PiecesPHP\AdminPanel\Controllers\AdminPanelController;
use PiecesPHP\Core\Menu\MenuGroup;
use PiecesPHP\Core\Menu\MenuGroupCollection;
use PiecesPHP\Core\Roles;
use PiecesPHP\Core\StringManipulate;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\UserSystem\Controllers\UsersController;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * PanelSearchSource - Lo que el buscador de la barra (Ctrl K) ofrece a un usuario: las pantallas que puede abrir y el
 * manual que ve. La usan la vista `panel/layout/topbar-search.php` y la herramienta MCP `panel_find`.
 *
 * Sale de las mismas fuentes que los menús, ya filtradas por permisos: el menú lateral, los grupos de Configuración, la
 * cuenta y el manual que ve. El menú lateral y los permisos por ruta se calculan con el usuario de la SESIÓN: con otro
 * usuario, lanza.
 *
 * @package     PiecesPHP\AdminPanel
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class PanelSearchSource
{
    /** Las palabras de relleno que find() no cuenta (las de menos de 3 letras ya no cuentan). */
    const STOP_WORDS = ['del', 'las', 'los', 'una', 'uno', 'para', 'con', 'por', 'que', 'sobre', 'the', 'and', 'for', 'with'];

    /**
     * Las entradas, en el orden del buscador: módulos, Configuración, la cuenta y el manual que ve (capítulos y secciones).
     *
     * @param UserDataPackage $user El usuario de la sesión
     * @return list<array{category:string,title:string,url:string}>
     * @throws \LogicException si no es el usuario de la sesión, o no hay sesión
     */
    public static function entries(UserDataPackage $user): array
    {
        self::assertSessionUser($user);
        $lang = AdminPanelController::ADMIN_LANG_GROUP;
        $entries = [];
        $add = function (string $category, string $title, string $url) use (&$entries): void {
            if ($url !== '' && $url !== '#') {
                $entries[] = ['category' => $category, 'title' => $title, 'url' => $url];
            }
        };
        $categoryModules = __($lang, 'Módulos');
        $categoryConfiguration = __($lang, 'Configuración');
        $categoryAccount = __($lang, 'Cuenta');
        foreach (get_sidebar_menu()->getItems() as $element) {
            if (!$element->isVisible()) {
                continue;
            }
            if ($element->asLink()) {
                ($add)($categoryModules, (string) $element->getName(), (string) $element->getHref());
            } else {
                foreach (array_filter($element->getItems(), fn($subElement) => $subElement->isVisible()) as $subElement) {
                    ($add)($categoryModules, "{$element->getName()} › {$subElement->getText()}", (string) $subElement->getHref());
                }
            }
        }
        foreach (SettingsController::panelGroups() as $group) {
            if ($group['visible']) {
                foreach ($group['items'] as $item) {
                    if ($item['visible']) {
                        ($add)($categoryConfiguration, "{$group['name']} › {$item['name']}", (string) $item['href']);
                    }
                }
            }
        }
        if (UsersController::allowedRoute('form-profile')) {
            ($add)($categoryAccount, __($lang, 'Editar Cuenta'), (string) UsersController::routeName('form-profile'));
        }
        if (MySpaceController::allowedRoute('user-security')) {
            ($add)($categoryAccount, __($lang, 'Seguridad'), (string) MySpaceController::routeName('user-security'));
        }
        foreach (self::accountMenuEnd((int) $user->type)->getItems() as $item) {
            if ($item->isVisible() && $item->asLink()) {
                ($add)($categoryAccount, (string) $item->getName(), (string) $item->getHref());
            }
        }
        //'manual' y no 'manual-page': la del capítulo pide slug y, sin él, allowedRoute() da false (los mismos roles).
        if (MySpaceController::allowedRoute('manual')) {
            $categoryManual = __($lang, 'Manual');
            foreach (MySpaceController::manualChapters() as $chapter) {
                $chapterUrl = (string) MySpaceController::routeName('manual-page', ['slug' => $chapter['slug']]);
                ($add)($categoryManual, "{$categoryManual} › {$chapter['titulo']}", $chapterUrl);
                foreach (MySpaceController::manualVisibleSections($chapter['secciones']) as $section) {
                    ($add)($categoryManual, "{$categoryManual} › {$chapter['titulo']} › {$section['titulo']}", "{$chapterUrl}#{$section['id']}");
                }
            }
        }
        return $entries;
    }

    /**
     * Las entradas que casan con MÁS palabras distintas de la consulta (en título o categoría, sin mayúsculas ni tildes),
     * si casan con alguna. Sin palabras útiles (todas cortas o de relleno), la consulta entera como subcadena.
     *
     * @param UserDataPackage $user
     * @param string $query
     * @param int $limit
     * @return list<array{category:string,title:string,url:string}>
     * @throws \LogicException si no es el usuario de la sesión, o no hay sesión
     */
    public static function find(UserDataPackage $user, string $query, int $limit): array
    {
        self::assertSessionUser($user);
        $needle = StringManipulate::friendlyURLString($query);
        //Una consulta que solo tiene puntuación se queda en nada: no casa con todo.
        if ($needle === '') {
            return [];
        }
        $entries = self::entries($user);
        $words = array_values(array_unique(array_filter(explode('-', $needle), fn(string $word): bool => mb_strlen($word) >= 3 && !in_array($word, self::STOP_WORDS, true))));
        if ($words === []) {
            $found = array_filter($entries, fn(array $entry): bool => str_contains(StringManipulate::friendlyURLString($entry['title']), $needle) || str_contains(StringManipulate::friendlyURLString($entry['category']), $needle));
            return array_slice(array_values($found), 0, max(0, $limit));
        }
        $scores = array_map(function (array $entry) use ($words): int {
            $haystack = StringManipulate::friendlyURLString($entry['title']) . '-' . StringManipulate::friendlyURLString($entry['category']);
            return count(array_filter($words, fn(string $word): bool => str_contains($haystack, $word)));
        }, $entries);
        $best = $scores === [] ? 0 : max($scores);
        if ($best < 1) {
            return [];
        }
        $found = array_filter($entries, fn(int $index): bool => $scores[$index] === $best, \ARRAY_FILTER_USE_KEY);
        return array_slice(array_values($found), 0, max(0, $limit));
    }

    /**
     * El menú, los grupos de Configuración y allowedRoute() leen el usuario de la sesión: pasar otro daría su lista.
     *
     * @param UserDataPackage $user
     * @return void
     * @throws \LogicException
     */
    private static function assertSessionUser(UserDataPackage $user): void
    {
        $sessionUser = getLoggedFrameworkUser();
        if ($sessionUser === null || (int) $sessionUser->id !== (int) $user->id) {
            throw new \LogicException('PanelSearchSource solo responde por el usuario de la sesión.');
        }
    }

    /**
     * El final del menú de usuario de la barra: «Acerca de» y «Cerrar sesión». Lo pinta la barra y lo lee el buscador.
     *
     * @param int $userType
     * @return MenuGroupCollection
     */
    public static function accountMenuEnd(int $userType): MenuGroupCollection
    {
        return new MenuGroupCollection([
            'items' => [
                new MenuGroup([
                    'name' => __(AdminPanelController::ADMIN_LANG_GROUP, 'Acerca de'),
                    'icon' => 'desktop',
                    'href' => AdminPanelController::routeName('about-framework'),
                    'visible' => Roles::hasPermissions('admin-about-framework', $userType),
                    'asLink' => true,
                ]),
                new MenuGroup([
                    'name' => __(ADMIN_MENU_LANG_GROUP, 'Cerrar sesión'),
                    'icon' => 'power off',
                    'href' => '#',
                    'attributes' => [
                        'pcsphp-users-logout' => '',
                    ],
                    'asLink' => true,
                ]),
            ],
        ]);
    }
}
