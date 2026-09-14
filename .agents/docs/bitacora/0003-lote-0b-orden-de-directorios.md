# 0003 — Lote 0b: una razón de ser por carpeta

- **Fecha:** 2026-09-14
- **Pedido por:** Product Owner (P21: aceptó la propuesta de carpetas y quitar los builds
  innecesarios, «antes de trabajar» en el framework)
- **ADR relacionados:** 0006. Reemplaza en parte el 0002.
- **Bloque:** 0b. **Mensajes:** `#008`–`#009`
- **Commits:** `3a5fa084` `b5113c89` `a0d28c6e` `0b527b62` `7cc9867b` `b58d3eb0` `27cef6e7`
  `45d1a84f`

## Qué se pidió

El PO, sobre la estructura del repositorio: *«siento que ese `files/*` y demás se está
enredando»*. Aceptó la propuesta del tramo del 2026-09-14 y quitar los builds que sobran.

## Qué se encontró al explorar

Lo midió un subagente en solo lectura, sobre 1.464 archivos de texto.

**El censo cambió dos puntos de la propuesta del propio arquitecto:**
- **Los `PHPStanResult.*` se quedan en la raíz.** Moverlos rompía 14 líneas en cuatro
  instrumentos, y los cinco repositorios comparten esa disposición a través del instrumental
  común. La comprobación 7 no vería la divergencia.
- **`tests.md` va a `.agents/context/` y no a `source-docs/`.** En `source-docs/` pondría rojo
  `bin/censo-rutas-doc`, con 10 rutas «rotas».

**Mover `PENDIENTES.md` y el roadmap posterior contradecía el ADR 0002**, escrito ese mismo día.
Por eso el ADR 0006 lo reemplaza en parte, en lugar de editarlo.

**`files/API/docs-dist/`** eran 7,8 MB versionados que nadie leía, desactualizados (4 de 7
módulos). Además, la tarea que los genera fallaba sin avisar (`done()` sin mirar el error).

**Solo sobraba ese build.** Los otros JS compilados que se versionan los necesita en ejecución
quien clone sin compilar, y de `FormJsonSchema.js` no hay fuentes.

## Qué se instruyó

`#008`, ocho commits en este orden:

1. renombrado puro, más las dos rutas de código que dependían de él;
2. el build fuera, y su línea en `.gitignore`;
3. el arreglo de `api-build`;
4. el ADR 0006;
5. las rutas nuevas en la documentación viva;
6. la regla 30;
7. la bitácora;
8. el estado.

Los registros históricos conservan la ruta de su fecha, y la tabla del ADR hace de puente.

## Qué reportó el coder

**`#009`: completado, sin desviaciones de fondo.**

- 28 renombrados al 100 % más dos cambios de una línea (`src/gulpfile.js` y dos docblocks de
  `VerifyIntegrityTask.php`, sin desplazar líneas).
- 50 archivos de build borrados.
- Árbol limpio. `verify-integrity` en verde; `censo-rutas-doc` sin rutas rotas (17
  comprobables); `ANDAMIAJE OK`.
- `bin/phpstan` y `gates` no se corrieron, con su motivo demostrado: dos docblocks, cero líneas
  netas.
- El arquitecto comprobó en la historia los renombrados, el arreglo de gulp y que `files/API/`
  ya no existe.

**Hallazgos del coder:**
- **H1.** El universo de la guarda de atribución baja de 1.470 a 1.402 archivos. Cuadra con lo
  que sale de `files/`. El ADR 0006 no nombra que `tests.md` también queda fuera de la guarda,
  aunque sí lo dice de `pendientes.md` y del roadmap posterior. Queda anotado aquí: el ADR no se
  edita.
- **H2.** Siguen los archivos de agentes en LF con `crlf` declarado. Es cosmético, igual que en
  la bitácora 0001.
- **H3.** El único archivo que no es JSON en `files/dev/` es `snapshots/.gitignore`, previsto
  por el ADR.

## Qué quedó fuera

Anotado en `pendientes.md`, «Hallazgos del lote 0b»:

- **`TODO.md`** sigue en la raíz: es una lista del PO.
- **`files/TraduccionesPublicas.json`** vale `{}` y no lo nombra nadie: huérfano en
  apariencia, sin verificar.
- **`bin/Preview/`** no limpia las copias sin extensión.
- **`permissions-and-property.sh:77`** busca `bin/node/copyDependencies.sh` después de hacer
  `cd` a `bin/`, así que nunca lo encuentra.
- **Conviven los lockfiles de npm y de pnpm.**
- **`CorregirTiempoDuraciónWebm.php`** podría duplicar `FixWebmDurationTask`.
- **`21-pruebas-y-puertas.md`** está desfasado: dice «dieciséis» comprobaciones y hay 26.

## Aprendido

- **Una propuesta aceptada no exime de medir.** El censo cambió dos puntos y el ADR dice por
  qué; el PO lo sabe antes de que se ejecute (LEY 17).
- **Un ADR del mismo día se reemplaza igual que uno viejo**, con `Reemplaza:`. Dónde vive cada
  cosa es exactamente lo que un agente sin memoria «corrige» si no queda escrito.
- **Un `git mv` sobre un archivo con cambios sin preparar** deja en el índice el contenido
  original. Así el renombrado queda puro, y los cambios del arquitecto van en su propio commit.
