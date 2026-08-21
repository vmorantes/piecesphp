# Apache + PHP-FPM múltiple - Local

## 🧰 Objetivo

* Usar **Apache** para servir múltiples dominios locales.
* Configurar:

  * `localhost` con **PHP 8.1**.
  * `74.localhost` con **PHP 7.4**.
* Habilitar **SSL básico** (sin necesidad de certificados reales).
* Asegurar que cada dominio usa su propia versión de PHP correctamente.

---

## ✅ Paso 1: Instalar Apache y versiones de PHP-FPM

```bash
sudo apt update
sudo apt install apache2 libapache2-mod-proxy-fcgi php8.1-fpm php7.4-fpm
```

(Agrega más versiones si deseas: `php7.2-fpm`, `php8.3-fpm`, etc.)

---

## ✅ Paso 2: Habilitar módulos necesarios en Apache

```bash
sudo a2enmod proxy_fcgi setenvif ssl
sudo systemctl restart apache2
```

---

## ✅ Paso 3: Configurar el archivo `/etc/hosts`

Edita:

```bash
sudo nano /etc/hosts
```

Asegúrate de tener:

```
127.0.0.1   localhost
127.0.0.1   74.localhost
```

---

## ✅ Paso 4: Crear los directorios para cada sitio

```bash
sudo mkdir -p /var/www/html
sudo mkdir -p /var/www/74.localhost
```

Agrega un archivo para verificar PHP:

**Para `localhost`:**

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

**Para `74.localhost`:**

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/74.localhost/info.php
```

---

## ✅ Paso 5: Configurar VirtualHosts

### 🖥️ `localhost` (PHP 8.1)

Archivo: `/etc/apache2/sites-available/000-default.conf`

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.1-fpm.sock|fcgi://localhost"
    </FilesMatch>

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
</VirtualHost>
```

---

### 🖥️ `74.localhost` (PHP 7.4)

Archivo: `/etc/apache2/sites-available/74.localhost.conf`

```apache
<VirtualHost *:80>
    ServerName 74.localhost
    DocumentRoot /var/www/74.localhost

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php7.4-fpm.sock|fcgi://localhost"
    </FilesMatch>

    SSLEngine on
    SSLCertificateFile /etc/ssl/certs/ssl-cert-snakeoil.pem
    SSLCertificateKeyFile /etc/ssl/private/ssl-cert-snakeoil.key
</VirtualHost>
```

---

## ✅ Paso 6: Habilitar los sitios

```bash
sudo a2ensite 74.localhost.conf
sudo a2ensite 000-default.conf
sudo systemctl reload apache2
```

---

## ✅ Paso 7: Verificar en navegador

* Accede a:
  `http://localhost/info.php` → debe mostrar **PHP 8.1**
  `http://74.localhost/info.php` → debe mostrar **PHP 7.4**

---

## 🔒 Nota sobre SSL

Aunque se incluye la configuración `SSLEngine on` y los archivos de certificados snakeoil, **esto no activa HTTPS por sí solo**. Para servir por HTTPS en local deberías también:

1. Crear un VirtualHost en el puerto `443`.
2. Generar certificados válidos o auto-firmados (si se desea realmente HTTPS funcional en navegador).

Pero **como se pidió**, la SSL está "activa" sintácticamente sin necesidad de certificados válidos.

---

## 🧹 Limpieza opcional

Puedes limpiar el contenido de los sitios cuando termines de probar:

```bash
sudo rm /var/www/html/info.php
sudo rm /var/www/74.localhost/info.php
```

---

## 🏁 Conclusión

Con esta configuración:

* Apache usa **PHP-FPM por versión** según el subdominio.
* No necesitas cambiar manualmente la versión activa de PHP.
* Puedes extender fácilmente esto a `72.localhost`, `83.localhost`, etc.