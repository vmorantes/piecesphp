# HestiaCP:

- Ubuntu 20
- HestiaCP

## Algunos paquetes
```bash
sudo apt install curl zip unzip openssl git wget
```

## Variables
```bash
export HESTIA_DOMAIN="sample.com"
export HESTIA_EMAIL="admin@sample.com"
export HESTIA_PASSWORD="hestiacp8083pass"
export HESTIA_PHP_VERSION_MODULES="*"
```

## Instalación
```bash
#Descargar
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh
#Instalar
bash hst-install.sh --apache yes --nginx yes --phpfpm yes --multiphp yes --vsftpd yes --proftpd no --named yes --mysql yes --postgresql no --exim yes --dovecot yes --clamav yes --spamassassin yes --iptables yes --fail2ban yes --quota yes --api yes --lang en --interactive yes --hostname $HESTIA_DOMAIN --email $HESTIA_EMAIL --password $HESTIA_PASSWORD 
```
_Nota: Puede ver las opciones en la [documentación de HestiaCP](https://docs.hestiacp.com/getting_started.html#all-available-options-of-install-script)_

## Módulos PHP y Apache
```bash
#Instalar módulos
sudo apt install php$HESTIA_PHP_VERSION_MODULES-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,mcrypt}
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
mv composer.phar /usr/local/bin/composer
```

## PHPMyAdmin
- Puede seguir los pasos acá: [PHPMyAdmin](../lamp/content/PHPMyAdmin.md)

## Adminer (alternativa ligera a PHPMyAdmin)
- Puede seguir los pasos acá: [Adminer](../lamp/content/Adminer.md)

## Otras configuraciones de HestiaCP

### Configuraciones de PHP

- max_execution_time: 600
- max_input_time: 600
- post_max_size: 20M
- upload_max_filesize: 20M
- memory_limit: 200M
