# Soporte de dominios (Virtual Hosts) en Apache2 (Ubuntu 24.04 LTS)

## Introducción
Los Virtual Hosts permiten alojar múltiples sitios web en un solo servidor Apache.

---

## Crear un nuevo Virtual Host

```bash
sudo cp /etc/apache2/sites-available/000-default.conf /etc/apache2/sites-available/TUDOMINIO.conf
```

Edita el archivo de configuración:

```bash
sudo nano /etc/apache2/sites-available/TUDOMINIO.conf
```

Ejemplo de configuración básica:

```apacheconf
<VirtualHost *:80>
    ServerAdmin admin@TUDOMINIO
    ServerName TUDOMINIO
    ServerAlias www.TUDOMINIO
    DocumentRoot /var/www/TUDOMINIO/public_html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

---

## Activar el Virtual Host y reiniciar Apache

```bash
sudo a2ensite TUDOMINIO.conf
# Se desactiva la configuración por defecto
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

---

## Recursos útiles
- [Guía de Virtual Hosts en Apache](https://httpd.apache.org/docs/2.4/vhosts/)
