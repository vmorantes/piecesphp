# Añadir soporte de dominios en apache2 (funciona para ssl, hacer los mismos pasos con el archivo default-ssl.conf) (Debian 9)
Nota: La información es sacada de [DigitalOcean](https://www.digitalocean.com/community/tutorials/como-configurar-virtual-hosts-de-apache-en-ubuntu-16-04-es)

- Crear el archivo de configuración

```bash
cd /etc/apache2/sites-available
cp 000-default.conf DOMINIO.COM.conf
```

- La información de DOMINIO.COM.conf es algo como lo siguiente

```apacheconf
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

- Modificar información

```bash
nano DOMINIO.COM.conf
```

```apacheconf
<VirtualHost *:80>
	...
	ServerAdmin admin@DOMINIO.COM #Opcional
    ServerName DOMINIO.COM
    ServerAlias www.DOMINIO.COM
	DocumentRoot /var/www/DOMINIO.COM/public_html
	...
</VirtualHost>
```

- Activar vhost y desactivar configuración por defecto

```bash
a2ensite DOMINIO.COM.conf
a2dissite 000-default.conf
```

- Reinicar servidor

```bash
service apache2 restart
```
