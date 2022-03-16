# PHPMyAdmin 5.0.2

- Ubuntu 20
- PHPMyAdmin 5.0.2

## Instalación 
```bash
# Moverse al directorio de usuario
cd ~

#Configurar algunas variables útiles
###La carpeta pública
export CARPETA_DE_INSTALACION="/var/www/html"

#Descargar
wget https://files.phpmyadmin.net/phpMyAdmin/5.0.2/phpMyAdmin-5.0.2-all-languages.zip

#Moverse a carpeta de instalación
cd $CARPETA_DE_INSTALACION

#Descomprimir
sudo unzip ~/phpMyAdmin-5.0.2-all-languages.zip -d .

#Borrar zip
rm ~/phpMyAdmin-5.0.2-all-languages.zip

#Renombrar
sudo mv phpMyAdmin-5.0.2-all-languages phpmyadmin

#Configuración
cd phpmyadmin
sudo cp config.sample.inc.php config.inc.php 

#Configurar blowfish
sudo nano config.inc.php 
### $cfg['blowfish_secret'] = 'RugpCGHE7vZIPoMSu48QMnfBywDrtKB1';
### $cfg['Servers'][$i]['AllowNoPassword'] = true; (Para habilitar login sin contraseña, no recomendado en producción)
### $cfg['Servers'][$i]['hide_db'] = '^(information_schema|mysql|performance_schema)$';

#Subir de directorio
cd ..

#Aplicar permisos a la carpeta de phpmyadmin
sudo chmod -R 0755 phpmyadmin
sudo chmod -R 0755 phpmyadmin/*
sudo mkdir phpmyadmin/tmp
sudo chmod -R 0777 phpmyadmin/tmp

#Ajustar propietario (puede verificar el dueño del directorio público con: ps aux | egrep '(apache|httpd|www)')
sudo chown -R www-data:www-data phpmyadmin
sudo chown -R www-data:www-data phpmyadmin/*

#Borrar setup por seguridad
sudo rm -Rf phpmyadmin/setup
```


