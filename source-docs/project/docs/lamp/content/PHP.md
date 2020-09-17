# Instalación de PHP 7.3 (probado también con 7.4, 7.1)

- Ubuntu 20
- PHP 7.3
- Composer

## Actualizar repositorios

```bash
sudo apt-get update
sudo apt -y install software-properties-common
sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
```
## Intalación

```bash
#Moverse al directorio de usuario
cd ~

#Instalar php
sudo apt install php7.3

#Reiniciar apache
sudo service apache2 restart

#Cambiar alternativa de php en consola (opcional si no ocurrió automáticamente):
sudo update-alternatives --config php

#Instalar extensiones PHP
sudo apt install php7.3-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl}
##Si se decidió por PHP 7.1
sudo apt install php7.1-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mcrypt,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl}
```
## Activación

```bash
#Activar versión en apache
sudo a2dismod php5* php7*
sudo a2enmod php7.3
sudo service apache2 restart
```

## Instalar Composer (globalmente)

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```
