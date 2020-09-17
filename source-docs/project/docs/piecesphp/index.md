# PiecesPHP Framework
- Requerimientos
    - PiecesPHP
    - Composer
    - NodeJS 12.x LTS
    - NPM
    - Gulp CLI

## Actualizar repositorios
```bash
#Integrar respositorio
curl -sL https://deb.nodesource.com/setup_12.x | sudo -E bash -
#Actualizar
sudo apt update
```

## Instalación de dependencias de desarrollo

### Composer

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

### NodeJS y NPM
```bash
#Instalar
sudo apt install nodejs
#Verificar versiones
node --version ##v12.18.4
npm --version ##6.14.6
```

### Gulp CLI
```bash
#Instalar globalmente
sudo npm install -g gulp-cli
#Verificar versión
gulp --version ##CLI version: 2.3.0
```

## Desplegar PiecesPHP

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
npm install ##NO USAR sudo

#Instalar paquetes de composer
cd $RAIZ_DESPLIEGUE/$CARPETA/src
composer install ##NO USAR sudo

```

### Otras configuraciones
- Existen las siguientes tareas en Gulp (dentro de src):
    - gulp sass:init (para compilar los archivos en src/statics/sass)
    - gulp sass:watch (para observar los archivos en src/statics/sass)
    - gulp sass-vendor:init (para compilar los archivos de estilo del área administrativa)
    - gulp sass-vendor:watch (para observar los archivos de estilo del área administrativa)
- Base de datos:
    - Se debe configurar la conexión en el archivo src/app/database.php
    - Los archivos para usar en la base de datos están en la carpeta databases
- Otras cosas:
    - En el archivo src/app/constants.php se pueden activar/desactivar algunas características que el framewok tiene integradas como Blog, Carga de imágenes, etc...
### Comentarios adicionales
Viene por defecto con una integración de la plantilla [Editorial de HTML5UP](https://html5up.net/editorial).
