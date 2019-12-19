# Instalación de apache2/php/mariadb/phpmyadmin

### Nota:
- Esta guia proporciona los pasos para un entorno con las siguientes características:
    - Debian 9
    - Apache2
    - PHP 7.1
    - MariaDB 10.3
    - PHPMyAdmin 4.9

## Intalación de apache2

```bash
apt install apache2
a2enmod rewrite
```
## Intalación de PHP 7.1

```bash
#Actualizar repositorios
apt install apt-transport-https lsb-release ca-certificates
wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
apt update

#Instalar php
apt install php7.1

#Verificar el cambio
php -v

#Cambiar alternativa de php en apache
a2dismod php* && a2enmod php7.1

#Reiniciar apache
sudo service apache2 restart

#Cambiar alternativa de php en consola (opcional si no ocurrió automáticamente):
update-alternatives --config php

#Instalar extensiones PHP 

sudo apt install php7.1-pdo php7.1-xml php7.1-ctype php7.1-mbstring php7.1-fileinfo php7.1-gd php7.1-mcrypt php7.1-mysqli php7.1-sqlite3 php7.1-zip php7.1-xsl php7.1-xmlwriter php7.1-xmlreader

#### Mcrypt en 7.2
sudo apt-get -y install gcc make autoconf libc-dev pkg-config
sudo apt-get -y install php7.2-dev
sudo apt-get -y install libmcrypt-dev
```

## Configuración de MariaDB

```bash
#Actualizar repositorios:
sudo apt-get install software-properties-common dirmngr
sudo apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xF1656F24C74CD1D8
sudo add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://mirror.upb.edu.co/mariadb/repo/10.3/debian stretch main'
sudo apt-get update
#Instalar
sudo apt-get install mariadb-server mariadb-client
```

### Crear un usuario

```bash
#Conectarse a la consola mysql
mysql
```
```sql
-- Crear usuario:
CREATE USER 'USUARIO'@'localhost' IDENTIFIED BY 'CONTRASEÑA';
-- Otorgar permisos al usuario
GRANT ALL PRIVILEGES ON *.* TO 'USUARIO'@'localhost';
-- Refrescar privilegios:
FLUSH PRIVILEGES;
```
## Instalar Composer (globalmente)

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
```

## PHPMyAdmin

```bash
# Descargar e instalar
cd ~
wget https://files.phpmyadmin.net/phpMyAdmin/4.9.2/phpMyAdmin-4.9.2-all-languages.zip
cd /CARPETA_PUBLICA
unzip ~/phpMyAdmin-4.9.2-all-languages.zip -d .
mv phpMyAdmin-4.9.2-all-languages phpmyadmin

# Configurar
cp config.sample.inc.php config.inc.php 

#Configurar blowfish
nano config.inc.php

#Permisos
chmod -R 0755 phpmyadmin
chmod -R 0755 phpmyadmin/*
mkdir phpmyadmin/tmp
chmod -R 0777 phpmyadmin/tmp
#Verificar usuario del servidor apache2 (posiblemente sea www-data)
ps aux | egrep '(apache|httpd|www)'
#Cambiar de propietario la carpeta de phpmyadmin
chown -R www-data:www-data phpmyadmin
chown -R www-data:www-data phpmyadmin/*

#Seguridad
##Borrar setup
rm -Rf setup
```
