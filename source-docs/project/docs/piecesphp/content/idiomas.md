# Idiomas y traducciones

Cómo decide PiecesPHP en qué idioma sale un texto, en PHP y en JavaScript, y qué hace cuando no encuentra la traducción.
Lo que se configura en `lang.php` (los idiomas permitidos, cómo se elige el de cada petición) está en
[Los archivos de configuración, `lang.php`](configuration.md#langphp).

## Las tres palabras

- **El idioma base** es `default_lang` (`config.php`). Es el idioma con el que la aplicación cae siempre por defecto.
  En el HTML va en el atributo `dlang` de `<html>`, y el JavaScript lo lee en `pcsphpGlobals.defaultLang`.
- **El idioma actual** es `app_lang`: el de la petición, que cambia por la URL, el navegador o la cookie. En el
  JavaScript, `pcsphpGlobals.lang`.
- **La clave** es el propio texto en español: `__($grupo, 'Guardar')`. La interfaz se escribe en español dentro de
  `__()`, y ese texto es a la vez la clave y lo que se ve si no hay traducción.

`src/app/lang/default.php` **no es un idioma**: es un archivo común que da un valor a una clave sin importar el
idioma (por ejemplo, los nombres de los idiomas del selector).

## Cuando falta una traducción

Que un texto sin traducir salga con su clave en español **es lo querido, no un defecto**: garantiza que siempre haya
contenido, en el idioma en que trabajan quienes implementan.

### En PHP: `__()`

`__($grupo, $texto)` (es `Config::i18n()`, `src/app/core/Config.php:591`) busca, en este orden:

1. el idioma actual;
2. `default.php`;
3. y si tampoco está, devuelve la clave.

No cae al idioma base: una página en inglés sin la traducción de un texto lo enseña en español (la clave), aunque el
idioma base sea otro.

`lang($grupo, $texto, $idioma)` pide el texto en un idioma concreto: busca ese idioma, luego `default.php` y luego el
idioma actual (si el idioma pedido es el base, solo los dos primeros), y si no, la clave.

### En JavaScript: `_i18n()`

`_i18n($grupo, $texto)` (`src/statics/core/js/configurations.js`) busca, en este orden:

1. el idioma actual;
2. el idioma base;
3. y si tampoco está, devuelve la clave.

Si el idioma actual no tiene archivo de traducciones, usa entero el del idioma base. Funciona así desde 2019.

**La diferencia con el PHP es a propósito y se ve en un proyecto cuyo idioma base no es el español**: en una página en
español, un texto que existe en el idioma base y no en `es.js` sale en el idioma base. Si eso no es lo que quieres para
un texto, ponlo también en `es.js`.

## Dónde viven las traducciones

| Qué | Dónde | Forma |
| :-- | :-- | :-- |
| Textos del PHP, globales | `src/app/lang/<código>.php` y sus subcarpetas por grupo | Devuelven `grupo => [clave => traducción]` |
| Textos del PHP, de un módulo | `src/app/classes/<Módulo>/lang/<código>.php` | Igual, inyectados con `LangInjector` |
| Textos del JavaScript | `src/statics/core/js/translations/<código>.js` | Definen `PCSPHP_TRANSLATIONS_<CÓDIGO>`, con grupos y claves |
| Nombres de lugares (países, ciudades) | `app/lang/locationBackend/names/<idioma>.php`, del proyecto | Devuelven `['Nombre guardado' => 'Traducción']` |

Un idioma permitido sin su archivo no rompe nada: el cargador lo salta y se ve la clave.

## Añadir un idioma

1. Descomenta su línea en `allowed_langs` (`lang.php`) y, si es un idioma que el framework no trae en sus tablas, sus
   entradas de `lang.php` (ver [Los archivos de configuración](configuration.md#langphp)).
2. Crea sus diccionarios del PHP: `src/app/lang/<código>.php` y el `<código>.php` de cada módulo que lo necesite.
3. Para el JavaScript, crea `src/statics/core/js/translations/<código>.js` con `var PCSPHP_TRANSLATIONS_<CÓDIGO> = {…}`
   (copia la forma de `en.js`) y recompila con `cd src && npx gulp js-vendor` (toma todos los `translations/*.js`). **Desde la `10.11.0` se registra solo**: antes había
   que descomentar su entrada en `configurations.js`, un archivo del núcleo que la actualización pisaba, y una entrada sin
   su archivo rompía `configurations.js` entero.
4. Revisa lo que falta con `bin/cli scan-missing-lang --lang=<código>` (ver [Terminal](terminal.md)); `--lang` revisa ese
   idioma aunque esté en `no_scan_langs`, y `--exclude-dir=<carpeta>` deja fuera lo que no quieras analizar.

## Cambiar una clave

Una clave es un texto: si lo cambias, la traducción de la clave vieja deja de usarse. **El framework lista en cada
`CHANGELOG` las claves que desaparecen**, para que un proyecto con traducciones propias las ponga en las nuevas; quien
mantiene el framework las saca con `bin/claves-traduccion <desde> <hasta>`.
