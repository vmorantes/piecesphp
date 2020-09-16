# Instalación de Apache2

- Ubuntu 20
- Apache2

## Intalación

```bash
#Moverse al directorio de usuario
cd ~
#Instalar apache
sudo apt install apache2
#Activar algunos módulos
sudo a2enmod rewrite headers ssl
#Activar soporte para el puerto SSL
sudo a2ensite default-ssl.conf
#Reiniciar
sudo service apache2 restart
#Paquetes útiles para el entorno
sudo apt install curl zip unzip openssl git
```

## Configuraciones generales

```bash
#Mover a carpeta de apache
cd /etc/apache2/

#Respaldar archivo de configuración por defecto
cp apache2.conf apache2.conf.bk

#Permitir .htaccess
sudo nano apache.conf

### En la configuración de Directory /var/www/ cambiar AllowOverride None por AllowOverride All
### Guardar y reinicias servidor
sudo service apache2 restart
```
