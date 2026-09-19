# Guía Rápida: Mautic + RabbitMQ en Ubuntu 24.04

Esta guía detalla el proceso para configurar Mautic con RabbitMQ como sistema de colas en un entorno Ubuntu (probado en 24.04), permitiendo un procesamiento eficiente de correos, segmentos y campañas.

---

## **Variables de Entorno de Configuración**

Antes de comenzar, es recomendable definir estas variables para facilitar la ejecución de los comandos:

```bash
# Rutas y PHP
export MAUTIC_PATH="/home/administrator/web/DOMAIN/public_html"
export PHP_BIN="/usr/bin/php8.1"

# RabbitMQ
export RABBITMQ_USER="mautic_user"
export RABBITMQ_PASS="StrongPassword123"
export RABBITMQ_VHOST="mautic_vhost"
export RABBITMQ_HOST="localhost"
export RABBITMQ_PORT="5672"

# Systemd (Nombre del servicio)
export SYSTEMD_SERVICE="mautic-worker"

# Base de datos Mautic
export DB_HOST="localhost"
export DB_NAME="mautic_db"
export DB_USER="mautic_dbuser"
export DB_PASS="StrongPassword123"

# Usuario CRON del servidor web
export CRON_USER="www-data"
```

---

## **1. Instalar RabbitMQ**

Ejecute los siguientes comandos para instalar el servidor de RabbitMQ y sus dependencias de Erlang:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y erlang-base erlang-asn1 erlang-crypto erlang-eldap erlang-inets \
erlang-mnesia erlang-os-mon erlang-parsetools erlang-public-key erlang-runtime-tools \
erlang-snmp erlang-ssl erlang-syntax-tools erlang-tools erlang-xmerl curl gnupg apt-transport-https

# Agregar repositorio oficial
curl -fsSL https://packagecloud.io/rabbitmq/rabbitmq-server/gpgkey | sudo gpg --dearmor -o /usr/share/keyrings/rabbitmq.gpg
echo "deb [signed-by=/usr/share/keyrings/rabbitmq.gpg] https://packagecloud.io/rabbitmq/rabbitmq-server/ubuntu/ noble main" | sudo tee /etc/apt/sources.list.d/rabbitmq.list

sudo apt update
sudo apt install -y rabbitmq-server
sudo systemctl enable --now rabbitmq-server
```

---

## **2. Configurar Usuario y Vhost en RabbitMQ**

Cree el entorno necesario dentro de RabbitMQ para que Mautic pueda conectarse:

```bash
sudo rabbitmqctl add_user $RABBITMQ_USER $RABBITMQ_PASS
sudo rabbitmqctl add_vhost $RABBITMQ_VHOST
sudo rabbitmqctl set_permissions -p $RABBITMQ_VHOST $RABBITMQ_USER ".*" ".*" ".*"

# (Opcional) Habilitar la interfaz web de gestión (Puerto 15672)
sudo rabbitmq-plugins enable rabbitmq_management
```

---

## **3. Instalar y Habilitar PHP AMQP**

Para que PHP pueda comunicarse con RabbitMQ, es necesaria la extensión AMQP:

```bash
sudo apt install php8.1-amqp
sudo phpenmod amqp
sudo systemctl restart apache2    # O php8.1-fpm si usa Nginx
php -m | grep amqp                 # Verificación: debe mostrar 'amqp'
```

---

## **4. Verificar PCNTL**

Mautic requiere la extensión `pcntl` para gestionar procesos en segundo plano:

```bash
php -m | grep pcntl  # Verificación: debe mostrar 'pcntl'
```

Si aparece deshabilitado en el CLI, asegúrese de que no esté en la lista `disable_functions` de su archivo `php.ini` del CLI:

```bash
sudo nano /etc/php/8.1/cli/php.ini
# Busque y limpie la línea:
# disable_functions =
```

---

## **5. Instalación de Mautic**

Si aún no tiene Mautic instalado, puede realizarlo desde la terminal:

```bash
cd $MAUTIC_PATH
wget https://www.mautic.org/download/latest -O mautic.zip
unzip mautic.zip
rm mautic.zip

# Instalación vía consola
$PHP_BIN $MAUTIC_PATH/bin/console mautic:install \
--db-host=$DB_HOST \
--db-name=$DB_NAME \
--db-user=$DB_USER \
--db-password=$DB_PASS \
--no-interaction
```

---

## **6. Configurar el Sistema de Colas en Mautic**

Dentro de la configuración de Mautic (o directamente en los archivos de configuración), utilice el siguiente DSN:

**DSN de Conexión:**
`amqp://$RABBITMQ_USER:$RABBITMQ_PASS@$RABBITMQ_HOST:$RABBITMQ_PORT/$RABBITMQ_VHOST`

Configure este transporte para:
- **Emails**
- **SMS / Push notifications**
- **Hits (seguimiento)**

---

## **7. Configurar Worker Permanente con Systemd**

Para asegurar que los mensajes de la cola se procesen continuamente, cree un servicio de sistema:

Archivo: `/etc/systemd/system/[NOMBRE_SYSTEMD_SERVICE].service`

```ini
[Unit]
Description=Mautic RabbitMQ Worker
After=network.target

[Service]
ExecStart=[PHP_BIN] [MAUTIC_PATH]/bin/console messenger:consume email --time-limit=3600
Restart=always
User=[CRON_USER]
WorkingDirectory=[MAUTIC_PATH]

[Install]
WantedBy=multi-user.target
```

**Activar el servicio:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now $SYSTEMD_SERVICE
sudo systemctl status $SYSTEMD_SERVICE
```

---

## **8. Configuración de Tareas Cron (Cron Jobs)**

Mautic requiere tareas periódicas para funcionar correctamente. Edite el crontab del usuario web (`crontab -u $CRON_USER -e`) y añada:

```cron
# Actualizar segmentos cada 15 minutos
*/15 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:segments:update

# Actualizar campañas cada 15 minutos
*/15 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:campaigns:update

# Disparar campañas cada 5 minutos
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:campaigns:trigger

# Enviar mensajes cada 5 minutos
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:messages:send

# Revisar correos entrantes cada 5 minutos
*/5 * * * * $PHP_BIN $MAUTIC_PATH/bin/console mautic:emails:fetch
```

---

✅ **Resultado final**:

* Mautic instalado y configurado.
* RabbitMQ funcionando como sistema de colas.
* Worker permanente con systemd.
* Cron jobs programados para mantenimiento y campañas.
* PHP con `pcntl` y `amqp` habilitados.
