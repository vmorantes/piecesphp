# Adminer 4.7.7

- Ubuntu 20
- Adminer 4.7.7

## Instalación 
```bash
# Moverse al directorio de usuario
cd ~

#Configurar algunas variables útiles
###La carpeta pública
export CARPETA_DE_INSTALACION="/var/www/html"

#Crear carpeta de adminer
sudo mkdir $CARPETA_DE_INSTALACION/adminer

#Moverse a carpeta de instalación
cd $CARPETA_DE_INSTALACION/adminer

#Descargar
sudo git clone https://vmorantes@bitbucket.org/vmorantes/util-adminer.git $CARPETA_DE_INSTALACION/adminer

#Remover repositorio
sudo rm -Rf $CARPETA_DE_INSTALACION/adminer/.git

#En caso de querer permitir el inicio sin contraseña (no recomendado), hacer lo siguiente
##Cambiar el valor de la constante _DEV_MODE_ por true
sudo nano $CARPETA_DE_INSTALACION/adminer/index.php

```


