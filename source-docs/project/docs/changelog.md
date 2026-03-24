# Historial de Cambios (Changelog)

Registro detallado de las actualizaciones y mejoras del framework PiecesPHP.

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
