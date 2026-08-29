# Seguridad y operación — cuatro revisiones declaradas por el PROPIETARIO

Registradas el 2026-08-29, **no ejecutadas**. Van aquí y no en `18-siguientes-ventanas.md`
porque todavía no existen como trabajo. Son para **el final de la campaña**, con las demás.

Sus palabras, formalizadas:

## 1 · El sistema de reporte y registro de errores

Revisar cómo se reportan y se registran los errores. Contexto ya medido que le concierne:
`bootstrap.php` promueve `E_WARNING` y `E_NOTICE` —un aviso mata la petición—, y `log_exception()`
es hoy el destino de casi todo. La campaña ha encontrado varias veces el mismo patrón: **un valor
de retorno que nadie lee** (`FileUpload::validate()`, `'success' => true` literal, `db-backup`).
Un sistema de errores que solo registra excepciones no ve nada de eso.

## 2 · El sistema de encriptación

Revisar y mejorar. Piezas conocidas: `BaseHashEncryption`, `app_key`, `SessionToken` y su fecha
mínima —tocar cualquiera de las dos invalida todas las sesiones—, y `password`, que la LEY 10
declara opaco para toda herramienta que mueva filas de usuario.

## 3 · El sistema de autenticación

Revisar y mejorar. Conecta con lo anterior y con `SessionToken` / `SessionTokenIsolated`, y con
la asimetría de guardas de T114 y las cuatro controladoras de `Locations` que deciden la operación
desde el cuerpo de la petición.

## 4 · Un sistema de tokens de API

Revisar la posibilidad. No existe hoy. Es lo único de los cuatro que es construcción y no
revisión, y depende de 2 y 3: un token de API sin un sistema de autenticación revisado hereda sus
defectos y los expone hacia fuera.

---

**Orden natural, si se abordan juntos**: 1 primero —sin ver los errores no se evalúa nada—, luego
2, luego 3, y 4 al final. Ninguno bloquea la major.
