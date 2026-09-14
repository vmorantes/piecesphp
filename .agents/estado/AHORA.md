# Ahora

- **Actualizado:** 2026-09-14 15:20
- **Último mensaje:** `#010 · ARQ`, lote 0a (`dev` en los paquetes) y commit de la documentación
  del lote 0b. En vuelo.
- **Tramo en curso:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, `HEAD` en `45d1a84f` antes de `#010`.

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
- dependencias, builds, servidores y credenciales.

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:**

- ~~el commit del andamiaje~~, hecho;
- ~~el lote 0b~~, hecho (bitácora 0003);
- el lote 0a, `dev` en los paquetes (P19). **En curso**;
- después, el mapa `../docs/roadmap.md`, **cuando el PO dé la orden de trabajar sobre el
  framework**.

## Espera al PO

- **Tras `#010`: la orden de trabajar sobre el framework.** El mapa empieza por BD (delegado en
  el arquitecto) y por los identificadores de SQL.
- **Las ramas `dev` nuevas de `datastructures` y `html` quedan solo en local**; subirlas es
  cosa tuya.

Siguen abiertas en `docs/pendientes.md`, sin bloquear nada: qué es el geovisor, el francés, el
rol 50 con nombre `null` y `Components`.

## En curso

`#010`:

1. crea `dev`, apuntando a `master`, en `datastructures` y `html`, y comprueba que en los cuatro
   paquetes `dev` y `master` coinciden;
2. commitea la documentación del lote 0b (`files/` en `02-estructura.md`, la bitácora 0003, el
   mapa, los pendientes y el estado).

Si se corta a medias, `git branch` en cada paquete y `git log` aquí dicen dónde quedó.

## Siguiente

Con el reporte de `#010`:

1. cerrar el tramo, con su resumen;
2. esperar la orden del PO para empezar el mapa.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md`: explica el traspaso.
