#Preparación del entorno del servidor:

###Nota:
- Esta guia proporciona los pasos para un entorno con las siguientes características:
    - Debian 9
    - VestaCP
    - PHP 7.1
    - MariaDB 10.3

##Intalación de VestaCP
- Instalar curl:
sudo apt install curl

- Descargar VestaCP:
curl -O http://vestacp.com/pub/vst-install.sh

- Instalar VestaCP:

bash vst-install.sh --nginx yes --apache yes --phpfpm no --named yes --remi yes --vsftpd yes --proftpd no --iptables yes --fail2ban yes --quota no --exim yes --dovecot no --spamassassin no --clamav no --softaculous no --mysql yes --postgresql no --hostname {{DOMINIO}} --email {{EMAIL}} --password {{CONTRASEÑA}}

###Nota:
- Algunas veces phpMyAdmin muestra varios errores por lo que es recomendable ejecutar la siguiente instrucción
curl -O -k https://raw.githubusercontent.com/skurudo/phpmyadmin-fixer/master/pma.sh && chmod +x pma.sh && ./pma.sh

##Intalación de PHP 7.1

- Actualizar repositorios:
sudo apt install apt-transport-https lsb-release ca-certificates
sudo wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg
sudo sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list'
sudo apt update

- Instalar php:
sudo apt install php7.1

- Verificar el cambio
php -v

- Cambiar alternativa de php en apache:
sudo a2dismod php* && sudo a2enmod php7.1

- Reiniciar apache
sudo service apache2 restart

- Cambiar alternativa de php en consola (opcional si no ocurrió automáticamente):
update-alternatives --config php

###Instalar extensiones PHP 
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

##Configuración de MariaDB

###Instalar MariaDB 10.3:
- Actualizar repositorios:
sudo apt-get install software-properties-common dirmngr
sudo apt-key adv --recv-keys --keyserver keyserver.ubuntu.com 0xF1656F24C74CD1D8
sudo add-apt-repository 'deb [arch=amd64,i386,ppc64el] http://mirror.upb.edu.co/mariadb/repo/10.3/debian stretch main'
sudo apt-get update
- Remover versiones anteriores:
    - Notas:
        - Por prevención se intenta remover mysql también.
        - MariaDB preguntará si se borrarán o no las bases de datos (seleccione que no se borren preferiblemente).

sudo apt-get remove --purge mysql* mariadb*

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


###Crear un usuario:
- Conectarse a la consola mysql:
mysql
- Crear usuario:
CREATE USER 'USUARIO'@'localhost' IDENTIFIED BY 'CONTRASEÑA';
- Otorgar permisos al usuario_
GRANT ALL PRIVILEGES ON *.* TO 'USUARIO'@'localhost';
- Refrescar privilegios:
FLUSH PRIVILEGES;

##Instalar Composer (globalmente):
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer


##Proteger phpmyadmin

- Permitir el uso de .htaccess:
nano /etc/apache2/conf.d/phpmyadmin.conf
AllowOverride All
- Crear .htpasswd:
cd /etc/phpmyadmin/
echo "admin:\$apr1\$F7IUvByI\$Eg4uhukF6sog9gCmpz8wh0" > .htpasswd
- Crear .htaccess:
cd /usr/share/phpmyadmin
echo "AuthType Basic" > .htaccess
echo "AuthName \"My Protected Area\"" >> .htaccess
echo "AuthUserFile /etc/phpmyadmin/.htpasswd" >> .htaccess
echo "Require valid-user" >> .htaccess
- Reiniciar apache2
service apache2 restart
