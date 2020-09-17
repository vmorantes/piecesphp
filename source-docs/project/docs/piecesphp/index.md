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
