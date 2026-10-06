# Adminer 6.1.1

**El código fuente de PiecesPHP no incluye Adminer**: es una herramienta externa y opcional para administrar la base de
datos. Esta guía instala la distribución que mantiene el autor del framework: Adminer **6.1.1** con plugins (volcados
en varios formatos, nombres de claves foráneas, importación desde carpeta, columnas PHP serializadas, la opción de
omitir `DEFINER` al exportar y un asistente SQL con Google Gemini).

- Requiere PHP 8.1 o superior, así que funciona con el PHP 8.5 de PiecesPHP.
- Está pensada para Apache: su `.htaccess` solo deja servir `index.php` y `adminer.css`.

## Instalación

```bash
# La carpeta pública
export CARPETA_DE_INSTALACION="/var/www/html"

# Descargar en su propia carpeta
sudo git clone https://github.com/vmorantes/adminer.git $CARPETA_DE_INSTALACION/adminer
cd $CARPETA_DE_INSTALACION/adminer

# Su configuración: DEV_MODE va en false en un servidor
sudo cp .env.example .env
sudo nano .env

# Propietario del servidor web
sudo chown -R www-data:www-data $CARPETA_DE_INSTALACION/adminer
```

Después, abre `index.php` de esa carpeta en el navegador.

- **El `.htaccess` necesita** que el `VirtualHost` permita `AllowOverride` con `AuthConfig`, `Options`, `FileInfo` e
  `Indexes`. Con otro servidor web hay que configurar lo mismo en él.
- **El asistente SQL** necesita `GEMINI_API_KEY` en `.env`, y envía a Google la estructura de la base (no los datos).
  Sin la clave, el resto funciona.
- **Los plugins activos** se eligen en `adminer-plugins.php`.
- **Para actualizar**, `git pull` en la carpeta. Por eso no se borra su `.git`.

## Seguridad

`DEV_MODE=true` permite entrar sin contraseña: solo en tu máquina, nunca en un servidor. **No expongas Adminer sin
protección en un servidor público**: limítalo por IP o con autenticación básica de Apache, o instálalo solo mientras
lo uses.
