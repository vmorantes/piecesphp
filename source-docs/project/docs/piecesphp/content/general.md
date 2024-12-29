# PiecesPHP Framework

## Configuración de entorno

- Requerimientos
    - PiecesPHP
    - Composer
    - NodeJS 18.x LTS con NVM
    - NPM
    - Gulp CLI

### Actualizar repositorios
```bash
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

#### NodeJS (v22.12.0) y NPM
```bash
#Instalar NVM
cd ~
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.35.3/install.sh | bash
source ~/.bashrc
#Instalar Node
nvm install v22.12.0
#Verificar versiones
node --version ##22.12.0
npm --version ##11.0.0
```

#### Gulp CLI y Typescript
```bash
#Instalar globalmente (sudo es solo para paquetes globales)
sudo npm install -g gulp-cli typescript
#Verificar versión
gulp --version ##CLI version: 3.0.0
tsc --version ##Version 5.7.2
```

### Desplegar PiecesPHP

#### Paso 1: Definir variables útiles

```bash
#Variable con la carpeta del proyecto, si no existe debe crearse antes
export FOLDER="/var/www/html/pcsphp_project"
```

O, en caso de que se quiera hacer en el directorio actual (donde está abierta la terminal):

```bash
export FOLDER=$(pwd)
```

#### Paso 2: Descargar y descomprimir

```bash
cd $FOLDER
wget https://bitbucket.org/piecesphp/piecesphp/get/last-stable.zip
unzip last-stable.zip -d . && rm last-stable.zip
```

#### Paso 3: Mover a la raíz, eliminar archivos innecesarios y ajustar permisos

Nota: En la eliminación se obtendrá una advertencia por los meta archivos . y .., no es importante.

##### Mover

```bash
find . -depth -type d -name * -execdir mv {} tmp \;
sudo mv ./tmp/{*,.*} ./;
```
##### Eliminar

```bash
sudo rm -Rf tmp CHANGELOG.md README.md TODO TODO.md guides source-docs src/adminer;
sudo chmod -Rf 0777 src;
```

#### Paso 4: Composer y Gulp

```bash
#Instalar gulp para desarrollo
cd $FOLDER
rm -Rf node_modules package-lock* ##Si hay algún proyecto NPM desplegado ya
npm cache clean --force ##Para actualizar los repositorios
npm install ##NO USAR sudo

#Instalar paquetes de composer
cd $FOLDER/src
composer install ##NO USAR sudo
```

##### Actualización de NPM (solo en caso de errores)
```bash
#Para actualizar dependencias
npm install -g npm-check-updates
ncu -u
npm install
```

#### Paso 5: Activación de módulos apache necesarios
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
