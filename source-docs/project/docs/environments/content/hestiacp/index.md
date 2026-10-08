# 🛡️ HestiaCP

Hestia Control Panel (HestiaCP) es un panel de control potente y ligero diseñado para administradores de servidores que buscan una interfaz intuitiva y eficiente.

> [!WARNING]
> **Compatibilidad de SO:** esta guía está escrita para **Ubuntu 26.04 LTS** y un servidor recién instalado. El
> instalador de HestiaCP admite Ubuntu 22.04, 24.04 y 26.04 LTS, y Debian 12 y 13. Ubuntu 24.04 está soportado desde
> HestiaCP 1.9.0, y Ubuntu 26.04, desde la 1.10.0.

> [!IMPORTANT]
> **Dos cosas distintas, y conviene no confundirlas:**
>
> - **Lo que exige PiecesPHP:** PHP `>=8.5 <8.6`. HestiaCP ofrece PHP 8.5 desde su versión 1.10.0 y es la de su
>   instalador por defecto. El dominio donde viva el framework **debe usar la plantilla de PHP-FPM 8.5**.
> - **Lo que lleva el servidor:** esta guía instala **todas** las versiones de PHP que ofrece HestiaCP, de la **5.6** a
>   la más reciente, porque un panel de control aloja varios proyectos y los antiguos necesitan la suya. Que el servidor
>   tenga PHP 5.6 instalado no cambia el piso de PiecesPHP: lo que decide es **la plantilla del dominio**, no lo que haya
>   en el sistema.
>
> Las versiones de su MultiPHP son **5.6, 7.0, 7.1, 7.2, 7.3, 7.4, 8.0, 8.1, 8.2, 8.3 y 8.4**, más la **8.5** desde la
> 1.10.0. Una suelta se añade después con `v-add-web-php <versión>`.

Este tutorial está ajustado a la **versión 1.10.5**. En Ubuntu 26.04, el instalador toma PHP del repositorio de Ondřej
Surý (`packages.sury.org/php`) y MariaDB 11.8 del repositorio de MariaDB; no instales antes Apache, nginx, MariaDB ni
PHP por tu cuenta.

---

## 🛠️ 1. Preparación del Sistema

Actualice el sistema e instale los paquetes base necesarios.

```bash
# Actualizar repositorios
sudo apt update && sudo apt upgrade -y

# Instalar herramientas esenciales
sudo apt install -y curl zip unzip openssl git wget

# Soporte de idiomas (puedes revisar los disponibles con 'locale -a')
sudo apt install -y language-pack-{es,it,en,pt,de,fr}
```

---

## 📋 2. Definición de Variables

Configure las siguientes variables en su terminal para facilitar el proceso de instalación automatizada. Reemplace los valores por los de su servidor.

```bash
export HESTIA_ADMIN_USER="admin"
export HESTIA_DOMAIN="sample.com"
export HESTIA_EMAIL="admin@sample.com"
export HESTIA_PASSWORD="$(openssl rand -base64 18)"
echo "$HESTIA_PASSWORD"   # guárdala en tu gestor de contraseñas: es la del administrador del panel
```

> [!TIP]
> Si su proveedor de hosting es **OVH**, se recomienda utilizar el nombre del VPS proporcionado por el proveedor como `HESTIA_DOMAIN`.

---

## 🚀 3. Instalación

Los siguientes comandos están optimizados para un uso estándar de **PiecesPHP**. Si desea una instalación más personalizada, visite la herramienta de [Instalación de HestiaCP](https://hestiacp.com/install.html).

### Descarga del instalador
```bash
wget https://raw.githubusercontent.com/hestiacp/hestiacp/release/install/hst-install.sh
```

El instalador comprueba que no haya ya instalados `exim4`, `mariadb-server`, `apache2`, `nginx`, `postfix` ni `ufw`, y
si los encuentra ofrece desinstalarlos antes de seguir. Ubuntu Server trae `ufw`: acepta quitarlo, porque HestiaCP
gestiona su propio cortafuegos.

> [!WARNING]
> **Lo que decidas aquí no se puede añadir después.** HestiaCP **no admite reejecutar el instalador** para agregar
> paquetes a una instalación que ya existe: si quieres PostgreSQL, tiene que ir en esta orden, ahora. Añadirlo más tarde
> significa reinstalar el servidor.
>
> Por eso estas dos opciones van encendidas:
>
> - **`--multiphp yes`**: todas las versiones de PHP. Ocupa más disco y obliga a instalar las extensiones **por cada
>   versión** (ver «Módulos PHP y Apache»), pero deja el servidor listo para proyectos de cualquier edad.
> - **`--postgresql yes`** junto con **`--mysql yes`**: las dos bases conviven. `--postgresql` viene **apagado** de
>   fábrica, así que hay que pedirlo expresamente.
>
> **PiecesPHP no usa PostgreSQL**: `src/composer.json` pide `ext-pdo_mysql` y `ext-mysqli` —y también `ext-sqlite3` y
> `ext-pdo_sqlite`, que exige porque la biblioteca de base de datos del framework las soporta—, y no hay una sola
> mención de PostgreSQL en su núcleo ni en sus módulos. PostgreSQL está aquí para **los demás proyectos del servidor**, no para el framework.

### Opción A: Instalación estándar (Recomendada)
Esta opción instala **todas las versiones de PHP**, **MariaDB y PostgreSQL**, y cuotas de disco, desactivando ClamAV
para ahorrar recursos.

```bash
sudo bash hst-install.sh \
    --hostname $HESTIA_DOMAIN \
    --email $HESTIA_EMAIL \
    --password $HESTIA_PASSWORD \
    --multiphp yes \
    --mysql yes \
    --postgresql yes \
    --clamav no \
    --quota yes
```

### Opción B: Instalación completa (Personalizada)
Si requiere control total sobre todos los servicios:

```bash
sudo bash hst-install.sh \
    --apache yes \
    --phpfpm yes \
    --multiphp yes \
    --vsftpd yes \
    --proftpd no \
    --named yes \
    --mysql yes \
    --mysql8 no \
    --postgresql yes \
    --exim yes \
    --dovecot yes \
    --clamav no \
    --spamassassin yes \
    --iptables yes \
    --fail2ban yes \
    --quota yes \
    --api yes \
    --lang es \
    --interactive yes \
    --hostname $HESTIA_DOMAIN \
    --email $HESTIA_EMAIL \
    --password $HESTIA_PASSWORD \
    --username $HESTIA_ADMIN_USER \
    --webterminal yes \
    --sieve no
```

No añadas `--force`: se salta la comprobación de paquetes ya instalados.

---

## 🔑 4. Acceso y Recomendaciones

### Acceso al Panel
Una vez finalizada la instalación, podrá acceder a través de:
*   **URL:** `https://TU_DOMINIO:8083` (o la IP del servidor)
*   **Puerto por defecto:** 8083

### Recomendaciones Post-Instalación
1.  **Seguridad:** Al crear su primer dominio, cree un usuario con permisos limitados y habilite el acceso Bash únicamente si es estrictamente necesario.
2.  **Gestión de archivos:** Utilice **FileZilla** o cualquier gestor SFTP para la carga y gestión de archivos.

---

## 🛠️ Otros Ajustes Necesarios

### Módulos PHP y Apache

Con MultiPHP hay **una instalación de PHP por versión**, así que las extensiones se instalan **para cada una**. Esta
orden no las nombra a mano: lee las versiones que HestiaCP dejó instaladas y recorre esa lista, así que sigue valiendo
cuando HestiaCP añada una nueva.

```bash
# Qué versiones instaló HestiaCP
ls -1 /etc/php

# Extensiones para cada versión instalada
for V in $(ls -1 /etc/php); do
    case "$V" in
        5.*|7.*) EXTRA="php$V-json" ;;   # en PHP 8.0+ json es del núcleo y su paquete ya no existe
        *)       EXTRA="" ;;
    esac
    sudo apt install -y php$V-{common,xml,mbstring,gd,curl,zip,mysql,sqlite3,pgsql} $EXTRA
done

# Activar módulos de Apache vitales (solo si instalaste Apache, como en las dos opciones de arriba)
sudo a2enmod rewrite headers ssl

# Reiniciar servicios
sudo systemctl restart apache2
```

- **`mysql`** trae `mysqli` y `pdo_mysql`, que son las dos que pide `src/composer.json`; **`xml`** incluye `xsl`.
  **`pgsql`** es para los otros proyectos del servidor: **el framework no la usa**.
- **`json` solo se instala en 5.6 y 7.x.** Desde PHP 8.0 está en el núcleo y no hay paquete que instalar: una línea
  única para todas las versiones falla justo por eso.
- *Sin verificar:* el nombre exacto de algún paquete de extensión en las versiones más viejas de Surý (5.6 y 7.0). Si
  uno no existe para una versión concreta, **`apt` aborta la línea entera y no instala nada de ella**: repite la orden
  para esa versión **sin el paquete que falte**, y anótalo. Ninguna de esas versiones la usa PiecesPHP.
- *Sin verificar:* que en HestiaCP `/etc/php` no contenga alguna entrada que no sea una versión. Si el bucle te saca
  algo raro, míralo antes de instalar.
- Para el dominio de PiecesPHP, lo que manda es su **plantilla de PHP-FPM 8.5**, no las versiones que haya instaladas.

### Bases de datos

*   [Guía de configuración de MariaDB](../lamp/content/MariaDB.md) — la que usa PiecesPHP.
*   **PostgreSQL** queda instalado y se administra **desde el panel de HestiaCP**, en su sección de bases de datos. No
    hay guía propia porque **el framework no lo necesita**: está para los demás proyectos del servidor.

### Gestión de Paquetes PHP (Composer)
Instale Composer de manera global:
```bash
cd ~
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
sudo mv composer.phar /usr/local/bin/composer
```

---

## 📂 Herramientas de Base de Datos

*   [PHPMyAdmin](../lamp/content/PHPMyAdmin.md)
*   [Adminer](../lamp/content/Adminer.md) — *Alternativa ligera y recomendada.*

---

## ⚙️ Optimizaciones Sugeridas (Stack)

Para un rendimiento óptimo con aplicaciones **PiecesPHP**, se sugieren los siguientes valores en el servidor:

### PHP

| Parámetro | Valor Sugerido | Razón |
| :--- | :--- | :--- |
| `max_execution_time` | `600` | Evita timeouts en procesos largos o reportes. |
| `max_input_time` | `600` | Tiempo máximo para procesar datos de entrada. |
| `max_input_vars` | `5000` | Vital para formularios extensos o grids dinámicos. |
| `post_max_size` | `100M` | Ajustado para permitir margen sobre el upload. |
| `upload_max_filesize` | `80M` | Capacidad para carga de archivos pesados. |
| `memory_limit` | `1024M` | Memoria suficiente para procesamiento pesado. |

### MySQL (MariaDB)

| Parámetro | Valor Sugerido | Razón |
| :--- | :--- | :--- |
| `wait_timeout` | `600` | Libera conexiones inactivas eficientemente. |
| `interactive_timeout` | `600` | Tiempo de espera para clientes interactivos. |
| `max_allowed_packet` | `64M` | Permite el manejo de paquetes de datos grandes. |

### Nginx (Proxy)

| Parámetro | Valor Sugerido | Razón |
| :--- | :--- | :--- |
| `client_max_body_size` | `100M` | Debe ser igual o mayor a `post_max_size`. |
| `proxy_read_timeout` | `600` | Tiempo de espera para la respuesta del backend. |
| `proxy_connect_timeout` | `600` | Tiempo para establecer conexión con el backend. |
| `proxy_send_timeout` | `600` | Tiempo para enviar la petición al backend. |

### Apache

| Parámetro | Valor Sugerido | Razón |
| :--- | :--- | :--- |
| `Timeout` | `600` | Sincronizado con los tiempos de PHP. |
| `KeepAliveTimeout` | `5` | Libera slots de conexión rápidamente. |
| `MaxKeepAliveRequests` | `100` | Límite de peticiones por conexión persistente. |


---

---

## 📦 Respaldos Incrementales (Restic + Rclone)

HestiaCP permite migrar del sistema tradicional de archivos `.tar` (que consume mucho espacio y CPU) hacia un sistema de **respaldos incrementales** utilizando **Restic**. Esta configuración *on-premise* permite mantener el control total de los datos sin depender de proveedores de nube externos.

### 1. Estrategias de Almacenamiento
Dependiendo de su disponibilidad de hardware, elija una de estas dos rutas:

*   **Ruta A: Almacenamiento Externo (Recomendado):** Utilice un segundo disco físico (ej. montado en `/mnt/backup_incremental`), un NAS o un NFS. Esto protege los datos ante un fallo total del disco principal.
*   **Ruta B: Mismo Disco (Sin montajes nuevos):** Puede usar una carpeta dentro de su disco actual (ej. `/backup_incremental`). Aunque no protege contra fallos físicos del disco, obtiene todos los beneficios de velocidad y ahorro de espacio de Restic.

### 2. Configurar Rclone (Puente Local)
Aunque Restic es nativo, usar Rclone como puente es el método más estable en HestiaCP.

1.  Ejecute la configuración interactiva:
    ```bash
    rclone config
    ```
2.  Siga estos pasos en el menú interactivo:
    *   Elija **`n`** para un nuevo remoto.
    *   **name:** `almacenamiento_local`
    *   **Storage:** Escriba **`local`** (o el número correspondiente a *Local Disk*).
    *   **Edit advanced config?** `n` (No)
    *   **Keep this remote?** `y` (Sí)
    *   **Finalizar:** Elija `q` para salir.

### 3. Vinculación con HestiaCP
Use el comando `v-add-backup-host-restic` indicando el remoto de Rclone (`almacenamiento_local`) seguido de la **ruta absoluta** elegida en el paso 1.

**Ejemplo para Disco Externo:**
```bash
v-add-backup-host-restic 'rclone:almacenamiento_local:/mnt/backup_incremental/' 30 8 5 3 -1
```

**Ejemplo para Mismo Disco (Sin montajes):**
```bash
# Crear la carpeta primero si no existe
mkdir -p /backup_incremental
v-add-backup-host-restic 'rclone:almacenamiento_local:/backup_incremental/' 30 8 5 3 -1
```
> [!NOTE]
> Los cinco números son la política de retención, en este orden: **últimas instantáneas (30), diarias (8), semanales
> (5), mensuales (3) y anuales (-1)**. Se aplican con `restic forget --keep-last`, `--keep-daily`, `--keep-weekly`,
> `--keep-monthly` y `--keep-yearly`; un `-1` deja esa regla sin aplicar. El comando guarda la configuración en
> `/usr/local/hestia/conf/restic.conf` y activa `BACKUP_INCREMENTAL` en HestiaCP. Si la ruta es local (empieza por `/`),
> la carpeta debe existir antes.

> [!CAUTION]
> **Un repositorio por usuario:** HestiaCP guarda cada usuario en `<ruta>/<usuario>` y lo inicializa solo en su primer
> respaldo, cuando crea la clave en `/usr/local/hestia/data/users/<usuario>/restic.conf`. Si esa clave ya existe pero
> el repositorio no (por ejemplo, porque cambiaste la ruta), el respaldo falla con «Unable to access restic repo». Se
> inicializa a mano con la misma clave:
> `restic init -r /mnt/backup_incremental/<usuario> --password-file /usr/local/hestia/data/users/<usuario>/restic.conf`
> (con la Ruta B, `/backup_incremental/<usuario>`).

### 4. Consideraciones Críticas

*   **⚡ Rendimiento:** Al ser local, no depende del ancho de banda de un destino remoto, y la deduplicación de Restic solo guarda lo que cambió entre respaldos.
*   **💾 Espacio de Caché:** Restic usa cache temporal en `/root/.cache/restic`. Asegúrese de tener espacio en el disco principal para evitar fallos durante la purga.
*   **🔑 Claves de Cifrado:** Restic cifra siempre los repositorios, y HestiaCP genera una clave aleatoria por usuario. **Es obligatorio** resguardar los archivos `restic.conf` ubicados en `/usr/local/hestia/data/users/[usuario]/`, fuera del servidor. Sin estos archivos, sus respaldos serán **ilegibles e irrecuperables**.

### 5. Programación y Automatización (Cron)

El instalador de HestiaCP programa los respaldos tradicionales (`v-backup-users`, a las 05:10), pero **no** programa los
de Restic. `v-backup-users-restic` respalda a todos los usuarios no suspendidos, y solo si `BACKUP_INCREMENTAL` está
activo (lo activa el paso 3).

1.  **Añadir la tarea programada.** Tiene que correr como `root` (los usuarios del panel, `admin` incluido, no tienen
    permiso para lanzar los scripts de HestiaCP), así que va en `/etc/cron.d/` y no en la sección **Cron** del panel.
    Se recomienda una hora distinta a la de los respaldos tradicionales, por ejemplo las 05:30:
    ```bash
    echo '30 5 * * * root /usr/local/hestia/bin/v-backup-users-restic > /dev/null 2>&1' | sudo tee /etc/cron.d/hestia-restic
    sudo chmod 644 /etc/cron.d/hestia-restic
    ```
    El resultado de cada respaldo queda en `/usr/local/hestia/log/backup.log`.

2.  **Mostrar los respaldos al usuario:**
    *   Vaya a **Packages** (Paquetes) y edite el paquete que usan sus usuarios (ej. `default`).
    *   En **Incremental Backups**, elija **Enabled**. Con eso, la pestaña **Backups** del usuario aparece aunque el
        número de respaldos tradicionales del paquete sea `0`, siempre que el servidor tenga un sistema de respaldo
        configurado.

3.  **Prueba Manual Final:**
    *   Siempre verifique la conexión y el primer envío manualmente:
        ```bash
        v-backup-user-restic [usuario]
        ```

---

## 🔒 Seguridad de Archivos Estáticos (Nginx Bypass)

Por defecto, HestiaCP configura Nginx para servir una amplia lista de extensiones de forma directa (Proxy Static Extensions). Esto mejora el rendimiento, pero genera una **brecha de seguridad crítica**: Nginx ignora las reglas del archivo `.htaccess` de Apache.

Si un archivo como `composer.json` o un backup `.sql.gz` coincide con una extensión en la lista de Nginx, cualquier persona podrá descargarlo incluso si Apache tiene prohibido el acceso.

### Configuración Recomendada

Para solucionar esto, edite la configuración de su **Web Domain** en HestiaCP y refine la lista de **Proxy Static Extensions** eliminando las extensiones sensibles.

#### ✅ Mantener en Nginx (Seguro)

Estas extensiones no suelen contener datos sensibles y se benefician del rendimiento de Nginx:
`css, js, mjs, png, jpg, jpeg, gif, webp, svg, ico, avif, woff, woff2, ttf, otf, eot, mp3, mp4, ogg, webm`

O:

`none` Para que Apache maneje todas las extensiones.

#### ❌ Eliminar de Nginx (Pasar a Apache)
Estas extensiones deben ser manejadas por Apache para que el `.htaccess` o el framework (**ServerStatics**) puedan protegerlas:
`json, xml, txt, gz, zip, rar, 7z, tar, tgz, sql, log, doc, docx, xls, xlsx, pdf`

### Archivos subidos protegidos (PiecesPHP 8)

Las subidas privadas **ya no dependen de esta lista**. PiecesPHP las guarda en disco con el
sufijo `.protected` al final (`documento.pdf.protected`), una extensión que Nginx no reconoce:
- `…/documento.pdf` no existe con ese nombre, así que Nginx lo pasa a Apache, y PiecesPHP lo sirve
  tras validar;
- `…/documento.pdf.protected` lo niega el `.htaccess` de `statics/uploads`.

Lo público lleva su nombre real y Nginx lo sirve directamente, así que puede quedarse en la
lista sin riesgo.

Tras actualizar una instalación existente, migra las subidas con
`bin/cli statics-protect-migrate` (primero el simulacro, luego `--run`). La guía completa está en
«Archivos protegidos», en la documentación del framework.

La recomendación de arriba **sigue valiendo** para lo que no son subidas: `composer.json`, los
respaldos `.sql.gz`, los logs y demás archivos sensibles que un `.htaccess` protege y Nginx no
lee.

---

## 🔍 Solución de Problemas (Troubleshooting)


### Error: «Username or Group allready exists»
El instalador se niega a seguir si el nombre de administrador (`--username`, `admin` por defecto) ya existe como
usuario o como grupo del sistema (`/etc/passwd` o `/etc/group`). Compruébalo y elige otro nombre:

```bash
getent passwd admin; getent group admin
export HESTIA_ADMIN_USER="hstadmin"
```

Y repite la instalación pasando `--username $HESTIA_ADMIN_USER` (la Opción B ya lo pasa). No edites `/etc/group` a mano.

