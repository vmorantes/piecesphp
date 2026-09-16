# Ahora

- **Actualizado:** 2026-09-16 09:20 (medido con `date`).
- **Mandato vigente del PO (A-031):** trabajar sin parar hasta cerrar los lotes 7 a 11, con todo lo
  que va antes en el mapa, y **parar antes del 12**. Detalle en `../docs/pendientes.md`, bloque del
  2026-09-16.
- **Tramo en curso:** [`tramos/2026-09-16-0908-lotes-4d-a-11.md`](tramos/2026-09-16-0908-lotes-4d-a-11.md).
- **Último mensaje:** `#110 · ARQ`. Próximo: `#111`. **Último al PO:** A-033.
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
  Las dos se reabrieron el 2026-09-16, así que cuentan como compactadas.
- **Rama:** `dev`. El hash de HEAD no se escribe aquí, porque se pudre entre rondas: se mira con
  `git --no-optional-locks log --oneline -1`.
- **Este archivo se rehízo corto el 2026-09-16.** Tenía 567 líneas: era historia, no estado. La
  historia está en git, en `tramos/2026-09-15-1022-lote-4-y-estudio-4b.md` y en `pendientes.md`.

## En curso

`#104`: **el aviso de `app_key` con `nag`.**
- `ui bottom fixed nag`, descartable (cookie, 7 días) y ocultable con `hide_app_key_warning` en
  «Seguridad e IA».
- Prueba de punta a punta por HTTP (ADR 0010) y captura con Chrome sin interfaz, que el arquitecto
  mira antes de darlo por bueno.
- Antes, la provocación que faltó en `#102`: `GUIA_PO` fuera de `ESCRIBIBLES_EXTRA`.

`#105 · COD`: **bloqueado en el PASO 5** de `#104`, y paró bien. No había credencial de root
utilizable: la de `zz-prueba-root` (id 485) se borró en `#091`. Restablecerla era una escritura que
`#104` §3 prohibía, con una redacción más estrecha que el ADR 0010. El código de los pasos 2 a 4
está escrito y verificado en estático (735, integridad limpia, gates 33/0); la provocación del
PASO 1 mordió (207/209, suma restaurada). `#106 · ARQ`: opción A, con la acción temporal
condicionada al prefijo `zz-prueba-` y el 5.2 en la forma segura del coder.
`#107 · COD`: **INCIDENTE en la base local, medido y acotado.** El POST del 5.2 con solo los
booleanos escribió cinco filas vacías (`modelOpenAI`, `modelMistral`, `OpenAIApiKey`,
`MistralAIApiKey` y `translationAI`) más la de `hide_app_key_warning`. Ninguna de las 25 filas
existentes cambió: lo midió comparando los volcados de las 09:26 y las 09:34, fila a fila, sin
imprimir valores. La causa: el hallazgo 10.1 de `#105` era falso (`Parameters::validate()` da su
valor por defecto a un opcional ausente), y **el arquitecto lo dictó sin verificarlo**. El
discriminante del mensaje de éxito lo cazó. `#108 · ARQ`: R2, que borra solo esas seis filas y
compara los volcados, y el cierre del aviso con GET, sin volver a usar el formulario.
- **Hallazgos de `#105`, por registrar en `pendientes.md` al cerrar la ronda:**
  - **10.2, ampliado en `#107`:** un POST parcial a un formulario de `AppConfigController` escribe
    `false` en los booleanos ausentes y `''` en los textos ausentes, y crea filas donde no las había.
    El panel no lo dispara, porque su JS manda todo; cualquier otra vía, sí. Es corrección: lote
    10, con el censo de los demás formularios;
  - **10.4:** el formulario pinta las API keys en claro en `value`, solo para root. Es preexistente.

Cerrado: `#102`/`#103`, la limpieza (`92ee7ef0`, `25c93d27`, `e4ee12e7`). Cuatro provocaciones de
la guarda, cada una con exactamente sus FALLO; 209/209.

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
- etiquetar `piecesphp` y tocar su `master` o su `last-stable`;
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

0. **✔ `4d`, resuelto por el PO (respuesta a A-032 y A-033, 2026-09-16).** Formalizado: la campaña
   es de ruptura y no hace falta decirlo en cada paso; lo que importa es que el futuro sea
   perfecto, y el pasado dañado no se puede arreglar entero. «Haz lo que debas con 4d».
   - **P-a: sí.** El framework pasa a `piecesphp/database ^5.0` y se actualiza con Composer.
   - **P-b: decide el arquitecto: entra** la tarea `bin/cli` que deshace una vez el escape de lo ya
     guardado, que por defecto solo cuenta.
   - **Criterio nuevo del PO, para la regla 30:** no se le consulta ni se le avisa de cada ruptura.
     Las rupturas se documentan en el `CHANGELOG` y ya. Sigue siendo punto serio lo demás de la
     lista (publicar `piecesphp`, remotos, lo irreversible sin copia).
   - **Encargo nuevo:** la guía personal incluye los paquetes `piecesphp/*` donde sean relevantes.
     Se pasa al agente del dossier y a `pendientes.md` al cerrar `#104`.
1. **Detectar las compactaciones por máquina**, leyendo el `.jsonl` desde `verificar.sh`.
   *Predeterminado:* se hace cuando lo nombre.
2. **El SQL y las filas crudas de los listados viajan al navegador** (H1 de `#050`, en
   `DataTablesHelper`, que es núcleo transversal). *Predeterminado:* aparcado hasta que lo nombre.
3. **La recuperación de contraseña envía una contraseña nueva en claro** por correo
   (`RecoveryPasswordController::mailNewPassword()`). *Predeterminado:* se mantiene y se documenta.
   El arquitecto recomienda sustituirla por el enlace o el código de recuperación, que ya existen.
4. **«Perfeccionar geovisor»**: ya se sabe cuál es, pero no qué quiere perfeccionar.
   *Predeterminado:* espera a que lo diga.
5. **Después de la MAJOR**, salvo que diga lo contrario: la aprobación encendible por módulo, la
   rutina de instalación y la vista «Sistema».
6. **Subir cuando quiera** los commits sin empujar de `dev`.

## Para una sesión nueva

1. Este archivo.
2. El tramo en curso.
3. `.agents/README.md`.
4. `../docs/roadmap.md` y el bloque del 2026-09-16 de `../docs/pendientes.md`.
