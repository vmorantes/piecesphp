<?php

//P312 a: los bloques registrados en los tres huecos de la barra lateral. El registro, en memoria y repuesto al final; el
//usuario no se lee de la base (los bloques de prueba no lo miran), así que la suite no toca la instalación.
//@areas nucleo

use PiecesPHP\AdminPanel\SidebarBlock;
use PiecesPHP\AdminPanel\SidebarBlockRegistry;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\UserDataPackage;

CliActions::make('unit-tests:core/sidebar-blocks', function ($args) {

    echoTerminal("\e[33m[TEST:SidebarBlocks] Los bloques de la barra lateral: el bloque, el registro y lo que pinta cada hueco\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };
    $throws = function (callable $fn): bool {
        try {
            $fn();
        } catch (\InvalidArgumentException) {
            return true;
        }
        return false;
    };

    $user = (new \ReflectionClass(UserDataPackage::class))->newInstanceWithoutConstructor();
    $html = fn(string $text): callable => fn(UserDataPackage $u): string => $text;
    $menu = SidebarBlock::SLOT_MENU_AFTER;
    $originals = SidebarBlockRegistry::swapForTesting();

    try {
        //─── a · El bloque ──────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] El bloque');
        $check($throws(fn() => new SidebarBlock('Zz_Malo', $menu, $html('x'))), 'a1 key fuera de kebab-case → lanza');
        $check($throws(fn() => new SidebarBlock('zz-a', 'header', $html('x'))), 'a2 hueco desconocido → lanza');
        $check($throws(fn() => new SidebarBlock('zz-a', $menu, $html('x'), 100, 'hidden')), 'a3 visibilidad desconocida → lanza');
        $check((new SidebarBlock('zz-a', $menu, fn(UserDataPackage $u) => 42))->render($user) === '', 'a4 render() que no devuelve cadena → \'\'');
        echoTerminal(' ');

        //─── b · El registro ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] El registro');
        SidebarBlockRegistry::register(new SidebarBlock('zz-a', $menu, $html('A'), 20));
        $check($throws(fn() => SidebarBlockRegistry::register(new SidebarBlock('zz-a', $menu, $html('A2')))), 'b1 key repetida → lanza');
        $check(SidebarBlockRegistry::remove('zz-no-esta') === false, 'b2 remove de una que no está → false');
        $check($throws(fn() => SidebarBlockRegistry::replace(new SidebarBlock('zz-no-esta', $menu, $html('x')))), 'b3 replace de una que no está → lanza');
        SidebarBlockRegistry::register(new SidebarBlock('zz-b', $menu, $html('B'), 10));
        SidebarBlockRegistry::register(new SidebarBlock('zz-c', $menu, $html('C'), 20));
        SidebarBlockRegistry::register(new SidebarBlock('zz-d', $menu, $html('D'), 20));
        $order = implode(',', array_keys(SidebarBlockRegistry::all()));
        $check($order === 'zz-b,zz-a,zz-c,zz-d', 'b4 por posición y, a igual posición, por registro', $order);
        SidebarBlockRegistry::replace(new SidebarBlock('zz-a', $menu, $html('A3'), 20));
        $order = implode(',', array_keys(SidebarBlockRegistry::all()));
        $check($order === 'zz-b,zz-a,zz-c,zz-d' && SidebarBlockRegistry::get('zz-a')?->render($user) === 'A3', 'b5 replace conserva su sitio en el orden', $order);
        $check(SidebarBlockRegistry::remove('zz-a') === true && SidebarBlockRegistry::get('zz-a') === null, 'b6 remove de una que está → true y ya no está');
        echoTerminal(' ');

        //─── c · Lo que pinta un hueco ──────────────────────────────────────────────────────────────────
        echoTerminal('[c] renderSlot()');
        SidebarBlockRegistry::swapForTesting();
        $check(SidebarBlockRegistry::renderSlot($menu, $user) === '', 'c1 registro vacío → \'\'');
        SidebarBlockRegistry::register(new SidebarBlock('zz-pie', SidebarBlock::SLOT_FOOTER, $html('<p>pie</p>')));
        $check(SidebarBlockRegistry::renderSlot($menu, $user) === '', 'c2 solo bloques de otro hueco → \'\'');
        $check(SidebarBlockRegistry::renderSlot(SidebarBlock::SLOT_FOOTER, null) === '', 'c3 sin usuario → \'\'');
        SidebarBlockRegistry::register(new SidebarBlock('zz-vacio', $menu, $html('')));
        SidebarBlockRegistry::register(new SidebarBlock('zz-blancos', $menu, $html('   ')));
        $check(SidebarBlockRegistry::renderSlot($menu, $user) === '', 'c4 bloques que devuelven \'\' o «   » → \'\'');

        SidebarBlockRegistry::swapForTesting();
        $out = fn(string $show): string => (function () use ($show, $menu, $html, $user): string {
            SidebarBlockRegistry::swapForTesting();
            SidebarBlockRegistry::register(new SidebarBlock('zz-x', $menu, $html('<b>x</b>'), 100, $show));
            return SidebarBlockRegistry::renderSlot($menu, $user);
        })();
        $got = $out(SidebarBlock::SHOW_ALWAYS);
        $check($got === '<div class="sidebar-block" data-sidebar-block="zz-x"><b>x</b></div>', 'c5 SHOW_ALWAYS, sin clase extra; el HTML del bloque, tal cual', $got);
        $got = $out(SidebarBlock::SHOW_EXPANDED);
        $check($got === '<div class="sidebar-block expanded only" data-sidebar-block="zz-x"><b>x</b></div>', 'c6 SHOW_EXPANDED → «expanded only»', $got);
        $got = $out(SidebarBlock::SHOW_COLLAPSED);
        $check($got === '<div class="sidebar-block collapsed only" data-sidebar-block="zz-x"><b>x</b></div>', 'c7 SHOW_COLLAPSED → «collapsed only»', $got);

        //El escape del atributo no se prueba: KEY_PATTERN no deja en la key nada que escape_html() cambie.
        SidebarBlockRegistry::swapForTesting();
        SidebarBlockRegistry::register(new SidebarBlock('zz-2', $menu, $html('<i title="a&b">2</i>'), 20));
        SidebarBlockRegistry::register(new SidebarBlock('zz-1', $menu, $html('<i>1</i>'), 10));
        $got = SidebarBlockRegistry::renderSlot($menu, $user);
        $check($got === '<div class="sidebar-block" data-sidebar-block="zz-1"><i>1</i></div><div class="sidebar-block" data-sidebar-block="zz-2"><i title="a&b">2</i></div>', 'c8 dos bloques del mismo hueco, en su orden y sin separador; el HTML, sin escapar', $got);

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        SidebarBlockRegistry::swapForTesting($originals);
        $check(SidebarBlockRegistry::all() === $originals, 'z1 el registro queda como estaba');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('P312 a: los bloques de la barra lateral (SidebarBlock, SidebarBlockRegistry): validación, registro, orden y lo que pinta cada hueco.')->setEffects([CliActions::EFFECT_NONE])->register();
