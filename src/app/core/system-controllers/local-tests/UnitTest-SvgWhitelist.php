<?php

//El SVG subido como animación de carga (H345): la lista blanca acepta los de fábrica y lo que solo usa lo permitido, y
//rechaza entero lo demás. Cada regla tiene un caso que pasaría si se quitara (DISCRIMINANTE). Sin red ni escrituras.
//@areas src/app/classes/PiecesPHP/Settings/

use PiecesPHP\Settings\Uploads\SvgWhitelistValidator as V;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/svg-whitelist', function ($args) {

    echoTerminal('[TEST:SvgWhitelist] Iniciando suite...', true, "\r\n", '33');
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, ?string $detail = null) use (&$passed, &$failed) {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}");
            if ($detail !== null) {
                echoTerminal("      - {$detail}");
            }
        }
        return $condition;
    };

    $svg = fn (string $inner, string $before = ''): string => $before . '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 10 10">' . $inner . '</svg>';
    $motivo = fn (string $content): string => V::rejectionReason($content) ?? 'aceptado';
    $rechaza = function (string $caso, string $content, string $esperado) use ($check, $motivo): void {
        $obtenido = $motivo($content);
        $check($obtenido === $esperado, "{$caso} → {$esperado}", "obtenido: {$obtenido}");
    };

    echoTerminal('[1/5] Lo que se acepta');
    foreach (['statics/images/loader-animation.svg', 'statics/images/loader-animation-dark.svg'] as $fabrica) {
        $contenido = is_file(basepath($fabrica)) ? (string) file_get_contents(basepath($fabrica)) : '';
        $check($contenido !== '' && V::isAllowed($contenido), "a1 CANARIO: el de fábrica, {$fabrica}", $motivo($contenido));
    }
    $completo = $svg('<title>Cargando</title><desc>La P</desc><defs><linearGradient id="g"><stop offset="0" stop-color="#00aaee"/></linearGradient>'
        . '<radialGradient id="r"><stop offset="1" stop-color="#00008b"/></radialGradient>'
        . '<circle cx="5" cy="5" r="4" fill="white"/><path id="p" d="M0 0L10 10"/></defs>'
        . '<style>.a { fill: url( "#g" ); animation: x 1s infinite; } @keyframes x { to { opacity: .5; } } @media (prefers-reduced-motion: reduce) { .a { animation: none; } }</style>'
        . '<g class="a" xml:space="preserve" style="opacity: 1; fill: url(\'#r\')">'
        . '<ellipse cx="5" cy="5" rx="2" ry="1"/><line x1="0" y1="0" x2="1" y2="1"/><polyline points="0,0 1,1"/><polygon points="0,0 1,1 0,1"/>'
        . '<use href="#p"/><use xlink:href=" #p"/>'
        . '<animate attributeName="opacity" values="0;1" dur="1s" repeatCount="indefinite"/><animateTransform attributeName="transform" type="rotate" from="0" to="360" dur="2s"/>'
        . '<animateMotion dur="2s" path="M0 0L1 1"/><set attributeName="fill" to="red" begin="1s"/></g><!-- un comentario -->');
    $check(V::isAllowed($completo), 'a2 DISCRIMINANTE: los 21 elementos permitidos, href internos (también con espacio delante), url() internos con comillas y espacios, xml:space y un comentario', $motivo($completo));
    $check(V::isAllowed('<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $svg('<rect width="1" height="1"/>')), 'a3 la declaración XML al principio');
    $check(V::isAllowed("\xEF\xBB\xBF" . '<?xml version="1.0"?>' . $svg('<rect width="1" height="1"/>')), 'a4 y con la marca de orden de bytes delante');
    echoTerminal(' ');

    echoTerminal('[2/5] Lo que se rechaza ANTES de analizar');
    $rechaza('r1 vacío', "  \n", V::REASON_EMPTY);
    $rechaza('r2 no es UTF-8', $svg("<title>\xFF\xFE</title>"), V::REASON_PARSE);
    $rechaza('r3 un DOCTYPE', '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">' . $svg(''), V::REASON_DOCTYPE);
    $rechaza('r4 un DOCTYPE en minúsculas con entidad interna', '<!doctype svg [<!ENTITY x "y">]>' . $svg('<title>&x;</title>'), V::REASON_DOCTYPE);
    $rechaza('r5 DISCRIMINANTE: <!ENTITY sin DOCTYPE (en un comentario)', $svg('<!-- <!ENTITY x SYSTEM "file:///etc/passwd"> -->'), V::REASON_ENTITY);
    $rechaza('r6 <?xml-stylesheet ?> tras la declaración', '<?xml version="1.0"?><?xml-stylesheet type="text/css" href="http://x/a.css"?>' . $svg(''), V::REASON_PROCESSING_INSTRUCTION);
    $rechaza('r7 una instrucción de procesamiento dentro', $svg('<?php echo 1; ?>'), V::REASON_PROCESSING_INSTRUCTION);
    $rechaza('r8 la declaración XML que no va al principio', $svg('') . '<?xml version="1.0"?>', V::REASON_PROCESSING_INSTRUCTION);
    $rechaza('r9 javascript: en el texto, con mayúsculas y espacios', $svg('<title>JaVa Script :f(1)</title>'), V::REASON_FORBIDDEN);
    echoTerminal(' ');

    echoTerminal('[3/5] Lo que se rechaza al recorrerlo');
    $rechaza('n1 una entidad no declarada', $svg('<title>&nbsp;</title>'), V::REASON_PARSE);
    $rechaza('n2 un XML roto', '<svg xmlns="http://www.w3.org/2000/svg"><g></svg>', V::REASON_PARSE);
    $rechaza('n3 DISCRIMINANTE: la raíz no es svg', '<g xmlns="http://www.w3.org/2000/svg"/>', V::REASON_ROOT);
    $rechaza('n4 un svg sin el espacio de nombres de SVG (también lo para la lista de elementos)', '<svg viewBox="0 0 1 1"><rect/></svg>', V::REASON_ROOT);
    $rechaza('n5 DISCRIMINANTE: <script> de SVG', $svg('<script>1</script>'), V::REASON_ELEMENT);
    $rechaza('n6 DISCRIMINANTE: <script> de XHTML', $svg('<h:script xmlns:h="http://www.w3.org/1999/xhtml">1</h:script>'), V::REASON_ELEMENT);
    $rechaza('n7 DISCRIMINANTE: <foreignObject>', $svg('<foreignObject width="1" height="1"><div xmlns="http://www.w3.org/1999/xhtml">x</div></foreignObject>'), V::REASON_ELEMENT);
    $rechaza('n8 DISCRIMINANTE: <a> e <image>, fuera de la lista', $svg('<a href="#p"><rect/></a>'), V::REASON_ELEMENT);
    $rechaza('n9 <image>', $svg('<image href="#p"/>'), V::REASON_ELEMENT);
    $rechaza('n10 un elemento de lista con otro espacio de nombres', $svg('<x:rect xmlns:x="urn:otro"/>'), V::REASON_ELEMENT);
    $rechaza('n11 DISCRIMINANTE: Path con otra caja (la lista es exacta)', $svg('<Path d="M0 0"/>'), V::REASON_ELEMENT);
    $rechaza('n12 DISCRIMINANTE: onload', $svg('<rect onload="x()"/>'), V::REASON_EVENT_HANDLER);
    $rechaza('n13 ONCLICK en mayúsculas, en la raíz', '<svg xmlns="http://www.w3.org/2000/svg" ONCLICK="x()"/>', V::REASON_EVENT_HANDLER);
    $rechaza('n14 DISCRIMINANTE: href externo', $svg('<use href="http://x/a.svg#b"/>'), V::REASON_EXTERNAL_HREF);
    $rechaza('n15 DISCRIMINANTE: xlink:href externo', $svg('<use xlink:href="//x/a.svg#b"/>'), V::REASON_EXTERNAL_HREF);
    $rechaza('n16 href con data:', $svg('<use href="data:image/svg+xml,&lt;svg/&gt;"/>'), V::REASON_EXTERNAL_HREF);
    $rechaza('n17 href con entidades numéricas (&#106;avascript:), decodificado (también lo para el href externo)', $svg('<use href="&#106;avascript:f(1)"/>'), V::REASON_FORBIDDEN);
    $rechaza('n18 DISCRIMINANTE: values con &#x6A;avascript:, que el texto crudo no ve', $svg('<animate attributeName="opacity" values="0;&#x6A;avascript:x"/>'), V::REASON_FORBIDDEN);
    $rechaza('n19 to="javascript:" en un set', $svg('<set attributeName="fill" to="javascript:x"/>'), V::REASON_FORBIDDEN);
    $rechaza('n20 DISCRIMINANTE: xml:base', $svg('<g xml:base="http://x/"><use href="#p"/></g>'), V::REASON_ATTRIBUTE_NAMESPACE);
    $rechaza('n21 DISCRIMINANTE: xlink:actuate (de xlink solo href)', $svg('<use xlink:href="#p" xlink:actuate="onLoad"/>'), V::REASON_ATTRIBUTE_NAMESPACE);
    $rechaza('n22 un atributo de otro espacio de nombres', $svg('<rect xmlns:ev="http://www.w3.org/2001/xml-events" ev:event="click"/>'), V::REASON_ATTRIBUTE_NAMESPACE);
    $rechaza('n23 DISCRIMINANTE: url() externo en un atributo', $svg('<rect fill="url(http://x/a.svg#g)"/>'), V::REASON_EXTERNAL_URL);
    $rechaza('n24 DISCRIMINANTE: url( "…" ) externo con comillas y espacios, en style=', $svg('<rect style="fill: url( &quot;http://x/a.svg#g&quot; )"/>'), V::REASON_EXTERNAL_URL);
    $rechaza('n25 DISCRIMINANTE: url() externo en un <style> con CDATA', $svg('<style><![CDATA[ .a { fill: url(\'http://x/a.svg#g\'); } ]]></style>'), V::REASON_EXTERNAL_URL);
    $rechaza('n26 DISCRIMINANTE: @import en <style>', $svg('<style>@import "http://x/a.css";</style>'), V::REASON_IMPORT);
    $rechaza('n27 @import en style=', $svg('<rect style="@import \'x.css\'"/>'), V::REASON_IMPORT);
    $rechaza('n28 DISCRIMINANTE: un escape de CSS (u\\72l), que escondería url(', $svg('<style>.a { fill: u\\72l(http://x/a.svg#g); }</style>'), V::REASON_CSS_ESCAPE);
    $rechaza('n29 expression( en style=', $svg('<rect style="width: expression(f(1))"/>'), V::REASON_FORBIDDEN);
    $rechaza('n30 behavior: en <style>', $svg('<style>.a { behavior: url(#x); }</style>'), V::REASON_FORBIDDEN);
    $rechaza('n35 DISCRIMINANTE: &#106;avascript: en un texto, que el texto crudo no ve', $svg('<title>&#106;avascript:f(1)</title>'), V::REASON_FORBIDDEN);
    $rechaza('n31 DISCRIMINANTE: set con attributeName="href"', $svg('<use href="#p"><set attributeName="href" to="#q"/></use>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('n32 animate con attributeName="xlink:href"', $svg('<use href="#p"><animate attributeName="xlink:href" values="#p;#q"/></use>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('n33 set con attributeName="onbegin"', $svg('<set attributeName="onbegin" to="x"/>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('n34 set con attributeName="style"', $svg('<set attributeName="style" to="x"/>'), V::REASON_ANIMATED_ATTRIBUTE);
    echoTerminal(' ');

    echoTerminal('[4/5] La auditoría de seguridad de H345: codificación, use anidados, tamaño y CSS que carga recursos');
    $utf7 = '<?xml version="1.0" encoding="UTF-7"?>' . "\n" . '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect id="r" width="10" height="10"/>+ADwAIQAtAC0- <script>document.documentElement.setAttribute("data-zz","1")</script> +AC0ALQA+-</svg>';
    $rechaza('s1 DISCRIMINANTE: UTF-7 que esconde un <script> tras un «comentario» +ADwAIQAtAC0-', $utf7, V::REASON_ENCODING);
    $rechaza('s2 DISCRIMINANTE: encoding=\'utf-7\' con comillas simples', "<?xml version='1.0' encoding='utf-7'?>" . $svg('<rect/>'), V::REASON_ENCODING);
    $rechaza('s3 DISCRIMINANTE: encoding="ISO-2022-JP-2"', '<?xml version="1.0" encoding="ISO-2022-JP-2"?>' . $svg('<rect/>'), V::REASON_ENCODING);
    $rechaza('s4 UTF-16LE sin BOM (con nulos; también lo para el analizador)', (string) mb_convert_encoding($svg('<rect/>'), 'UTF-16LE', 'UTF-8'), V::REASON_ENCODING);
    $rechaza('s5 DISCRIMINANTE: x:onload con el prefijo sin declarar (un aviso no fatal del analizador)', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" x:onload="zz()"><rect/></svg>', V::REASON_PARSE);
    $check(V::isAllowed('<?xml version="1.0" encoding="utf-8"?>' . $svg('<rect/>')), 's6 encoding="utf-8" en minúsculas sigue valiendo');
    $rechaza('s7 DISCRIMINANTE: un use que apunta a un grupo con use (la bomba de instancias)', $svg('<defs><rect id="a" width="1" height="1"/><g id="b"><use href="#a"/><use href="#a"/></g></defs><use href="#b"/><use href="#b"/>'), V::REASON_NESTED_USE);
    $rechaza('s8 un use que apunta a otro use', $svg('<defs><rect id="a"/><use id="b" href="#a"/></defs><use href="#b"/>'), V::REASON_NESTED_USE);
    $rechaza('s9 un use que apunta a su propio antepasado', $svg('<g id="a"><use xlink:href="#a"/></g>'), V::REASON_NESTED_USE);
    $check(V::isAllowed($svg('<defs><g id="a"><rect/><circle r="1"/></g></defs><use href="#a"/><use href="#a"/>')), 's10 varios use a un grupo sin use, sí');
    $rechaza('s11 DISCRIMINANTE: más de ' . V::MAX_ELEMENTS . ' elementos', $svg(str_repeat('<rect/>', V::MAX_ELEMENTS)), V::REASON_TOO_MANY_ELEMENTS);
    $check(V::isAllowed($svg(str_repeat('<rect/>', V::MAX_ELEMENTS - 1))), 's12 y justo ' . V::MAX_ELEMENTS . ' con la raíz, sí');
    $rechaza('s13 image-set("red.png" 1x) (también lo para la regla de las cadenas)', $svg('<style>svg{background-image:image-set("red.png" 1x)}</style>'), V::REASON_CSS_FUNCTION);
    $rechaza('s14 DISCRIMINANTE: -webkit-image-set( sin comillas', $svg('<style>svg{background-image:-webkit-image-set(red.png 1x)}</style>'), V::REASON_CSS_FUNCTION);
    $rechaza('s15 image(', $svg('<rect style="fill: image(red.png)"/>'), V::REASON_CSS_FUNCTION);
    $rechaza('s16 cross-fade(', $svg('<style>svg{background:cross-fade(a.png, b.png, 50%)}</style>'), V::REASON_CSS_FUNCTION);
    $rechaza('s17 src(', $svg('<style>svg{background:src(a.png)}</style>'), V::REASON_CSS_FUNCTION);
    $rechaza('s18 DISCRIMINANTE: una cadena entre comillas fuera de url(#…)', $svg('<style>svg{font-family:"zz-fuente"}</style>'), V::REASON_CSS_STRING);
    $rechaza('s19 url("#a") seguido de otra cadena', $svg('<style>svg{fill:url("#a"); content:\'x.png\'}</style>'), V::REASON_CSS_STRING);
    $rechaza('t1 DISCRIMINANTE: la bomba de use con ids en mayúsculas (el destino se busca exacto)', $svg('<defs><rect id="L0" width="1" height="1"/><g id="L1"><use href="#L0"/><use href="#L0"/></g></defs><use href="#L1"/>'), V::REASON_NESTED_USE);
    $rechaza('t2 un use con el fragmento codificado (#%6C0; también lo para el destino exacto)', $svg('<defs><rect id="l0" width="1" height="1"/></defs><use href="#%6C0"/>'), V::REASON_ENCODED_HREF);
    $rechaza('t3 DISCRIMINANTE: un use a un id que no existe (falla en cerrado)', $svg('<use href="#zz-no-existe"/>'), V::REASON_USE_TARGET);
    $rechaza('t4 un use con espacios dentro del fragmento', $svg('<g id="a"><rect/></g><use href="# a"/>'), V::REASON_USE_TARGET);
    $rechaza('t5 DISCRIMINANTE: la cuadrática sin anidar (101 use a un grupo de 100)', $svg('<defs><g id="big">' . str_repeat('<rect width="1" height="1"/>', 100) . '</g></defs>' . str_repeat('<use href="#big"/>', 101)), V::REASON_TOO_MANY_INSTANCES);
    $check(V::isAllowed($svg('<defs><g id="big">' . str_repeat('<rect width="1" height="1"/>', 100) . '</g></defs>' . str_repeat('<use href="#big"/>', 90))), 't6 y 90 use al mismo grupo, por debajo del tope de ' . V::MAX_RENDERED_ELEMENTS . ', sí');
    $rechaza('t7 DISCRIMINANTE: un href codificado fuera de un use', $svg('<rect id="a"/><set href="#%61" attributeName="fill" to="red"/>'), V::REASON_ENCODED_HREF);
    $rechaza('t8 DISCRIMINANTE: animar el id (cambia a qué apunta un use)', $svg('<rect id="a"><set attributeName="id" to="b"/></rect>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('t9 DISCRIMINANTE: un escape de CSS en un atributo de presentación (fill="\\75 rl(…)")', $svg('<rect fill="\\75 rl(ext.svg#m)"/>'), V::REASON_CSS_ESCAPE);
    $rechaza('t10 y en los values de un animate', $svg('<rect><animate attributeName="fill" values="\\75 rl(ext.svg#m)" dur="1s"/></rect>'), V::REASON_CSS_ESCAPE);
    $rechaza('t11 DISCRIMINANTE: una cadena de 1.100.000 caracteres en content: (la expresión regular se pasaba del límite)', $svg('<style>svg{content:"' . str_repeat('a', 1100000) . '"}</style>'), V::REASON_CSS_STRING);
    //Máscaras y clipPath anidados: nueve niveles de diez rect que enmascaran con el nivel anterior (la prueba de la auditoría).
    $anidadas = function (string $elemento, string $atributo, string $prefijo, int $niveles) use ($svg): string {
        $c = "<defs><{$elemento} id=\"{$prefijo}0\"><rect width=\"10\" height=\"10\"/></{$elemento}>";
        for ($i = 1; $i <= $niveles; $i++) {
            $c .= "<{$elemento} id=\"{$prefijo}{$i}\">" . str_repeat("<rect width=\"10\" height=\"10\" {$atributo}=\"url(#{$prefijo}" . ($i - 1) . ')"/>', 10) . "</{$elemento}>";
        }
        return $svg($c . "</defs><rect width=\"10\" height=\"10\" {$atributo}=\"url(#{$prefijo}{$niveles})\"/>");
    };
    $rechaza('u1 nueve niveles de máscaras anidadas (antes por el coste; hoy, <mask> fuera de la lista)', $anidadas('mask', 'mask', 'm', 9), V::REASON_ELEMENT);
    $rechaza('u2 nueve niveles de clipPath anidados (hoy, <clipPath> fuera de la lista)', $anidadas('clipPath', 'clip-path', 'c', 9), V::REASON_ELEMENT);
    $rechaza('u3 DISCRIMINANTE: el anidado por <style> (.l1 rect{mask:url(#m0)})', $svg('<style>.l1 rect { mask: url(#m0); }</style><defs><mask id="m0"><rect/></mask></defs><g class="l1"><rect/></g>'), V::REASON_CSS_MASKING);
    $rechaza('u4 clip-path:url( en style=', $svg('<rect id="c"/><rect style="clip-path: url(#c)"/>'), V::REASON_CSS_MASKING);
    $porUse = '<defs><mask id="m0"><rect width="10" height="10"/></mask><g id="g0" mask="url(#m0)"><rect/></g>';
    for ($i = 1; $i <= 5; $i++) {
        $porUse .= "<mask id=\"m{$i}\">" . str_repeat('<use href="#g' . ($i - 1) . '"/>', 10) . "</mask><g id=\"g{$i}\" mask=\"url(#m{$i})\"><rect/></g>";
    }
    $rechaza('u5 use dentro de una máscara hacia un grupo enmascarado, en cinco niveles (hoy, <mask> fuera de la lista)', $svg($porUse . '</defs><use href="#g5"/>'), V::REASON_ELEMENT);
    $rechaza('u6 DISCRIMINANTE: un ciclo de referencias (dos degradados que heredan el uno del otro)', $svg('<defs><linearGradient id="a" href="#b"/><linearGradient id="b" href="#a"/></defs><rect fill="url(#a)"/>'), V::REASON_REFERENCE_CYCLE);
    $rechaza('u7 DISCRIMINANTE: url(#noexiste) (falla en cerrado)', $svg('<rect fill="url(#zz-no-existe)"/>'), V::REASON_REFERENCE_TARGET);
    $degradados = $svg('<defs><linearGradient id="g"><stop offset="0" stop-color="#00aaee"/></linearGradient><linearGradient id="h" href="#g"/></defs><rect fill="url(#h)" stroke="url(#g)"/>');
    $check(V::isAllowed($degradados), 'u8 degradados (también uno que hereda de otro) siguen pasando', $motivo($degradados));
    $rechaza('u8b CAMBIA (antes se aceptaba): una máscara simple', $svg('<defs><mask id="m"><rect width="10" height="10" fill="white"/></mask></defs><rect mask="url(#m)"/>'), V::REASON_ELEMENT);
    $rechaza('u8c CAMBIA (antes se aceptaba): un clipPath simple', $svg('<defs><clipPath id="c"><rect width="5" height="5"/></clipPath></defs><rect clip-path="url(#c)"/>'), V::REASON_ELEMENT);
    $rechaza('u9 DISCRIMINANTE: fill="image-set(\'x.png\' 1x)" (un atributo de presentación)', $svg('<rect fill="image-set(\'x.png\' 1x)"/>'), V::REASON_CSS_FUNCTION);
    $rechaza('u10 cursor="image-set(\'c.png\' 1x), auto"', $svg('<rect cursor="image-set(\'c.png\' 1x), auto"/>'), V::REASON_CSS_FUNCTION);
    $rechaza('u11 stroke="image(\'x.png\')"', $svg('<rect stroke="image(\'x.png\')"/>'), V::REASON_CSS_FUNCTION);
    $rechaza('u12 DISCRIMINANTE: una cadena entre comillas en un atributo de presentación', $svg('<rect cursor="\'c.png\', auto"/>'), V::REASON_CSS_STRING);
    $check(V::isAllowed('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10" role="img" aria-label="It\'s la «P» (de PiecesPHP)"><path d="M0 0L1 1" transform="translate(1 1) rotate(45)"/><polygon points="0,0 1,1 0,1"/></svg>'), 'u13 aria-label con comillas y paréntesis, d, points y transform siguen pasando');
    //Las cuatro formas de anidar que el coste no ve (cuarta auditoría): SMIL, var() en <style>, var() en atributos y un comentario.
    $mascara = '<defs><linearGradient id="g"><stop offset="0" stop-color="#00aaee"/></linearGradient></defs><rect id="r" width="10" height="10"/>';
    $rechaza('v1 DISCRIMINANTE: set que anima la máscara (attributeName="mask", aunque sin url)', $svg($mascara . '<set href="#r" attributeName="mask" to="none"/>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('v2 clip-path, filter y marker-end tampoco se animan', $svg($mascara . '<animate href="#r" attributeName="marker-end" values="none;none"/>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('v3 DISCRIMINANTE: set con to="url(#…)" (fill)', $svg($mascara . '<set href="#r" attributeName="fill" to="url(#g)"/>'), V::REASON_ANIMATED_REFERENCE);
    $rechaza('v4 animate con values que llevan url(', $svg($mascara . '<animate href="#r" attributeName="fill" values="red;url(#g)" dur="1s"/>'), V::REASON_ANIMATED_REFERENCE);
    $rechaza('v5 DISCRIMINANTE: var() en <style>', $svg('<style>rect { fill: var(--c); }</style><rect/>'), V::REASON_CSS_VARIABLE);
    $rechaza('v6 DISCRIMINANTE: una propiedad personalizada (--c:) en <style>', $svg('<style>#a { --c: red; }</style><rect id="a"/>'), V::REASON_CSS_VARIABLE);
    $rechaza('v7 DISCRIMINANTE: var() solo con atributos (fill="var(--m)")', $svg($mascara . '<rect fill="var(--m)"/>'), V::REASON_CSS_VARIABLE);
    $rechaza('v8 y la propiedad personalizada en style= (--m:url(#m))', $svg($mascara . '<g style="--m: url(#m)"><rect/></g>'), V::REASON_CSS_VARIABLE);
    $rechaza('v9 DISCRIMINANTE: un comentario en una declaración de style= (quitando la regla en sus dos sitios)', $svg('<rect style="opacity:/**/1"/>'), V::REASON_CSS_COMMENT);
    $rechaza('v10 un comentario en <style> (mask:/*;*/url(#m))', $svg($mascara . '<style>#a rect { mask: /*;*/ url(#m); }</style>'), V::REASON_CSS_COMMENT);
    $rechaza('v11 un comentario en un atributo de presentación', $svg('<rect fill="red/**/"/>'), V::REASON_CSS_COMMENT);
    $rechaza('v12 DISCRIMINANTE: en <style>, filter aunque no lleve url(', $svg('<style>rect { filter: none; }</style><rect/>'), V::REASON_CSS_MASKING);
    $rechaza('v13 y -webkit-mask-image en <style>', $svg('<style>rect { -webkit-mask-image: none; }</style><rect/>'), V::REASON_CSS_MASKING);
    $check(V::isAllowed($svg($mascara . '<rect fill="url(#g)"><animateTransform attributeName="transform" type="rotate" values="0 5 5;360 5 5" dur="2s" repeatCount="indefinite"/><animate attributeName="opacity" values="0;1;0" keyTimes="0;0.5;1" dur="2s"/><set attributeName="fill" to="red" begin="1s"/></rect>')), 'v14 lo legítimo sigue pasando: degradado por atributo, animar transform y opacity y un set de color');
    $inicio = microtime(true);
    $grande = $svg('<style>' . str_repeat('url(#a) ', 262000) . '</style>');
    $check(V::isAllowed($grande) && microtime(true) - $inicio < 2.0, 's20 DISCRIMINANTE: 2 MB de url(#a) en menos de 2 s (antes, cuadrático: 10 s)', sprintf('%.2f s', microtime(true) - $inicio));
    echoTerminal(' ');

    echoTerminal('[5/5] Máscaras y recortes, fuera: ni elementos ni atributos ni propiedades, con cualquier valor (quinta auditoría)');
    //Nueve niveles de máscaras (o clipPath) cuyos diez rect heredan la del grupo: `inherit` y `all` esquivaban el coste.
    $hereda = function (string $hijo, string $tipo = 'mask', string $estilo = '') use ($svg): string {
        [$elemento, $atributo, $prefijo, $relleno] = $tipo === 'mask' ? ['mask', 'mask', 'm', ' fill="white"'] : ['clipPath', 'clip-path', 'c', ''];
        $c = ($estilo !== '' ? "<style>{$estilo}</style>" : '') . "<defs><{$elemento} id=\"{$prefijo}0\"><rect width=\"10\" height=\"10\"{$relleno}/></{$elemento}>";
        for ($i = 1; $i <= 9; $i++) {
            $c .= "<{$elemento} id=\"{$prefijo}{$i}\"><g {$atributo}=\"url(#{$prefijo}" . ($i - 1) . ')">' . str_repeat("<rect width=\"10\" height=\"10\"{$relleno} {$hijo}/>", 10) . "</g></{$elemento}>";
        }
        return $svg($c . "</defs><rect width=\"10\" height=\"10\" {$atributo}=\"url(#{$prefijo}9)\"/>");
    };
    $rechaza('w1 DISCRIMINANTE: inh-attr-mask-9, mask="inherit" en nueve niveles', $hereda('mask="inherit"'), V::REASON_ELEMENT);
    $rechaza('w2 DISCRIMINANTE: inh-attr-clip-9, clip-path="inherit" en nueve niveles', $hereda('clip-path="inherit"', 'clip'), V::REASON_ELEMENT);
    $rechaza('w3 DISCRIMINANTE: inh-style-mask-9, style="mask:inherit"', $hereda('style="mask:inherit"'), V::REASON_ELEMENT);
    $rechaza('w4 DISCRIMINANTE: inh-all-style-9, style="all:inherit"', $hereda('style="all:inherit"'), V::REASON_ELEMENT);
    $rechaza('w5 DISCRIMINANTE: inh-all-sheet-9, <style>mask g rect{all:inherit}</style>', $hereda('', 'mask', 'mask g rect{all:inherit}'), V::REASON_CSS_MASKING);
    $rechaza('w6 DISCRIMINANTE: mask="none" en un atributo de presentación (cualquier valor)', $svg('<rect mask="none"/>'), V::REASON_CSS_MASKING);
    $rechaza('w7 DISCRIMINANTE: all: unset en style=', $svg('<rect style="all: unset"/>'), V::REASON_CSS_MASKING);
    $rechaza('w8 DISCRIMINANTE: mask:inherit en style=, sin url(', $svg('<rect style="mask:inherit"/>'), V::REASON_CSS_MASKING);
    $rechaza('w9 DISCRIMINANTE: all en <style>', $svg('<style>rect { all: inherit; }</style><rect/>'), V::REASON_CSS_MASKING);
    $rechaza('w10 DISCRIMINANTE: clip-path="inherit" en un atributo', $svg('<rect clip-path="inherit"/>'), V::REASON_CSS_MASKING);
    $rechaza('w11 DISCRIMINANTE: un mask-* en un atributo (mask-type)', $svg('<rect mask-type="alpha"/>'), V::REASON_CSS_MASKING);
    $rechaza('w12 DISCRIMINANTE: -webkit-mask-image: none en style=', $svg('<rect style="-webkit-mask-image: none"/>'), V::REASON_CSS_MASKING);
    $rechaza('w13 DISCRIMINANTE: all en un @keyframes', $svg('<style>@keyframes k { to { all: inherit; } }</style><rect/>'), V::REASON_CSS_MASKING);
    $rechaza('w14 DISCRIMINANTE: animar un mask-* (attributeName="mask-image")', $svg('<rect><set attributeName="mask-image" to="none"/></rect>'), V::REASON_ANIMATED_ATTRIBUTE);
    $rechaza('w15 DISCRIMINANTE: animar all', $svg('<rect><set attributeName="all" to="inherit"/></rect>'), V::REASON_ANIMATED_ATTRIBUTE);
    $check(V::isAllowed($svg('<style>.small { fill: #00008b; stroke-dasharray: 1 2; }</style><rect class="small" stroke-linecap="round"/>')), 'w16 lo parecido sigue pasando: una clase small, stroke-dasharray y stroke-linecap');
    echoTerminal(' ');

    echoTerminal(str_repeat('=', 80));
    echoTerminal(" BALANCE FINAL: {$passed}/" . ($passed + $failed) . " PASADAS ");
    echoTerminal(str_repeat('=', 80));
    echoTerminal('');
    echoTerminal('[TEST:SvgWhitelist] Suite finalizada.', true, "\r\n", $failed === 0 ? '32' : '31');
    echoTerminal('');

    return [
        'success' => $failed === 0 && $passed > 0,
        'message' => $failed === 0 ? 'Suite ejecutada correctamente.' : "Suite completada con {$failed} prueba(s) fallida(s).",
    ];

})->setDescription('La lista blanca del SVG subido como animación de carga: acepta los de fábrica y lo permitido; rechaza entero el DOCTYPE, las entidades, las instrucciones de procesamiento, los elementos y atributos fuera de lista, los manejadores, las referencias y url() externos, @import, los escapes de CSS, javascript: (también codificado), animar href, las máscaras y recortes (elementos, atributos y propiedades) y all. No escribe.')->setEffects([CliActions::EFFECT_NONE])->register();
