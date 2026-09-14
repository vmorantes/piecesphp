# 0008 — Lote 3: las subidas dejan de servirse a cualquiera

- **Fecha:** 2026-09-14
- **Pedido por:**
  - el mapa, lote 3;
  - el PO: «hace falta auditar seguridad de DATOS de uploads de modulos privados como
    documents, news»;
  - P24, aprobada: «P24 me parece bien… que sirva de arquetipo en todo como se ha venido
    haciendo».
- **Bloques:** BH (auditoría), BJ (la antesala: el estado en rutas públicas, la caché,
  `protect()` y Documents) y BK (P24). **Mensajes:** `#026`–`#027`, `#032`–`#035` y la
  comprobación de H1 en `#036`–`#037`.
- **Commits:**
  - BH: `4a7b4124` `9e708797`;
  - BJ: `f0acfc7a` `962f6baf` `18bdf1b0` `b0f04ce4` `5ff91984` `87559cc5` `43ba6169`
    `0761bc1b`;
  - BK: `0427b0c9` `d46b1fa9` `95758ca3` `4ca2e99d` `3a627ea8` `22446022` `2845cb40`
    `01e8b1b4`.

## Qué se pidió

Proteger las carpetas de subidas. El plan heredado solo miraba la puerta (`protect()`); el PO
pidió mirar los datos.

## Qué se encontró al explorar (auditoría, `#027`)

- **Nueve `UPLOAD_DIR`, y solo publications en `protect()`**, con un validador que devolvía
  `true`. Apache servía todo lo demás a quien tuviera la URL: documentos de cualquier tipo con
  el nombre original, y el RUT y el logo de las organizaciones.
- **Fuera del alcance, lo más grave de la jornada:** una inyección SQL en dos rutas públicas.
  Se abrió el lote 3a (bitácora 0007).
- **Rutas públicas que devolvían borradores y borrados** con `?status=ANY`.
- **`protect()` no protegía una carpeta que aún no existía**, y su prefijo casaba sin
  separador.
- **Otros defectos:** nombres de archivo adivinables, SVG servido desde el mismo origen, `tmp`
  muertos, archivos huérfanos al borrar y andamiaje sin llamadores.

## Qué se instruyó y qué reportó el coder

- **BJ (`#032`/`#033`):**
  - las rutas públicas solo devuelven lo publicado sin sesión con permiso;
  - la clave de caché de publications refleja los valores efectivos. La caché estaba apagada,
    pero con la clave vieja habría servido a un anónimo un listado privilegiado;
  - `protect()` crea la carpeta que falta y exige el separador;
  - Documents filtra de verdad por estado.
- **BK (`#034`/`#035`):**
  - **Publications, arquetipo:** con sesión, se sirve; sin sesión, solo si
    `isVisibleToPublic()`, extraído de `singleView()`. Falla cerrado.
  - **documents, organizations y news-categories:** con sesión.
  - **banner y generic:** declarados públicos en `files/dev/upload-dirs.json`.
  - **Retirados** los tres `UPLOAD_DIR` sin archivos. Con ellos murieron 6 errores de PHPStan
    (744 → 738) y 6 retornos ignorados.
  - **La comprobación 29:** toda carpeta de subidas está protegida o declarada.
  - **Los universos de los censos, declarados.**
- **H1 de `#035` (`#037`):** ninguna ruta ni vista pública muestra archivos de documents,
  organizations ni news-categories.
  - Del inventario, ninguna de las 94 rutas sin sesión es de esos módulos.
  - La vista «public» de noticias solo la usan rutas con sesión.
  - Protegerlos no rompió nada visible. SIN VERIFICAR: enlaces guardados dentro de la base.

## Qué quedó fuera

- **Nombres adivinables (H5) y SVG del mismo origen (H6):** una vez protegidas las carpetas
  privadas, pesan menos. Se reevalúan con el lote 10.
- **Huérfanos al borrar (H8) y los `UPLOAD_DIR_TMP` muertos:** lote 10.
- **P25 candidata:** `singleView()` y los archivos no miran la aprobación de SystemApprovals;
  el listado sí.
- **Los listados públicos de Locations**, que enseñan tablas enteras sin sesión (H3 de
  `#031`).

## Aprendido

- **Auditar los datos, y no la puerta, encontró lo más grave del día**, y además fuera de su
  alcance. Seguir un valor desde la ruta hasta la sentencia ve lo que ningún censo ve.
- **Un arquetipo se extrae, no se copia.** `isVisibleToPublic()` salió de `singleView()` y ahora
  lo usan los dos: un solo criterio.
- **Retirar código muerto mueve trinquetes**: PHPStan y retornos. Registrarlo con su reparto es
  parte del bloque, no un punto serio.
