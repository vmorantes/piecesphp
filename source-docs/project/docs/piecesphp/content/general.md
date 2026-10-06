# PiecesPHP Framework

## Configuración de entorno

- Requerimientos
    - PiecesPHP
    - **PHP 8.5** (`>=8.5 <8.6`) — **el que sirve la web Y el que ejecuta `composer`**
    - Composer
    - NodeJS 22.x LTS con FNM
    - NPM
    - Gulp CLI

### Actualizar repositorios
```bash
sudo apt update && sudo apt upgrade -y
```

### Instalación de dependencias de desarrollo

#### Composer

- Mediante apt:
```bash
#Instalar
sudo apt install -y composer
#Verificar versión
composer --version
```
- Mediante descarga:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

#### NodeJS (v22.12.0) y NPM
```bash
#Instalar FNM
cd ~
curl -fsSL https://fnm.vercel.app/install | bash
source ~/.bashrc
#Instalar Node
fnm install 22.12.0
fnm use 22.12.0
#Verificar versiones
node --version
npm --version
## Aplicar medidas de seguridad a entorno Node
npm config set ignore-scripts true --global
npm config get ignore-scripts
```

#### Gulp CLI y Typescript
```bash
sudo npm install -g gulp-cli typescript
gulp --version
tsc --version
```

### El entorno: local o producción

Cada instalación dice si es de desarrollo o de producción en un archivo que **no se versiona**:

```bash
cp src/app/config/environment.example.php src/app/config/environment.php
```

y dentro, `return 'local';` en tu máquina de desarrollo o `return 'production';` en un servidor.

- **Sin ese archivo, la instalación funciona como producción.** Es lo seguro: un servidor nunca enseña trazas completas
  por olvido. Pero en tu máquina de desarrollo significa que `src/app/config/database.php` elige las credenciales de
  producción y seguramente no conecta.
- «Local» ya no depende del nombre con que se visita el sitio (`localhost`, `*.localhost`): antes lo decidía la
  cabecera `Host`, que manda el navegador y se puede falsear.
- Qué cambia en local: se enseñan los errores completos, las deprecaciones abortan y se puede depurar el SQL de los
  listados.
- En la terminal, `bin/cli` sigue siendo local con `--local`.
- Si falta el archivo, root lo ve en «Avisos del sistema».

### El alta pública de usuarios por API

`POST /core/api/users/register/` es el alta que usa una aplicación externa (móvil, sitio propio) para registrar a
alguien. No pide autenticación, y el usuario nace pendiente de aprobación.

Admite dos formas:

| Caso | Qué se manda | Qué pasa |
| :-- | :-- | :-- |
| Se une a una organización existente | `organizationID` (cifrado) | El usuario queda en esa organización |
| Crea su organización | `organizationID=NONE` y `organizationName` | Se crea la organización, el usuario queda como **su administrador**, y las dos cosas nacen pendientes de aprobación |

Cómo está montado, por si lo tocas:

- el alta de la organización **no se hace fabricando una petición HTTP**: `OrganizationsController::createOrganization(array $valores)`
  es el único camino, y la pantalla del panel llama exactamente al mismo método. Si mañana cambia el alta, cambia para
  los dos;
- si la organización no se puede crear, **el usuario tampoco**, y si el usuario falla después, la organización recién
  creada se retira. Nunca queda una mitad;
- el NIT de una organización creada así es un marcador `SIN_INFORMACION_…`, por constante
  (`OrganizationMapper::NIT_WITHOUT_INFORMATION_PREFIX`). No puede ir vacío: el mapper trata el NIT como único y la
  segunda organización sin NIT sería rechazada como duplicada;
- la organización global (`OrganizationMapper::INITIAL_ID_GLOBAL`, `-10`) **no es el comodín de «no hay organización»**:
  solo es el valor por defecto de los tipos de usuario que no requieren organización.

La documentación para quien consume la API, con los parámetros exactos y el ejemplo, está en la documentación de la API (`source-docs/api/docs/modules/Usuarios.md`), que se publica aparte.

### Consumir el framework sin el panel (headless)

Una aplicación propia —móvil, un sitio aparte, otro backend— puede usar el framework sin su panel. Esto es lo que tiene
que saber quien la escriba:

**1. Entrar.** `POST /users/login/` devuelve un **token** y, hoy, un paquete `userData` con los datos del usuario.

**2. Mandar el token en cada petición**, en la cabecera `JWTAuth` (también vale una cookie con ese mismo nombre; si
llegan las dos, manda la cabecera). **Ese nombre es configurable por instalación**, así que no lo escribas fijo en tu
aplicación: pídelo o hazlo ajustable.

**3. Qué lleva el token y qué NO.** Lleva el identificador del usuario, las fechas y, si está activo, un candado que lo
ata a su cliente. **No lleva el tipo de usuario**: lo que alguien ES —tipo, estado, organización— se lee en el servidor
en cada petición. No decidas permisos en tu aplicación con el contenido del token.

**4. Caducidad.** Por defecto, 31 días. Un token caducado deja de valer; el candado por cliente, si está activo, se
calcula con la dirección del visitante y el nombre de la máquina, así que **un cambio de nombre del servidor invalida
las sesiones**.

**5. Peticiones desde otro dominio.** El servidor permite la cabecera de la sesión con el nombre configurado. Si
cambias ese nombre, cámbialo también donde corresponda en tu cliente.

**6. Zonas con su propia capa de acceso.** Si tu aplicación necesita una sesión que no es la de los usuarios del panel
—una zona pública con su propio acceso—, existe `PiecesPHP\Core\SessionTokenIsolated` y su pareja en el navegador. Lee
antes lo que garantiza y lo que no: su aislamiento es **del canal**, no criptográfico, salvo que le pases una clave
propia.

**7. Lo que NO debes dar por estable:** el contenido del paquete `userData` del login. Hoy lleva nueve campos
(`UsersController::LOGIN_USER_DATA_FIELDS`: `id`, `username`, `email`, `firstname`, `secondname`, `firstLastname`,
`secondLastname`, `type` y `organization`) más `misc` (el avatar y las meta-propiedades del usuario); el panel no lee
ninguno salvo el identificador, y está previsto adelgazarlo. Si necesitas datos del usuario, pídelos al
servidor.

### Correo en desarrollo

La instalación local **no manda correo al exterior**: en «Configuración de emails» está el **modo de pruebas**, que de
fábrica se enciende solo cuando el entorno es `local` y desvía todo al servidor de pruebas (`127.0.0.1:1025`). Puedes
forzarlo a «Siempre encendido» o a «Apagado», y eso manda sobre el entorno; la configuración SMTP real se queda como
está. Mientras está activo, root lo ve en «Avisos del sistema».

Para instalar y usar el servidor de pruebas: [Mailpit](../../environments/content/mailpit/index.md).

### Rutas mal escritas

El patrón de cada ruta se comprueba **al registrarla**:

- en tu máquina de desarrollo, el arranque falla en el acto y te dice la ruta, el patrón, lo que FastRoute no entendió y
  el archivo y la línea donde la declaraste;
- en producción, la aplicación no se cae: esa ruta se descarta (responde 404), el error va al log y root ve el aviso en
  «Avisos del sistema» y en `bin/cli system-alerts`.

Antes de subir nada, `bin/check-routes`: analiza los patrones ya montados con el prefijo de su grupo, que es lo que la
comprobación anterior no puede ver, y funciona aunque la aplicación no arranque.

### Errores internos: el código de referencia

Cada error que se registra en el log recibe un **código de referencia**, por ejemplo `ERR-20260919-A1B2C3` (la fecha y
seis caracteres al azar).

- **En producción**, una excepción que nadie captura responde con estado 500 y **solo** un mensaje genérico con el
  código: «Ocurrió un error interno. Si lo reporta, indique la referencia ERR-…». Ni el mensaje de la excepción, ni el
  archivo, ni la línea, ni la traza. En JSON: `{"success": false, "message": "…", "reference": "ERR-…"}`.
- **En local** se ve todo, como siempre, y además la referencia.
- **Para encontrar el error:** busca el código en `src/app/logs/error.log.json` (campo `reference`) o en
  `src/app/logs/error.plain.log` (`[ref ERR-…]`):

  ```bash
  grep -n "ERR-20260919-A1B2C3" src/app/logs/error.plain.log
  ```

- **En tu código:** `log_exception($e)` devuelve el código. Úsalo cuando captures una excepción interna y tengas que
  responder algo al usuario:

  ```php
  try {
      $mapper->save();
  } catch (\Throwable $e) {
      $reference = log_exception($e);
      $result->setMessage(\PiecesPHP\Core\CustomErrorsHandlers\CustomSlimErrorHandler::genericMessage($reference));
  }
  ```

  Nunca `setMessage($e->getMessage())` con una excepción interna: enseña detalles de la base o del servidor.

### Desplegar PiecesPHP

#### Paso 1: Definir variables útiles

```bash
#Variable con la carpeta del proyecto, si no existe debe crearse antes
export FOLDER="/var/www/html/pcsphp_project"
mkdir -p $FOLDER
```

O, en caso de que se quiera hacer en el directorio actual (donde está abierta la terminal):

```bash
export FOLDER=$(pwd)
```

#### Paso 2: Descargar y descomprimir

```bash
cd $FOLDER
wget https://github.com/vmorantes/piecesphp/archive/refs/heads/last-stable.zip
unzip last-stable.zip -d . && rm last-stable.zip
```

#### Paso 3: Mover a la raíz, eliminar archivos innecesarios y ajustar permisos

Nota: En la eliminación se obtendrá una advertencia por los meta archivos . y .., no es importante.

##### Mover

```bash
find . -depth -type d -name * -execdir mv {} tmp \;
sudo mv ./tmp/{*,.*} ./;
```
##### Eliminar y Ajustar Permisos

```bash
sudo rm -Rf tmp CHANGELOG.md README.md TODO TODO.md guides source-docs LICENSE;
# Ejecuta la utilidad de ajuste de permisos y propiedad
chmod +x permissions-and-property.sh;
./permissions-and-property.sh;
```

> [!IMPORTANT]
> **Permisos:** Para información sobre permisos ver la [Guía de Permisos y Propiedad](./permissions.md).

#### Paso 4: Composer y Gulp

```bash
#Instalar gulp para desarrollo
cd $FOLDER
rm -Rf node_modules package-lock* ##Si hay algún proyecto NPM desplegado ya
npm cache clean --force ##Para actualizar los repositorios
npm install ##NO USAR sudo

#Instalar paquetes de composer
cd $FOLDER/src
composer install ##NO USAR sudo
```

!!! danger "Composer usa el PHP del PATH, no el de la web"
    `composer` es un guion PHP: corre con **el `php` que encuentre en el PATH**, que no tiene
    por qué ser el que sirve Apache. Y **no te va a avisar**: `src/composer.json` fija
    `config.platform.php` en `8.5.0`, así que Composer resuelve las dependencias como si el PHP
    fuera 8.5.0 aunque lo ejecute uno más viejo.

    El fallo aparece después: `vendor/composer/platform_check.php` detiene la aplicación si el
    PHP que la ejecuta está por debajo de 8.5. Ejecuta Composer con el binario correcto y
    comprueba los dos:

    ```bash
    php8.5 /usr/bin/composer install     # o la ruta donde esté el composer.phar
    php -v && php8.5 -v                  # el del PATH y el que quieres
    ```

    **Son dos versiones distintas que comprobar**: la que ejecuta `composer` y la que sirve
    la web (`php-fpm<version> -v`). Que una esté bien no dice nada de la otra.

##### Actualización de NPM (solo en caso de errores)
```bash
#Para actualizar dependencias
npm install -g npm-check-updates
ncu -u
npm install
```

#### Paso 5: Activación de módulos apache necesarios
```bash
sudo a2enmod rewrite headers ssl
sudo systemctl restart apache2
```

#### Paso 6: Declarar el entorno y comprobar la instalación

`src/vendor/` y `src/app/config/environment.php` no vienen con el framework: el primero lo crea `composer install`
(paso 4) y el segundo lo creas tú a partir del ejemplo. Sin `environment.php`, la instalación no está declarada y el
correo se retiene (ver [Correo en desarrollo](#correo-en-desarrollo)).

```bash
cd $FOLDER/src/app/config
cp environment.example.php environment.php   # y edítalo: local o producción

cd $FOLDER
bin/check-routes              # las rutas: funciona aunque la aplicación no arranque
bin/cli verify-integrity      # la integridad estructural
bin/cli gates                 # las suites de prueba, solo en una instalación declarada `local`
```

En un clon, `verify-integrity` y `gates` dicen **«[NO APLICA EN LA DISTRIBUCIÓN]»** en las comprobaciones que dependen
de herramientas del repositorio de desarrollo del framework, que no viajan; el resultado final dice cuántas no se
hicieron. Es lo esperado: lo que sí se comprueba es tu código.

Si trabajas con agentes, activa además el control de los commits que trae `.agents/`:

```bash
git config core.hooksPath .agents/scripts/git-hooks
```

#### Más información
- Durante el desarrollo se recomiendo el uso de las siguientes tareas de gulp (para más información, [clic aquí](./gulp.md)):
    - init-project
    - init-project:watch
- Base de datos:
    - Se debe configurar la conexión en el archivo `src/app/config/database.php`
    - Los archivos para usar en la base de datos están en la carpeta databases
- Otras cosas:
    - En el archivo `src/app/config/constants.php` se pueden activar/desactivar algunas características integradas.

## Despliegue de PiecesPHP (Ubuntu 26.04 LTS)

!!! note "PHP: Ubuntu 26.04 trae la versión que hace falta"
    El piso es **PHP 8.5** y el techo, **8.6**, y Ubuntu 26.04 LTS trae **8.5** en sus
    repositorios oficiales: no hace falta ningún repositorio adicional. Las extensiones que
    declara `src/composer.json`:

    ```bash
    sudo apt update
    sudo apt install -y php8.5-cli php8.5-{xml,mbstring,gd,curl,zip,mysql,sqlite3}
    ```

    El reparto de cada extensión por paquete y la elección entre `mod_php` y PHP-FPM están en
    la [guía de PHP](../../environments/content/lamp/content/PHP.md).

    **El rango lo declara `require.php` de `src/composer.json` — `">=8.5 <8.6"` — y ésa es la
    única fuente.** `vendor/composer/platform_check.php` comprueba en cada arranque **solo el
    piso** (`PHP_VERSION_ID >= 80500`): un despliegue con 8.4 no arranca.

    **El techo no se comprueba al arrancar**: una instalación que pase a 8.6 arrancaría sin
    aviso. 8.6 no está probado: no lo uses mientras no se pruebe. (Además, `src/composer.json`
    fija `config.platform.php` en `8.5.0`: Composer resuelve como si el PHP fuera 8.5.0 sea cual
    sea el que lo ejecuta.)

## Notas adicionales
- Configura la base de datos en `src/app/config/database.php`.
- Los archivos SQL están en la carpeta `databases`.
- Puedes activar/desactivar características en `src/app/config/constants.php`.
