# Pruebas útiles en desarrollo

> Para fines de ejecución local se usa ```$ bin/cli ``` para declarar explícitamente que es loca, que es lo mismo que usar ```$ php index.php cli --local ```.

## Verificación de integridad

```bash
bin/cli verify-integrity                      # comprueba
bin/cli verify-integrity update-snapshot=yes  # regenera la instantánea
```

Devuelve **código de salida 1** si algo falla, así que sirve tal cual en CI.

Comprueba dos cosas sobre los 779 archivos PHP de `src/app` e `index.php`:

1. **Docblocks sin cerrar.** Un comentario de bloque que no cierra, y —lo importante— un
   docblock que se ha tragado una declaración de función.
2. **Firmas desaparecidas.** Compara el inventario de funciones y métodos contra
   `files/dev/integrity-signatures.json`, que se versiona. Solo reporta desapariciones:
   una firma nueva es trabajo normal, que algo deje de existir no lo es.

**Por qué existe.** Una sesión que solo tocaba docblocks dejó uno sin cerrar; el
comentario se tragó la declaración siguiente y ese método dejó de existir.
**`php -l` no lo detecta** —un docblock sin cerrar no es un error de sintaxis— y PHPStan
tampoco lo señaló. Se reprodujo el fallo para validar la tarea: `php -l` responde «No
syntax errors» y `verify-integrity` lo caza por las dos vías.

Usa el analizador léxico de PHP, no expresiones regulares sobre el texto: `/*` aparece
dentro de cadenas —`'image/*'` es el caso típico— y contarlo a pelo daba 32 falsos
positivos en las vistas.

Cuando un cambio de firmas sea intencionado, regenera la instantánea y **commitéala con
el mismo cambio**.

## La línea base de PHPStan

`PHPStanResult.Summary.baseline.txt` **no es una corrida viva**: es el punto de
comparación congelado. `bin/phpstan` reescribe `PHPStanResult.txt` y
`PHPStanResult.Summary.txt` en cada ejecución, pero el `.baseline.txt` solo cambia
cuando **decidimos aceptar** un recuento nuevo.

Se versiona a propósito, por lo mismo que la instantánea de integridad: un clon limpio o
CI no tienen contra qué comparar sin él. Y `PHPStanResult.txt` también, porque
`bin/tools/refactorization/Rector.php` lee de ahí la lista de archivos que analiza.

```bash
bin/phpstan                                                  # corrida viva
cp PHPStanResult.Summary.txt PHPStanResult.Summary.baseline.txt   # aceptar base nueva
```

Regenerar la base es una decisión, no un paso rutinario: hazlo solo cuando el recuento
nuevo esté justificado, y **commitéalo con el cambio que lo justifica**.

## Unitarias

### Quién juzga cada suite

**Una prueba vale más cuando quien juzga no es quien produjo el resultado** (T21). Esta tabla
está para saber, de un vistazo, cuáles se apoyan en un juez externo y cuáles se creen a sí
mismas — las segundas son las que hay que mirar con más cuidado.

| Suite | Quién juzga |
| :-- | :-- |
| `core/scheme-sql-round-trip` | **MariaDB** — aplica los dos scripts de verdad |
| `core/db-backup-round-trip` | **MariaDB** + `password_verify()` |
| `core/mapper-finders` | **MariaDB** |
| `core/otp-fresh-user` | **MariaDB** |
| `core/helpers-directories` | **El sistema de archivos** |
| `core/http-client` | **Un servidor HTTP real** (ojo: `webhook.site` ajeno, caducará) |
| `core/database-exporter` | Base de datos y archivo |
| `core/otp-write-separation` | **Mixta** — tres comprobaciones leen el cuerpo del método |
| `core/meta-property-hybrid` | **Se juzga sola** (reflexión) |
| `core/session-user` | **Se juzga sola** (valores devueltos) |
| `functions/systemOutFormatted` | **Se juzga sola** |
| `verify-integrity` | **Se juzga sola**, salvo el analizador léxico de PHP |


- PiecesPHP\Core\Helpers\Directories
    - Se probaron las siguientes funcionalidades:
        - Normalización de rutas
        - FileObject y Enlaces Simbólicos
        - DirectoryObject Scan y No-Recursión en Symlinks
        - FilesIgnore (Exclusión e Inclusión)
        - Borrado Seguro (Trust the Path)
    - src/app/core/system-controllers/local-tests/UnitTest-Helpers_Directories.php
```bash
bin/cli unit-tests:core/helpers-directories
```
- PiecesPHP\Core\Http\HttpClient
    - Se probaron las siguientes funcionalidades:
        - GET con parámetros de consulta
        - POST con cuerpo JSON
        - Fusión con override_defaults = true
        - Fusión con override_defaults = false
        - Timeout configurado
    - src/app/core/system-controllers/local-tests/UnitTest-HttpClient.php
```bash
bin/cli unit-tests:core/http-client
```
- Buscadores de mapper (`getBy`, `lastModifiedElement`, `getByMultipleCriteries`)
    - Congela el contrato de los buscadores estáticos antes de tocar la nulabilidad.
    - Se probaron:
        - `getBy()` con id inexistente devuelve `null`
        - `getBy()` sin el flag devuelve `\stdClass`
        - `getBy()` con el flag devuelve una instancia del propio mapper
        - `lastModifiedElement()` respeta el mismo contrato, y sus dos ramas coinciden
          en si hay resultado
        - `getByMultipleCriteries()` sin coincidencia devuelve `null`
    - **Es de solo lectura**: no inserta, no actualiza y no borra. Descubre un id
      existente en ejecución y omite el caso «encontrado» si la tabla está vacía.
    - **Recorre mappers reales a propósito.** `getBy` no se hereda: está copiado en 26
      mappers concretos, así que una prueba contra un mapper de juguete sería una copia
      más y no protegería ninguno.
    - src/app/core/system-controllers/local-tests/UnitTest-MapperFinders.php
```bash
bin/cli unit-tests:core/mapper-finders
```
- Sesión y usuario (`getLoggedFrameworkUser`, `SessionToken`)
    - **Pruebas de caracterización, no de aspiración**: describen el comportamiento
      ACTUAL, incluido el defectuoso, para poder cambiarlo con red.
    - Se probaron:
        - `getLoggedFrameworkUser()` sin sesión devuelve `null`, y es estable
        - Encadenar sobre ese resultado sin comprobar **falla** — la forma exacta de los
          123 errores de nulabilidad
        - `SessionToken::getJWTReceived()` devuelve **cadena vacía**, nunca `null`
        - `isActiveSession()` con entradas inválidas devuelve `false` sin lanzar
        - Sin sesión activa no hay usuario: las dos vías coinciden
    - Cuando la ventana de nulabilidad cambie el contrato, varias fallarán. **Ese fallo
      es la señal**, no un problema: cada prueba dice qué se espera que pase entonces.
    - src/app/core/system-controllers/local-tests/UnitTest-SessionUser.php
```bash
bin/cli unit-tests:core/session-user
```
- Separación de lectura y escritura en OTP
    - **Dos comprobaciones son ESTRUCTURALES a propósito**: la versión de comportamiento
      exigiría crear un usuario sin registros —escribir en una base ajena— y además no
      fallaría, porque el relleno masivo que había en `routes()` tapaba el defecto.
    - Se probaron:
        - `getOTPData()` y `getTOTPData()` no contienen ninguna escritura
        - `UserSystemFeaturesRoutes::routes()` no consulta ni escribe
        - Un intento de credenciales fallido no cambia el conteo de filas (solo lectura)
    - src/app/core/system-controllers/local-tests/UnitTest-OTPWriteSeparation.php
```bash
bin/cli unit-tests:core/otp-write-separation
```
- `MetaProperty` tal como se ejecuta AQUÍ (el híbrido)
    - **Existe porque nadie prueba esta combinación.** `MetaProperty` está declarada dos
      veces —núcleo y `piecesphp/database`— y PSR-4 hace ganar siempre a la del núcleo por
      prefijo más largo. Lo que corre es `MetaProperty` del núcleo llamando a
      `EntityMapper::validateType()` del paquete, y **ninguno de los dos repositorios prueba
      eso**: la suite del paquete llama a `MetaProperty::validateType()`, un estático que en
      la copia que corre aquí no existe.
    - Se probaron:
        - `MetaProperty` resuelve al archivo del núcleo y `EntityMapper` al del paquete
        - Existen los métodos que `EntityMapperExtensible::addMetaProperty()` consume, y el
          mensaje de error nombra el campo
        - La ruta de fecha —por donde llegó **de rebote** el arreglo de PHP 8.5— acepta
          `null` y guarda la cadena TAL CUAL, sin convertirla en `DateTime`
        - `null` en un campo mapper anulable no instancia nada
        - Nada de lo anterior emite una deprecación
    - **Es de solo lectura**: el caso de tipo mapper usa `null` justamente porque es el
      camino que no llega a tocar la base de datos.
    - **Contraste comprobado**, no supuesto: cargando la copia del paquete en aislamiento,
      `getInternalName()` no existe, `validateType()` sí, y una fecha vuelve como `DateTime`
      en vez de como cadena. Cuatro de las doce comprobaciones cambian de resultado según
      qué copia se cargue, que es exactamente lo que se quería fijar.
    - src/app/core/system-controllers/local-tests/UnitTest-MetaPropertyHybrid.php
```bash
bin/cli unit-tests:core/meta-property-hybrid
```
- El SQL del esquema, de ida y de vuelta
    - Descubre TODOS los mappers, emite el `CREATE` y el `DROP`, y **se los da a MariaDB** en
      una base de usar y tirar.
    - **Hoy sale en rojo a propósito**: 20 de las 33 tablas no se pueden crear desde sus
      propios mappers. Ver T52. No es un fallo de la suite.
    - src/app/core/system-controllers/local-tests/UnitTest-SchemeSqlRoundTrip.php
```bash
bin/cli unit-tests:core/scheme-sql-round-trip
```
- Pruebas variadas sobre funciones
    - src/app/core/system-controllers/local-tests/UnitTest-Functions.php
```bash
bin/cli unit-tests:functions/systemOutFormatted
```

## Otras

- Prueba de uso de Mautic
    - Se probaron las siguientes funcionalidades:
        - Segmentación automática
        - Envío de emails
    - src/app/core/system-controllers/local-tests/test-mautic-cronjob.php
    - Se deben configurar las credenciales de Mautic en secure-keys/mautic en el siguiente formato:
```txt
[API_URL]::[CLIENT_ID]::[CLIENT_SECRET]::[EMAIL_FROM]
```
```bash
bin/cli tests:mautic-batch-send
```

## La caché de la aplicación viva

Cualquier medición A/B contra la web **tiene que invalidar la caché de código antes de medir**,
y desde T51 eso no depende de acordarse: vive en el arnés y aborta si no puede hacerlo.

```bash
bin/live-cache --base=https://85.localhost/vicsen/piecesphp/src --report      # qué SAPI, qué ventana
bin/live-cache --base=… --invalidate --file=<archivo editado>                 # invalida y explica la espera
bin/live-cache --base=… --self-test                                           # provoca la trampa y la desactiva
```

`bin/walk-routes` la llama al arrancar. **Nadie puede recorrer sin invalidar.**

**La ventana medida en esta máquina son 3 segundos** —`max(revalidate_freq,
file_update_protection) + 1`, con los dos en 2— y sale de `php-fpm8.5 -i`, no de los `.ini`:
OPcache viene compilado en el binario y los archivos de configuración no lo mencionan.
