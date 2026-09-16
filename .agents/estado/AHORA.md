# Ahora

- **Actualizado:** 2026-09-16 13:12 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-16-0908-lotes-4d-a-11.md`](tramos/2026-09-16-0908-lotes-4d-a-11.md).
- **Último mensaje:** `#175 · ARQ`. Próximo: `#176`. **Último al PO:** A-046.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron el 2026-09-16, así que cuentan como compactadas.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

**`#175`: `v8.0.0-alpha.4`** (ADR 0019), al cerrar `7c`.

Cerrado: **`7c` entero** (`#126`→`#174`). B3: tres correos reales a Mailinator, los tres `true` (`2b33cdaf`, `24f726a1`).
**Pendiente del PO: revisar los buzones** `zz-prueba-recuperacion-55e5ee`, `zz-prueba-codigo-55e5ee` y
`zz-prueba-problemas-55e5ee` @mailinator.com (A-046). **Sin empujar**: `dev`, `master`, `last-stable` y las etiquetas
`alpha.1` a `alpha.3`.

**Después de `#175`:** los lotes 9, 10 y 11. El lote 8 espera P-a..P-d.

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

**Decidido por el PO el 2026-09-16 (A-038 y A-039)**, pasado a `pendientes.md` (punto 22):
- **P30:** «soluciona lo que debas sin perder función». En diseño (`#143`).
- **P31:** quiere retirar su clave y poner la de pruebas de Google. **Google solo publica claves de prueba para v2**
  (FAQ oficial, medido con WebFetch); para v3 pide crear una clave aparte. Se le propuso (a) claves a la
  configuración, vacías, y sin claves el formulario rechaza y registra; o (b) pasar el módulo a v2. Ver P31 abajo.
- **P32:** la documentación de los paquetes, **en español** (su lengua y la de su entorno), en el lote 9.
- **Push de los cuatro paquetes: hecho.** En los cuatro, `origin/master` = `master` (medido en local, sin tocar el
  remoto). Las etiquetas no se pueden comprobar sin consultarlo. Desbloquea `4d` parte B.

Preguntas abiertas:
1. **P31 · reCAPTCHA v3 sin claves de prueba.** El PO preguntó de qué cuenta es la clave para rotarla (A-042): no se
   deduce de la clave. La introdujo el commit `393d29ef` («ReCaptcha v3», 2021-11-15, autor con su correo personal);
   se le indicó buscar la clave pública (`src/statics/js/contact-form.js:16`) en la consola de cada cuenta. *Predeterminado:* (a), las claves a la configuración, vacías; sin
   claves, el formulario de contacto rechaza y deja una línea en el log. Recomendado además: borrar o regenerar la
   clave en `google.com/recaptcha/admin` o en `console.cloud.google.com/security/recaptcha`, porque sigue en el
   historial de git. La clave pública, para encontrarla, está en `src/statics/js/contact-form.js:16`.
2. **P33 · `generate_code()` usa `rand()`** (`src/app/core/Utilities.php:455`) y genera los códigos del 2FA, de la
   recuperación, de problemas de usuario y de los tokens (6 llamadas). Pasarlo a `random_int()`, mismo formato. Es
   un helper compartido: **no se instruye hasta que conteste**. *Predeterminado propuesto:* sí.
3. ~~P34~~ **Decidida (A-039 y A-040): ADR 0019.** Arquitecto y coder versionan y etiquetan, salvo la MAYOR estable;
   `last-stable` siempre en una estable. Primera: `v8.0.0-alpha.1`, en `#143`, con `last-stable` → `v7.1.0`.
4. **Lote 8, P-a a P-d** (`propuesta-2026-09-16-lote-8.md`, A-036).
5. **Alcance del ADR 0017** (`#115`). *Predeterminado:* toda la campaña.
6. **Detectar las compactaciones por máquina.** *Predeterminado:* cuando lo nombre.
7. **El SQL y las filas crudas de los listados viajan al navegador** (`DataTablesHelper`, núcleo). *Predeterminado:*
   aparcado.
8. **«Perfeccionar geovisor».** *Predeterminado:* espera a que diga qué.
9. **Después de la MAJOR:** la aprobación encendible por módulo, la rutina de instalación y la vista «Sistema».
10. **Subir cuando quiera** los commits sin empujar de `dev`.

## Para una sesión nueva

1. Este archivo.
2. El tramo en curso.
3. `.agents/README.md`.
4. `../docs/roadmap.md` y el bloque del 2026-09-16 de `../docs/pendientes.md`.
