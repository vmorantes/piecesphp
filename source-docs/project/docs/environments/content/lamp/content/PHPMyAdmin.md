# PHPMyAdmin

La versión estable de phpMyAdmin es la **5.2.3** (8 de octubre de 2025), que corrige avisos de obsolescencia de PHP 8.4
y 8.5. Ubuntu 26.04 LTS la trae en sus repositorios (*universe*) y corre sobre su PHP 8.5, el mismo que exige
PiecesPHP (`>=8.5 <8.6`).

## Opción A: paquete de Ubuntu (más simple)

```bash
sudo apt update
sudo apt install -y phpmyadmin
```

El paquete deja la aplicación en `/usr/share/phpmyadmin`, pregunta por el servidor web (marca `apache2`) y ofrece
crear su base de configuración con `dbconfig-common`. Queda accesible en `http://<servidor>/phpmyadmin`. Comprueba la
versión con `apt-cache policy phpmyadmin`.

## Opción B: descarga manual de la 5.2.3

Para tenerla en una carpeta propia o actualizarla sin esperar al paquete de Ubuntu. Si
<https://www.phpmyadmin.net/downloads/> anuncia una versión más reciente, cambia `PMA_VERSION`.

```bash
# Moverse al directorio de usuario
cd ~

# La carpeta pública y la versión que vas a instalar
export CARPETA_DE_INSTALACION="/var/www/html"
export PMA_VERSION="5.2.3"

# Descargar y comprobar la suma SHA-256 publicada junto al archivo (debe responder «OK», o «La suma coincide» con el sistema en español)
wget https://files.phpmyadmin.net/phpMyAdmin/${PMA_VERSION}/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz
wget https://files.phpmyadmin.net/phpMyAdmin/${PMA_VERSION}/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz.sha256
sha256sum -c phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz.sha256

# Descomprimir en la carpeta de instalación
cd $CARPETA_DE_INSTALACION
sudo tar -xzf ~/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz

# Borrar lo descargado
rm ~/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz ~/phpMyAdmin-${PMA_VERSION}-all-languages.tar.gz.sha256

# Renombrar
sudo mv phpMyAdmin-${PMA_VERSION}-all-languages phpmyadmin

# Configuración
cd phpmyadmin
sudo cp config.sample.inc.php config.inc.php

# Generar un secreto propio de 32 caracteres para blowfish_secret
openssl rand -hex 16

# Editar la configuración
sudo nano config.inc.php
### $cfg['blowfish_secret'] = '<el valor generado arriba>';   (cada instalación, el suyo; nunca copies uno de un ejemplo)
### $cfg['Servers'][$i]['hide_db'] = '^(information_schema|mysql|performance_schema)$';
### $cfg['Servers'][$i]['AllowNoPassword'] = true; (login sin contraseña: no lo actives en producción)

# Subir de directorio
cd ..

# Permisos: lectura para el servidor web, y escritura solo en tmp
sudo chmod -R 0755 phpmyadmin
sudo mkdir -p phpmyadmin/tmp
sudo chmod 0770 phpmyadmin/tmp

# Ajustar propietario (puedes verificar el dueño del servidor web con: ps aux | egrep '(apache|httpd|www)')
sudo chown -R www-data:www-data phpmyadmin

# Borrar setup por seguridad
sudo rm -Rf phpmyadmin/setup
```

Con `AllowNoPassword` desactivado (lo normal) el acceso exige una cuenta de MariaDB con contraseña.

---

## Recursos útiles
- [Documentación oficial de PHPMyAdmin](https://docs.phpmyadmin.net/es/latest/)
