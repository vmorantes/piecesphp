# Apache + PHP-FPM múltiple - Local

## 🧰 Objetivo

* Usar **Apache** para servir múltiples dominios locales, cada uno con su versión de PHP-FPM.
* Configurar:

  * `localhost` con **PHP 8.5**, la que exige PiecesPHP (`>=8.5 <8.6`).
  * `83.localhost` con **PHP 8.3**, como ejemplo de un segundo sitio con otra versión (no es para PiecesPHP: solo sirve
    para probar que cada dominio usa su propio PHP-FPM).
* Asegurar que cada dominio usa su propia versión de PHP correctamente.

> **Requisito:** Ubuntu 26.04 LTS trae en sus repositorios oficiales una sola versión de PHP, la 8.5. Para una segunda
> versión (aquí, la 8.3) hace falta el repositorio de Ondřej Surý, `packages.sury.org/php`, que publica para Ubuntu
> 26.04 (*resolute*) varias versiones de PHP, de la 8.0 a la 8.5. Si solo te hace falta PiecesPHP, usa únicamente el
> sitio `localhost`, omite el segundo y no añadas ese repositorio.

---

## ✅ Paso 1: Instalar Apache y versiones de PHP-FPM

Solo PHP 8.5, con los paquetes de Ubuntu:

```bash
sudo apt update
sudo apt install -y apache2 php8.5-fpm
```

Para el segundo sitio, añade el repositorio de Surý (son los pasos de su
[README](https://packages.sury.org/php/README.txt)) e instala la 8.3:

```bash
sudo apt install -y lsb-release ca-certificates curl
sudo curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb
sudo dpkg -i /tmp/debsuryorg-archive-keyring.deb
sudo sh -c 'echo "deb [signed-by=/usr/share/keyrings/debsuryorg-archive-keyring.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt update
sudo apt install -y php8.3-fpm
```

Con ese repositorio activo, `apt upgrade` sustituye también los paquetes `php8.5-*` de Ubuntu por los de Surý, que
llevan un número de versión mayor. Puedes añadir más versiones del mismo modo (por ejemplo `php8.4-fpm`) para otros
sitios que no sean PiecesPHP.

Si tenías activado un `mod_php` (`libapache2-mod-php*`), desactívalo para que no compita con PHP-FPM, por ejemplo
`sudo a2dismod php8.5`.

---

## ✅ Paso 2: Habilitar módulos necesarios en Apache

```bash
sudo a2enmod proxy_fcgi setenvif
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
127.0.0.1   83.localhost
```

---

## ✅ Paso 4: Crear los directorios para cada sitio

```bash
sudo mkdir -p /var/www/html
sudo mkdir -p /var/www/83.localhost
```

Agrega un archivo para verificar PHP:

**Para `localhost`:**

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/html/info.php
```

**Para `83.localhost`:**

```bash
echo "<?php phpinfo(); ?>" | sudo tee /var/www/83.localhost/info.php
```

---

## ✅ Paso 5: Configurar VirtualHosts

### 🖥️ `localhost` (PHP 8.5)

Archivo: `/etc/apache2/sites-available/000-default.conf`

```apache
<VirtualHost *:80>
    ServerName localhost
    DocumentRoot /var/www/html

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.5-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

---

### 🖥️ `83.localhost` (PHP 8.3)

Archivo: `/etc/apache2/sites-available/83.localhost.conf`

```apache
<VirtualHost *:80>
    ServerName 83.localhost
    DocumentRoot /var/www/83.localhost

    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

---

## ✅ Paso 6: Habilitar los sitios

```bash
sudo a2ensite 83.localhost.conf
sudo a2ensite 000-default.conf
sudo systemctl reload apache2
```

---

## ✅ Paso 7: Verificar en navegador

* Accede a:
  `http://localhost/info.php` → debe mostrar **PHP 8.5**
  `http://83.localhost/info.php` → debe mostrar **PHP 8.3**

---

## 🔒 Nota sobre SSL

Esta guía sirve solo por HTTP (puerto 80). **No se pone `SSLEngine on` en un `VirtualHost *:80`**: Apache lo trataría
como un puerto en claro con SSL activado y las peticiones fallarían. Para HTTPS en local:

1. Activa el módulo: `sudo a2enmod ssl`.
2. Crea un `VirtualHost *:443` con `SSLEngine on`, `SSLCertificateFile` y `SSLCertificateKeyFile` (puedes usar el
   certificado autofirmado `snakeoil` que instala el paquete `ssl-cert`, en `/etc/ssl/certs/ssl-cert-snakeoil.pem` y
   `/etc/ssl/private/ssl-cert-snakeoil.key`; el navegador mostrará una advertencia porque no es de una autoridad real).
3. Para un dominio real, ver la guía de SSL con Let's Encrypt del entorno LAMP.

---

## 🧹 Limpieza opcional

Puedes limpiar el contenido de los sitios cuando termines de probar. `info.php` muestra la configuración completa de
PHP: no lo dejes en un servidor accesible.

```bash
sudo rm /var/www/html/info.php
sudo rm /var/www/83.localhost/info.php
```

---

## 🏁 Conclusión

Con esta configuración:

* Apache usa **PHP-FPM por versión** según el subdominio.
* No necesitas cambiar manualmente la versión activa de PHP.
* Puedes extender fácilmente esto a otros subdominios (`84.localhost`, etc.), recordando que **PiecesPHP solo corre
  sobre PHP 8.5**.
