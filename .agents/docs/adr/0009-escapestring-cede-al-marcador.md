# 0009 — `escapeString()` cede al marcador y queda obsoleta

- **Estado:** Aceptada
- **Fecha:** 2026-09-14
- **Decide:** Arquitecto, dentro del mandato del PO («Trabaja. Adelante.»; lote 4 del mapa)
- **Estructural:** sí (contrato de un ayudante global que usan los clones)

## En cristiano

`escapeString()` era la única forma de escapar texto para SQL que tenía el framework, y no es
segura en todos los servidores. Dentro del framework, sus 23 usos pasan a enviar el valor por
marcador, que es lo que ya hace el resto del SQL. La función se queda, marcada como obsoleta y
diciendo por qué, para no romper a los clones que la usan. No se toca la configuración de la
conexión con la base de datos.

## Contexto

- `escapeString()` es `addslashes(stripslashes($str))` (`src/app/core/AppHelpers.php:2805`).
- El 20 §7 («EL HALLAZGO MÁS PROFUNDO»), medido entonces y comprobado hoy:
  - la aplicación no fija nunca `sql_mode`;
  - la conexión solo ejecuta `SET NAMES` y `SET time_zone`, en
    `piecesphp/database` `Database.php:240`;
  - con `NO_BACKSLASH_ESCAPES` activo en el servidor, `addslashes()` produce `\'`, y la comilla
    sigue cerrando la cadena;
  - el juego de caracteres es `utf8mb4`, así que la vía multibyte no aplica.
- Hoy: 23 llamadas en 13 archivos.
  - Mappers: OrganizationMapper 5, UsersModel 3, StateMapper 2, CountryMapper 2, CityMapper 2,
    y 1 en cada uno de SystemApprovals, Publication, PublicationCategory, NewsCategory,
    Documents y Point.
  - Además, DataTablesHelper 2 y UsersController 1.
- La campaña ya migró a marcador todo el SQL de listados y búsquedas: lotes AX-AZ, 2 y 3a.

## Decisión

1. Los 23 usos del framework pasan a marcador. Si alguno no puede, porque es un identificador
   o un fragmento sin segmento preparado, se declara con su motivo; no se deja a medias.
2. `escapeString()` se queda con `@deprecated`. Su docblock dice que depende de `sql_mode`, y
   que un valor para SQL va por marcador.
3. Una prueba: ningún uso de `escapeString()` en `src/app` fuera de su definición. Si reaparece,
   falla.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| Fijar `sql_mode` en la conexión, quitando `NO_BACKSLASH_ESCAPES` | Vive en el paquete `database`, así que hay que versionarlo para que llegue. Cambia el modo de TODAS las consultas del clon. Y trata el síntoma: el texto seguiría concatenado |
| Reescribir `escapeString()` con `PDO::quote()` | La función es global y no tiene conexión. Cambiaría su salida, porque `quote()` añade comillas, y rompería a los clones que la usan |
| Borrarla | Rompe a los clones que la llaman. La MAJOR lo permite, pero no hace falta: basta con que el framework deje de usarla y lo diga |

## Consecuencias

- **Lo bueno:** el framework deja de depender de un ajuste del servidor que no controla.
- **Lo malo:**
  - un clon que la use sigue expuesto hasta que migre, y el CHANGELOG se lo dice;
  - los comodines `%` y `_` del `LIKE`: pasar a marcador no los neutraliza. Si una búsqueda
    tiene que tratarlos como texto, necesita escaparlos aparte; se decide sitio por sitio en la
    auditoría.

## Reversión

1. Quitar la prueba de «ningún uso».
2. Quitar el `@deprecated`.
3. Revertir los commits de migración, que son atómicos por módulo.

La reversión devuelve el riesgo.

## Verificación

- Los 23 sitios migrados o declarados, cada uno con su prueba de rechazo vista fallar (la
  comilla suelta, como en el lote 3a).
- `bin/cli gates`, `verify-integrity` y `bin/phpstan` en verde.
