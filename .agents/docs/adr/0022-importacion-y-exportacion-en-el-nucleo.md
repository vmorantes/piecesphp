# 0022 — Importación y exportación: motor en el núcleo, un solo módulo de panel

- **Estado:** Aceptada
- **Fecha:** 2026-09-17
- **Decide:** Product Owner (P-a a P-d, respondidas en A-049) y arquitecto en lo delegado (P-c)
- **Estructural:** sí (retira un módulo y un espacio de nombres del núcleo; lote 8)
- **Detalle del diseño y censo previo:** `.agents/estado/propuesta-2026-09-16-lote-8.md`

## En cristiano

El framework tiene dos maneras de importar datos: el módulo `Importers`, que funciona pero tiene fallos de seguridad,
y `DataImportExportUtility`, apagado y con fallos peores. Se quedan en una sola: un motor de importación y exportación
agnóstico, que vive en el núcleo como si fuera un paquete, y un único módulo de panel que lo usa. Cada tipo de dato que
se quiera importar (usuarios, por ejemplo) es una implementación de ese motor. Importar usuarios sigue permitiendo
entregar credenciales generadas a quien las reparte (escuelas), pero las contraseñas se entregan una sola vez y no
quedan guardadas en claro.

## Contexto

- El PO creó el importador como proyecto ambicioso y agnóstico, «como un paquete pero contenido en el framework»: el
  motor en el núcleo y los importadores como implementaciones (P-a).
- Defectos medidos (propuesta §2): S1 y S2 ya corregidos en `#132`; S3 subida sin validar, S4 XSS en mensajes, S5
  lectura de archivos por la query, S6 contraseñas en claro, S7 GET que escribe, S8 `rand()` (ya corregido en el
  lote 10), S9 permiso fuera del nombre de la ruta.

## Decisión

1. **Motor en el núcleo**, espacio de nombres nuevo `PiecesPHP\Core\DataTransfer` (definición de importación, columnas,
   fuente de filas, informe, ejecutor; definición de exportación y escritor). Se retira `PiecesPHP\Core\Importer`.
2. **Un módulo de panel**, `DataImportExportUtility`. Cada importador o exportador se registra con una **ruta propia**,
   y su nombre es su permiso. Se retiran `Importers/`, `IMPORTS_MODULE_ENABLED` y las rutas `importer-*`.
3. **Todo o nada por archivo** (P-c, decidido por el arquitecto): se validan todas las filas, se informa de cada error
   y solo si todas son válidas se guarda el archivo entero en una transacción.
4. **Solo columnas declaradas**: una columna del archivo que la definición no declara se ignora (cierra S1 y S2 por
   diseño).
5. **Importación de usuarios** (P-d): por defecto no se importan administradores. Los tipos importables están en una
   lista en código, fácil de cambiar, y nadie importa un tipo con más prioridad que el suyo
   (`UsersModel::TYPES_USER_PRIORITY`).
6. **Credenciales generadas** (P-b): se conservan y se documentan en todas las capas. Las contraseñas generadas se
   entregan **una sola vez** a quien importa (descarga inmediata de las fichas o del archivo de credenciales) y no se
   guardan en claro en disco, en la base ni en logs.
7. Subida por `UploadedFileAdapter`, lector explícito (XLSX y CSV), tamaño y filas máximos; mensajes sin HTML; sin
   GET que escriba; exportación CSV con las celdas que empiezan por `=`, `+`, `-` o `@` neutralizadas.

## Alternativas descartadas

- **Todo dentro del módulo:** haría depender de un módulo a cualquier módulo con importador; contradice el diseño del PO.
- **Filas independientes** (como hoy): un archivo a medio importar es peor que uno rechazado con su lista de errores.
- **Retirar las fichas con credenciales:** el PO las necesita para sistemas que reparten credenciales.

## Consecuencias

- Buenas: un solo camino, permisos por nombre de ruta, sin fallos S3-S9.
- Malas: rupturas para quien tenga un importador propio sobre `Core\Importer` (se reescribe) y para quien dependa de
  las rutas o de la respuesta JSON viejas (propuesta §5). Las credenciales generadas que no se descarguen en el
  momento se pierden: hay que generarlas otra vez.

## Reversión

Parcial y por rondas: cada ronda del lote 8 es un commit. Antes de la retirada (R5) basta con no registrar el módulo
nuevo; después, restaurar `Importers/`, `PiecesPHP\Core\Importer` y la constante desde el commit anterior a la retirada.

## Verificación

- La suite de caracterización del comportamiento viejo (R1) y la de paridad del nuevo (R3), con cada cambio marcado.
- Pruebas de rechazo: columna `type`/`id` en el archivo, tipo con más prioridad que el importador, archivo con una fila
  inválida (no se guarda ninguna), subida con tipo o tamaño no permitido, contraseñas ausentes de disco, base y logs.
