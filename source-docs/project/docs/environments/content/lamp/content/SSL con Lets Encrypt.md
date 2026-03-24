# Soporte de SSL con Let's Encrypt en Apache2 (Ubuntu 24.04 LTS)

## Introducción
Let's Encrypt permite obtener certificados SSL gratuitos y automáticos para tu servidor web.

---

## Instalación de Certbot

```bash
sudo apt update
sudo apt install certbot python3-certbot-apache -y
```

---

## Generar certificados SSL

```bash
sudo certbot --apache
```

Sigue las instrucciones para tu dominio. Si hay errores con la configuración de vhost SSL, revisa los logs y la configuración.

Puedes revisar la sintaxis de Apache antes de continuar:

```bash
sudo apachectl configtest
```

---

## Configuración de VirtualHost SSL

Asegúrate de que tu archivo de configuración tenga las siguientes líneas:

```apacheconf
<VirtualHost *:443>
    ...
    SSLCertificateFile /etc/letsencrypt/live/TUDOMINIO/cert.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/TUDOMINIO/privkey.pem
    SSLCertificateChainFile /etc/letsencrypt/live/TUDOMINIO/chain.pem
    ...
</VirtualHost>
```

Activa el módulo SSL si no está activo:

```bash
sudo a2enmod ssl
```

Recarga Apache para aplicar los cambios:

```bash
sudo systemctl reload apache2
```

---

## Renovación automática

Let's Encrypt recomienda probar la renovación automática:

```bash
sudo certbot renew --dry-run
```

---

## Forzar HTTPS siempre

Activa el módulo rewrite:

```bash
sudo a2enmod rewrite
```

Redirecciona todo el tráfico HTTP a HTTPS en tu VirtualHost de puerto 80:

```apacheconf
<VirtualHost *:80>
    ...
    Redirect permanent / https://TUDOMINIO/
    ...
</VirtualHost>
```

Recarga Apache para aplicar la redirección:

```bash
sudo systemctl reload apache2
```

---

## Recursos útiles
- [Guía oficial de Let's Encrypt](https://letsencrypt.org/getting-started/)

```
