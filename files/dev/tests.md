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

## Unitarias

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
