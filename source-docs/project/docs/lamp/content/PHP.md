# Instalación de PHP

- Ubuntu 20
- Composer

## Actualizar repositorios

```bash
sudo apt-get update
sudo apt -y install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
```
## Instalación

```bash
#Moverse al directorio de usuario
cd ~

#Instalar php
sudo apt install -y php5.6
sudo apt install -y php7.0
sudo apt install -y php7.1
sudo apt install -y php7.2
sudo apt install -y php7.3
sudo apt install -y php7.4
sudo apt install -y php8.0
sudo apt install -y php8.1

#Reiniciar apache
sudo service apache2 restart

#Seleccionar la versión de PHP en usada mediante terminal:
sudo update-alternatives --config php

#Instalar extensiones PHP
sudo apt install -y php{5.6,7.0,7.1}-mcrypt
sudo apt install -y php*-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,intl}
```
## Activación

```bash
#Activar versión en apache
sudo a2dismod php5* php7* php8*
sudo a2enmod php7.4
sudo service apache2 restart
```

## Instalar Composer (globalmente)

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```
