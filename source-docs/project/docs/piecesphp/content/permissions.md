# Gestión de Permisos y Propiedad

Una correcta configuración de permisos es fundamental tanto para la seguridad como para el funcionamiento operativo del framework (logs, caché, subidas de archivos).

## Conceptos Generales

En sistemas Linux, PiecesPHP suele operar en dos contextos:
1. **Usuario de Ejecución (PHP):** El usuario bajo el cual corre el proceso PHP (ej. `www-data` en instalaciones estándar o el nombre del usuario en paneles como HestiaCP).
2. **Grupo del Servidor Web:** Generalmente `www-data`, necesario para que Apache/Nginx pueda leer archivos estáticos.

### El bit SetGID (2)

Utilizamos el bit **SetGID** en los directorios de escritura. Esto asegura que cualquier archivo creado por PHP herede automáticamente el grupo del directorio padre, evitando problemas de permisos cuando un desarrollador intenta manipular archivos creados por el sistema (o viceversa).

---

## Estructura Recomendada

### Permisos Base
- **Directorios:** `755` (rwxr-xr-x)
- **Archivos:** `644` (rw-r--r--)

### Directorios de Escritura (Recomendado: `2775` / `664`)
Las siguientes rutas requieren permisos de escritura para el funcionamiento del framework:

| Ruta | Descripción |
| :--- | :--- |
| `app/logs` | Registros de errores y depuración. |
| `app/cache` | Archivos temporales de caché de rutas, vistas, etc. |
| `app/lang/dynamic-translations` | Traducciones editables desde el panel. |
| `app/lang/missing-lang-messages` | Registro de etiquetas de idioma faltantes. |
| `tmp` | Archivos temporales generales. |
| `dumps` | Volcados de base de datos o exportaciones. |
| `statics/uploads` | Archivos subidos por los usuarios. |
| `statics/css` | Solo si se compila SASS en el servidor. |
| `statics/server-delegated` | Archivos gestionados por lógica interna. |

---

## Script de Ajuste Automatizado (Ejemplo para HestiaCP)

Si te encuentras en un entorno con **HestiaCP**, puedes usar este script para realizar un ajuste inicial completo:

```bash
# Establecer variables utilitarias
export HESTIA_CP_USER=[USER]
export HESTIA_CP_DOMAIN=[WEB]
export APACHE_USER=www-data
```

```bash
# Moverse a carpeta pública
cd /home/"$HESTIA_CP_USER"/web/"$HESTIA_CP_DOMAIN"/public_html

# Ajustes de propiedad (en public_html, HestiaCP)
echo -e "\n[+] Estableciendo propiedad..."
sudo chown -R "$HESTIA_CP_USER":"$APACHE_USER" .

# Permisos restrictivos generales
echo -e "\n[+] Estableciendo permisos generales..."
find . -type d -exec chmod 755 {} \; # 755: Propietario (lectura, escritura, ejecución), Grupo (lectura, ejecución), Otros (lectura, ejecución)
find . -type f -exec chmod 644 {} \; # 644: Propietario (lectura, escritura), Grupo (lectura), Otros (lectura)

# Directorios de aplicación que requieren permisos de escritura
echo -e "\n[+] Estableciendo permisos para directorios de aplicación que requieren permisos de escritura..."

WRITABLE_DIRS=(
    "app/logs"
    "app/cache"
    "app/lang/missing-lang-messages"
    "app/lang/dynamic-translations"
    "tmp"
    "dumps"
    "statics/css"
    "statics/server-delegated"
    "statics/uploads"
    "statics/filemanager"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        # Directorios
        find "$dir" -type d -exec chmod 2775 {} \;
        # Archivos
        find "$dir" -type f -exec chmod 664 {} \;
    fi
done
```

> [!IMPORTANT]
> Nunca utilices `777` en entornos de producción. El uso de SetGID y una correcta gestión de grupos (`www-data`) es la forma profesional y segura de manejar la escritura.
