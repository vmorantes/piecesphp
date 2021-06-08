# PiecesPHP Framework

## Configuración de entorno

- Requerimientos
    - PiecesPHP
    - Composer
    - NodeJS 12.x LTS
    - NPM
    - Gulp CLI

### Actualizar repositorios
```bash
#Integrar respositorio
curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -
#Actualizar
sudo apt update
```

### Instalación de dependencias de desarrollo

#### Composer

- Mediante apt:
```bash
#Instalar
sudo apt install composer
#Verificar versión
composer --version ##1.10.1
```
- Mediante descarga:
```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
mv composer.phar /usr/local/bin/composer ##Para que esté disponible globalmente
```

#### NodeJS y NPM
```bash
#Instalar
sudo apt install nodejs
#Verificar versiones
node --version ##v12.18.4
npm --version ##6.14.6
```

#### Gulp CLI y Typescript
```bash
#Instalar globalmente (sudo es solo para paquetes globales)
sudo npm install -g gulp-cli typescript
#Verificar versión
gulp --version ##CLI version: 2.3.0
tsc --version ##Version 4.3.2
```

### Desplegar PiecesPHP

```bash

#Variables
export RAIZ_DESPLIEGUE="/var/www/html"
export CARPETA="pcsphp-project"

#Moverse a la carpeta raíz
cd $RAIZ_DESPLIEGUE

#Descargar
wget -O $CARPETA.zip https://bitbucket.org/piecesphp/piecesphp/get/last-stable.zip

#Descomprimir
unzip $CARPETA.zip -d $CARPETA

#Moverse a la carpeta del proyecto
cd $RAIZ_DESPLIEGUE/$CARPETA

#Renombrar carpeta
find . -depth -type d -name * -execdir mv {} tmp \;
cd $RAIZ_DESPLIEGUE/$CARPETA/tmp
sudo mv ./{*,.*} ../ ##Se obtendrá un error por los meta archivos . y .., no es importante.

#Borrar elementos innecesarios
cd $RAIZ_DESPLIEGUE/$CARPETA
sudo rm -Rf tmp CHANGELOG.md README.md TODO TODO.md guides source-docs

#Ajustar permisos
sudo chmod -Rf 0777 src

#Instalar gulp para desarrollo
cd $RAIZ_DESPLIEGUE/$CARPETA
rm -Rf node_modules package-lock* ##Si hay algún proyecto NPM desplegado ya
npm cache clean --force ##Para actualizar los repositorios
npm install ##NO USAR sudo

#Instalar paquetes de composer
cd $RAIZ_DESPLIEGUE/$CARPETA/src
composer install ##NO USAR sudo

```

#### Activación de módulos apache necesarios
```bash
sudo a2enmod rewrite headers ssl
```

#### Más información
- Durante el desarrollo se recomiendo el uso de las siguientes tareas de gulp (para más información, [clic aquí](../gulp)):
    - init-project
    - init-project:watch
- Base de datos:
    - Se debe configurar la conexión en el archivo src/app/database.php
    - Los archivos para usar en la base de datos están en la carpeta databases
- Otras cosas:
    - En el archivo src/app/constants.php se pueden activar/desactivar algunas características integradas.
