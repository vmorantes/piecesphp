# Uso de la Terminal en PiecesPHP

## Introducción
Puedes ejecutar tareas administrativas y de mantenimiento desde la terminal usando el CLI de PiecesPHP. Estas tareas están orientadas a la gestión del framework y requieren permisos de usuario root en el sistema PiecesPHP.

---

## Instrucciones básicas

Desde la carpeta `src`, ejecuta:

```bash
php index.php cli <acción> [parámetro=valor ...]
```

Por ejemplo:

```bash
php index.php cli bundle app=yes zip=yes
```

---

## Acciones disponibles

### 1. db-backup
Respalda la base de datos por defecto.

**Parámetros:**
- `gz` (yes|no) — Define si el respaldo se comprime en gzip. Por defecto: yes.

**Ejemplo:**
```bash
php index.php cli db-backup gz=yes
```

---

### 2. bundle
Empaqueta la aplicación y/o los archivos estáticos.

**Parámetros:**
- `app` (yes|no) — Solo carpeta app. Por defecto: no
- `statics` (yes|no) — Solo carpeta statics (sin filemanager, uploads ni plugins). Por defecto: no
- `all` (yes|no) — app y statics. Por defecto: no
- `zip` (yes|no) — Define si solo copia los archivos o los comprime como zip. Por defecto: no

**Ejemplo:**
```bash
php index.php cli bundle all=yes zip=yes
```

---

### 3. clean-cache
Fuerza la limpieza de caché de archivos estáticos mediante la renovación del token.

**Parámetros:**
- N/A

**Ejemplo:**
```bash
php index.php cli clean-cache
```

---

### 4. clean-logs
Limpia los archivos de logs (errores, logs antiguos y logs de sesiones expiradas).

**Parámetros:**
- N/A

**Ejemplo:**
```bash
php index.php cli clean-logs
```

---

### 5. clean-all
Limpia caché y logs en una sola acción.

**Parámetros:**
- N/A

**Ejemplo:**
```bash
php index.php cli clean-all
```

---

### 6. scan-missing-lang
Revisa los mensajes faltantes por traducción y genera un archivo con ellos.

**Parámetros:**
- `--exclude-lang` — Cadena separada por comas de idiomas a ignorar. Ejemplo: `--exclude-lang=es,en`
- `--exclude-group` — Cadena separada por comas de grupos a ignorar. Ejemplo: `--exclude-group=general,public`

**Ejemplo:**
```bash
php index.php cli scan-missing-lang --exclude-lang=es,en --exclude-group=general,public
```

---

### 7. help / h
Muestra la lista de tareas disponibles y su descripción.

**Ejemplo:**
```bash
php index.php cli help
php index.php cli h
```

---

## Notas y advertencias
- Algunas tareas requieren permisos de usuario root PiecesPHP.
- Los respaldos de base de datos se guardan en la carpeta `dumps` y los bundles en la carpeta `bundle`.
- Los parámetros pueden ser escritos en mayúsculas o minúsculas, pero se recomienda usar minúsculas.
- Si tienes dudas sobre los parámetros de una acción, ejecuta `php index.php cli help`.
