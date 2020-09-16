# Añadir soporte de SSL con Lets Encrypt (LE) en apache2 (Debian 9)
Nota: La información es sacada de [snel.com](https://www.snel.com/support/lets-encrypt-on-debian-9-with-apache-webserver/)

```bash
# Instalar el cerbot de LE
apt install certbot python3-certbot-apache
#Generar certificados
certbot --apache
#Nota: Probablemente se presente un error con la configuración de vhost ssl
```

- Configurar el vhost ssl con las siguiente información

```apacheconf
<VirtualHost _default_:443>
	...
	SSLCertificateFile /etc/letsencrypt/live/DOMINIO/cert.pem
	SSLCertificateKeyFile /etc/letsencrypt/live/DOMINIO/privkey.pem
	SSLCertificateChainFile /etc/letsencrypt/live/DOMINIO/chain.pem
	...
</VirtualHost>
```

```bash
#Revisar sintaxis apache
apachectl configtest

#Activar módulo SSL
a2enmod ssl

#Recargar servidor
service apache2 reload

#Configurar renovación automática
sudo certbot renew --dry-run
```

## Forzar https siempre

```bash
#Activar módulo rewrite
a2enmod rewrite
```

- Modificar configuración de vhost (NO SSL) para que redireccione

```apacheconf
<VirtualHost _default_:80>
	...
	Redirect permanent / https://DOMINIO.COM/
	...
</VirtualHost>
```

```bash
#Recarcar servidor
service apache2 reload
```
