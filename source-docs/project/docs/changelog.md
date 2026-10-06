# Historial de Cambios (Changelog)

Registro detallado de las actualizaciones y mejoras del framework PiecesPHP.

> **La última versión estable es la `8.0.0` (05-10-2026), con cambios incompatibles respecto de la `7.1.0`.** La rama
> `last-stable` apunta siempre a la última estable. Antes de actualizar desde la `7.x` lee la sección `8.0.0` del
> `CHANGELOG.md` de la raíz del repositorio, que es la fuente completa: esta página es solo un resumen.

---

## 🚀 8.0.0 (05-10-2026)

Versión estable. Reúne las rupturas de la campaña desde la `7.1.0`; antes salieron las pre-versiones `v8.0.0-alpha.1` a
`alpha.4`, `rc.1`, `rc.2` y `rc.3`.

- **Rango de PHP soportado: `>=8.5 <8.6`** (`src/composer.json`). Antes era `>=8.4.1 <8.6`. El `composer.json` fija además
  `config.platform.php` en `8.5.0`, para que Composer no resuelva contra el PHP que lo arranca. Los cuatro paquetes
  `piecesphp/*` suben de versión mayor y adoptan el mismo rango.
- **Cambios incompatibles** (el detalle, con qué revisar en un clon, está en el `CHANGELOG.md` de la raíz):
    - **Correo**: sin `src/app/config/environment.php` el correo **no sale** (`mail_delivery` en `auto` vale `sink`).
      En producción declara `production` o elige `real` en **Integraciones → Correo**. Además hay dos archivos SQL que
      aplicar para el registro de correos (`databases/actualizaciones/2026-10-03-registro-de-correos.sql` y
      `2026-10-05-cuerpo-del-correo.sql`).
    - **`bin/cli gates` (y `bin/verify`)** solo corren en una instalación declarada `local`.
    - **URLs**: `friendlyURLString()` ya no elimina los dígitos; la forma vieja redirige (301) a la nueva.
    - **Tareas programadas**: el estado y el candado de cada una llevan una huella del nombre; cada tarea con franja
      se ejecuta una vez de más tras actualizar.
    - **Respaldos de la base**: se gobiernan con una política y el primer respaldo correcto borra los que sobren
      (se desactiva en **Configuración → Sistema → Respaldos**).
    - **Rutas**: se retira el «alias» de una ruta y con él el atajo `bin/cli h` (usa `bin/cli help`).
    - **Sesiones caducadas**: ya no se escribe un archivo con el JWT por petición; hay una línea de registro opcional.
    - **Parámetros opcionales** mal formados responden 400 en vez de sustituirse en silencio por su valor por omisión.
    - **`src/app/config/final-configurations-includes/` pasa a `src/app/config/extensions/`**, y cada archivo de
      `app/config` declara de quién es.
    - **Vistas, scripts y clases movidos de lugar** (tabla de rutas viejas y nuevas en el `CHANGELOG.md`).
    - Y, entre otros: `ServerStatics::serveModuleStatic()` ya no existe (usa `serve()`); el alta y la edición se
      deciden por el nombre de la ruta y no por el cuerpo; se retiran módulos y tablas (convocatorias, repositorio de
      imágenes, áreas de interés, experiencias previas y diccionarios de `fr`, `pt`, `it` y `de`).
- **Seguridad**: el registro de acciones dice quién actuaba de verdad al «conectarse como otro usuario», y se corrigió
  la recuperación de contraseña (se podía tomar una cuenta).
- **Nuevo**: `bin/cli mail-doctor` y `bin/cli mail-demo`, y la pantalla «Registro de correos».

---

## 🚀 7.1.0 (20-08-2026)

- **Rango de PHP soportado: `>=8.4.1 <8.6`.** Antes era `>=8.1 <8.5`.
    - Se abandona PHP 8.1, sin parches de seguridad desde el 31-dic-2025.
    - El `.1` del piso lo exige `symfony/cache` 8.1, que entra como transitiva.
    - **Ubuntu 24.04 LTS trae 8.3**: el despliegue requiere el repositorio de ondrej.
      Ver [general.md](piecesphp/content/general.md).
- **Compatibilidad con PHP 8.5**: 13 correcciones de deprecaciones en el código propio
  (casts no canónicos, `Reflection*::setAccessible()`, `$http_response_header`).
- **Manejo de errores** (`bootstrap.php`), con efecto en producción:
    - `E_USER_ERROR` y `E_RECOVERABLE_ERROR` ya no se descartan en silencio: abortan.
      Eso incluye el `platform_check` de Composer, que antes se perdía y permitía arrancar
      sobre una versión de PHP no soportada.
    - Las deprecaciones solo abortan en local; en producción van a
      `app/logs/deprecations.log`, que `bin/cli clean-logs` ya limpia.
- **Paquetes propios** alineados a `">=8.4 <9.0"`: `database` v3.1.0, `datastructures`
  v3.1.0, `html` v2.1.0, `geojson` v2.1.0.
- **Symfony pasa de 6.4 a 8.1**, más PhpSpreadsheet 5.9.0 y ZipStream 3.2.2.
- Los `composer.lock` de la aplicación y de las herramientas pasan a versionarse.

---

## 🚀 7.0.0 (23-03-2026)

- **Migración a PHP 8.4 funcional.** Con soporte extendido hasta PHP 8.1.
- **Optimización del Núcleo:** Ajustes para compatibilidad con las últimas directivas de PHP 8.4.

---

## 🛠️ 7.0.0-beta

- Soporte para PHP 8.4 en proceso.
- Ajuste de `composer.json`.
- **Upgrade con PHPStan:**
    - Se ignoran falsos positivos con `__()` añadiendo documentación condicional.
    - Corrección de nullables implícitos.
    - Resolución de errores de variables no declaradas.
    - Nivel 2 de PHPStan completado al 100%.

---

## 📦 6.4.4 (22-03-2026)

- **Integración con Mautic:**
    - Refactorización de `MauticEmailAdapter` para mayor confiabilidad.
    - Prueba de procesamiento vía cronjob.
- **HttpClient Modernizado:**
    - Mejoras significativas en `HttpClient.php` con soporte para métodos modernos y mayor robustez.
    - Inclusión de pruebas unitarias exhaustivas.
- **Gestión de Usuarios:**
    - Optimización de la lógica para funcionar sin el módulo de organizaciones.
    - Formularios dinámicos que ocultan campos innecesarios.
    - Nuevo estado de usuario: "Eliminado".

---

## 🏗️ 6.4.3 (18-03-2026)

- **Sistema de Colas (Queue System):**
    - Introducción del procesamiento de tareas en segundo plano.
    - Implementación de `QueueTask` y `QueueHandlerResponse`.
    - Gestión de persistencia con `QueueJobMapper` (reintentos, programación diferida).
- **FreezeRequest:**
    - Motor de "congelación" de peticiones para ejecución diferida en colas.
    - Captura completa de contexto: `$_POST`, `$_GET`, `$_FILES`, `$_SESSION`, etc.
- **Eventos Globales:**
    - Centralización en `BaseEventDispatcher`.
    - Nuevo archivo `event-listeners.php` para suscripciones organizadas.

---

> [!TIP]
> Para ver el historial completo, consulta el archivo `CHANGELOG.md` en la raíz del repositorio.
