# PiecesPHP Framework

## Características:
- Rutas.
- Controladores con Modelos (MySQL y MongoDB).
- Encriptación.
- Tokens.
- Validación de formularios.
- PHPMailer.
- Sessiones.
- Mensajes flash.
- Múltiples funciones útiles.
- Generación de PDF.
- Sistema de usuarios.
- Sistema de roles.

## Resumen:

### Rutas

#### GET

- users/login    
    - Pantalla de ingreso para usuarios.

- users/logout 
    - Ruta de desconexión.

- users/recovery/{url_token}
    - Vista de recuperación de contraseña.

#### POST

- users/login  
    - Validación de login.

- users/register  
    - Registrar usuario. 

- users/recovery   
    - Solicitud de recuperación de contraseña. 

### Controladores

- PanelAdministrativo
	- Rutas manejadas
		- admin/ (GET)

- UsuariosController

    - Controlador para el sistema de usuarios
    - Rutas manejadas:
        - users/login (POST|GET)
        - users/logout (GET)
        - users/recovery/{url_token} (GET)
        - users/register (POST)
        - users/recovery (POST)

- TokenController

    - Controlador para crear tokens y almacenarlos en la base de datos
    - Rutas manejadas:
        - Ninguna

### Modelos

- UsuariosModel
    - Modelo para el sistema de usuarios
    - Controladores que usan el modelo:
        - UsuariosController

- TokenModel
    - Modelo para crear tokens y almacenarlos en la base de datos
    - Controladores que usan el modelo:
        - TokenController

## Librerías o frameworks backend:

- rlanvin/php-form (Composer)
    - Validación de formularios
    - Versión: ^2.0

- slim/slim (Composer)
    - Enrutamiento
    - Versión: ^3.0

- phpmailer/phpmailer (Composer)
    - Envío de correos
    - Versión: ^6.0

- mongodb/mongodb (Composer)
    - Abstracción de MongoDB
    - Versión: ^1.3

- monolog/monolog (Composer)
    - Generación de logs
    - Versión: ^1.23

- spipu/html2pdf (Composer)
    - Generación de PDF
    - Versión: ^5.1

## Librerías frontend implementadas:
- JQuery 3.3.1
- Semantic UI
- DataTables
    - RowReorder
    - ColReorder
	- Reponsive
- JQuery Mask
- NProgress
- SweetAlert2
- AlertifyJS
- Cropper JS

## Notas de desarrollo

### Orden en la carga de archivos en PiecesPHP:

- /app/index.php
	- /app/core/bootstrap.php
		- /vendor/autoload.php
		- /app/core/autoload.php
		- /app/core/Utilities.php
		- /app/config/config.php
		- /app/core/Config.php
		- /app/core/AppHelpers.php
		- /app/config/lang.php
		- /app/config/functions.php
		- /app/config/constants.php
		- /app/config/autoloads.php
	- /app/config/assets.php
	- /app/config/containers.php
	- /app/config/routes.php
