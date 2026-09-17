# Ahora

- **Actualizado:** 2026-09-17 08:46 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-17-0846-lote-10-continua.md`](tramos/2026-09-17-0846-lote-10-continua.md).
- **Último mensaje:** `#230 · ARQ` (en vuelo). Próximo: `#231 · COD`. **Último al PO:** A-054.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron el 2026-09-17, así que cuentan como compactadas.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

**2026-09-17, lote 10 continúa. Ronda F1 (#230):** la portada revienta por el banner (`home.js`) y la vista del banner no escapa. Después, en este orden: E2 ampliada (`piecesphp/database` 5.1.0: serialización sin credenciales, P42, P44), F (R14, R32, R34), propuestas P38 y P39, lote 8 y lote 11 con P36. Las claves de prueba de reCAPTCHA (#224) siguen esperando la confirmación del PO en la sesión del coder.

Cerrado: **`#175`→`#176`, `v8.0.0-alpha.4`** (`4c928396`, etiquetada); `master` → `4c928396`. **Pendiente del PO: revisar
los tres buzones de Mailinator** (A-046). **Sin empujar**: `dev`, `master`, `last-stable` y las etiquetas `alpha.1` a
`alpha.4`.

**Después del lote 9:** los lotes 9, 10 y 11. El lote 8 espera P-a..P-d.

## Orden del tramo

1. `#102`, la limpieza.
2. **El aviso de `app_key` con `nag`**: descartable, que recuerde el descarte y apagable, flotando
   sobre el contenido sin romper el diseño (`pendientes.md`, «Encargos y correcciones del PO tras
   cerrar el tirón», punto 1).
3. **`4d`**: lo ya guardado mal se da por perdido; lo que se guarde a partir de ahora no falla nunca.
4. `4e`, `4f` y `4b-4`.
5. Lotes 7, 7c, 8, 9, 10 y 11. **El lote 7 toca unos 168 archivos: la regla de los diez obliga a
   enseñar el plan al PO antes de commitear.** Mientras espera, se sigue con lo que no dependa de él.
6. **PARAR antes del 12.**

**La guía personal del PO, alcance fijado por él (2026-09-16):** desde el 19-08-2026 **hasta el
presente, siempre**; de este repositorio y de los paquetes `piecesphp/*`, en lo relevante. Tema
`readthedocs` (elegido por él). Obligación continua: **al cerrar cada lote, el arquitecto actualiza
la guía** con lo que afecte al PO, y la commitea en su repositorio (ADR 0016).

En paralelo, sin ocupar al coder: la guía personal del PO (ADR 0016). Hay que elegir el tema,
cubrir desde el 19 de agosto de 2026, y que le sirva sin IA.

## Autorización de commits del PO (ADR 0005)

**Vigente desde el 2026-09-14.** El coder prepara y commitea, **en commits atómicos**, el trabajo
que el PO nombra y el arquitecto instruye, sin pedir permiso commit a commit.

**Reservado al PO:**

- `git push` y todo lo que toca un remoto;
- publicar una versión MAYOR estable de `piecesphp` (hoy, `v8.0.0`); las pre-versiones, sus etiquetas y el
  avance de `master` y `last-stable` son de arquitecto y coder (ADR 0019);
- crear ramas, salvo `dev` en los paquetes;
- reescribir historia;
- escribir o destruir datos de una base de datos, salvo lo que nombre la instrucción con su
  autorización;
- dependencias, builds, servidores y credenciales. Excepciones: las herramientas de análisis
  (ADR 0007) y `piecesphp/*` dentro de los paquetes hermanos (ADR 0008).

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:** el mapa, `../docs/roadmap.md`, en su orden. Orden del PO:
«Trabaja. Adelante.» (2026-09-14) y «Trabajen» (2026-09-15).

## Espera al PO

Lista única de lo que solo el PO puede decidir o hacer. **Solo lo abierto**: lo decidido sale a `../docs/pendientes.md`.
Cada punto dice qué se pregunta, por qué importa y qué hace el arquitecto si no hay respuesta. El número no cambia
mientras siga abierto. Para contestar basta el número: «P37 a», «S2 no». Espejo: la guía personal, «Lo que espera de ti».

### Acciones tuyas

- **Confirmar en la sesión del coder (PiecesPHPUpgrade-Coder-Main)** que quieres tus dos claves de reCAPTCHA de prueba
  versionadas en `src/app/config/config.php` (ADR 0021). Por ejemplo: «Confirmo: versiona mis claves de reCAPTCHA de
  prueba en config.php, como dice el ADR 0021». Mientras tanto las claves ya salieron del código y, **sin ellas, el
  formulario de contacto de tu instalación local rechaza los envíos**. Ya no frena otras rondas.
- **Empujar.** Todo está en local.
  - Framework: `dev`, `master`, `last-stable` y las etiquetas `v8.0.0-alpha.1` a `v8.0.0-alpha.4`.
  - Paquetes `database`, `datastructures`, `geojson` y `html`: `master` y `dev` de cada uno.
- **Mirar los tres buzones de Mailinator** (`zz-prueba-recuperacion-55e5ee`, `zz-prueba-codigo-55e5ee`,
  `zz-prueba-problemas-55e5ee`, @mailinator.com). Si ya no están, no pasa nada.

### Preguntas abiertas

- **P37 · Alta pública por API con organización nueva.** Hoy falla siempre. Arreglarla abre una vía pública para crear
  una organización y quedar como su administrador. (a) arreglarla, (b) retirar esa vía, (c) dejarla cerrada como está.
  *Predeterminado:* (c), documentado.
- **P40 · `piecesphp/html` no escapa nada.** El framework lo usa en los menús con textos traducidos y URL de rutas
  (riesgo que se cree bajo, sin medir). *Predeterminado:* revisión con el auditor de seguridad y propuesta antes de
  tocar.
- **P41 · `piecesphp/geojson` saca `[latitud, longitud]` por defecto**, que no es el estándar. Cambiarlo rompe a quien
  lo use. *Predeterminado:* no se toca; documentado en su README.

### Propuestas que prepara el arquitecto (no tienes que hacer nada aún)

- **P38 · El desplegable global de Aprobaciones** (posible fuga de etiquetas entre organizaciones): propuesta pedida
  en A-049.
- **P39 · `organization = -10` en el login:** medir si `TYPES_USER_DONT_REQUIRE_ORGANIZATION` cubre todos los casos.
- **P36 · Los seis controladores al estándar**, con `roles.php` y el veto a `get_route()` directo: el plan se te enseña
  antes de commitear (lote 11, regla de los diez).

### Sin prisa (tienen predeterminado y no frenan nada)

- **S1 · Alcance del ADR 0017** (el framework actualiza sus paquetes con Composer). *Predeterminado:* toda la campaña.
- **S2 · Detectar por máquina cuándo se compacta una sesión.** *Predeterminado:* cuando lo nombres.
- **S3 · El SQL y las filas crudas de los listados viajan al navegador** (`DataTablesHelper`, núcleo). *Predeterminado:*
  aparcado.
- **S4 · «Perfeccionar geovisor».** *Predeterminado:* espera a que digas qué.
- **S5 · Después de la MAJOR:** la aprobación encendible por módulo, la rutina de instalación y la vista «Sistema».
- **S6 · P35, la ruta `external` de la API:** documentada como extensión apagada. Solo si prefieres retirarla.

## Para una sesión nueva

1. Este archivo.
2. El tramo en curso.
3. `.agents/README.md`.
4. `../docs/roadmap.md` y el bloque del 2026-09-16 de `../docs/pendientes.md`.
