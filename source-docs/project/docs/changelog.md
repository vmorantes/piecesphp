# Historial de Cambios (Changelog)

Registro detallado de las actualizaciones y mejoras del framework PiecesPHP.

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
