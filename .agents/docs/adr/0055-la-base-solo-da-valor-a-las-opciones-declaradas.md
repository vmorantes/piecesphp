# 0055 — La base solo da valor a las opciones declaradas

- **Estado:** Aceptada
- **Fecha:** 2026-10-07
- **Decide:** el PO (2026-10-07, P109: «ok, pero que sea claro y no haya duplicidad y esté bien documentado, porque si
  no es confuso. La propuesta robusta, no la corta»), sobre la propuesta del arquitecto en
  `planes/p108-recuentos-y-p109-cargador-de-configuracion.md`. Es núcleo transversal, así que se habló antes (regla 30).
- **Estructural:** sí (cambia de quién es la última palabra sobre un ajuste, y añade algo que cada módulo declara)

## Contexto

Medido el 2026-10-07 sobre `a94c7bb1` (`v8.0.6`):

- `src/index.php:155-161`: en **cada petición**, `SettingsModel::getConfigurations()`
  (`SettingsModel.php:114-128`) devuelve `nombre => valor` de **todas** las filas de la tabla de configuración, y cada
  una se aplica con `set_config($name, $value)`.
- `set_config()` escribe en la misma estructura donde vive la configuración fija de `app/config/config.php`, **sin
  lista de protegidas**: si una fila se llama igual que un ajuste fijo, gana la fila.
- La tabla **no tiene catálogo**: el nombre es una columna de texto y cada pantalla guarda el que le toca.
- Por tanto **el nombre de una fila es, de hecho, la decisión de qué ajuste del framework se cambia**, incluidos
  ajustes de los que depende el control de acceso (`index.php:727-728` lee uno de ellos).
- Eso explica por qué una pantalla de configuración mal acotada tiene efectos que no se ven en su propio código: es lo
  que se cerró en la acción genérica en la `v8.0.6` (lista de permitidos), **pero el cargador sigue abierto a
  cualquier otra vía que escriba una fila**.

## Decisión

**La base de datos solo puede dar valor a las opciones que la aplicación declara como suyas.** Lo que no está
declarado no se aplica.

1. **Catálogo de opciones declaradas.** Cada opción que la base puede dar se declara con su **nombre** y, cuando se
   pueda, su **forma** (texto, entero, color, JSON). El catálogo lo compone el framework con lo que declaran sus
   pantallas y sus módulos.
2. **Una sola fuente, sin duplicidad** (condición del PO). Cada opción se declara **una vez**, donde vive su pantalla o
   su módulo. Ni el cargador, ni `index.php`, ni la pantalla repiten la lista: la leen del catálogo. Donde ya hay una
   lista con el mismo papel (`GENERIC_SAVE_ALLOWED`, `ROOT_ONLY_CONFIG_KEYS`), o se alimenta del catálogo o se dice en
   su docblock qué pregunta distinta responde.
3. **Un módulo declara las suyas** con el mismo patrón con el que registra rutas, para que un clon añada sus opciones
   sin tocar el núcleo.
4. **Una fila no declarada no se aplica**, la configuración fija se queda como está, y el hecho **se registra una vez**
   con `log_exception`. Como en P61 («navegar no escribe»): el defecto se enseña, no se tapa ni se arregla solo.
5. **Primera versión en modo aviso.** La primera versión **registra sin bloquear** y deja ver el catálogo y las filas
   que no lo cumplen; la siguiente ya no las aplica. Así se descubre una opción legítima olvidada antes de que deje de
   funcionar.
6. **Claro y documentado** (condición del PO):
   - un sitio donde ver el catálogo completo y qué filas de la base no lo cumplen (la vista «Sistema» o `bin/cli`);
   - su página en `source-docs/` para quien desarrolla un clon, con el ejemplo de cómo declara un módulo sus opciones;
   - su entrada en `.agents/context/`;
   - el `CHANGELOG` con la receta, como cambio incompatible.

## Alternativas descartadas

| Alternativa | Por qué no |
| :-- | :-- |
| Lista corta de claves fijas protegidas | El PO la descartó expresamente. Hay que mantenerla a mano: la clave fija que alguien añada mañana y no apunte queda desprotegida, que es la trampa que esta campaña viene cerrando |
| Dejarlo en las pantallas, con listas de permitidos por acción | Es lo que ya se hizo en la `v8.0.6`, y no alcanza: cubre una vía, no el cargador |
| Filtrar por prefijo (solo nombres `app_*`) | Obliga a renombrar las filas de todas las instalaciones y no dice nada de la forma del valor |
| Bloquear desde la primera versión | Una opción legítima olvidada dejaría de aplicarse sin aviso, en todos los clones a la vez |

## Consecuencias

**Buenas.** La configuración fija deja de depender de lo que haya en una tabla. Las filas ya plantadas en una
instalación dejan de tener efecto al actualizar, sin tocar la base. Aparece, por primera vez, un sitio donde está
escrito qué opciones existen; eso sirve a quien desarrolla un clon tanto como a la seguridad.

**Malas.** Es el cambio más grande de los dos: toca el arranque y obliga a revisar cada pantalla de configuración. Si
el catálogo queda incompleto, una opción legítima deja de aplicarse, y el síntoma («esto ya no se guarda») no apunta a
su causa; de ahí el modo aviso y el censo previo. Y un clon con opciones propias tiene que declararlas: es trabajo para
quien ya tiene su instalación.

## En cristiano

La aplicación tiene ajustes escritos en su código y ajustes que se cambian desde el panel y viven en la base de datos.
Al arrancar, los de la base se ponían encima de los del código, sin preguntar: cualquier fila con el nombre adecuado
mandaba sobre el código, incluidos los ajustes que deciden quién entra a dónde. Ahora la aplicación lleva una lista de
qué ajustes puede cambiar la base; los demás los manda el código. Lo que aparezca en la base y no esté en la lista no
se aplica, y queda anotado para que puedas borrarlo. Cada módulo apunta sus propios ajustes en esa lista, igual que
apunta sus páginas.

## Reversión

En orden inverso, y en términos de estado:

1. El cargador vuelve a aplicar todas las filas (quitar la comprobación del catálogo): una línea.
2. Las declaraciones de los módulos y el catálogo pueden quedarse sin hacer daño: sin la comprobación, no deciden nada.
3. La página de `source-docs/`, la entrada de `context/` y el aviso de la vista «Sistema» se retiran, o se marcan como
   no vigentes.
4. **Es reversión limpia** mientras el catálogo no haya hecho que alguien borre filas de su base creyéndolas inútiles:
   lo que se borró no vuelve. Por eso el aviso dice qué filas no se aplican, pero no las borra.

## Verificación

- Una fila con el nombre de un ajuste fijo **no cambia** ese ajuste, y deja su línea en el registro.
- Una fila de una opción declarada **sí** se aplica: canarios con los colores de marca, el título y el correo.
- Un módulo de ejemplo declara una opción propia y funciona, sin tocar el núcleo.
- `grep` del catálogo: ninguna opción declarada dos veces.
- Prueba de rechazo: si se quita la comprobación, la prueba se pone roja.
- Censo previo, en el reporte: qué nombres guarda hoy la base de esta instalación, y cuáles quedarían fuera.
