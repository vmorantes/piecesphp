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

export PHP_VERSION_MODULES_5=5.6
export PHP_VERSION_MODULES_70_71={7.0,7.1}
export PHP_VERSION_MODULES_72_74={7.2,7.3,7.4}
export PHP_VERSION_MODULES_80_81={8.0,8.1}
export PHP_MODULES_5={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,mcrypt}
export PHP_MODULES_70_71={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,mcrypt}
export PHP_MODULES_72_74={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl}
export PHP_MODULES_80_81={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl}

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
sudo apt install -y php$PHP_VERSION_MODULES_5-$PHP_MODULES_5
sudo apt install -y php$PHP_VERSION_MODULES_70_71-$PHP_MODULES_70_71
sudo apt install -y php$PHP_VERSION_MODULES_72_74-$PHP_MODULES_72_74
sudo apt install -y php$PHP_VERSION_MODULES_80_81-$PHP_MODULES_80_81
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
