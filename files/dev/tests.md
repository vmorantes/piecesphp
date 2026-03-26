# Pruebas útiles en desarrollo

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
php index.php cli --local run-cronjobs unit-tests core/helpers-directories
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
php index.php cli --local run-cronjobs unit-tests core/http-client
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
php index.php cli --local run-cronjobs mautic run
```
