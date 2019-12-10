# Preparación del entorno del servidor:

### Nota:
- Esta guia proporciona los pasos para un entorno con las siguientes características:
    - Debian 9
    - HestiaCP
    - PHP 7.1
    - MariaDB 10.3

## Intalación de algunos paquetes
apt install unzip

## Intalación de HestiaCP

### HestiaCP

- Descargar HestiaCP:
cd ~
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh

- Instalar HestiaCP:

bash hst-install.sh --nginx yes --apache yes --multiphp yes --named yes --vsftpd yes --iptables yes --fail2ban yes --exim no --dovecot no --spamassassin no --clamav no --mysql no --lang es --hostname DOMINIO --email EMAIL --password CONTRASEÑA

## Intalación de PHP 7.1

- Actualizar repositorios:
sudo apt install apt-transport-https lsb-release ca-certificates
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt update

- Instalar php:
sudo apt install php7.1 libapache2-mod-php7.1

- Verificar el cambio
php -v

- Cambiar alternativa de php en consola (opcional si no ocurrió automáticamente):
update-alternatives --config php

- Cambiar alternativa de php en apache:
sudo a2dismod phpVERSION && sudo a2enmod php7.1

- Reiniciar apache
sudo service apache2 restart

### Instalar extensiones PHP 
- Notas: 
    - La extensión mcrypt debe instalarse como extensión PECL luego de php 7.1.*
    - Al finalizar se debe reiniciar apache

sudo apt install php7.1-pdo php7.1-xml php7.1-ctype php7.1-mbstring php7.1-fileinfo php7.1-gd php7.1-mcrypt php7.1-mysqli php7.1-sqlite3 php7.1-zip php7.1-xsl php7.1-xmlwriter php7.1-xmlreader

#### Extensiones sugeridas:
sudo apt install php7.1-mongodb

#### Mcrypt en > 7.1
sudo apt-get -y install gcc make autoconf libc-dev pkg-config
sudo apt-get -y install php7.2-dev
sudo apt-get -y install libmcrypt-dev

## Módulos apache2
a2enmod rewrite
service apache2 restart

## Configuración de MariaDB

### Instalar MariaDB 10.3:
- Actualizar repositorios:
sudo apt-get install software-properties-common dirmngr
sudo apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xF1656F24C74CD1D8
sudo add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://mirror.upb.edu.co/mariadb/repo/10.3/debian stretch main'
sudo apt-get update

- Instalar MariaDB:
sudo apt-get install mariadb-server mariadb-client

- Caso de error 1524 (HY000): Plugin 'unix_socket' is not loaded:

sudo su
/etc/init.d/mysql stop
mysqld_safe --skip-grant-tables &
mysql -uroot
use mysql;
update user set password=PASSWORD("") where User='root';
update user set plugin="mysql_native_password";
quit;
/etc/init.d/mysql stop
kill -9 $(pgrep mysql)
/etc/init.d/mysql start


### Crear un usuario:
- Conectarse a la consola mysql:
mysql
- Crear usuario:
CREATE USER 'USUARIO'@'localhost' IDENTIFIED BY 'CONTRASEÑA';
- Otorgar permisos al usuario_
GRANT ALL PRIVILEGES ON *.* TO 'USUARIO'@'localhost';
- Refrescar privilegios:
FLUSH PRIVILEGES;

## Instalar Composer (globalmente):
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer
composer --version

## PHPMyAdmin

### Instalar 
Nota: Activar SSL en Hestia
cd ~
wget https://files.phpmyadmin.net/phpMyAdmin/4.9.2/phpMyAdmin-4.9.2-all-languages.zip
cd /home/admin/web/DOMINIO/public_html/
unzip ~/phpMyAdmin-4.9.2-all-languages.zip -d .
rm index.html robots.txt
mv phpMyAdmin-4.9.2-all-languages phpmyadmin

### Configurar
cp config.sample.inc.php config.inc.php 
nano config.inc.php
- Configurar blowfish

### Permisos

- Aplicar permisos a la carpeta de phpmyadmin
chmod -R 0755 phpmyadmin
chmod -R 0755 phpmyadmin/*
mkdir phpmyadmin/tmp
chmod -R 0777 phpmyadmin/tmp
- Verificar usuario del servidor apache2 (posiblemente sea www-data)
ps aux | egrep '(apache|httpd|www)'
- Cambiar de propietario la carpeta de phpmyadmin
chown -R www-data:www-data phpmyadmin
chown -R www-data:www-data phpmyadmin/*

### Seguridad

- Borrar setup
rm -Rf setup

## Otras configuraciones

### Configuraciones de PHP

- max_execution_time: 600
- max_input_time: 600
- post_max_size: 20M
- upload_max_filesize: 20M
- memory_limit: 200M
