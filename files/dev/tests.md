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
