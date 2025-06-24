# Instalación de PHP en Ubuntu 24.04 LTS

## Introducción
PHP es el lenguaje base de PiecesPHP. Aquí aprenderás a instalar varias versiones y las extensiones necesarias.

---

## Actualizar repositorios

```bash
sudo apt update && sudo apt install -y software-properties-common lsb-release ca-certificates apt-transport-https
```

Agrega el repositorio de PHP mantenido por Ondřej Surý:

```bash
sudo add-apt-repository ppa:ondrej/php
sudo apt update
```

---

## Instalación de PHP y extensiones

Instala la versión recomendada (8.1) y otras si lo necesitas:

```bash
#Instalar php
sudo apt install -y php5.6
sudo apt install -y php7.0
sudo apt install -y php7.1
sudo apt install -y php7.2
sudo apt install -y php7.3
sudo apt install -y php7.4
sudo apt install -y php8.0
sudo apt install -y php8.1
sudo apt install -y php8.2
sudo apt install -y php8.3
```

---

## Cambiar la versión activa de PHP

```bash
#Seleccionar la versión de PHP en usada mediante terminal:
sudo update-alternatives --config php

#Instalar extensiones PHP
sudo apt install -y php{5.6,7.0,7.1}-mcrypt
sudo apt install -y php*-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,intl}
```

---

## Activar PHP en Apache

```bash
#Activar versión en apache
sudo a2dismod php5* php7* php8*
sudo a2enmod php8.1
sudo systemctl restart apache2
```

---

## Instalar Composer (globalmente)

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

---

## Recursos útiles
- [Documentación oficial de PHP](https://www.php.net/manual/es/)
