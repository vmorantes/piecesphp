<?php

//Cada campo lleva una etiqueta HTML: la plantilla tiene que devolverla escapada, nunca como HTML vivo.
//Efecto (5/5): una vista rota en un temporal del sistema, fuera del árbol, que se borra al terminar.
//@areas src/app/classes/PiecesPHP/BuiltIn/

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

    //─── 1/5 · Formulario de contacto ───────────────────────────────────────────────────────────────────
    echoTerminal('[1/5] mailing/generic-contact-form');
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

    //─── 2/5 · Otros problemas ──────────────────────────────────────────────────────────────────────────
    echoTerminal('[2/5] usuarios/mail/other-problems');
    //La vista vive en el módulo desde que UserProblemsController se la llevó: se le dice a esta
    //instancia dónde buscarla, igual que hace el controlador que la usa en producción.
    $salida = (string) (new BaseController())
        ->setInstanceViewDir(basepath('app/classes/PiecesPHP/UserSystem/Views/'))
        ->render('usuarios/mail/other-problems', [
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

    //─── 3/5 · Banner de la portada ─────────────────────────────────────────────────────────────────────
    //El título y el enlace los escribe el administrador, pero van a atributos: sin escape, una comilla rompe el HTML.
    echoTerminal('[3/5] BuiltIn/Banner/Views/public/util/item.php');
    $datos = ['title' => 'zz"<b>', 'content' => '<p>zz-contenido</p>', 'desktopImage' => 'zz-d.jpg', 'mobileImage' => 'zz-m.jpg', 'link' => 'https://x.test/?a=1&b="2"'];
    $element = new class($datos) {
        public function __construct(private array $datos) {}
        public function currentLangData(string $name): ?string
        {
            return $this->datos[$name] ?? null;
        }
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

    //─── 4/5 · Plantilla sin estilos con comentarios ────────────────────────────────────────────────────
    //Aprobaciones usa la plantilla del núcleo y le pasa `reason` ya escapado: la plantilla lo imprime tal cual.
    echoTerminal('[4/5] mailing/template_base_no_style (núcleo)');
    $conMotivo = (string) (new BaseController())->render('mailing/template_base_no_style', ['text' => 'zz-texto', 'reason' => htmlspecialchars('zz"<b>', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')], false);
    $encabezado = __(MAILING_GENERAL_LANG_GROUP, 'Con los siguientes comentarios:');
    $check(str_contains($conMotivo, "<h3>{$encabezado}</h3>"), 'r1: con reason sale el encabezado de comentarios');
    $check(str_contains($conMotivo, '<p>zz&quot;&lt;b&gt;</p>') && !str_contains($conMotivo, 'zz"<b>'), 'r2: el reason sale escapado');
    $sinMotivo = (string) (new BaseController())->render('mailing/template_base_no_style', ['text' => 'zz-texto', 'reason' => ''], false);
    $check(!str_contains($sinMotivo, "<h3>{$encabezado}</h3>") && str_contains($sinMotivo, 'zz-texto'), 'r3: con reason vacío no sale el bloque');
    echoTerminal(' ');

    //─── 5/5 · Una vista que lanza ──────────────────────────────────────────────────────────────────────
    //Quien pide un correo tiene que poder capturar el fallo de su plantilla: render() relanza sin registrar.
    echoTerminal('[5/5] Una vista que lanza: la excepción llega a quien llama, y render() no registra');
    $directorio = sys_get_temp_dir() . '/zz-agente-vista-rota-' . getmypid();
    $logDir = rtrim((string) constant('LOG_ERRORS_PATH'), '/\\');
    $registros = [$logDir . '/error.log.jsonl', $logDir . '/error.plain.log'];
    $lineas = fn(): array => array_map(fn(string $f): int => is_file($f) ? count(file($f) ?: []) : 0, $registros);
    $borrada = false;
    try {
        $creada = (is_dir($directorio) || mkdir($directorio, 0700, true))
            && file_put_contents($directorio . '/rota.php', "<p>antes</p><?php throw new \\RuntimeException('zz-vista-rota');") !== false;
        $check($creada, 'v0 PRE: la vista rota de prueba existe', $directorio);
        foreach (['render' => fn(BaseController $c) => $c->render('rota', [], false), '_render' => fn(BaseController $c) => $c->_render('rota.php', [], false)] as $metodo => $pintar) {
            $antes = $lineas();
            $niveles = ob_get_level();
            try {
                $pintar((new BaseController())->setInstanceViewDir($directorio));
                $capturada = 'no lanzó';
            } catch (\RuntimeException $e) {
                $capturada = $e->getMessage();
            }
            $check($capturada === 'zz-vista-rota', "v1 {$metodo}(): quien llama captura la excepción de la vista", $capturada);
            $check($lineas() === $antes, "v2 {$metodo}(): no escribe en el registro de errores", (string) json_encode([$antes, $lineas()]));
            $check(ob_get_level() === $niveles, "v3 {$metodo}(): no deja abierto su búfer de salida");
        }
    } finally {
        $borrada = (!is_file($directorio . '/rota.php') || unlink($directorio . '/rota.php')) && (!is_dir($directorio) || rmdir($directorio));
    }
    $check($borrada && !file_exists($directorio), 'v4 la vista rota de prueba ya no existe');
    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Las plantillas de correo de los formularios públicos escapan lo que escribe el visitante.')->setEffects([CliActions::EFFECT_NONE])->register();
