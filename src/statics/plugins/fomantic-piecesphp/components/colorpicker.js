/* Fork: componente propio, no existe en Fomantic. */
/*!
 * # Fomantic-UI 0.13.0 - Colorpicker
 * https://github.com/fomantic/Fomantic-UI/
 *
 *
 * Released under the MIT license
 * https://opensource.org/licenses/MIT
 *
 */

(function ($, window, document) {
    'use strict';

    function isFunction(obj) {
        return typeof obj === 'function' && typeof obj.nodeType !== 'number';
    }

    window = window !== undefined && window.Math === Math
        ? window
        : globalThis;

    /*
     * Motor de color: aritmética propia y determinista. Solo usa + - * / y Math.sqrt, cuyo resultado
     * fija IEEE 754 al bit en todos los motores; Math.pow, cbrt, sin, cos, atan2 y hypot son
     * aproximaciones que cambian de un motor a otro. Ni canvas, ni getComputedStyle, ni color-mix():
     * el mismo texto da la misma cadena en cualquier navegador. Todo color pasa por sRGB de 8 bits
     * con alfa de 3 decimales; las tolerancias, en diseno/componentes/colorpicker.md.
     */
    var colorEngine = (function () {
        var
            RADIAN = 3.141592653589793 / 180,
            POWERS = [1, 10, 100, 1000, 10000],
            NAMES = 'aliceblue:f0f8ff,antiquewhite:faebd7,aqua:00ffff,aquamarine:7fffd4,azure:f0ffff,beige:f5f5dc,bisque:ffe4c4,black:000000,blanchedalmond:ffebcd,blue:0000ff,blueviolet:8a2be2,brown:a52a2a,burlywood:deb887,cadetblue:5f9ea0,chartreuse:7fff00,chocolate:d2691e,coral:ff7f50,cornflowerblue:6495ed,cornsilk:fff8dc,crimson:dc143c,cyan:00ffff,darkblue:00008b,darkcyan:008b8b,darkgoldenrod:b8860b,darkgray:a9a9a9,darkgreen:006400,darkgrey:a9a9a9,darkkhaki:bdb76b,darkmagenta:8b008b,darkolivegreen:556b2f,darkorange:ff8c00,darkorchid:9932cc,darkred:8b0000,darksalmon:e9967a,darkseagreen:8fbc8f,darkslateblue:483d8b,darkslategray:2f4f4f,darkslategrey:2f4f4f,darkturquoise:00ced1,darkviolet:9400d3,deeppink:ff1493,deepskyblue:00bfff,dimgray:696969,dimgrey:696969,dodgerblue:1e90ff,firebrick:b22222,floralwhite:fffaf0,forestgreen:228b22,fuchsia:ff00ff,gainsboro:dcdcdc,ghostwhite:f8f8ff,gold:ffd700,goldenrod:daa520,gray:808080,green:008000,greenyellow:adff2f,grey:808080,honeydew:f0fff0,hotpink:ff69b4,indianred:cd5c5c,indigo:4b0082,ivory:fffff0,khaki:f0e68c,lavender:e6e6fa,lavenderblush:fff0f5,lawngreen:7cfc00,lemonchiffon:fffacd,lightblue:add8e6,lightcoral:f08080,lightcyan:e0ffff,lightgoldenrodyellow:fafad2,lightgray:d3d3d3,lightgreen:90ee90,lightgrey:d3d3d3,lightpink:ffb6c1,lightsalmon:ffa07a,lightseagreen:20b2aa,lightskyblue:87cefa,lightslategray:778899,lightslategrey:778899,lightsteelblue:b0c4de,lightyellow:ffffe0,lime:00ff00,limegreen:32cd32,linen:faf0e6,magenta:ff00ff,maroon:800000,mediumaquamarine:66cdaa,mediumblue:0000cd,mediumorchid:ba55d3,mediumpurple:9370db,mediumseagreen:3cb371,mediumslateblue:7b68ee,mediumspringgreen:00fa9a,mediumturquoise:48d1cc,mediumvioletred:c71585,midnightblue:191970,mintcream:f5fffa,mistyrose:ffe4e1,moccasin:ffe4b5,navajowhite:ffdead,navy:000080,oldlace:fdf5e6,olive:808000,olivedrab:6b8e23,orange:ffa500,orangered:ff4500,orchid:da70d6,palegoldenrod:eee8aa,palegreen:98fb98,paleturquoise:afeeee,palevioletred:db7093,papayawhip:ffefd5,peachpuff:ffdab9,peru:cd853f,pink:ffc0cb,plum:dda0dd,powderblue:b0e0e6,purple:800080,rebeccapurple:663399,red:ff0000,rosybrown:bc8f8f,royalblue:4169e1,saddlebrown:8b4513,salmon:fa8072,sandybrown:f4a460,seagreen:2e8b57,seashell:fff5ee,sienna:a0522d,silver:c0c0c0,skyblue:87ceeb,slateblue:6a5acd,slategray:708090,slategrey:708090,snow:fffafa,springgreen:00ff7f,steelblue:4682b4,tan:d2b48c,teal:008080,thistle:d8bfd8,tomato:ff6347,turquoise:40e0d0,violet:ee82ee,wheat:f5deb3,white:ffffff,whitesmoke:f5f5f5,yellow:ffff00,yellowgreen:9acd32',
            names = {},
            // Armonías: desplazamiento de tono y de luminosidad de cada serie (escaparate de Colores)
            HARMONIES = {
                analogous: [[30, 0], [-30, 0], [60, -0.06], [-60, 0.06]],
                triad: [[120, 0], [240, 0], [120, 0.12], [240, -0.1]],
                complementary: [[180, 0], [150, 0], [210, 0], [180, 0.16]],
            }
        ;

        NAMES.split(',').forEach(function (entry) {
            var pair = entry.split(':');
            names[pair[0]] = pair[1];
        });

        function clamp(value, min, max) {
            return value < min ? min : (value > max ? max : value);
        }

        function round(value, decimals) {
            var factor = POWERS[decimals];

            return Math.round(value * factor) / factor;
        }

        function wrapHue(hue) {
            return ((hue % 360) + 360) % 360;
        }

        // Raíz n-ésima por Newton con iteraciones fijas
        function nthRoot(value, degree) {
            var
                guess = 1,
                iteration,
                power,
                exponent
            ;
            if (value <= 0) {
                return 0;
            }
            for (iteration = 0; iteration < 48; iteration++) {
                power = 1;
                for (exponent = 1; exponent < degree; exponent++) {
                    power *= guess;
                }
                guess = (((degree - 1) * guess) + (value / power)) / degree;
            }

            return guess;
        }

        function signedCbrt(value) {
            return value < 0 ? -nthRoot(-value, 3) : nthRoot(value, 3);
        }

        // Seno y coseno de un ángulo en grados: series de Taylor sobre |x| ≤ 45°
        function sinCos(degrees) {
            var
                wrapped = wrapHue(degrees),
                quadrant = Math.round(wrapped / 90),
                x = (wrapped - (quadrant * 90)) * RADIAN,
                square = x * x,
                sine = x,
                cosine = 1,
                sineTerm = x,
                cosineTerm = 1,
                index
            ;
            for (index = 1; index <= 7; index++) {
                sineTerm *= -square / ((2 * index) * ((2 * index) + 1));
                cosineTerm *= -square / (((2 * index) - 1) * (2 * index));
                sine += sineTerm;
                cosine += cosineTerm;
            }
            switch (quadrant % 4) {
                case 1: {
                    return [cosine, -sine];
                }
                case 2: {
                    return [-sine, -cosine];
                }
                case 3: {
                    return [-cosine, sine];
                }
                default: {
                    return [sine, cosine];
                }
            }
        }

        // Ángulo de (x, y) en grados, de 0 a 360: dos reducciones a la mitad y serie de arcotangente
        function angle(y, x) {
            var
                absX = x < 0 ? -x : x,
                absY = y < 0 ? -y : y,
                swap = absY > absX,
                ratio,
                square,
                sum = 0,
                term,
                index
            ;
            if (absX === 0 && absY === 0) {
                return 0;
            }
            ratio = swap ? absX / absY : absY / absX;
            ratio /= 1 + Math.sqrt(1 + (ratio * ratio));
            ratio /= 1 + Math.sqrt(1 + (ratio * ratio));
            square = ratio * ratio;
            term = ratio;
            for (index = 0; index < 12; index++) {
                sum += (index % 2 === 0 ? term : -term) / ((2 * index) + 1);
                term *= square;
            }
            sum = (sum * 4) / RADIAN;
            if (swap) {
                sum = 90 - sum;
            }
            if (x < 0) {
                sum = 180 - sum;
            }

            return y < 0 ? 360 - sum : (sum === 360 ? 0 : sum);
        }

        // sRGB ↔ lineal: x^2,4 = x² · (x²)^(1/5) y x^(1/2,4) = (x⁵)^(1/12)
        function decode(channel) {
            var value = channel / 255;
            if (value <= 0.04045) {
                return value / 12.92;
            }
            value = (value + 0.055) / 1.055;

            return value * value * nthRoot(value * value, 5);
        }

        function encode(linear) {
            if (linear <= 0.0031308) {
                return linear * 12.92;
            }

            return (1.055 * Math.sqrt(Math.sqrt(nthRoot(linear * linear * linear * linear * linear, 3)))) - 0.055;
        }

        function toByte(value) {
            return clamp(Math.round(value * 255), 0, 255);
        }

        function fromHsv(hue, saturation, brightness, alpha) {
            var
                sector = wrapHue(hue) / 60,
                index = Math.floor(sector),
                offset = sector - index,
                low = brightness * (1 - saturation),
                falling = brightness * (1 - (saturation * offset)),
                rising = brightness * (1 - (saturation * (1 - offset))),
                channels = [
                    [brightness, rising, low],
                    [falling, brightness, low],
                    [low, brightness, rising],
                    [low, falling, brightness],
                    [rising, low, brightness],
                    [brightness, low, falling],
                ][index]
            ;

            return {
                r: toByte(channels[0]),
                g: toByte(channels[1]),
                b: toByte(channels[2]),
                a: alpha,
            };
        }

        function hueOf(color, max, delta) {
            var hue = 0;
            if (delta === 0) {
                return 0;
            }
            if (max === color.r) {
                hue = (color.g - color.b) / delta;
            } else if (max === color.g) {
                hue = ((color.b - color.r) / delta) + 2;
            } else {
                hue = ((color.r - color.g) / delta) + 4;
            }

            return wrapHue(hue * 60);
        }

        function toHsv(color) {
            var
                max = Math.max(color.r, color.g, color.b),
                delta = max - Math.min(color.r, color.g, color.b)
            ;

            return {
                h: hueOf(color, max, delta),
                s: max === 0 ? 0 : delta / max,
                v: max / 255,
            };
        }

        function fromHsl(hue, saturation, lightness, alpha) {
            var chroma = saturation * (lightness < 0.5 ? lightness : 1 - lightness);

            return fromHsv(hue, lightness + chroma === 0 ? 0 : (2 * chroma) / (lightness + chroma), lightness + chroma, alpha);
        }

        function toHsl(color) {
            var
                max = Math.max(color.r, color.g, color.b),
                min = Math.min(color.r, color.g, color.b),
                delta = max - min,
                lightness = (max + min) / 510,
                divisor = 255 * (1 - Math.abs((2 * lightness) - 1))
            ;

            return {
                h: hueOf(color, max, delta),
                s: delta === 0 ? 0 : delta / divisor,
                l: lightness,
            };
        }

        // Matrices de CSS Color 4 (sRGB lineal → XYZ D65 → LMS → OKLab, y sus inversas)
        function toOklab(color) {
            var
                red = decode(color.r),
                green = decode(color.g),
                blue = decode(color.b),
                x = ((506752 / 1228815) * red) + ((87881 / 245763) * green) + ((12673 / 70218) * blue),
                y = ((87098 / 409605) * red) + ((175762 / 245763) * green) + ((12673 / 175545) * blue),
                z = ((7918 / 409605) * red) + ((87881 / 737289) * green) + ((1001167 / 1053270) * blue),
                l = signedCbrt((0.819022437996703 * x) + (0.3619062600528904 * y) - (0.1288737815209879 * z)),
                m = signedCbrt((0.0329836539323885 * x) + (0.9292868615863434 * y) + (0.0361446663506424 * z)),
                s = signedCbrt((0.0481771893596242 * x) + (0.2642395317527308 * y) + (0.6335478284694309 * z))
            ;

            return {
                l: (0.210454268309314 * l) + (0.7936177747023054 * m) - (0.0040720430116193 * s),
                a: (1.9779985324311684 * l) - (2.42859224204858 * m) + (0.450593709617411 * s),
                b: (0.0259040424655478 * l) + (0.7827717124575296 * m) - (0.8086757549230774 * s),
            };
        }

        function fromOklab(lightness, a, b, alpha) {
            var
                l = lightness + (0.3963377773761749 * a) + (0.2158037573099136 * b),
                m = lightness - (0.1055613458156586 * a) - (0.0638541728258133 * b),
                s = lightness - (0.0894841775298119 * a) - (1.2914855480194092 * b),
                longCone = l * l * l,
                mediumCone = m * m * m,
                shortCone = s * s * s,
                x = (1.2268798758459243 * longCone) - (0.5578149944602171 * mediumCone) + (0.2813910456659647 * shortCone),
                y = (-0.0405757452148008 * longCone) + (1.112286803280317 * mediumCone) - (0.0717110580655164 * shortCone),
                z = (-0.0763729366746601 * longCone) - (0.4214933324022432 * mediumCone) + (1.5869240198367816 * shortCone)
            ;

            return {
                r: toByte(encode(((12831 / 3959) * x) - ((329 / 214) * y) - ((1974 / 3959) * z))),
                g: toByte(encode(((-851781 / 878810) * x) + ((1648619 / 878810) * y) + ((36519 / 878810) * z))),
                b: toByte(encode(((705 / 12673) * x) - ((2585 / 12673) * y) + ((705 / 667) * z))),
                a: alpha,
            };
        }

        function fromOklch(lightness, chroma, hue, alpha) {
            var trigonometry = sinCos(hue);

            return fromOklab(lightness, chroma * trigonometry[1], chroma * trigonometry[0], alpha);
        }

        function toOklch(color) {
            var
                lab = toOklab(color),
                chroma = Math.sqrt((lab.a * lab.a) + (lab.b * lab.b))
            ;
            if (chroma < 0.0001) {
                return { l: lab.l, c: 0, h: 0 };
            }

            return { l: lab.l, c: chroma, h: angle(lab.b, lab.a) };
        }

        function fromHex(digits) {
            var
                short = digits.length < 6,
                size = short ? 1 : 2,
                read = function (index) {
                    var pair = digits.substr(index * size, size);

                    return parseInt(short ? pair + pair : pair, 16);
                }
            ;

            return {
                r: read(0),
                g: read(1),
                b: read(2),
                a: digits.length === 4 || digits.length === 8 ? round(read(3) / 255, 3) : 1,
            };
        }

        // Un número CSS con su unidad; `none` vale 0. Más de 17 cifras no llegan a Number(), que
        // solo garantiza el redondeo correcto hasta 20
        function readNumber(token) {
            var match = /^([+-]?)(\d+\.?\d*|\.\d+)(e[+-]?\d+)?(%|deg|grad|rad|turn)?$/.exec(token);
            if (token === 'none') {
                return { value: 0, unit: '' };
            }
            if (!match) {
                return null;
            }

            return {
                value: Number(match[1] + match[2].slice(0, 18) + (match[3] || '')),
                unit: match[4] || '',
            };
        }

        function readAlpha(number) {
            if (number === undefined) {
                return 1;
            }

            return round(clamp(number.unit === '%' ? number.value / 100 : number.value, 0, 1), 3);
        }

        function readHue(number) {
            var factor = { '': 1, deg: 1, grad: 0.9, rad: 1 / RADIAN, turn: 360 }[number.unit];

            return factor === undefined ? NaN : number.value * factor;
        }

        function readPercent(number) {
            return clamp(number.value / 100, 0, 1);
        }

        function readChannel(number) {
            return clamp(Math.round(number.unit === '%' ? (number.value * 255) / 100 : number.value), 0, 255);
        }

        function fromFunction(name, tokens) {
            var
                numbers = tokens.map(readNumber),
                alpha
            ;
            if (numbers.indexOf(null) !== -1 || (numbers.length !== 3 && numbers.length !== 4)) {
                return null;
            }
            alpha = readAlpha(numbers[3]);
            switch (name) {
                case 'rgb':
                case 'rgba': {
                    return { r: readChannel(numbers[0]), g: readChannel(numbers[1]), b: readChannel(numbers[2]), a: alpha };
                }
                case 'hsl':
                case 'hsla': {
                    return isNaN(readHue(numbers[0])) ? null : fromHsl(readHue(numbers[0]), readPercent(numbers[1]), readPercent(numbers[2]), alpha);
                }
                case 'oklch': {
                    return isNaN(readHue(numbers[2]))
                        ? null
                        : fromOklch(
                            clamp(numbers[0].unit === '%' ? numbers[0].value / 100 : numbers[0].value, 0, 1),
                            Math.max(numbers[1].unit === '%' ? (numbers[1].value * 0.4) / 100 : numbers[1].value, 0),
                            readHue(numbers[2]),
                            alpha
                        );
                }
                case 'oklab': {
                    return fromOklab(
                        clamp(numbers[0].unit === '%' ? numbers[0].value / 100 : numbers[0].value, 0, 1),
                        numbers[1].unit === '%' ? (numbers[1].value * 0.4) / 100 : numbers[1].value,
                        numbers[2].unit === '%' ? (numbers[2].value * 0.4) / 100 : numbers[2].value,
                        alpha
                    );
                }
                default: {
                    return null;
                }
            }
        }

        // Admite hex con o sin #, nombres CSS y rgb(), hsl(), oklch(), oklab() y color(srgb …), con
        // comas o espacios, y alfa tras una coma o una barra. Devuelve null si no es un color.
        function parse(text) {
            var
                value = String(text === undefined || text === null ? '' : text).trim().toLowerCase(),
                match,
                tokens
            ;
            if (value === 'transparent') {
                return { r: 0, g: 0, b: 0, a: 0 };
            }
            if (Object.prototype.hasOwnProperty.call(names, value)) {
                return fromHex(names[value]);
            }
            match = /^#?([\da-f]{3,4}|[\da-f]{6}|[\da-f]{8})$/.exec(value);
            if (match) {
                return fromHex(match[1]);
            }
            match = /^([a-z]+)\(\s*(.*?)\s*\)$/.exec(value);
            if (!match) {
                return null;
            }
            tokens = match[2].split(/\s*[\s,/]\s*/).filter(Boolean);
            if (match[1] === 'color') {
                if (tokens[0] !== 'srgb') {
                    return null;
                }

                return fromFunction('rgb', tokens.slice(1).map(function (token, index) {
                    var number = readNumber(token);

                    return number && index < 3 && number.unit === '' ? String(number.value * 100) + '%' : token;
                }));
            }

            return fromFunction(match[1], tokens);
        }

        function toHexDigits(value) {
            return (value < 16 ? '0' : '') + value.toString(16);
        }

        function format(color, name, uppercase) {
            var
                alpha = color.a < 1,
                suffix,
                result
            ;
            switch (name) {
                case 'rgb': {
                    return (alpha ? 'rgba(' : 'rgb(') + color.r + ', ' + color.g + ', ' + color.b + (alpha ? ', ' + color.a : '') + ')';
                }
                case 'hsl': {
                    result = toHsl(color);

                    return (alpha ? 'hsla(' : 'hsl(') + round(result.h, 2) + ', ' + round(result.s * 100, 2) + '%, ' + round(result.l * 100, 2) + '%' + (alpha ? ', ' + color.a : '') + ')';
                }
                case 'oklch': {
                    result = toOklch(color);
                    suffix = alpha ? ' / ' + color.a : '';

                    return 'oklch(' + round(result.l, 4) + ' ' + round(result.c, 4) + ' ' + round(result.h, 2) + suffix + ')';
                }
                default: {
                    result = '#' + toHexDigits(color.r) + toHexDigits(color.g) + toHexDigits(color.b) + (alpha || name === 'hex8' ? toHexDigits(Math.round(color.a * 255)) : '');

                    return uppercase ? result.toUpperCase() : result;
                }
            }
        }

        // Luminancia y contraste de WCAG 2.x. Un color con alfa se compone sobre el fondo
        function luminance(color) {
            return (0.2126 * decode(color.r)) + (0.7152 * decode(color.g)) + (0.0722 * decode(color.b));
        }

        function contrast(color, background) {
            var
                mix = function (channel) {
                    return Math.round((color[channel] * color.a) + (background[channel] * (1 - color.a)));
                },
                first = luminance({ r: mix('r'), g: mix('g'), b: mix('b') }),
                second = luminance(background),
                lighter = Math.max(first, second),
                darker = Math.min(first, second)
            ;

            return (lighter + 0.05) / (darker + 0.05);
        }

        // Armonías del escaparate de Colores: desplazan el tono, suben la saturación a 0,45 como
        // mínimo, acotan la luminosidad a 0,32–0,5 y apartan el tono `range` grados de los de `avoid`
        function harmony(color, name, avoid, range) {
            var
                hsl = toHsl(color),
                separate = function (hue) {
                    var result = wrapHue(hue);
                    (avoid || []).forEach(function (reserved) {
                        var distance = ((result - reserved + 540) % 360) - 180;
                        if (Math.abs(distance) < range) {
                            result = reserved + (distance < 0 ? -range : range);
                        }
                    });

                    return result;
                }
            ;

            return (HARMONIES[name] || []).map(function (shift) {
                return fromHsl(
                    separate(hsl.h + shift[0]),
                    Math.max(0.45, hsl.s),
                    Math.max(0.32, Math.min(0.5, hsl.l + shift[1])),
                    1
                );
            });
        }

        return {
            parse: parse,
            format: format,
            contrast: contrast,
            harmony: harmony,
            fromHsv: fromHsv,
            toHsv: toHsv,
            toHsl: toHsl,
            toOklch: toOklch,
            round: round,
        };
    }());

    $.fn.colorpicker = function (parameters) {
        var
            $allModules     = $(this),

            time            = Date.now(),
            performance     = [],

            query           = arguments[0],
            methodInvoked   = typeof query === 'string',
            queryArguments  = [].slice.call(arguments, 1),
            returnedValue
        ;
        $allModules.each(function () {
            var
                settings        = $.isPlainObject(parameters)
                    ? $.extend(true, {}, $.fn.colorpicker.settings, parameters)
                    : $.extend({}, $.fn.colorpicker.settings),

                namespace       = settings.namespace,
                className       = settings.className,
                error           = settings.error,
                metadata        = settings.metadata,
                selector        = settings.selector,
                cssVars         = settings.cssVars,
                text            = settings.text,
                templates       = settings.templates,

                eventNamespace  = '.' + namespace,
                moduleNamespace = 'module-' + namespace,

                element         = this,
                instance        = $(this).data(moduleNamespace),

                $module         = $(this),
                $root,
                $input,
                $trigger,
                $panel,
                $form           = $(),

                id              = Math.random().toString(16).slice(2, 10),
                input,
                valueProperty   = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value'),
                originalType,
                ownsRoot        = false,
                ownsInput       = false,
                nativeChange,
                writing         = false,
                frame           = null,
                placementFrame  = null,
                pendingInput    = false,
                drag            = null,
                visible         = false,
                paletteOnly     = false,
                state           = {
                    h: 0,
                    s: 0,
                    v: 0,
                    a: 1,
                    empty: true,
                    token: null,
                    format: 'hex',
                },
                initial         = null,
                lastChanged     = '',
                module
            ;

            module = {
                initialize: function () {
                    if ($module.is(selector.panel)) {
                        return;
                    }
                    module.verbose('Initializing colorpicker', $module);
                    module.setup.elements();
                    module.read.settings();
                    module.setup.state();
                    if (!settings.inline) {
                        module.create.trigger();
                    }
                    module.bind.events();
                    if (settings.inline) {
                        module.create.panel();
                        $panel.appendTo($root);
                        $root.addClass(className.inline);
                        $input.addClass(className.hidden);
                    }
                    module.render();
                    if (settings.disabled || input.disabled) {
                        module.set.disabled();
                    }
                    if (input.readOnly) {
                        $root.addClass(className.readonly);
                        if ($trigger) {
                            $trigger.prop('disabled', true);
                        }
                    }
                    if (settings.fireOnInit) {
                        settings.onChange.call(input, module.get.value(), module.get.color());
                    }
                    module.instantiate();
                },
                instantiate: function () {
                    instance = module;
                    $input.data(moduleNamespace, module);
                    $root.data(moduleNamespace, module);
                },
                destroy: function () {
                    module.verbose('Destroying colorpicker');
                    if (visible) {
                        module.hide(true);
                    }
                    cancelAnimationFrame(frame);
                    cancelAnimationFrame(placementFrame);
                    module.unbind.events();
                    if ($panel) {
                        $panel.remove();
                        $panel = undefined;
                    }
                    if ($trigger) {
                        $trigger.remove();
                    }
                    $root[0].style.removeProperty(cssVars.color);
                    if ($root[0].getAttribute('style') === '') {
                        $root.removeAttr('style');
                    }
                    $input
                        .removeClass(className.hidden)
                        .removeAttr('aria-invalid')
                        .removeData(moduleNamespace)
                    ;
                    $root
                        .removeClass(className.inline + ' ' + className.invalid + ' ' + className.readonly + ' ' + className.empty)
                        .removeData(moduleNamespace)
                    ;
                    delete input.value;
                    if (originalType) {
                        input.type = originalType;
                    }
                    if (ownsInput) {
                        $input.remove();
                    }
                    if (ownsRoot === 'wrap') {
                        $input.unwrap();
                    } else if (ownsRoot === 'class') {
                        $root.removeClass(namespace);
                    }
                    instance = undefined;
                },

                setup: {
                    elements: function () {
                        var $parent;
                        if ($module.is('input')) {
                            $input = $module;
                            $parent = $input.parent();
                            if ($parent.is(selector.root)) {
                                $root = $parent;
                            } else if ($parent.is(selector.uiInput)) {
                                $root = $parent.addClass(namespace);
                                ownsRoot = 'class';
                            } else {
                                $input.wrap(templates.root(className));
                                $root = $input.parent();
                                ownsRoot = 'wrap';
                            }
                        } else {
                            $root = $module;
                            $input = $root.find(selector.input).first();
                            if ($input.length === 0) {
                                $input = $('<input type="hidden">').appendTo($root);
                                ownsInput = true;
                            }
                        }
                        input = $input[0];
                        if (input.type === 'color') {
                            originalType = 'color';
                            input.type = 'text';
                        }
                        $form = $(input.form);
                        settings.inline = settings.inline || $root.hasClass(className.inline);
                    },
                    state: function () {
                        state.format = settings.format;
                        module.adopt(input.value);
                        lastChanged = module.get.value();
                        initial = module.snapshot();
                    },
                },

                // Ajustes por campo: data-<prefijo>-<ajuste>, en el input o en su contenedor
                read: {
                    settings: function () {
                        $.each(metadata.options, function (index, option) {
                            var
                                attribute = 'data-' + settings.dataPrefix + '-' + option.replace(/[A-Z]/g, function (letter) {
                                    return '-' + letter.toLowerCase();
                                }),
                                value = $input.attr(attribute)
                            ;
                            value = value === undefined ? $root.attr(attribute) : value;
                            if (value !== undefined) {
                                settings[option] = module.read.value(value);
                            }
                        });
                        settings.inline = settings.inline || $root.hasClass(className.inline);
                        paletteOnly = Boolean(settings.paletteOnly);
                        if (settings.formats === true) {
                            settings.formats = ['hex', 'rgb', 'hsl', 'oklch'];
                        } else if (typeof settings.formats === 'string') {
                            settings.formats = settings.formats.split(/[\s,]+/);
                        }
                    },
                    value: function (value) {
                        if (value === '' || value === 'true') {
                            return true;
                        }
                        if (value === 'false') {
                            return false;
                        }
                        if (/^\s*[[{]/.test(value)) {
                            try {
                                return JSON.parse(value);
                            } catch (exception) {
                                module.error(error.metadata, value);
                            }
                        }

                        return value;
                    },
                },

                create: {
                    trigger: function () {
                        $trigger = $(templates.trigger(className, text)).insertBefore($input);
                    },
                    panel: function () {
                        var model = {
                            id: namespace + '-' + id,
                            inline: settings.inline,
                            alpha: settings.alpha,
                            input: settings.showInput,
                            initial: settings.showInitial,
                            formats: settings.formats || [],
                            channels: settings.channels,
                            clear: settings.allowEmpty,
                            eyedropper: settings.eyedropper && 'EyeDropper' in window,
                            contrast: Boolean(settings.contrast),
                            buttons: settings.buttons,
                            toggle: settings.togglePaletteOnly && module.has.palette(),
                        };
                        $panel = $(templates.panel(model, className, text));
                        $panel.find(selector.palette).html(module.get.paletteHtml());
                        module.set.paletteTokens();
                        module.set.paletteMode();
                        module.bind.panel();
                    },
                },

                bind: {
                    events: function () {
                        var property = {
                            configurable: true,
                            get: function () {
                                return valueProperty.get.call(input);
                            },
                            set: function (value) {
                                valueProperty.set.call(input, value);
                                if (!writing) {
                                    module.sync();
                                }
                            },
                        };
                        Object.defineProperty(input, 'value', property);
                        nativeChange = module.event.field.change;
                        // En captura: normaliza el texto antes de que lo lean los demás oyentes
                        input.addEventListener('change', nativeChange, true);
                        $input.on('input' + eventNamespace, module.event.field.input);
                        if ($trigger) {
                            $trigger.on('click' + eventNamespace, module.event.trigger);
                        }
                        $form.on('reset' + eventNamespace + id, module.event.reset);
                    },
                    panel: function () {
                        $panel
                            .on('pointerdown' + eventNamespace, selector.area, module.event.drag.start)
                            .on('pointerdown' + eventNamespace, selector.bar, module.event.drag.start)
                            .on('keydown' + eventNamespace, selector.area, module.event.keyboard.area)
                            .on('keydown' + eventNamespace, selector.hue, module.event.keyboard.hue)
                            .on('keydown' + eventNamespace, selector.alpha, module.event.keyboard.alpha)
                            .on('keyup' + eventNamespace, selector.sliders, module.event.keyboard.release)
                            .on('click' + eventNamespace, selector.swatch, module.event.swatch)
                            .on('keydown' + eventNamespace, selector.swatch, module.event.keyboard.swatch)
                            .on('click' + eventNamespace, selector.initial, module.event.restore)
                            .on('click' + eventNamespace, selector.formatButton, module.event.format)
                            .on('click' + eventNamespace, selector.clear, module.event.clear)
                            .on('click' + eventNamespace, selector.eyedropper, module.event.eyedropper)
                            .on('click' + eventNamespace, selector.toggle, module.event.togglePalette)
                            .on('click' + eventNamespace, selector.accept, module.confirm)
                            .on('click' + eventNamespace, selector.cancel, module.cancel)
                            .on('input' + eventNamespace, selector.text, module.event.text.input)
                            .on('change' + eventNamespace, selector.text, module.event.text.change)
                            .on('input' + eventNamespace, selector.channel, module.event.channel.input)
                            .on('change' + eventNamespace, selector.channel, module.event.channel.change)
                            .on('keydown' + eventNamespace, selector.channel, module.event.channel.keydown)
                            .on('keydown' + eventNamespace, module.event.keyboard.panel)
                        ;
                        if (!settings.inline) {
                            // Un flotante del framework abierto debajo no debe tomarlo por un clic fuera
                            $panel.on('click' + eventNamespace, function (event) {
                                event.stopPropagation();
                            });
                        }
                    },
                    outside: function () {
                        document.addEventListener('pointerdown', module.event.outside, true);
                        window.addEventListener('scroll', module.event.reposition, true);
                        $(window).on('resize' + eventNamespace + id, module.event.reposition);
                    },
                },
                unbind: {
                    events: function () {
                        input.removeEventListener('change', nativeChange, true);
                        $input.off(eventNamespace);
                        $form.off(eventNamespace + id);
                        module.unbind.outside();
                    },
                    outside: function () {
                        document.removeEventListener('pointerdown', module.event.outside, true);
                        window.removeEventListener('scroll', module.event.reposition, true);
                        $(window).off(eventNamespace + id);
                    },
                },

                event: {
                    trigger: function () {
                        module.toggle();
                    },
                    reset: function () {
                        // El navegador devuelve los valores después de disparar `reset`
                        setTimeout(function () {
                            if (visible) {
                                module.hide();
                            }
                            module.sync();
                            lastChanged = module.get.value();
                        }, 0);
                    },
                    outside: function (event) {
                        if ($(event.target).closest($panel).length > 0 || $(event.target).closest($root).length > 0) {
                            return;
                        }
                        if (settings.buttons && !settings.clickoutFiresChange) {
                            module.cancel();
                        } else {
                            module.confirm();
                        }
                    },
                    reposition: function () {
                        cancelAnimationFrame(placementFrame);
                        placementFrame = requestAnimationFrame(module.set.placement);
                    },
                    field: {
                        input: function () {
                            if (!writing && module.typed(input.value, false)) {
                                settings.onInput.call(input, input.value, module.get.color());
                            }
                        },
                        change: function () {
                            var value;
                            module.typed(input.value, true);
                            value = module.get.value();
                            module.write(value);
                            module.set.invalid(false);
                            if (value !== lastChanged) {
                                lastChanged = value;
                                module.recents.add();
                                settings.onChange.call(input, value, module.get.color());
                            }
                        },
                    },
                    text: {
                        input: function () {
                            if (module.typed(this.value, false)) {
                                module.queue();
                            }
                        },
                        change: function () {
                            module.typed(this.value, true);
                            module.flush();
                            module.render(true);
                            module.set.invalid(false);
                            module.refreshHarmonies();
                            if (!settings.buttons) {
                                module.fire.change();
                            }
                        },
                    },
                    channel: {
                        input: function () {
                            module.apply.channels();
                        },
                        change: function () {
                            module.render(true);
                            if (!settings.buttons) {
                                module.flush();
                                module.fire.change();
                            }
                        },
                        keydown: function (event) {
                            var
                                step = (event.shiftKey ? 10 : 1) * (event.key === 'ArrowUp' ? 1 : (event.key === 'ArrowDown' ? -1 : 0)),
                                maximum = Number($(this).attr('data-max'))
                            ;
                            if (step !== 0) {
                                event.preventDefault();
                                this.value = String(Math.max(0, Math.min(maximum, Math.round((Number(this.value) || 0) + step))));
                                module.apply.channels();
                            }
                        },
                    },
                    drag: {
                        start: function (event) {
                            var target = this;
                            if (event.button !== 0 || module.is.disabled()) {
                                return;
                            }
                            event.preventDefault();
                            target.focus();
                            drag = { target: target, pointer: event.pointerId };
                            try {
                                target.setPointerCapture(event.pointerId);
                            } catch (exception) {
                                module.verbose('Pointer is no longer active');
                            }
                            target.addEventListener('pointermove', module.event.drag.move);
                            target.addEventListener('pointerup', module.event.drag.end);
                            target.addEventListener('pointercancel', module.event.drag.end);
                            module.apply.pointer(event);
                        },
                        move: function (event) {
                            if (drag && event.pointerId === drag.pointer) {
                                module.apply.pointer(event);
                            }
                        },
                        end: function (event) {
                            var target = this;
                            target.removeEventListener('pointermove', module.event.drag.move);
                            target.removeEventListener('pointerup', module.event.drag.end);
                            target.removeEventListener('pointercancel', module.event.drag.end);
                            if (drag && target.hasPointerCapture(event.pointerId)) {
                                target.releasePointerCapture(event.pointerId);
                            }
                            drag = null;
                            module.flush();
                            module.refreshHarmonies();
                            if (!settings.buttons) {
                                module.fire.change();
                            }
                        },
                    },
                    keyboard: {
                        // Flechas: 1 %; con Mayús, 10 %. Inicio y Fin, la saturación; Re Pág y Av Pág, el brillo
                        area: function (event) {
                            var step = (event.shiftKey ? 10 : 1) / 100;
                            if (module.is.disabled()) {
                                return;
                            }
                            switch (event.key) {
                                case 'ArrowLeft': {
                                    module.apply.area(state.s - step, state.v);

                                    break;
                                }
                                case 'ArrowRight': {
                                    module.apply.area(state.s + step, state.v);

                                    break;
                                }
                                case 'ArrowUp': {
                                    module.apply.area(state.s, state.v + step);

                                    break;
                                }
                                case 'ArrowDown': {
                                    module.apply.area(state.s, state.v - step);

                                    break;
                                }
                                case 'Home': {
                                    module.apply.area(0, state.v);

                                    break;
                                }
                                case 'End': {
                                    module.apply.area(1, state.v);

                                    break;
                                }
                                case 'PageUp': {
                                    module.apply.area(state.s, 1);

                                    break;
                                }
                                case 'PageDown': {
                                    module.apply.area(state.s, 0);

                                    break;
                                }
                                default: {
                                    return;
                                }
                            }
                            event.preventDefault();
                        },
                        hue: function (event) {
                            var value = module.get.stepped(event, state.h, 360, 1, 30);
                            if (value !== undefined && !module.is.disabled()) {
                                event.preventDefault();
                                module.apply.hue(value);
                            }
                        },
                        alpha: function (event) {
                            var value = module.get.stepped(event, state.a * 100, 100, 1, 25);
                            if (value !== undefined && !module.is.disabled()) {
                                event.preventDefault();
                                module.apply.alpha(value / 100);
                            }
                        },
                        release: function (event) {
                            if (/^(Arrow|Home|End|Page)/.test(event.key)) {
                                module.flush();
                                module.refreshHarmonies();
                                if (!settings.buttons) {
                                    module.fire.change();
                                }
                            }
                        },
                        swatch: function (event) {
                            var
                                $all = $panel.find(selector.swatch).filter(':visible'),
                                index = $all.index(this),
                                columns = module.get.columns($all),
                                target
                            ;
                            switch (event.key) {
                                case 'ArrowRight': {
                                    target = index + 1;

                                    break;
                                }
                                case 'ArrowLeft': {
                                    target = index - 1;

                                    break;
                                }
                                case 'ArrowDown': {
                                    target = index + columns;

                                    break;
                                }
                                case 'ArrowUp': {
                                    target = index - columns;

                                    break;
                                }
                                case 'Home': {
                                    target = 0;

                                    break;
                                }
                                case 'End': {
                                    target = $all.length - 1;

                                    break;
                                }
                                default: {
                                    return;
                                }
                            }
                            event.preventDefault();
                            if (target >= 0 && target < $all.length) {
                                $all.attr('tabindex', '-1');
                                $all.eq(target).attr('tabindex', '0').trigger('focus');
                            }
                        },
                        panel: function (event) {
                            var
                                $focusable,
                                first,
                                last
                            ;
                            if (event.key === 'Escape') {
                                if (!settings.inline || module.get.value() !== initial.value) {
                                    // Sin esto, la misma pulsación cerraría el modal o el flyout de debajo
                                    event.stopPropagation();
                                    event.preventDefault();
                                    module.cancel();
                                }
                            } else if (event.key === 'Enter') {
                                if ($(event.target).is('button')) {
                                    return;
                                }
                                event.preventDefault();
                                if ($(event.target).is(selector.text) && !module.typed(event.target.value, true)) {
                                    return;
                                }
                                module.confirm();
                            } else if (event.key === 'Tab' && !settings.inline) {
                                $focusable = $panel.find(selector.focusable).filter(':visible');
                                first = $focusable[0];
                                last = $focusable[$focusable.length - 1];
                                if (event.shiftKey && (document.activeElement === first || document.activeElement === $panel[0])) {
                                    event.preventDefault();
                                    last.focus();
                                } else if (!event.shiftKey && document.activeElement === last) {
                                    event.preventDefault();
                                    first.focus();
                                }
                            }
                        },
                    },
                    swatch: function () {
                        var
                            $swatch = $(this),
                            value = $swatch.attr('data-value')
                        ;
                        if ($panel.find(selector.swatch).filter(':visible').length > 0) {
                            $panel.find(selector.swatch).attr('tabindex', '-1');
                            $swatch.attr('tabindex', '0');
                        }
                        if (module.adopt(value)) {
                            module.queue();
                            module.flush();
                            if (!settings.buttons) {
                                module.fire.change();
                                if (settings.hideOnChoose && !settings.inline) {
                                    module.hide();
                                }
                            }
                        }
                    },
                    restore: function () {
                        module.adopt(initial.value);
                        state.format = initial.format;
                        module.queue();
                        module.flush();
                    },
                    format: function () {
                        state.format = $(this).attr('data-format');
                        module.queue();
                        module.flush();
                        module.render(true);
                        if (!settings.buttons) {
                            module.fire.change();
                        }
                    },
                    clear: function () {
                        state.empty = true;
                        state.token = null;
                        module.queue();
                        module.flush();
                        if (!settings.buttons) {
                            module.fire.change();
                        }
                    },
                    eyedropper: function () {
                        new window.EyeDropper().open().then(function (result) {
                            if (module.adopt(result.sRGBHex)) {
                                module.queue();
                                module.flush();
                                if (!settings.buttons) {
                                    module.fire.change();
                                }
                            }
                        }).catch(function () {
                            module.verbose('Eyedropper closed');
                        });
                    },
                    togglePalette: function () {
                        paletteOnly = !paletteOnly;
                        module.set.paletteMode();
                        module.set.placement();
                    },
                },

                // Entrada de color: cada función deja el estado al día y pide pintar y avisar
                apply: {
                    pointer: function (event) {
                        var
                            rectangle = drag.target.getBoundingClientRect(),
                            x = colorEngine.round(Math.max(0, Math.min(1, (event.clientX - rectangle.left) / rectangle.width)), 3),
                            y = colorEngine.round(Math.max(0, Math.min(1, (event.clientY - rectangle.top) / rectangle.height)), 3)
                        ;
                        if ($(drag.target).is(selector.area)) {
                            module.apply.area(x, 1 - y);
                        } else if ($(drag.target).is(selector.hue)) {
                            module.apply.hue(x * 360);
                        } else {
                            module.apply.alpha(x);
                        }
                    },
                    area: function (saturation, brightness) {
                        state.s = colorEngine.round(Math.max(0, Math.min(1, saturation)), 3);
                        state.v = colorEngine.round(Math.max(0, Math.min(1, brightness)), 3);
                        module.apply.done();
                    },
                    hue: function (hue) {
                        state.h = colorEngine.round(Math.max(0, Math.min(360, hue)), 1);
                        module.apply.done();
                    },
                    alpha: function (alpha) {
                        state.a = colorEngine.round(Math.max(0, Math.min(1, alpha)), 3);
                        module.apply.done();
                    },
                    channels: function () {
                        var
                            values = $panel.find(selector.channel).map(function () {
                                return Number(this.value);
                            }).get(),
                            isHsl = state.format === 'hsl',
                            typedColor = (isHsl ? 'hsl(' : 'rgb(') + values[0] + ' ' + values[1] + (isHsl ? '% ' : ' ') + values[2] + (isHsl ? '%' : '') + (values[3] === undefined ? '' : ' / ' + values[3] + '%') + ')'
                        ;
                        if (module.adopt(typedColor)) {
                            module.queue();
                        }
                    },
                    done: function () {
                        state.empty = false;
                        state.token = null;
                        module.set.invalid(false);
                        module.queue();
                    },
                },

                // Lee un texto. Devuelve si era un color; si no, el estado no cambia
                adopt: function (value) {
                    var
                        text = String(value === undefined || value === null ? '' : value).trim(),
                        token = /^var\(\s*--[\w-]+\s*(,.*)?\)$/i.test(text) ? text : null,
                        color,
                        hsv
                    ;
                    if (text === '') {
                        state.empty = true;
                        state.token = null;

                        return true;
                    }
                    color = token ? module.get.resolved(token) : colorEngine.parse(text);
                    if (!color) {
                        return false;
                    }
                    hsv = colorEngine.toHsv(color);
                    // En un gris o en negro no hay tono ni saturación: se conservan los de la barra
                    if (hsv.s > 0) {
                        state.h = hsv.h;
                    }
                    if (hsv.v > 0) {
                        state.s = hsv.s;
                    }
                    state.v = hsv.v;
                    state.a = settings.alpha ? color.a : 1;
                    state.empty = false;
                    state.token = token;

                    return true;
                },
                // Texto escrito por el usuario, en el campo o en el panel. `final`: lo vacío solo vale si
                // se permite vaciar y lo inválido no se marca mientras acabe de confirmar
                typed: function (value, final) {
                    var
                        valid = String(value).trim() === ''
                            ? settings.allowEmpty
                            : module.adopt(value)
                    ;
                    if (valid && String(value).trim() === '') {
                        module.adopt('');
                    }
                    module.set.invalid(!valid && !final);
                    if (!valid) {
                        settings.onInvalid.call(input, value);
                    } else if (!final) {
                        module.render();
                    }

                    return valid;
                },
                // El input cambió desde fuera (.val(), .form('reset'), form.reset()): sin eventos
                sync: function () {
                    if (!module.adopt(valueProperty.get.call(input))) {
                        module.set.invalid(true);
                    } else {
                        module.set.invalid(false);
                    }
                    module.render(true);
                },
                write: function (value) {
                    writing = true;
                    valueProperty.set.call(input, value);
                    writing = false;
                },
                snapshot: function () {
                    return { value: module.get.value(), format: state.format };
                },

                // Durante el arrastre pinta y avisa una vez por fotograma
                queue: function () {
                    pendingInput = true;
                    if (frame === null) {
                        frame = requestAnimationFrame(module.flush);
                    }
                },
                flush: function () {
                    cancelAnimationFrame(frame);
                    frame = null;
                    if (!pendingInput) {
                        return;
                    }
                    pendingInput = false;
                    module.render();
                    module.fire.input();
                },
                fire: {
                    input: function () {
                        var value = module.get.value();
                        module.write(value);
                        writing = true;
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                        writing = false;
                        settings.onInput.call(input, value, module.get.color());
                    },
                    change: function () {
                        var value = module.get.value();
                        if (value === lastChanged) {
                            return;
                        }
                        lastChanged = value;
                        module.write(value);
                        input.removeEventListener('change', nativeChange, true);
                        input.dispatchEvent(new Event('change', { bubbles: true }));
                        input.addEventListener('change', nativeChange, true);
                        module.recents.add();
                        settings.onChange.call(input, value, module.get.color());
                    },
                },

                render: function (force) {
                    var
                        rgba = module.get.rgba(),
                        cssColor = state.empty ? 'transparent' : colorEngine.format(rgba, 'rgb'),
                        value = module.get.value(),
                        field = document.activeElement === input
                    ;
                    $root[0].style.setProperty(cssVars.color, cssColor);
                    $root.toggleClass(className.empty, state.empty);
                    if (force === true || !field) {
                        module.write(value);
                    }
                    if ($panel) {
                        module.render.panel(cssColor, value, force === true);
                    }
                },

                show: function () {
                    if (visible || settings.inline || module.is.disabled() || input.readOnly) {
                        return;
                    }
                    if (settings.onShow.call(input) === false) {
                        return;
                    }
                    if (!$panel) {
                        module.create.panel();
                    }
                    initial = module.snapshot();
                    lastChanged = initial.value;
                    module.refreshHarmonies();
                    $panel.appendTo($(settings.context));
                    module.set.placement();
                    $panel.addClass(className.visible);
                    visible = true;
                    $trigger.attr({ 'aria-expanded': 'true', 'aria-controls': $panel.attr('id') });
                    module.render(true);
                    module.bind.outside();
                    $panel.find(selector.autofocus).filter(':visible').first().trigger('focus');
                },
                hide: function (silent) {
                    if (!visible) {
                        return;
                    }
                    visible = false;
                    module.unbind.outside();
                    $panel.removeClass(className.visible).detach();
                    $trigger.attr('aria-expanded', 'false').removeAttr('aria-controls');
                    if (silent !== true) {
                        $trigger.trigger('focus');
                        settings.onHide.call(input);
                    }
                },
                toggle: function () {
                    if (visible) {
                        if (settings.buttons && !settings.clickoutFiresChange) {
                            module.cancel();
                        } else {
                            module.confirm();
                        }
                    } else {
                        module.show();
                    }
                },
                confirm: function () {
                    module.flush();
                    module.fire.change();
                    initial = module.snapshot();
                    module.hide();
                },
                cancel: function () {
                    var changed = module.get.value() !== initial.value;
                    module.adopt(initial.value);
                    state.format = initial.format;
                    module.render(true);
                    if (changed) {
                        module.fire.input();
                        if (lastChanged !== initial.value) {
                            module.fire.change();
                        }
                    }
                    settings.onCancel.call(input, module.get.value());
                    module.hide();
                },
                // Vacía el valor, si se permite
                clear: function () {
                    if (!settings.allowEmpty) {
                        return;
                    }
                    state.empty = true;
                    state.token = null;
                    module.render(true);
                    module.fire.input();
                    module.fire.change();
                },
                // Vuelve a leer el input y, si el panel existe, sus muestras
                refresh: function () {
                    module.sync();
                    if ($panel) {
                        module.set.palette(settings.palette);
                    }
                },
                reset: function () {
                    module.adopt(input.defaultValue);
                    module.render(true);
                    module.fire.input();
                    module.fire.change();
                },

                refreshHarmonies: function () {
                    if (settings.harmonies && $panel) {
                        module.set.palette(settings.palette);
                    }
                },

                recents: {
                    key: function () {
                        return namespace + '.' + (settings.recents === true ? 'default' : settings.recents);
                    },
                    read: function () {
                        var list = [];
                        if (!settings.recents) {
                            return list;
                        }
                        try {
                            list = JSON.parse(window.localStorage.getItem(module.recents.key())) || [];
                        } catch (exception) {
                            module.verbose('Recent colors are not available');
                        }

                        return Array.isArray(list) ? list : [];
                    },
                    add: function () {
                        var value = module.get.value(), list;
                        if (!settings.recents || value === '') {
                            return;
                        }
                        list = module.recents.read().filter(function (entry) {
                            return entry !== value;
                        });
                        list.unshift(value);
                        try {
                            window.localStorage.setItem(module.recents.key(), JSON.stringify(list.slice(0, settings.recentsLimit)));
                        } catch (exception) {
                            module.verbose('Recent colors are not available');
                        }
                        if ($panel) {
                            module.set.palette(settings.palette);
                        }
                    },
                },

                has: {
                    palette: function () {
                        return (settings.palette || []).length > 0;
                    },
                },
                is: {
                    disabled: function () {
                        return $root.hasClass(className.disabled);
                    },
                    visible: function () {
                        return visible;
                    },
                    empty: function () {
                        return state.empty;
                    },
                    inline: function () {
                        return Boolean(settings.inline);
                    },
                },
                get: {
                    value: function () {
                        if (state.empty) {
                            return '';
                        }
                        if (state.token && settings.saveToken) {
                            return state.token;
                        }

                        return colorEngine.format(module.get.rgba(), state.format, settings.uppercase);
                    },
                    rgba: function () {
                        return colorEngine.fromHsv(state.h, state.s, state.v, state.a);
                    },
                    color: function () {
                        var rgba = module.get.rgba();
                        if (state.empty) {
                            return { empty: true };
                        }

                        return {
                            empty: false,
                            token: state.token,
                            hex: colorEngine.format({ r: rgba.r, g: rgba.g, b: rgba.b, a: 1 }, 'hex', settings.uppercase),
                            hex8: colorEngine.format(rgba, 'hex8', settings.uppercase),
                            rgb: colorEngine.format(rgba, 'rgb'),
                            hsl: colorEngine.format(rgba, 'hsl'),
                            oklch: colorEngine.format(rgba, 'oklch'),
                            alpha: rgba.a,
                            contrast: module.get.contrast(),
                        };
                    },
                    contrast: function () {
                        var background;
                        if (!settings.contrast || state.empty) {
                            return null;
                        }
                        background = colorEngine.parse(module.get.contrastSetting('against'));

                        return background ? colorEngine.round(colorEngine.contrast(module.get.rgba(), background), 2) : null;
                    },
                    contrastSetting: function (name) {
                        if (typeof settings.contrast === 'string') {
                            return name === 'against' ? settings.contrast : settings.contrastDefaults[name];
                        }

                        return ($.isPlainObject(settings.contrast) && settings.contrast[name]) || settings.contrastDefaults[name];
                    },
                    // Un token se lee de su variable con el analizador propio; si es una expresión
                    // (color-mix(), light-dark()), lo resuelve el navegador: es la única excepción
                    resolved: function (token) {
                        var
                            name = /--[\w-]+/.exec(token)[0],
                            raw = window.getComputedStyle(document.documentElement).getPropertyValue(name).trim(),
                            color = colorEngine.parse(raw),
                            probe
                        ;
                        if (color) {
                            return color;
                        }
                        probe = document.createElement('span');
                        probe.className = className.hidden;
                        probe.style.setProperty('color', token);
                        $root[0].appendChild(probe);
                        color = colorEngine.parse(window.getComputedStyle(probe).color);
                        $root[0].removeChild(probe);

                        return color;
                    },
                    stepped: function (event, value, maximum, small, large) {
                        switch (event.key) {
                            case 'ArrowLeft':
                            case 'ArrowDown': {
                                return value - (event.shiftKey ? small * 10 : small);
                            }
                            case 'ArrowRight':
                            case 'ArrowUp': {
                                return value + (event.shiftKey ? small * 10 : small);
                            }
                            case 'PageDown': {
                                return value - large;
                            }
                            case 'PageUp': {
                                return value + large;
                            }
                            case 'Home': {
                                return 0;
                            }
                            case 'End': {
                                return maximum;
                            }
                            default: {
                                return undefined;
                            }
                        }
                    },
                    columns: function ($all) {
                        var
                            top = $all.first().offset().top,
                            columns = 0
                        ;
                        $all.each(function () {
                            if ($(this).offset().top !== top) {
                                return false;
                            }
                            columns++;
                        });

                        return columns || 1;
                    },
                    paletteGroups: function () {
                        var
                            groups = [],
                            source = settings.palette || [],
                            current = module.get.rgba()
                        ;
                        $.each(source, function (index, group) {
                            if (typeof group === 'string') {
                                if (groups.length === 0 || groups[groups.length - 1].title !== '') {
                                    groups.push({ title: '', colors: [] });
                                }
                                groups[groups.length - 1].colors.push(group);
                            } else if (Array.isArray(group)) {
                                groups.push({ title: '', colors: group });
                            } else {
                                groups.push(group);
                            }
                        });
                        $.each(settings.harmonies ? ['analogous', 'triad', 'complementary'] : [], function (index, name) {
                            groups.push({
                                title: text[name],
                                colors: colorEngine.harmony(current, name, settings.harmonyAvoid, settings.harmonyRange).map(function (color) {
                                    return colorEngine.format(color, 'hex');
                                }),
                            });
                        });
                        if (settings.recents && module.recents.read().length > 0) {
                            groups.push({ title: text.recents, colors: module.recents.read() });
                        }

                        return groups;
                    },
                    paletteHtml: function () {
                        return module.get.paletteGroups().map(function (group) {
                            return templates.paletteGroup(group, className, text);
                        }).join('');
                    },
                },
                set: {
                    value: function (value, fire) {
                        if (!module.adopt(value)) {
                            module.error(error.invalid, value);
                            settings.onInvalid.call(input, value);

                            return;
                        }
                        module.set.invalid(false);
                        module.render(true);
                        if (fire === false) {
                            lastChanged = module.get.value();
                        } else {
                            module.fire.input();
                            module.fire.change();
                        }
                    },
                    invalid: function (invalid) {
                        $root.toggleClass(className.invalid, invalid);
                        if (invalid) {
                            $input.attr('aria-invalid', 'true');
                        } else {
                            $input.removeAttr('aria-invalid');
                        }
                        if ($panel) {
                            $panel.find(selector.textInput).toggleClass(className.error, invalid);
                        }
                    },
                    disabled: function () {
                        $root.addClass(className.disabled);
                        input.disabled = true;
                        if ($trigger) {
                            $trigger.prop('disabled', true);
                        }
                        if ($panel) {
                            $panel.addClass(className.disabled);
                            $panel.find('input, button').prop('disabled', true);
                            $panel.find(selector.sliders).attr('tabindex', '-1');
                        }
                        if (visible) {
                            module.hide(true);
                        }
                    },
                    enabled: function () {
                        $root.removeClass(className.disabled);
                        input.disabled = false;
                        if ($trigger) {
                            $trigger.prop('disabled', false);
                        }
                        if ($panel) {
                            $panel.removeClass(className.disabled);
                            $panel.find('input, button').prop('disabled', false);
                            $panel.find(selector.sliders).attr('tabindex', '0');
                        }
                    },
                    palette: function (palette) {
                        settings.palette = palette;
                        if ($panel) {
                            $panel.find(selector.palette).html(module.get.paletteHtml());
                            module.set.paletteTokens();
                            module.set.paletteMode();
                        }
                    },
                    // Cada muestra lee su token con CSS: sigue los cambios en vivo sin tocar el panel
                    paletteTokens: function () {
                        $panel.find(selector.swatch).each(function () {
                            this.style.setProperty(cssVars.swatch, $(this).attr('data-value'));
                        });
                        $panel.find(selector.swatch).attr('tabindex', '-1').first().attr('tabindex', '0');
                    },
                    paletteMode: function () {
                        $panel.toggleClass(className.paletteOnly, paletteOnly);
                        $panel.find(selector.toggle).text(paletteOnly ? text.more : text.less);
                    },
                    // Bajo el campo, o encima si no cabe, y siempre dentro de la ventana
                    placement: function () {
                        var
                            anchor,
                            size,
                            margin = settings.viewportMargin,
                            width = document.documentElement.clientWidth,
                            height = window.innerHeight,
                            top,
                            left,
                            above = /top/.test(settings.position),
                            right = /right/.test(settings.position)
                        ;
                        if (!visible && !$panel) {
                            return;
                        }
                        anchor = $root[0].getBoundingClientRect();
                        size = $panel[0].getBoundingClientRect();
                        top = above ? anchor.top - size.height - settings.offset : anchor.bottom + settings.offset;
                        if (above && top < margin && anchor.bottom + settings.offset + size.height <= height - margin) {
                            top = anchor.bottom + settings.offset;
                        } else if (!above && top + size.height > height - margin && anchor.top - settings.offset - size.height >= margin) {
                            top = anchor.top - size.height - settings.offset;
                        }
                        left = right ? anchor.right - size.width : anchor.left;
                        top = Math.max(margin, Math.min(top, height - size.height - margin));
                        left = Math.max(margin, Math.min(left, width - size.width - margin));
                        $panel[0].style.setProperty(cssVars.top, Math.round(top) + 'px');
                        $panel[0].style.setProperty(cssVars.left, Math.round(left) + 'px');
                    },
                },
                setting: function (name, value) {
                    module.debug('Changing setting', name, value);
                    if ($.isPlainObject(name)) {
                        $.extend(true, settings, name);
                    } else if (value !== undefined) {
                        if ($.isPlainObject(settings[name])) {
                            $.extend(true, settings[name], value);
                        } else {
                            settings[name] = value;
                        }
                    } else {
                        return settings[name];
                    }
                },
                internal: function (name, value) {
                    if ($.isPlainObject(name)) {
                        $.extend(true, module, name);
                    } else if (value !== undefined) {
                        module[name] = value;
                    } else {
                        return module[name];
                    }
                },
                debug: function () {
                    if (!settings.silent && settings.debug) {
                        if (settings.performance) {
                            module.performance.log(arguments);
                        } else {
                            module.debug = Function.prototype.bind.call(console.info, console, settings.name + ':');
                            module.debug.apply(console, arguments);
                        }
                    }
                },
                verbose: function () {
                    if (!settings.silent && settings.verbose && settings.debug) {
                        if (settings.performance) {
                            module.performance.log(arguments);
                        } else {
                            module.verbose = Function.prototype.bind.call(console.info, console, settings.name + ':');
                            module.verbose.apply(console, arguments);
                        }
                    }
                },
                error: function () {
                    if (!settings.silent) {
                        module.error = Function.prototype.bind.call(console.error, console, settings.name + ':');
                        module.error.apply(console, arguments);
                    }
                },
                performance: {
                    log: function (message) {
                        var
                            currentTime,
                            executionTime,
                            previousTime
                        ;
                        if (settings.performance) {
                            currentTime = Date.now();
                            previousTime = time || currentTime;
                            executionTime = currentTime - previousTime;
                            time = currentTime;
                            performance.push({
                                Name: message[0],
                                Arguments: [].slice.call(message, 1) || '',
                                Element: element,
                                'Execution Time': executionTime,
                            });
                        }
                        clearTimeout(module.performance.timer);
                        module.performance.timer = setTimeout(function () {
                            module.performance.display();
                        }, 500);
                    },
                    display: function () {
                        var
                            title = settings.name + ':',
                            totalTime = 0
                        ;
                        time = false;
                        clearTimeout(module.performance.timer);
                        $.each(performance, function (index, data) {
                            totalTime += data['Execution Time'];
                        });
                        title += ' ' + totalTime + 'ms';
                        if ($allModules.length > 1) {
                            title += ' (' + $allModules.length + ')';
                        }
                        if (performance.length > 0) {
                            console.groupCollapsed(title);
                            if (console.table) {
                                console.table(performance);
                            } else {
                                $.each(performance, function (index, data) {
                                    console.log(data.Name + ': ' + data['Execution Time'] + 'ms');
                                });
                            }
                            console.groupEnd();
                        }
                        performance = [];
                    },
                },
                invoke: function (query, passedArguments, context) {
                    var
                        object = instance,
                        maxDepth,
                        found,
                        response
                    ;
                    passedArguments = passedArguments || queryArguments;
                    context = context || element;
                    if (typeof query === 'string' && object !== undefined) {
                        query = query.split(/[ .]/);
                        maxDepth = query.length - 1;
                        $.each(query, function (depth, value) {
                            var camelCaseValue = depth !== maxDepth
                                ? value + query[depth + 1].charAt(0).toUpperCase() + query[depth + 1].slice(1)
                                : query
                            ;
                            if ($.isPlainObject(object[camelCaseValue]) && (depth !== maxDepth)) {
                                object = object[camelCaseValue];
                            } else if (object[camelCaseValue] !== undefined) {
                                found = object[camelCaseValue];

                                return false;
                            } else if ($.isPlainObject(object[value]) && (depth !== maxDepth)) {
                                object = object[value];
                            } else if (object[value] !== undefined) {
                                found = object[value];

                                return false;
                            } else {
                                module.error(error.method, query);

                                return false;
                            }
                        });
                    }
                    if (isFunction(found)) {
                        response = found.apply(context, passedArguments);
                    } else if (found !== undefined) {
                        response = found;
                    }
                    if (Array.isArray(returnedValue)) {
                        returnedValue.push(response);
                    } else if (returnedValue !== undefined) {
                        returnedValue = [returnedValue, response];
                    } else if (response !== undefined) {
                        returnedValue = response;
                    }

                    return found;
                },
            };
            module.render.panel = function (cssColor, value, force) {
                var
                    panel = $panel[0],
                    alphaColor = colorEngine.format($.extend(module.get.rgba(), { a: 1 }), 'rgb'),
                    hsvText = { saturation: Math.round(state.s * 100), brightness: Math.round(state.v * 100), hue: Math.round(state.h), alpha: Math.round(state.a * 100), value: value },
                    fill = function (template) {
                        return template.replace(/{(\w+)}/g, function (match, name) {
                            return hsvText[name] === undefined ? match : hsvText[name];
                        });
                    },
                    $text = $panel.find(selector.text),
                    ratio = module.get.contrast(),
                    threshold = Number(module.get.contrastSetting('threshold')),
                    passes
                ;
                panel.style.setProperty(cssVars.color, cssColor);
                panel.style.setProperty(cssVars.opaque, alphaColor);
                panel.style.setProperty(cssVars.hue, String(state.h));
                panel.style.setProperty(cssVars.x, String(state.s));
                panel.style.setProperty(cssVars.y, String(1 - state.v));
                panel.style.setProperty(cssVars.hueOffset, String(state.h / 360));
                panel.style.setProperty(cssVars.alphaOffset, String(state.a));
                panel.style.setProperty(cssVars.initial, initial && initial.value !== '' ? (module.get.initialColor()) : 'transparent');
                $panel.toggleClass(className.empty, state.empty);
                $panel.find(selector.area)
                    .attr('aria-valuenow', hsvText.saturation)
                    .attr('aria-valuetext', fill(text.areaValue))
                ;
                $panel.find(selector.hue)
                    .attr('aria-valuenow', hsvText.hue)
                    .attr('aria-valuetext', fill(text.hueValue))
                ;
                $panel.find(selector.alpha)
                    .attr('aria-valuenow', hsvText.alpha)
                    .attr('aria-valuetext', fill(text.alphaValue))
                ;
                $panel.find(selector.current).attr('aria-label', fill(text.current));
                $panel.find(selector.initial).attr('aria-label', text.initial.replace('{value}', initial ? initial.value : ''));
                if (force || $text[0] !== document.activeElement) {
                    $text.val(value);
                }
                $panel.find(selector.formatButton).each(function () {
                    var active = $(this).attr('data-format') === state.format;
                    $(this).toggleClass(className.active, active).attr('aria-pressed', String(active));
                });
                module.render.channels(force);
                if (ratio !== null) {
                    passes = ratio >= threshold;
                    $panel.find(selector.contrast).toggleClass(className.failing, !passes);
                    $panel.find(selector.contrastValue).text(text.contrastValue.replace('{ratio}', ratio));
                    $panel.find(selector.contrastState).text((passes ? text.contrastPass : text.contrastFail).replace('{threshold}', threshold));
                    $panel.find(selector.contrastIcon).attr('class', (passes ? settings.icons.pass : settings.icons.fail) + ' icon contrast-icon');
                }
            };
            module.render.channels = function (force) {
                var
                    rgba = module.get.rgba(),
                    hsl = colorEngine.toHsl(rgba),
                    isHsl = state.format === 'hsl',
                    values = isHsl
                        ? [Math.round(hsl.h), Math.round(hsl.s * 100), Math.round(hsl.l * 100)]
                        : [rgba.r, rgba.g, rgba.b],
                    limits = isHsl ? [360, 100, 100] : [255, 255, 255],
                    labels = isHsl ? [text.hueChannel, text.saturation, text.lightness] : [text.red, text.green, text.blue]
                ;
                if (!settings.channels) {
                    return;
                }
                values.push(Math.round(rgba.a * 100));
                limits.push(100);
                labels.push(text.alpha);
                $panel.find(selector.channel).each(function (index) {
                    var $channel = $(this);
                    $channel.attr({ 'data-max': limits[index], 'aria-label': labels[index] });
                    $channel.closest(selector.channelField).find(selector.channelLabel).text(labels[index]);
                    if (force || this !== document.activeElement) {
                        $channel.val(values[index]);
                    }
                });
            };
            module.get.initialColor = function () {
                var color = initial.value === '' ? null : (/^var\(/i.test(initial.value) ? module.get.resolved(initial.value) : colorEngine.parse(initial.value));

                return color ? colorEngine.format(color, 'rgb') : 'transparent';
            };
            if (methodInvoked) {
                if (instance === undefined) {
                    module.initialize();
                }
                module.invoke(query);
            } else {
                if (instance !== undefined) {
                    instance.invoke('destroy');
                }
                module.initialize();
            }
        });

        return returnedValue !== undefined
            ? returnedValue
            : this;
    };

    // El motor de color, para los proyectos y las pruebas
    $.fn.colorpicker.color = colorEngine;

    $.fn.colorpicker.settings = {

        name: 'Colorpicker',
        namespace: 'colorpicker',

        silent: false,
        debug: false,
        verbose: false,
        performance: true,

        // Formato del valor: hex, rgb, hsl u oklch. `formats`: selector en el panel (true, o la lista)
        format: 'hex',
        formats: false,
        // Hexadecimal en mayúsculas
        uppercase: false,
        // Barra de transparencia; sin ella, un alfa pegado se descarta
        alpha: false,
        // Siempre visible, sin popup (Spectrum: flat)
        inline: false,
        // Permite el valor vacío, distinto de blanco
        allowEmpty: false,
        // Inicial junto al nuevo; pulsar el inicial lo recupera
        showInitial: true,
        // Campo de texto en el panel
        showInput: true,
        // Canales numéricos (rgb o hsl, según el formato)
        channels: false,
        // Aceptar y Cancelar; sin ellos, cada cambio se aplica al instante
        buttons: true,
        // Con botones, un clic fuera cancela; true lo confirma. Sin botones, siempre se queda lo elegido
        clickoutFiresChange: false,
        // Muestras: valores o tokens `var(--x)`, en una lista o en grupos {title, colors: []}
        palette: [],
        paletteOnly: false,
        // Botón para pasar de solo paleta al área libre y volver
        togglePaletteOnly: false,
        // Una muestra de token guarda `var(--x)` en vez de su valor
        saveToken: false,
        // Colores recientes: true o la clave con la que se guardan en localStorage
        recents: false,
        recentsLimit: 8,
        // Grupos de armonías del color actual; apartan el tono `harmonyRange` grados de los de `harmonyAvoid`
        harmonies: false,
        harmonyAvoid: [],
        harmonyRange: 18,
        // Contraste en vivo (solo informa): true, un color de fondo o {against, threshold}
        contrast: false,
        contrastDefaults: { against: '#ffffff', threshold: 4.5 },
        // Cuentagotas donde exista EyeDropper; donde no, no aparece
        eyedropper: false,
        // Lado preferido del popup: bottom left, bottom right, top left o top right
        position: 'bottom left',
        offset: 6,
        viewportMargin: 8,
        // Dónde se cuelga el popup: fuera de cualquier overflow, transform o capa
        context: 'body',
        // Sin botones, cierra al elegir una muestra
        hideOnChoose: false,
        disabled: false,
        fireOnInit: false,
        // Atributos: data-<dataPrefix>-<ajuste>
        dataPrefix: 'colorpicker',

        onInput: function (value, color) {},
        onChange: function (value, color) {},
        onCancel: function (value) {},
        onShow: function () {},
        onHide: function () {},
        onInvalid: function (value) {},

        error: {
            method: 'The method you called is not defined',
            invalid: 'The value is not a color',
            metadata: 'The data attribute is not valid JSON',
        },

        metadata: {
            options: ['format', 'formats', 'uppercase', 'alpha', 'inline', 'allowEmpty', 'showInitial', 'showInput', 'channels', 'buttons', 'clickoutFiresChange', 'palette', 'paletteOnly', 'togglePaletteOnly', 'saveToken', 'recents', 'harmonies', 'contrast', 'eyedropper', 'position', 'hideOnChoose', 'disabled'],
        },

        className: {
            root: 'ui colorpicker input',
            panel: 'ui colorpicker panel',
            trigger: 'trigger',
            hidden: 'hidden',
            inline: 'inline',
            visible: 'visible',
            active: 'active',
            disabled: 'disabled',
            readonly: 'readonly',
            invalid: 'invalid',
            error: 'error',
            empty: 'empty',
            failing: 'failing',
            paletteOnly: 'palette-only',
        },

        cssVars: {
            color: '--colorpicker-color',
            opaque: '--colorpicker-opaque',
            initial: '--colorpicker-initial',
            hue: '--colorpicker-hue',
            x: '--colorpicker-x',
            y: '--colorpicker-y',
            hueOffset: '--colorpicker-hue-offset',
            alphaOffset: '--colorpicker-alpha-offset',
            top: '--colorpicker-top',
            left: '--colorpicker-left',
            swatch: '--swatch',
        },

        selector: {
            root: '.ui.colorpicker',
            panel: '.ui.colorpicker.panel',
            uiInput: '.ui.input',
            input: 'input',
            area: '.area',
            bar: '.bar',
            hue: '.hue.bar',
            alpha: '.alpha.bar',
            sliders: '.area, .bar',
            palette: '.palette',
            swatch: '.palette .ui.swatch',
            initial: '.compare .initial',
            current: '.compare .current',
            formatButton: '.formats .button',
            clear: '.clear.button',
            eyedropper: '.eyedropper.button',
            toggle: '.toggle.button',
            accept: '.accept.button',
            cancel: '.cancel.button',
            text: '.value-input input',
            textInput: '.value-input',
            channel: '.channel input',
            channelField: '.channel',
            channelLabel: 'label',
            contrast: '.contrast',
            contrastValue: '.contrast .ratio',
            contrastState: '.contrast .status',
            contrastIcon: '.contrast .contrast-icon',
            focusable: 'button:not(:disabled):not([tabindex="-1"]), input:not(:disabled), [tabindex="0"]',
            autofocus: '.area, .ui.swatch[tabindex="0"], input, button',
        },

        icons: {
            trigger: 'dropdown',
            clear: 'ban',
            eyedropper: 'eye dropper',
            pass: 'check circle',
            fail: 'exclamation triangle',
        },

        text: {
            panel: 'Color picker',
            trigger: 'Choose color',
            area: 'Saturation and brightness',
            areaRole: '2D slider',
            areaValue: '{saturation}% saturation, {brightness}% brightness, {value}',
            hue: 'Hue',
            hueValue: '{hue} degrees, {value}',
            alpha: 'Opacity',
            alphaValue: '{alpha}%, {value}',
            input: 'Color value',
            initial: 'Previous color: {value}. Press to restore it',
            current: 'New color: {value}',
            format: 'Color format',
            formats: {
                hex: 'HEX',
                rgb: 'RGB',
                hsl: 'HSL',
                oklch: 'OKLCH',
            },
            channels: 'Color channels',
            red: 'Red',
            green: 'Green',
            blue: 'Blue',
            hueChannel: 'H',
            saturation: 'S',
            lightness: 'L',
            palette: 'Palette',
            swatch: 'Use {value}',
            recents: 'Recent',
            analogous: 'Analogous',
            triad: 'Triad',
            complementary: 'Complementary',
            contrast: 'Contrast',
            contrastValue: '{ratio}:1',
            contrastPass: 'Passes {threshold}:1',
            contrastFail: 'Below {threshold}:1',
            eyedropper: 'Pick a color from the screen',
            clear: 'No color',
            more: 'More colors',
            less: 'Fewer colors',
            accept: 'Accept',
            cancel: 'Cancel',
        },

        templates: {
            escape: function (string) {
                return String(string)
                    .replace(/&/g, '&amp;')
                    .replace(/"/g, '&quot;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                ;
            },
            root: function (classNames) {
                return '<div class="' + classNames.root + '"></div>';
            },
            trigger: function (classNames, text) {
                return '<button type="button" class="' + classNames.trigger + '" aria-haspopup="dialog" aria-expanded="false" aria-label="' + $.fn.colorpicker.settings.templates.escape(text.trigger) + '"></button>';
            },
            slider: function (kind, label, maximum, role) {
                var escape = $.fn.colorpicker.settings.templates.escape;

                return '<div class="' + kind + '" role="slider" tabindex="0" aria-label="' + escape(label) + '" aria-valuemin="0" aria-valuemax="' + maximum + '"' + (role ? ' aria-roledescription="' + escape(role) + '"' : '') + '><span class="thumb"></span></div>';
            },
            paletteGroup: function (group, classNames, text) {
                var escape = $.fn.colorpicker.settings.templates.escape;

                return '<div class="group"' + (group.title ? ' role="group" aria-label="' + escape(group.title) + '"' : '') + '>'
                    + (group.title ? '<div class="title">' + escape(group.title) + '</div>' : '')
                    + '<div class="swatches">'
                    + group.colors.map(function (entry) {
                        var
                            value = typeof entry === 'string' ? entry : entry.value,
                            label = typeof entry === 'string' ? entry : (entry.label || entry.value)
                        ;

                        return '<button type="button" class="ui mini swatch" data-value="' + escape(value) + '" aria-label="' + escape(text.swatch.replace('{value}', label)) + '" title="' + escape(label) + '"></button>';
                    }).join('')
                    + '</div></div>';
            },
            panel: function (model, classNames, text) {
                var
                    templates = $.fn.colorpicker.settings.templates,
                    escape = templates.escape,
                    icons = $.fn.colorpicker.settings.icons,
                    html = '<div class="' + classNames.panel + '" id="' + model.id + '"' + (model.inline ? '' : ' role="dialog" aria-modal="true"') + ' aria-label="' + escape(text.panel) + '">'
                ;
                html += '<div class="picker">'
                    + templates.slider('area', text.area, 100, text.areaRole)
                    + '<div class="controls">';
                if (model.eyedropper) {
                    html += '<button type="button" class="ui tiny icon basic eyedropper button" aria-label="' + escape(text.eyedropper) + '" title="' + escape(text.eyedropper) + '"><i class="' + icons.eyedropper + ' icon" aria-hidden="true"></i></button>';
                }
                html += '<div class="bars">'
                    + templates.slider('hue bar', text.hue, 360)
                    + (model.alpha ? templates.slider('alpha bar', text.alpha, 100) : '')
                    + '</div>'
                    + '<div class="compare">'
                    + (model.initial ? '<button type="button" class="initial"></button>' : '')
                    + '<span class="current" role="img"></span>'
                    + '</div>'
                    + '</div>';
                if (model.formats.length > 0) {
                    html += '<div class="ui mini fluid buttons formats" role="group" aria-label="' + escape(text.format) + '">'
                        + model.formats.map(function (name) {
                            return '<button type="button" class="ui button" data-format="' + escape(name) + '">' + escape(text.formats[name] || name) + '</button>';
                        }).join('')
                        + '</div>';
                }
                if (model.input || model.clear) {
                    html += '<div class="values">';
                    if (model.input) {
                        html += '<div class="ui small fluid input value-input"><input type="text" autocomplete="off" spellcheck="false" aria-label="' + escape(text.input) + '"></div>';
                    }
                    if (model.clear) {
                        html += '<button type="button" class="ui small icon basic clear button" aria-label="' + escape(text.clear) + '" title="' + escape(text.clear) + '"><i class="' + icons.clear + ' icon" aria-hidden="true"></i></button>';
                    }
                    html += '</div>';
                }
                if (model.channels) {
                    html += '<div class="channels" role="group" aria-label="' + escape(text.channels) + '">'
                        + [0, 1, 2].concat(model.alpha ? [3] : []).map(function () {
                            return '<div class="channel ui mini input"><label></label><input type="text" inputmode="numeric" autocomplete="off"></div>';
                        }).join('')
                        + '</div>';
                }
                html += '<div class="palette" role="group" aria-label="' + escape(text.palette) + '"></div>';
                if (model.contrast) {
                    html += '<div class="contrast"><span class="label">' + escape(text.contrast) + '</span><span class="ratio"></span><i class="contrast-icon icon" aria-hidden="true"></i><span class="status"></span></div>';
                }
                if (model.buttons || model.toggle) {
                    html += '<div class="actions">'
                        + (model.toggle ? '<button type="button" class="ui mini basic toggle button"></button>' : '')
                        + (model.buttons ? '<button type="button" class="ui mini basic cancel button">' + escape(text.cancel) + '</button><button type="button" class="ui mini primary accept button">' + escape(text.accept) + '</button>' : '')
                        + '</div>';
                }

                return html + '</div>';
            },
        },

    };
})(jQuery, window, document);
