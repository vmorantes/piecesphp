# HestiaCP

- Ubuntu 20
- HestiaCP

## Algunos paquetes
```bash
sudo apt update
sudo apt install curl zip unzip openssl git wget
# Soporte de español, pueden revisarse los idiomas disponibles con locale -a
sudo apt-get install language-pack-es
```

## Variables
```bash
export HESTIA_DOMAIN="sample.com"
export HESTIA_EMAIL="admin@sample.com"
export HESTIA_PASSWORD="hestiacp8083pass"
export HESTIA_PHP_VERSION_MODULES_5=5.6
export HESTIA_PHP_VERSION_MODULES_70_71={7.0,7.1}
export HESTIA_PHP_VERSION_MODULES_72_74={7.2,7.3,7.4}
export HESTIA_PHP_VERSION_MODULES_80_81={8.0,8.1}
export HESTIA_PHP_MODULES_5={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,mcrypt}
export HESTIA_PHP_MODULES_70_71={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,mcrypt}
export HESTIA_PHP_MODULES_72_74={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl}
export HESTIA_PHP_MODULES_80_81={common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl}
```

## Instalación
```bash
#Descargar
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh
#Instalar
bash hst-install.sh --apache yes --phpfpm yes --multiphp yes --vsftpd yes --proftpd no --named yes --mysql yes --postgresql no --exim yes --dovecot yes --clamav yes --spamassassin yes --iptables yes --fail2ban yes --quota yes --api yes --lang en --interactive yes --hostname $HESTIA_DOMAIN --email $HESTIA_EMAIL --password $HESTIA_PASSWORD -f
```
_Nota: Puede ver las opciones en la [documentación de HestiaCP](https://docs.hestiacp.com/getting_started.html#all-available-options-of-install-script)_

## Módulos PHP y Apache
```bash
#Instalar módulos
sudo apt install -y php$HESTIA_PHP_VERSION_MODULES_5-$HESTIA_PHP_MODULES_5
sudo apt install -y php$HESTIA_PHP_VERSION_MODULES_70_71-$HESTIA_PHP_MODULES_70_71
sudo apt install -y php$HESTIA_PHP_VERSION_MODULES_72_74-$HESTIA_PHP_MODULES_72_74
sudo apt install -y php$HESTIA_PHP_VERSION_MODULES_80_81-$HESTIA_PHP_MODULES_80_81
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
- post_max_size: 70M
- upload_max_filesize: 50M
- memory_limit: 500M
