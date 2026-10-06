# Instalación de PHP en Ubuntu 26.04 LTS

## Introducción
PHP es el lenguaje base de PiecesPHP. **El framework exige PHP `>=8.5 <8.6`** (`src/composer.json`): ni 8.4 ni
anteriores, ni 8.6. Ubuntu 26.04 LTS trae PHP 8.5 en sus repositorios oficiales (paquetes `php8.5-*`), así que no hace
falta ningún repositorio adicional.

---

## Actualizar repositorios

```bash
sudo apt update
```

`php8.5-zip`, `php8.5-fpm` y `php8.5-mongodb` están en el componente *universe*, activo en una instalación normal de
Ubuntu. Si `apt` no las encuentra, actívalo:

```bash
sudo add-apt-repository universe
sudo apt update
```

---

## Instalación de PHP y extensiones

Instala PHP 8.5 con las extensiones que declara `src/composer.json` (`ext-*`). En los paquetes de Ubuntu se reparten
así:

- **Compiladas en el propio PHP** (no tienen paquete ni archivo `.ini`): `date`, `pcre`, `hash`, `json`, `openssl`,
  `session` y `zlib`.
- **`php8.5-common`** (lo arrastra cualquier otro paquete `php8.5-*`): `ctype`, `fileinfo` y `pdo`, entre otras.
- **`php8.5-xml`**: `xml`, `xmlreader`, `xmlwriter`, `xsl`, `dom` y `simplexml`. El paquete `php8.5-xsl` existe, pero
  es un paquete vacío que solo depende de `php8.5-xml`.
- **`php8.5-mysql`**: `mysqli`, `pdo_mysql` y `mysqlnd`.
- **`php8.5-sqlite3`**: `sqlite3` y `pdo_sqlite`.
- **Un paquete cada una**: `mbstring`, `gd`, `curl` y `zip` (`php8.5-mbstring`, `php8.5-gd`, `php8.5-curl`,
  `php8.5-zip`).

```bash
sudo apt install -y php8.5-cli php8.5-common \
    php8.5-xml php8.5-mbstring php8.5-gd php8.5-curl php8.5-zip \
    php8.5-mysql php8.5-sqlite3
```

- `zlib` (sugerida por el framework) ya viene compilada.
- `mongodb` (`ext-mongodb`, sugerida) es opcional y solo para quien use esa integración: `sudo apt install -y
  php8.5-mongodb`.
- OPcache viene compilado en PHP 8.5: no hay paquete `php8.5-opcache`.
- El paquete `php8.5` es un metapaquete que exige además un SAPI web (`libapache2-mod-php8.5`, `php8.5-fpm` o
  `php8.5-cgi`); por eso aquí no se instala y el SAPI se elige en «Activar PHP en Apache».

Comprueba que están todas:

```bash
php8.5 -m
```

Y, desde `src/`, que Composer las ve:

```bash
composer check-platform-reqs
```

---

## Cambiar la versión activa de PHP

Si la máquina tiene más de una versión instalada (por ejemplo, otra añadida desde un repositorio externo), `php` a
secas puede apuntar a otra. Selecciona la 8.5 para la terminal:

```bash
sudo update-alternatives --config php
php -v
```

---

## Activar PHP en Apache

Con el módulo de Apache (`mod_php`):

```bash
sudo apt install -y libapache2-mod-php8.5
sudo a2enmod php8.5
sudo systemctl restart apache2
```

Con PHP-FPM (recomendado para rendimiento; ver la guía de rendimiento):

```bash
sudo apt install -y php8.5-fpm
sudo a2dismod php8.5          # solo si tenías activado mod_php (libapache2-mod-php8.5)
sudo a2dismod mpm_prefork     # mpm_event no se activa mientras mpm_prefork esté activo
sudo a2enmod proxy_fcgi setenvif mpm_event
sudo a2enconf php8.5-fpm
sudo systemctl restart php8.5-fpm apache2
```

---

## Instalar Composer (globalmente)

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

Asegúrate de que `php -v` dice 8.5 antes de instalar Composer y de ejecutarlo: Composer se arranca con el PHP que
encuentre en el `PATH`.

---

## Recursos útiles
- [Documentación oficial de PHP](https://www.php.net/manual/es/)
