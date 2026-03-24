# Despliegue con Docker y HestiaCP (Guía Completa)

## BETA: Esta guía se encuentra en desarrollo

Esta guía presenta un flujo de trabajo para dockerizar **PiecesPHP**, integrándolo con **HestiaCP** como Proxy Inverso y utilizando tu versión personalizada de **Adminer** incluida en el código fuente.

---

## 🏗️ 1. Estructura del "Paquete"

Para este despliegue, coloca los archivos Docker en la raíz de tu proyecto:

```text
/mi-proyecto/
├── src/                # Código fuente (incluye src/adminer/)
├── bin/                # Scripts CLI
├── secure-keys/        # Llaves de seguridad
├── Dockerfile          # Definición de la imagen
├── docker-compose.yml  # Orquestación y persistencia
└── ...
```

---

## 🛠️ 2. Dockerfile (Extensiones Cruciales)

Este archivo crea una imagen de PHP 8.4 con **todas** las extensiones necesarias, incluyendo las que usa tu versión compilada de Adminer (como `sqlite3`).

```dockerfile
FROM php:8.4-apache

# 1. Instalar librerías de sistema necesarias
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev \
    libzip-dev libicu-dev libxml2-dev libxslt1-dev \
    libonig-dev libcurl4-openssl-dev libsqlite3-dev \
    locales zip unzip git curl openssl \
    && rm -rf /var/lib/apt/lists/*

# 2. Configurar e instalar extensiones PHP (Matching HestiaCP)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    pdo pdo_mysql pdo_sqlite mysqli sqlite3 \
    xml xmlwriter xmlreader xsl \
    mbstring fileinfo gd zip intl curl \
    && docker-php-ext-enable opcache

# 3. Soporte de múltiples idiomas (Locales)
RUN sed -i 's/# es_ES.UTF-8 UTF-8/es_ES.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i 's/# it_IT.UTF-8 UTF-8/it_IT.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i 's/# en_US.UTF-8 UTF-8/en_US.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i 's/# pt_PT.UTF-8 UTF-8/pt_PT.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i 's/# de_DE.UTF-8 UTF-8/de_DE.UTF-8 UTF-8/' /etc/locale.gen && \
    sed -i 's/# fr_FR.UTF-8 UTF-8/fr_FR.UTF-8 UTF-8/' /etc/locale.gen && \
    locale-gen

ENV LANG es_ES.UTF-8
ENV LANGUAGE es_ES:es
ENV LC_ALL es_ES.UTF-8

# 4. Habilitar mod_rewrite para Slim/PiecesPHP
RUN a2enmod rewrite headers

# 5. Configurar DocumentRoot a /var/www/html/src
ENV APACHE_DOCUMENT_ROOT /var/www/html/src
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html
```

---

## 📦 3. Docker Compose (Persistencia Total)

Utilizamos un montaje de volumen unificado. No se requiere contenedor externo de Adminer ya que el tuyo está en `src/adminer/`.

```yaml
version: '3.8'

services:
  app:
    build: .
    container_name: piecesphp-app
    ports:
      - "8080:80" # HestiaCP hará Proxy Pass aquí
    volumes:
      - .:/var/www/html # Persistencia total "en el mismo lugar" del host
    environment:
      - DB_HOST=db
      - LANG=es_ES.UTF-8
    depends_on:
      - db

  db:
    image: mariadb:10.11
    container_name: piecesphp-db
    restart: always
    environment:
      MARIADB_ROOT_PASSWORD: my_root_password
      MARIADB_DATABASE: my_db
      MARIADB_USER: my_user
      MARIADB_PASSWORD: my_user_password
    volumes:
      - db_data:/var/lib/mysql

volumes:
  db_data:
```

---

## 💾 4. Acceso a Adminer y Migración

Tu versión de **Adminer** vive dentro de la aplicación servidor.

1.  **Acceso:** Ve a `http://tudominio.com/adminer/`. 
2.  **Seguridad:** Como has configurado **Basic Auth** en `index.php`, te pedirá usuario y contraseña antes de cargar el motor de DB.
3.  **Conexión:** Desde tu Adminer, conecta al servidor MariaDB usando el nombre de host `db`.

Para migrar datos rápidamente:
```bash
docker exec -i piecesphp-db mysql -u my_user -pmy_user_password my_db < backup.sql
```

---

## 🛡️ 5. Configuración en HestiaCP (Proxy Pass)

Configura el dominio en HestiaCP para que apunte al puerto `8080`:

```nginx
location / {
    proxy_pass http://127.0.0.1:8080;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
}
```

---

## 📧 6. Correos con HestiaCP (SMTP)

Añade esto a tu `docker-compose.yml` para que el contenedor reconozca el servidor de correo local:

```yaml
    extra_hosts:
      - "host.docker.internal:host-gateway"
```

Luego, en `config.php`, usa `host.docker.internal` para conectar con el EXIM de HestiaCP por el puerto `587`.
