# HestiaCP

Antes de iniciar con la instalación debe confirmar que la la versión del sistema operativo es Ubuntu 22.04 LTS, la versión 1.8x de Hestia CP no es compatible con la versión 24.04 LTS de Ubuntu. Este tutorial está ajustado a la versión de HestiaCP 1.8.12 del 26 de agosto de 2024.

## Algunos paquetes

Realice una actualziación del sistema antes de iniciar intslando los siguientes paquetes:

```bash
sudo apt update
sudo apt install -y curl zip unzip openssl git wget
# Soporte de español, pueden revisarse los idiomas disponibles con locale -a
sudo apt-get install -y language-pack-es
```

## Variables

Defina las variables a utilizar, reemplazando los valores a continuación por los que requiera:

```bash
export HESTIA_DOMAIN="sample.com"
export HESTIA_EMAIL="admin@sample.com"
export HESTIA_PASSWORD="hestiacp8083pass"
```

_Nota: Si su proveedor de hosting es OVH, utilice el nombre del VPS en Hestia Domain._


## Instalación

Los siguientes valores están optimizados para un uso estandar de PiecesPHP, en caso de requerir personalizar la instalación vaya a la sección para ajustar la [Instalación de HestiaCP](https://hestiacp.com/install.html).

```bash
#Descargar
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh
#Instalación con opciones relevantes
sudo bash hst-install.sh --hostname $HESTIA_DOMAIN --email $HESTIA_EMAIL --password $HESTIA_PASSWORD --multiphp yes --clamav no --quota yes

#Instalación con todas las opciones
sudo bash hst-install.sh --apache yes --phpfpm yes --multiphp yes --vsftpd yes --proftpd no --named yes --mysql yes --postgresql no --exim yes --dovecot yes --clamav no --spamassassin yes --iptables yes --fail2ban yes --quota yes --api yes --lang en --interactive yes --hostname $HESTIA_DOMAIN --email $HESTIA_EMAIL --password $HESTIA_PASSWORD -f
```

### Acceso

Ingrese por el valor asignado en HESTIA_DOMAIN y el puerto seleccionado, por defecto es el 8083.

### Recomendaciones

Cuando finalice la instalación se recomienda:
- Al momento de crear el primer dominio dentro de HestiaCP, crear un usuario con permisos limitados y habilitar el acceso Bash al perfil del usuario creado.
- Conectese por FileZilla o cualquier gestor FTP que utilice para la carga de archivos.


## Casos de error

### User admin exists

```bash
#Abrir /etc/group
nano /etc/group
#Borrar línea admin:x:117: o similiar y guardar, luego repetir el proceso de instalación y no debería dar ningún otro problema
```

## Módulos PHP y Apache
```bash
#Instalar módulos
sudo apt install -y php{5.6,7.0,7.1}-mcrypt
sudo apt install -y php*-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,intl}
#Módulos apache
sudo a2enmod rewrite headers ssl
#Reiniciar apache
sudo service apache2 restart
```

## Configuración de MariaDB
- Puede seguir los pasos acá: [MariaDB](../lamp/content/MariaDB.md)

## Instalar Composer (globalmente)

```bash
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

## PHPMyAdmin
- Puede seguir los pasos acá: [PHPMyAdmin](../lamp/content/PHPMyAdmin.md)

## Adminer (alternativa ligera a PHPMyAdmin)
- Puede seguir los pasos acá: [Adminer](../lamp/content/Adminer.md)

## Otras configuraciones de HestiaCP

### Configuraciones de PHP

- max_execution_time: 600
- max_input_time: 600
- post_max_size: 70M
- upload_max_filesize: 50M
- memory_limit: 1000M
