# Guía Rápida: Mautic + RabbitMQ en Ubuntu 26.04

Esta guía instala Mautic 7 con RabbitMQ como sistema de colas en Ubuntu 26.04 LTS, para procesar en segundo plano los
correos y el seguimiento (visitas y aperturas) en lugar de hacerlo durante la petición web.

> **Mautic y PiecesPHP comparten PHP 8.5.** Mautic 7.x admite PHP 8.2, 8.3, 8.4 y 8.5, y PiecesPHP exige `>=8.5 <8.6`,
> así que los dos pueden correr con el mismo PHP 8.5 en el mismo servidor. Pero Mautic exige la extensión `imap`, que
> desde PHP 8.4 ya no forma parte de PHP y que Ubuntu 26.04 no empaqueta para su PHP 8.5. Por eso esta guía toma PHP del
> repositorio de Ondřej Surý (`packages.sury.org/php`), que sí publica `php8.5-imap` para Ubuntu 26.04. Con ese
> repositorio activo, todos los paquetes `php8.5-*` del servidor pasan a ser los suyos, también los de PiecesPHP.

---

## **Variables de Entorno de Configuración**

Antes de comenzar, defina estas variables para ejecutar los comandos en la terminal (las líneas del `crontab` y del
servicio de systemd **no las heredan**; ver las secciones 7 y 8). Las contraseñas se generan, no se inventan:

```bash
# Rutas y PHP
export MAUTIC_PATH="/var/www/mautic"          # en HestiaCP: /home/<usuario>/web/<dominio>/public_html
export MAUTIC_URL="https://mautic.example.com"
export PHP_BIN="/usr/bin/php8.5"

# RabbitMQ
export RABBITMQ_USER="mautic_user"
export RABBITMQ_PASS="$(openssl rand -hex 24)"
export RABBITMQ_VHOST="mautic_vhost"
export RABBITMQ_HOST="localhost"
export RABBITMQ_PORT="5672"

# Systemd (Nombre del servicio)
export SYSTEMD_SERVICE="mautic-worker"

# Base de datos Mautic
export DB_HOST="localhost"
export DB_NAME="mautic_db"
export DB_USER="mautic_dbuser"
export DB_PASS="$(openssl rand -hex 24)"

# Administrador de Mautic (la contraseña debe mezclar mayúsculas, minúsculas, números y símbolos)
export MAUTIC_ADMIN_EMAIL="admin@example.com"
export MAUTIC_ADMIN_PASS="$(openssl rand -hex 12)Aa1!"

# Usuario del servidor web (dueño de los archivos, del cron y del worker; en HestiaCP, el usuario del panel)
export CRON_USER="www-data"

# Guarde los tres secretos en su gestor de contraseñas antes de cerrar la terminal
echo "RabbitMQ: $RABBITMQ_PASS"; echo "Base de datos: $DB_PASS"; echo "Admin Mautic: $MAUTIC_ADMIN_PASS"
```

---

## **1. Instalar RabbitMQ**

Ubuntu 26.04 trae RabbitMQ 4.0 en sus repositorios oficiales (componente *main*), con sus dependencias de Erlang:

```bash
sudo apt update
sudo apt install -y rabbitmq-server
sudo systemctl enable --now rabbitmq-server
sudo rabbitmqctl status | head -20
```

---

## **2. Configurar Usuario y Vhost en RabbitMQ**

Cree el entorno necesario dentro de RabbitMQ para que Mautic pueda conectarse:

```bash
sudo rabbitmqctl add_user "$RABBITMQ_USER" "$RABBITMQ_PASS"
sudo rabbitmqctl add_vhost "$RABBITMQ_VHOST"
sudo rabbitmqctl set_permissions -p "$RABBITMQ_VHOST" "$RABBITMQ_USER" ".*" ".*" ".*"

# (Opcional) Habilitar la interfaz web de gestión (Puerto 15672)
sudo rabbitmq-plugins enable rabbitmq_management
```

La interfaz web escucha en el puerto 15672: no lo abra a Internet en el cortafuegos.

---

## **3. PHP 8.5 con las extensiones de Mautic**

Añada el repositorio de Surý (son los pasos de su [README](https://packages.sury.org/php/README.txt)):

```bash
sudo apt install -y lsb-release ca-certificates curl
sudo curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt update
```

Instale PHP 8.5 con las extensiones que exige Mautic (`xml`, `mysql`, `imap`, `zip`, `intl`, `curl`, `gd`, `mbstring`,
`bcmath`) y `amqp`, la que necesita para hablar con RabbitMQ:

```bash
sudo apt install -y php8.5-cli php8.5-fpm \
    php8.5-xml php8.5-mysql php8.5-imap php8.5-zip php8.5-intl php8.5-curl php8.5-gd php8.5-mbstring php8.5-bcmath \
    php8.5-amqp composer unzip
sudo systemctl restart php8.5-fpm   # o apache2, si usa mod_php
$PHP_BIN -m | grep -E '^(amqp|imap|pcntl)$'   # debe mostrar amqp, imap y pcntl
```

---

## **4. Verificar PCNTL**

El worker de la sección 7 usa `pcntl` para atender las señales de parada de systemd. Viene compilada en el PHP de la
terminal (no tiene paquete propio) y ya apareció en la comprobación anterior. Si no aparece, revise que no esté en la
lista `disable_functions` del `php.ini` de la terminal:

```bash
grep -n '^disable_functions' /etc/php/8.5/cli/php.ini   # no debe listar funciones pcntl_*
```

---

## **5. Instalación de Mautic**

Cree la base de datos y su usuario en MariaDB:

```bash
sudo mariadb -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';"
```

Descargue la versión estable (7.2.1; las publicaciones están en <https://github.com/mautic/mautic/releases>) en la
carpeta pública del sitio:

```bash
export MAUTIC_VERSION="7.2.1"
sudo mkdir -p $MAUTIC_PATH
cd $MAUTIC_PATH
sudo wget https://github.com/mautic/mautic/releases/download/${MAUTIC_VERSION}/${MAUTIC_VERSION}.zip -O mautic.zip
sudo unzip -q mautic.zip
sudo rm mautic.zip
sudo chown -R $CRON_USER:$CRON_USER $MAUTIC_PATH
```

El paquete de Mautic no incluye el transporte AMQP de Symfony Messenger: sin él, Mautic rechaza un DSN `amqp://` con
«Unsupported scheme». Añádalo con Composer, sin lanzar los *scripts* del proyecto (que compilan los recursos con `npm`,
y el paquete ya los trae compilados):

```bash
cd $MAUTIC_PATH
sudo -u $CRON_USER env COMPOSER_HOME=/tmp/composer-mautic composer require symfony/amqp-messenger:~7.4.0 --update-no-dev --no-scripts
```

Instale por consola. Las opciones van con guion bajo (`--db_host`, no `--db-host`), y la URL del sitio es obligatoria:

```bash
sudo -u $CRON_USER $PHP_BIN $MAUTIC_PATH/bin/console mautic:install "$MAUTIC_URL" \
    --db_driver=pdo_mysql \
    --db_host="$DB_HOST" \
    --db_name="$DB_NAME" \
    --db_user="$DB_USER" \
    --db_password="$DB_PASS" \
    --admin_email="$MAUTIC_ADMIN_EMAIL" \
    --admin_password="$MAUTIC_ADMIN_PASS" \
    --no-interaction
```

El sitio web debe servir `$MAUTIC_PATH` con PHP 8.5 (PHP-FPM o `mod_php`; en HestiaCP, con la plantilla de PHP-FPM 8.5)
y por HTTPS.

---

## **6. Configurar el Sistema de Colas en Mautic**

Mautic tiene dos colas: **email** (correos, SMS y notificaciones *push*) y **hit** (visitas a páginas y aperturas de
correo). Por defecto ninguna está activa (esquema `sync`, procesado en el momento). Para pasarlas a RabbitMQ, use un
*exchange* distinto para cada una; con un único DSN, las dos compartirían la misma cola de RabbitMQ.

**Opción A: desde el panel.** Engranaje → **Configuration** → pestaña **Queue Settings**. En **Queue for email (SMS
and push messages)**:

- Scheme: `amqp`
- Host: el valor de `$RABBITMQ_HOST`
- Port: el valor de `$RABBITMQ_PORT`
- User y Password: los de `$RABBITMQ_USER` y `$RABBITMQ_PASS`
- Path: `/mautic_vhost/email` (el *vhost* y, tras la barra, el nombre del *exchange*)

En **Queue for hits (page and email)**, lo mismo con Path `/mautic_vhost/hit`. El botón **Send test message** de cada
cola comprueba la conexión.

**Opción B: en el archivo de configuración** `$MAUTIC_PATH/config/local.php`, dentro del array `$parameters` (con sus
valores, no con los nombres de las variables):

```php
'messenger_dsn_email' => 'amqp://mautic_user:<RABBITMQ_PASS>@localhost:5672/mautic_vhost/email',
'messenger_dsn_hit'   => 'amqp://mautic_user:<RABBITMQ_PASS>@localhost:5672/mautic_vhost/hit',
```

Después, `sudo -u $CRON_USER $PHP_BIN $MAUTIC_PATH/bin/console cache:clear`.

---

## **7. Configurar Worker Permanente con Systemd**

Para que los mensajes de las dos colas se procesen continuamente, cree un servicio de sistema. Systemd **no expande las
variables `export` de su terminal**: el bloque escribe el archivo con sus valores ya sustituidos.

```bash
sudo tee /etc/systemd/system/$SYSTEMD_SERVICE.service > /dev/null <<EOF
[Unit]
Description=Mautic RabbitMQ Worker
After=network.target rabbitmq-server.service mariadb.service

[Service]
ExecStart=$PHP_BIN $MAUTIC_PATH/bin/console messenger:consume email hit --time-limit=3600
Restart=always
User=$CRON_USER
WorkingDirectory=$MAUTIC_PATH

[Install]
WantedBy=multi-user.target
EOF
cat /etc/systemd/system/$SYSTEMD_SERVICE.service
```

`--time-limit=3600` hace que el worker se detenga cada hora y `Restart=always` lo vuelve a arrancar: así no acumula
memoria.

**Activar el servicio:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now $SYSTEMD_SERVICE
sudo systemctl status $SYSTEMD_SERVICE
```

---

## **8. Configuración de Tareas Cron (Cron Jobs)**

Mautic requiere tareas periódicas para funcionar correctamente. El `crontab` **no hereda las variables `export` de su
terminal**: este bloque imprime las líneas con sus rutas reales, para pegarlas en el `crontab` del usuario web.

```bash
cat <<EOF
# Actualizar segmentos cada 15 minutos
*/15 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:segments:update

# Actualizar campañas cada 15 minutos
*/15 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:campaigns:update

# Disparar campañas cada 5 minutos
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:campaigns:trigger

# Enviar los mensajes pendientes por reglas de frecuencia cada 5 minutos
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:messages:send

# Enviar los correos de difusión (envíos a segmentos) cada 5 minutos
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:broadcasts:send

# Revisar el correo monitorizado (rebotes y bajas) cada 5 minutos; solo si lo configuró en Mautic
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:email:fetch
EOF

sudo crontab -u $CRON_USER -e
```

---

## **9. Cómo se conecta PiecesPHP a Mautic**

PiecesPHP habla con Mautic **por su API HTTP**, no por RabbitMQ: la clase `API\Adapters\MauticEmailAdapter`
(`src/app/classes/API/Adapters/MauticEmailAdapter.php`) recibe en su constructor la **URL base** de la instancia de
Mautic, un **Client ID** y un **Client Secret** de OAuth2, y pide un token con `grant_type=client_credentials` a
`/oauth/v2/token`. Con él crea contactos, segmentos y correos y dispara envíos (`/api/emails/{id}/send`,
`/api/contacts/new`, `/api/segments/new`…). El token se guarda en `src/app/logs/mautic_errors/token.json` y los errores,
en `log.json` de esa misma carpeta.

Para obtener esas credenciales en Mautic 7:

1. Engranaje → **Configuration** → pestaña **API Settings**: **API enabled?** en *Yes*, y guardar. Eso añade **API
   Credentials** al menú del engranaje.
2. Engranaje → **API Credentials** → **New**: protocolo *OAuth 2*, un nombre y, en **Redirect URI**, la URL de su
   Mautic (el flujo *client credentials* no la usa). Al guardar, Mautic muestra el **Client ID** y el **Client
   Secret**.
3. Créelas **con un usuario administrador**: Mautic solo habilita el tipo *client credentials*, el que usa PiecesPHP,
   en las credenciales que crea un administrador.

RabbitMQ solo interviene dentro de Mautic: PiecesPHP no se conecta a él. En el código del framework, el adaptador solo
lo usan las pruebas locales (`Test-Mautic.php`, `UnitTest-MauticBatchLogic.php` y `Mautic-BatchFlow.php`, en
`src/app/core/system-controllers/local-tests/`): no hay una pantalla ni una opción que lo cablee, así que es su código
quien debe pasarle esos tres valores. No guarde el secreto en un archivo versionado.

---

✅ **Resultado final**:

* Mautic instalado y configurado.
* RabbitMQ funcionando como sistema de colas, con una cola para correos y otra para seguimiento.
* Worker permanente con systemd.
* Cron jobs programados para mantenimiento y campañas.
* PHP 8.5 con `imap`, `amqp` y `pcntl`.
