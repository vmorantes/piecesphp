<?php

//Cada campo lleva una etiqueta HTML: la plantilla tiene que devolverla escapada, nunca como HTML vivo.
//render() hace die si la vista lanza: un error de plantilla mata la suite sin balance, y eso es una parada.

use PiecesPHP\Core\BaseController;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/mail-templates-escape', function ($args) {

    echoTerminal("\e[33m[TEST:MailTemplatesEscape] Las plantillas de correo de los formularios públicos escapan al visitante\e[39m");
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

    $carga = fn(string $campo): string => "<b class=\"zz-{$campo}\">{$campo}</b>";
    $escapada = fn(string $campo): string => "&lt;b class=&quot;zz-{$campo}&quot;&gt;{$campo}&lt;/b&gt;";
    $comprobarCampo = function (string $salida, string $campo) use ($check, $escapada): void {
        $check(!str_contains($salida, "<b class=\"zz-{$campo}\">"), "(i) {$campo}: la salida NO contiene la etiqueta viva");
        $check(str_contains($salida, $escapada($campo)), "(ii) {$campo}: la salida contiene la etiqueta escapada");
    };

    //─── 1/4 · Formulario de contacto ───────────────────────────────────────────────────────────────────
    echoTerminal('[1/4] mailing/generic-contact-form');
    $salida = (string) (new BaseController())->render('mailing/generic-contact-form', [
        'title' => "<i class='zz-title'>t</i>",
        'name' => $carga('name'),
        'email' => $carga('email'),
        'subject' => $carga('subject'),
        'message' => $carga('message'),
        'updates' => true,
    ], false);
    foreach (['name', 'email', 'subject', 'message'] as $campo) {
        $comprobarCampo($salida, $campo);
    }
    $check(str_contains($salida, "<i class='zz-title'>t</i>"), 't1: el título, que compone el controlador, sigue saliendo como HTML');
    echoTerminal(' ');

    //─── 2/4 · Otros problemas ──────────────────────────────────────────────────────────────────────────
    echoTerminal('[2/4] usuarios/mail/other-problems');
    $salida = (string) (new BaseController())->render('usuarios/mail/other-problems', [
        'originURL' => 'https://zz-prueba.test/origen',
        'subject' => $carga('subject'),
        'mail' => $carga('mail'),
        'name' => $carga('name'),
        'message' => $carga('message'),
        'extra' => [
            ['display' => $carga('display'), 'text' => $carga('text')],
            ['display' => 'ZZ segundo', 'text' => 'ZZ dos'],
        ],
    ], false);
    foreach (['subject', 'mail', 'name', 'message', 'display', 'text'] as $campo) {
        $comprobarCampo($salida, $campo);
    }
    $check(!str_contains($salida, '</strong></p>' . '\\n' . '<p><strong>ZZ segundo'), 's1: los extra no se separan con una barra-n literal');

    echoTerminal(' ');

    //─── 3/4 · Banner de la portada ─────────────────────────────────────────────────────────────────────
    //El título y el enlace los escribe el administrador, pero van a atributos: sin escape, una comilla rompe el HTML.
    echoTerminal('[3/4] BuiltIn/Banner/Views/public/util/item.php');
    $datos = ['title' => 'zz"<b>', 'content' => '<p>zz-contenido</p>', 'desktopImage' => 'zz-d.jpg', 'mobileImage' => 'zz-m.jpg', 'link' => 'https://x.test/?a=1&b="2"'];
    $element = new class($datos) {
        public function __construct(private array $datos) {}
        public function currentLangData(string $name): ?string { return $this->datos[$name] ?? null; }
    };
    $salida = (function (object $element): string {
        ob_start();
        include basepath('app/classes/PiecesPHP/BuiltIn/Banner/Views/public/util/item.php');
        return (string) ob_get_clean();
    })($element);
    $check(str_contains($salida, 'href="https://x.test/?a=1&amp;b=&quot;2&quot;"'), 'b1: el enlace sale entre comillas y escapado', $salida);
    $check(substr_count($salida, 'alt="zz&quot;&lt;b&gt;"') === 2, 'b2: los dos alt llevan el título escapado');
    $check(str_contains($salida, '<div class="title">zz&quot;&lt;b&gt;</div>'), 'b3: el título visible sale escapado');
    $check(!str_contains($salida, 'zz"<b>'), 'b4: el título no sale vivo en ningún sitio');
    $check(str_contains($salida, '<p>zz-contenido</p>'), 'b5: el contenido enriquecido sigue saliendo como HTML');
    echoTerminal(' ');

    //─── 4/4 · Plantilla sin estilos con comentarios ────────────────────────────────────────────────────
    //Aprobaciones usa la plantilla del núcleo y le pasa `reason` ya escapado: la plantilla lo imprime tal cual.
    echoTerminal('[4/4] mailing/template_base_no_style (núcleo)');
    $conMotivo = (string) (new BaseController())->render('mailing/template_base_no_style', ['text' => 'zz-texto', 'reason' => htmlspecialchars('zz"<b>', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')], false);
    $encabezado = __(MAILING_GENERAL_LANG_GROUP, 'Con los siguientes comentarios:');
    $check(str_contains($conMotivo, "<h3>{$encabezado}</h3>"), 'r1: con reason sale el encabezado de comentarios');
    $check(str_contains($conMotivo, '<p>zz&quot;&lt;b&gt;</p>') && !str_contains($conMotivo, 'zz"<b>'), 'r2: el reason sale escapado');
    $sinMotivo = (string) (new BaseController())->render('mailing/template_base_no_style', ['text' => 'zz-texto', 'reason' => ''], false);
    $check(!str_contains($sinMotivo, "<h3>{$encabezado}</h3>") && str_contains($sinMotivo, 'zz-texto'), 'r3: con reason vacío no sale el bloque');
    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las plantillas de correo de los formularios públicos escapan lo que escribe el visitante.')->setEffects([CliActions::EFFECT_NONE])->register();
