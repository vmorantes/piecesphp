# Despliegue con Docker y HestiaCP

Esta guía contenedoriza **PiecesPHP** con la imagen oficial `php:8.5-apache` y una base MariaDB 11.8 (versión de soporte
largo) en otro contenedor, y lo publica detrás del nginx de **HestiaCP** como proxy inverso. El servidor de referencia es
Ubuntu 26.04 LTS. El código fuente de PiecesPHP no incluye ningún cliente de base de datos (ni Adminer).

La regla que recorre toda la guía: **el contenedor solo aporta el entorno de ejecución**. Los datos (el código, las
subidas, la base) viven en carpetas del servidor montadas en el contenedor, de modo que borrar y recrear los
contenedores no pierde nada.

---

## 🐳 1. Docker Engine

Desde el repositorio oficial de Docker, que publica para Ubuntu 26.04 (*resolute*). Son los pasos de su
[guía de instalación](https://docs.docker.com/engine/install/ubuntu/):

```bash
sudo apt update
sudo apt install -y ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

docker --version
docker compose version
```

**No añadas usuarios al grupo `docker`**: quien está en él puede hacerse `root` del servidor. Los comandos de esta guía
se ejecutan con `sudo`.

---

## 🏗️ 2. Estructura del proyecto

En un servidor con HestiaCP, el proyecto vive en el `home` del usuario del panel, **junto a** `web/` y no dentro: así
cuenta para su cuota y entra en sus respaldos, y borrar el dominio en el panel no lo borra. La carpeta se llama como el
dominio.

```text
/home/<usuario>/containers/<dominio>/
├── Dockerfile
├── docker-compose.yml
├── .env               # secretos de Compose (permisos 600)
├── app/               # el clon de PiecesPHP (src/, bin/, databases/…)
└── db-data/           # los datos de MariaDB
```

```bash
export HUSER=usuario            # el usuario de HestiaCP dueño del dominio
export DOMINIO=dominio.com
export P=/home/$HUSER/containers/$DOMINIO

sudo mkdir -p $P/db-data
# Copia aquí tu clon de PiecesPHP como $P/app (por ejemplo, con git clone o por SFTP)
sudo chown -R $HUSER:$HUSER $P/app
```

Fuera de HestiaCP (en tu máquina, por ejemplo) sirve cualquier carpeta con la misma estructura.

---

## 🛠️ 3. Dockerfile

La imagen `php:8.5-apache` ya trae compiladas casi todas las extensiones que declara `src/composer.json`: `date`,
`pcre`, `hash`, `session`, `json`, `openssl`, `ctype`, `fileinfo`, `pdo`, `xml`, `xmlwriter`, `xmlreader`, `mbstring`,
`sqlite3`, `pdo_sqlite`, `curl` y `zlib`, además de OPcache, que en PHP 8.5 va siempre compilado. Faltan cinco, que el
`Dockerfile` compila: `gd`, `mysqli`, `pdo_mysql`, `zip` y `xsl`.

Apache corre como `www-data`. Para que lo que escribe en las carpetas montadas (subidas, caché) pertenezca al usuario
de HestiaCP, el `Dockerfile` le da a `www-data` el mismo UID y GID que ese usuario.

`$P/Dockerfile`:

```dockerfile
FROM php:8.5-apache

# UID y GID del dueño de los archivos en el servidor (los pasa docker-compose.yml)
ARG APP_UID=1000
ARG APP_GID=1000

# 1. Librerías para compilar gd, zip y xsl, y locales del sistema
RUN apt-get update && apt-get install -y --no-install-recommends \
        libfreetype-dev libjpeg62-turbo-dev libpng-dev \
        libzip-dev libxml2-dev libxslt1-dev \
        locales unzip git \
    && rm -rf /var/lib/apt/lists/*

# 2. Las extensiones de src/composer.json que la imagen no trae
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip xsl

# 3. php.ini de producción
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 4. Locales que usa PiecesPHP (src/app/config/lang.php, locale_langs)
RUN sed -i -E 's/^# (es_ES|es_CO|es_MX|en_US|fr_FR|de_DE|it_IT|pt_PT)\.UTF-8 UTF-8/\1.UTF-8 UTF-8/' /etc/locale.gen \
    && locale-gen
ENV LANG=es_ES.UTF-8

# 5. Composer, desde su imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# 6. www-data con el UID y GID del dueño de los archivos
RUN groupmod -o -g "${APP_GID}" www-data && usermod -o -u "${APP_UID}" -g "${APP_GID}" www-data

# 7. mod_rewrite para el .htaccess del framework, y la raíz pública en src/
RUN a2enmod rewrite headers \
    && sed -ri 's!DocumentRoot /var/www/html$!DocumentRoot /var/www/html/src!' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
```

La imagen ya permite el `.htaccess` (`AllowOverride All` sobre `/var/www/`), que PiecesPHP necesita.

---

## 📦 4. Docker Compose

### Secretos

Genera las contraseñas de la base en el `.env` de Compose (cada instalación, las suyas) y fija el UID y GID:

```bash
sudo tee $P/.env > /dev/null <<EOF
APP_UID=$(id -u $HUSER)
APP_GID=$(id -g $HUSER)
APP_PORT=42001
MARIADB_DATABASE=piecesphp
MARIADB_USER=piecesphp
MARIADB_PASSWORD=$(openssl rand -hex 24)
MARIADB_ROOT_PASSWORD=$(openssl rand -hex 24)
EOF
sudo chmod 600 $P/.env
```

`APP_PORT` es el puerto del servidor por el que nginx llegará al contenedor. Con varios proyectos, cada uno lleva el
suyo; no uses 8080 ni 8443, que en HestiaCP con Apache son de Apache.

### `$P/docker-compose.yml`

```yaml
services:
  app:
    build:
      context: .
      args:
        APP_UID: ${APP_UID}
        APP_GID: ${APP_GID}
    restart: unless-stopped
    ports:
      - "127.0.0.1:${APP_PORT}:80"   # solo accesible desde el propio servidor
    volumes:
      - ./app:/var/www/html
    extra_hosts:
      - "host.docker.internal:host-gateway"
    depends_on:
      - db

  db:
    image: mariadb:11.8
    restart: unless-stopped
    environment:
      MARIADB_DATABASE: ${MARIADB_DATABASE}
      MARIADB_USER: ${MARIADB_USER}
      MARIADB_PASSWORD: ${MARIADB_PASSWORD}
      MARIADB_ROOT_PASSWORD: ${MARIADB_ROOT_PASSWORD}
    volumes:
      - ./db-data:/var/lib/mysql
```

- **El prefijo `127.0.0.1:` en `ports` es obligatorio.** Sin él, Docker publica el puerto en todas las interfaces del
  servidor, y lo hace con reglas propias de iptables que **no pasan por el cortafuegos de HestiaCP**: el puerto
  quedaría abierto a Internet aunque el panel lo muestre cerrado.
- La base no publica ningún puerto: solo la alcanza la aplicación, por la red interna de Compose.
- `db-data/` es una carpeta del proyecto, no un volumen de Docker, para que entre en los respaldos de HestiaCP. Sus
  archivos pertenecen al usuario `mysql` de dentro de la imagen de MariaDB, no al usuario del panel.
- En Docker Compose v2 el campo `version:` es obsoleto y se ignora, por eso no aparece.

### Arranque y dependencias

```bash
cd $P
sudo docker compose up -d --build

# Dependencias de Composer (src/vendor no se versiona), como www-data
sudo docker compose exec -u www-data -e COMPOSER_HOME=/tmp/composer app composer install --working-dir=src --no-dev

sudo docker compose ps
curl -sI http://127.0.0.1:42001/ | head -1
```

### Conectar la aplicación a la base de datos

PiecesPHP **no lee variables de entorno para la conexión** (`DB_HOST` no lo usa nada). La conexión sale de
`src/app/config/database.php`, un archivo del clon que tú editas. Allí se declaran, dentro de
`$config['database']['default']`, las claves `driver`, `db`, `user`, `password`, `host` y `charset`. Pon los valores del
`.env` (`sudo cat $P/.env`):

- `host`: el nombre del servicio de Compose, `db` (dentro de la red de Compose, `localhost` es el propio contenedor de
  la aplicación y no llega a la base);
- `db`, `user` y `password`: los de `MARIADB_DATABASE`, `MARIADB_USER` y `MARIADB_PASSWORD`.

Ese archivo lleva credenciales: no lo subas a un repositorio público.

---

## 💾 5. Migración

Para cargar un volcado `backup.sql` en la base del contenedor, sin escribir la contraseña en la orden (la imagen de
MariaDB 11 ya no trae el comando `mysql`, sino `mariadb`):

```bash
cd $P
sudo docker compose exec -T db sh -c 'exec mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < backup.sql
```

Y para sacar uno:

```bash
sudo docker compose exec -T db sh -c 'exec mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > backup.sql
```

---

## 🛡️ 6. Publicarlo detrás del nginx de HestiaCP

HestiaCP no trae una plantilla web que envíe un dominio a un contenedor: se crea una, `docker`, una sola vez por
servidor, y luego se aplica a cada dominio. El `proxy_pass` no va en la plantilla, sino en un archivo por dominio
(`nginx.docker.conf`) que la plantilla incluye.

### 6.1 Identificar el modo de HestiaCP

HestiaCP se instala en dos modos, y cada uno usa otro directorio de plantillas, otras variables de puerto y otro
comando. **Equivocarse de modo hace que nginx no arranque para ningún dominio del servidor.**

| | Modo A — nginx + Apache | Modo B — solo nginx |
| :-- | :-- | :-- |
| Se reconoce por | `WEB_SYSTEM='apache2'` y `PROXY_SYSTEM='nginx'` | `WEB_SYSTEM='nginx'` |
| Directorio de plantillas | `/usr/local/hestia/data/templates/web/nginx/` | `/usr/local/hestia/data/templates/web/nginx/php-fpm/` |
| Variables de puerto | `%proxy_port%` y `%proxy_ssl_port%` | `%web_port%` y `%web_ssl_port%` |
| Comando para aplicarla | `v-change-web-domain-proxy-tpl` | `v-change-web-domain-tpl` |

Como `root` (`sudo -i`), detéctalo y guárdalo; el resto de la sección lo lee de ahí:

```bash
cat > /root/hestia-docker.vars <<'EOF'
HC=/usr/local/hestia/conf/hestia.conf
if grep -q "^PROXY_SYSTEM='nginx'" "$HC" && grep -q "^WEB_SYSTEM='apache2'" "$HC"; then
  export MODO="A (nginx + Apache)"
  export TPL_DIR="/usr/local/hestia/data/templates/web/nginx"
  export TPL_CMD="v-change-web-domain-proxy-tpl"
  export PORT_VAR="%proxy_port%"
  export SSL_PORT_VAR="%proxy_ssl_port%"
elif grep -q "^WEB_SYSTEM='nginx'" "$HC"; then
  export MODO="B (nginx solo)"
  export TPL_DIR="/usr/local/hestia/data/templates/web/nginx/php-fpm"
  export TPL_CMD="v-change-web-domain-tpl"
  export PORT_VAR="%web_port%"
  export SSL_PORT_VAR="%web_ssl_port%"
else
  echo "ALTO: no reconozco esta configuración. Revisa $HC a mano." >&2
fi
EOF

source /root/hestia-docker.vars
echo "Modo: $MODO · plantillas: $TPL_DIR · comando: $TPL_CMD"
```

En Modo A, **no cambies la plantilla de Apache** (`v-change-web-domain-tpl`) del dominio: se queda en `default`, y la
que cambia es la de proxy.

### 6.2 La variable `$connection_upgrade`

La plantilla reenvía WebSockets y para eso nginx necesita un `map` que no trae de fábrica. Como `root`:

```bash
if grep -rqs 'connection_upgrade' /etc/nginx/; then
  echo "· el map ya existe"
else
  cat > /etc/nginx/conf.d/00-docker-upgrade.conf <<'EOF'
# Requerido por las plantillas web "docker". No borrar.
map $http_upgrade $connection_upgrade {
    default upgrade;
    ''      close;
}
EOF
fi
nginx -t && systemctl reload nginx
```

### 6.3 Crear la plantilla `docker`

Como `root`. Los *heredoc* van con `<<'EOF'` (delimitador entre comillas) para que el shell no expanda `$host`,
`$scheme` ni `$remote_addr`; luego `sed` pone las variables de puerto de tu modo.

```bash
source /root/hestia-docker.vars

cat > /tmp/docker.tpl <<'EOF'
server {
    listen      %ip%:__PORT__;
    server_name %domain_idn% %alias_idn%;
    root  %docroot%;
    index index.html;

    access_log /var/log/%web_system%/domains/%domain%.log combined;
    access_log /var/log/%web_system%/domains/%domain%.bytes bytes;
    error_log  /var/log/%web_system%/domains/%domain%.error.log error;

    include %home%/%user%/conf/web/%domain%/nginx.forcessl.conf*;

    location ~ /\.(?!well-known\/) {
        deny all;
        return 404;
    }

    location / {
        include %home%/%user%/conf/web/%domain%/nginx.docker.conf*;

        proxy_http_version 1.1;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host  $host;
        proxy_set_header X-Forwarded-Port  $server_port;
        proxy_set_header Upgrade           $http_upgrade;
        proxy_set_header Connection        $connection_upgrade;
        proxy_redirect off;
    }

    include %home%/%user%/conf/web/%domain%/nginx.conf_*;
}
EOF

cat > /tmp/docker.stpl <<'EOF'
server {
    listen      %ip%:__SSL_PORT__ ssl;
    http2       on;
    server_name %domain_idn% %alias_idn%;
    root  %sdocroot%;
    index index.html;

    ssl_certificate     %ssl_pem%;
    ssl_certificate_key %ssl_key%;

    include %home%/%user%/conf/web/%domain%/nginx.hsts.conf*;

    access_log /var/log/%web_system%/domains/%domain%.log combined;
    access_log /var/log/%web_system%/domains/%domain%.bytes bytes;
    error_log  /var/log/%web_system%/domains/%domain%.error.log error;

    location ~ /\.(?!well-known\/) {
        deny all;
        return 404;
    }

    location / {
        include %home%/%user%/conf/web/%domain%/nginx.docker.conf*;

        proxy_http_version 1.1;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Forwarded-Host  $host;
        proxy_set_header X-Forwarded-Port  $server_port;
        proxy_set_header Upgrade           $http_upgrade;
        proxy_set_header Connection        $connection_upgrade;
        proxy_redirect off;
    }

    include %home%/%user%/conf/web/%domain%/nginx.ssl.conf_*;
}
EOF

mkdir -p "$TPL_DIR"
sed "s|__PORT__|${PORT_VAR}|"         /tmp/docker.tpl  > "$TPL_DIR/docker.tpl"
sed "s|__SSL_PORT__|${SSL_PORT_VAR}|" /tmp/docker.stpl > "$TPL_DIR/docker.stpl"
chmod 644 "$TPL_DIR/docker.tpl" "$TPL_DIR/docker.stpl"
rm -f /tmp/docker.tpl /tmp/docker.stpl

grep -H 'listen' "$TPL_DIR/docker.tpl" "$TPL_DIR/docker.stpl"
nginx -v
```

El `grep` debe mostrar `%proxy_port%` / `%proxy_ssl_port%` en Modo A, o `%web_port%` / `%web_ssl_port%` en Modo B.
`http2 on;` es sintaxis de nginx 1.25 o posterior; si `nginx -v` dice una anterior, cambia en `docker.stpl` esa línea
por `listen %ip%:__SSL_PORT__ ssl http2;` con tu variable de puerto.

Por qué la plantilla es así:

- **Los `include ... nginx.conf_*;` y `nginx.ssl.conf_*;` del final son obligatorios.** Por ahí mete HestiaCP su bloque
  para el reto de Let's Encrypt. Sin ellos, el reto acaba en el contenedor, y el certificado no se emite ni se renueva.
- **No añadas un `location ^~ /.well-known/...`**: tiene más prioridad que el bloque de HestiaCP y rompería la
  renovación.
- **El `*` al final de cada `include` es deliberado.** Un `include` de un archivo que no existe impide arrancar nginx
  (y caen todos los dominios); con el `*`, si no hay archivo no pasa nada.

### 6.4 Aplicarla al dominio

Con el dominio ya creado en HestiaCP para `$HUSER`, como `root`:

```bash
source /root/hestia-docker.vars
HUSER=usuario; DOMINIO=dominio.com; APP_PORT=42001

$TPL_CMD "$HUSER" "$DOMINIO" docker

cat > "/home/$HUSER/conf/web/$DOMINIO/nginx.docker.conf" <<EOF
proxy_pass http://127.0.0.1:${APP_PORT};
client_max_body_size 100m;
proxy_read_timeout 600s;
proxy_send_timeout 600s;
EOF
chown root:root "/home/$HUSER/conf/web/$DOMINIO/nginx.docker.conf"
chmod 644 "/home/$HUSER/conf/web/$DOMINIO/nginx.docker.conf"

v-rebuild-web-domain "$HUSER" "$DOMINIO"
nginx -t && systemctl reload nginx
```

`client_max_body_size` y los tiempos de espera van en este archivo, no en la plantilla: nginx no admite la misma
directiva dos veces en un `location`. Ajusta `client_max_body_size` para que sea igual o mayor que el `post_max_size` de
PHP.

### 6.5 Certificado SSL

Con la plantilla puesta, el certificado se pide como siempre:

```bash
v-add-letsencrypt-domain "$HUSER" "$DOMINIO"
v-add-web-domain-ssl-force "$HUSER" "$DOMINIO"
```

Para comprobar que la **renovación** funcionará (no consume cuota de Let's Encrypt): HestiaCP responde cualquier ruta
bajo `/.well-known/acme-challenge/` con `<ruta>.<huella de la cuenta>`.

```bash
TOK="prueba$(date +%s)"
for ESQ in http https; do
  R="$(curl -sSL --max-time 15 "$ESQ://$DOMINIO/.well-known/acme-challenge/$TOK" 2>/dev/null)"
  case "$R" in
    "$TOK".*) echo "✓ $ESQ: HestiaCP responde el reto" ;;
    *)        echo "✗ $ESQ: respuesta inesperada" ;;
  esac
done
```

Las dos líneas deben salir con `✓`. Si sale el HTML de PiecesPHP o un `502`, a la plantilla le falta el `include ...
nginx.conf_*;` del final.

---

## 📧 7. Correos con HestiaCP (SMTP)

El `extra_hosts` de `docker-compose.yml` ya da al contenedor el nombre `host.docker.internal`, que apunta al servidor.
Pero el certificado TLS del correo de HestiaCP no está a ese nombre, sino al del dominio de correo (`mail.<dominio>`, si
el dominio de correo tiene SSL activado) o al del servidor. Para que la verificación del certificado no falle, añade
ese nombre apuntando también al servidor:

```yaml
    extra_hosts:
      - "host.docker.internal:host-gateway"
      - "mail.dominio.com:host-gateway"
```

```bash
cd $P && sudo docker compose up -d
```

El correo **no se configura en `config.php`**: se configura en el panel de PiecesPHP, en **Integraciones → Correo**.
Allí pon como servidor `mail.dominio.com` y como puerto `587` (el Exim de HestiaCP), con el usuario y la contraseña de
una cuenta de correo de HestiaCP.

Además, **declara el entorno**: copia `src/app/config/environment.example.php` como `src/app/config/environment.php` y
haz que devuelva `'production'` (o `'local'` en desarrollo). Sin ese archivo, la entrega del correo vale «retenido» y
**ningún correo sale**. Detalle de la entrega, sus valores y el sumidero de pruebas en la
[guía de Mailpit](../mailpit/index.md); `bin/cli mail-doctor` dice en una pantalla cómo está el correo. Dentro del
contenedor:

```bash
cd $P && sudo docker compose exec -u www-data app bash bin/cli mail-doctor
```

---

## 🔄 8. Actualizar

```bash
cd $P
sudo docker compose pull db            # la imagen de MariaDB 11.8 más reciente
sudo docker compose build --pull app   # PHP 8.5 más reciente
sudo docker compose up -d
```

Los datos están en `app/` y `db-data/`: recrear los contenedores no los toca.
