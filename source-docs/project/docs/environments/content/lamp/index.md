# LAMP

La siguiente guía está escrita para Ubuntu 26.04 LTS, que trae en sus repositorios oficiales Apache 2.4, MariaDB 11.8 y
PHP 8.5. PiecesPHP exige PHP `>=8.5 <8.6`.

Puedes validar la versión de Ubuntu instalada así:

```bash
lsb_release -a
```

Se recomienda realizar la instalación de los componentes en el siguiente orden:

### [Apache2](./content/Apache.md)
### [PHP](./content/PHP.md)
### [MariaDB](./content/MariaDB.md)

## Componentes / Módulos adicionales

### [PHPMyAdmin](./content/PHPMyAdmin.md)
### [Adminer](./content/Adminer.md) (alternativa ligera a PHPMyAdmin)
### [SSL con Lets Encrypt](./content/SSL con Lets Encrypt.md)
### [Virtual Hosts - Soporte para dominios](./content/Virtual Hosts.md)

## Que Apache no diga su versión

De fábrica, Apache responde con `Server: Apache/2.4.66 (Ubuntu)` y firma sus páginas de error con su versión. Es
información gratis para quien busca fallos conocidos. En la configuración global de Apache (por ejemplo
`/etc/apache2/conf-available/security.conf` en Debian y Ubuntu):

```apache
ServerTokens Prod
ServerSignature Off
```

Después, `sudo systemctl reload apache2`. La cabecera queda en `Server: Apache`. PHP, en `php.ini`: `expose_php = Off`.

