# Ahora

- **Actualizado:** 2026-09-14 18:52 (medido con `date`). **Tramo sin el PO, CERRADO** tras las
  cinco rondas autorizadas («Puedes trabajar unas tres o cinco rondas, pero toma en cuenta que no
  estaré así que no responderé nada»).
- **Último mensaje:** `#038 · ARQ`, la ronda de cierre, que solo commitea documentos. El próximo
  número es `#039`, su reporte. Después viene `#040`.
- **Tramo:** [`tramos/2026-09-14-1726-desatendido-lote-3a.md`](tramos/2026-09-14-1726-desatendido-lote-3a.md),
  cerrado, con su resumen.
- **Informe del estado del proyecto**, al día:
  [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md).
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `69b48dc7` antes de `#038`. Hay 27 commits sin empujar en piecesphp; los
  paquetes, sin cambios desde la subida del PO.

## Autorización de commits del PO (ADR 0005)

**Vigente desde el 2026-09-14.** El coder prepara y commitea, **en commits atómicos**, el trabajo
que el PO nombra y el arquitecto instruye, sin pedir permiso commit a commit.

**Reservado al PO:**

- `git push` y todo lo que toca un remoto;
- etiquetar `piecesphp` y tocar su `master` o su `last-stable`;
- crear ramas, salvo `dev` en los paquetes;
- reescribir historia;
- escribir o destruir datos de una base de datos, salvo lo que nombre la instrucción con su
  autorización;
- dependencias, builds, servidores y credenciales. Excepciones: las herramientas de análisis
  (ADR 0007) y `piecesphp/*` dentro de los paquetes hermanos (ADR 0008).

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:** el mapa, `../docs/roadmap.md`, en su orden. Orden del PO:
«Trabaja. Adelante.» (2026-09-14).

## Espera al PO

Nada bloquea el mapa. Para cuando vuelva:

1. **El plan del lote 4, bloque 2**: migrar los 14 llamadores de `DataTablesHelper::process()`
   para quitar el último `escapeString()` de la búsqueda del panel. Pasa de diez archivos, así
   que lo ve antes (regla de los diez).
2. **P25 (candidata)**: una publicación sin aprobar se ve por su enlace directo, y sus archivos
   se sirven, aunque el listado la oculte. *Predeterminado*: se queda así.
3. **Locations**: los listados públicos de puntos, ciudades y estados enseñan tablas enteras sin
   sesión. *Predeterminado*: se quedan públicos.
4. **Subir cuando quiera**: 27 commits.

Siguen abiertas en `docs/pendientes.md`: qué perfeccionar del geovisor, el francés, el rol 50 con
nombre `null` y `Components`.

## En curso

`#038`, cierre: el coder commitea las bitácoras 0008 y 0009, el `CHANGELOG.md`, el mapa,
`pendientes.md`, el informe y el estado. Nada del producto.

## Siguiente — la próxima jornada

1. **Las dos órdenes `/rename`**, antes que nada.
2. **Lote 4, bloque 2:**
   - las etiquetas de `OrganizationMapper.php:664-667` sin `escapeString()`: pasarlas a PHP
     después de leer la fila, o un literal hexadecimal. Medir antes cuál conserva el resultado;
   - el plan de los 14 llamadores de `process()`, presentado al PO;
   - al final, `@deprecated` en `escapeString()` y el cero en la prueba de fuente.
3. **Lote 5 (OTP)** y **5b (tokens genéricos)**, en su orden.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`, el informe del estado del proyecto y el último tramo.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
