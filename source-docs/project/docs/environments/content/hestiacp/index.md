# 🛡️ HestiaCP

Hestia Control Panel (HestiaCP) es un panel de control potente y ligero diseñado para administradores de servidores que buscan una interfaz intuitiva y eficiente.

> [!WARNING]
> **Compatibilidad de SO:** Antes de iniciar, confirme que su sistema operativo sea **Ubuntu 22.04 LTS**.
> La versión 1.9.x de HestiaCP **no es compatible** con Ubuntu 24.04 LTS.

Este tutorial está ajustado a la **versión 1.9.4** 

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
export HESTIA_PASSWORD="hestiacp8083pass"
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

### Opción A: Instalación estándar (Recomendada)
Esta opción incluye soporte Multi-PHP y cuotas de disco, desactivando ClamAV para ahorrar recursos.

```bash
sudo bash hst-install.sh \
    --hostname $HESTIA_DOMAIN \
    --email $HESTIA_EMAIL \
    --password $HESTIA_PASSWORD \
    --multiphp yes \
    --clamav no \
    --quota yes
```

### Opción B: Instalación completa (Personalizada)
Si requiere control total sobre todos los servicios:

```bash
sudo bash hst-install.sh \
    --apache yes \
    --phpfpm yes \
    --multiphp '7.3,7.4,8.0,8.1,8.2,8.4,8.5' \
    --vsftpd yes \
    --proftpd no \
    --named yes \
    --mysql yes \
    --mysql8 no \
    --postgresql no \
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
    --sieve no \
    --force
```

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

### Módulos PHP y Apache (Recomendados para PiecesPHP)
```bash
# Instalar módulos necesarios para múltiples versiones
sudo apt install -y php*-{common,pdo,xml,ctype,mbstring,fileinfo,gd,mysqli,sqlite3,zip,xsl,xmlwriter,xmlreader,curl,intl}

# Activar módulos de Apache vitales
sudo a2enmod rewrite headers ssl

# Reiniciar servicios
sudo systemctl restart apache2
```

### Configuración de Base de Datos
*   [Guía de configuración de MariaDB](../lamp/content/MariaDB.md)

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
> Los números representan la política de retención: **Días (30), Semanas (8), Meses (5), Años (3)** y Total de instantáneas (-1 para ilimitado).

> [!CAUTION]
> **Error de Inicialización:** Si el respaldo falla indicando que el repositorio no existe, deberá inicializarlo manualmente una sola vez con: `restic init -r /mnt/backups/`.

### 4. Consideraciones Críticas

*   **⚡ Rendimiento:** Al ser local, la velocidad es drásticamente superior y puede lograr ahorros de almacenamiento de hasta **25:1** gracias a la deduplicación.
*   **💾 Espacio de Caché:** Restic usa cache temporal en `/root/.cache/restic`. Asegúrese de tener espacio en el disco principal para evitar fallos durante la purga.
*   **🔑 Claves de Cifrado:** HestiaCP cifra los datos por defecto. **Es obligatorio** resguardar los archivos `restic.conf` ubicados en `/usr/local/hestia/data/users/[usuario]/`. Sin estos archivos, sus respaldos en el NAS serán **ilegibles e irrecuperables**.

### 5. Programación y Automatización (Cron)

HestiaCP **no activa automáticamente** el cronjob para Restic al añadir el host. Debe realizar los siguientes pasos adicionales para que el sistema sea autónomo:

1.  **Habilitar en el Paquete:**
    *   Vaya a **Packages** (Paquetes) y edite el paquete que usan sus usuarios (ej. `default`).
    *   Asegúrese de que el soporte para backups esté activo y, si aparece la opción específica, habilite los respaldos incrementales.

2.  **Añadir el Cron Job Específico:**
    *   Vaya a la sección **Cron** del panel de HestiaCP (como admin).
    *   Añada una nueva tarea con el comando: `v-backup-users-restic`
    *   Programe la hora (se recomienda una hora distinta a la de los backups tradicionales, por ejemplo: `30 05 * * *`).

3.  **Prueba Manual Final:**
    *   Siempre verifique la conexión y el primer envío manualmente:
        ```bash
        v-backup-user-restic [usuario]
        ```

*   **¿Se pueden eliminar los .tar al 100%? (Realidad Técnica):**
    *   Lamentablemente, HestiaCP requiere que el valor de **Backups** en el paquete sea al menos **`1`** para que el proceso capture datos reales. Si se pone en `0`, los respaldos de Restic resultarán en carpetas vacías.
    *   Si se desactiva el backup local globalmente (`local = no`), la pestaña de **Backups** desaparecerá de la interfaz.

> [!IMPORTANT]
> **La recomendación final:**
> Para mantener la pestaña activa en la interfaz y que los respaldos contengan datos, lo ideal es configurar **`Backups = 1`** en el paquete y aceptar la existencia de un único archivo `.tar` residual. Es el pequeño precio a pagar por mantener la comodidad de la gestión vía web.

---

## 🔍 Solución de Problemas (Troubleshooting)


### Error: "User admin exists"
Si la instalación falla porque el usuario `admin` ya existe:

1.  Abra el archivo de grupos: `sudo nano /etc/group`
2.  Elimine la línea `admin:x:117:` (o similar).
3.  Guarde los cambios y reintente la instalación.

