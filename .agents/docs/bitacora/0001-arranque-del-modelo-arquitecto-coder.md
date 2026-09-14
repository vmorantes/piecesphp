# 0001 — Arranque del modelo arquitecto-coder y herencia de la campaña

- **Fecha:** 2026-09-14
- **Pedido por:** Product Owner
- **ADR relacionados:** 0001, 0002, 0003, 0004
- **Bloque:** ninguno (preparación). **Mensajes:** `#001` (saludo al coder)

## Qué se pidió

El PO pidió dos cosas:

1. **Montar el modelo arquitecto-coder** con la skill que él creó. Tenía que tomar lo mejor de un
   andamiaje copiado de otro proyecto (CustomPluginsHestiaCP) y adaptarlo, sin commits en el
   arranque, y decidir si hacía falta un coder nuevo.
2. **Heredar al arquitecto de la campaña** con su conversación entera, un archivo que no debía
   sobrevivir a su uso.

A mitad del trabajo añadió cuatro encargos más: borrar lo que no aplicase, recibir el reporte
pendiente del coder, dejar claro lo pedido, lo hecho y lo que queda, y proponer un orden de
directorios.

## Qué se encontró al explorar

**Lo copiado describía el proyecto de origen, no este.**
- Su regla de salvaguardas y su guarda **permitían `git push`**, por una excepción de aquel
  repositorio. Aquí la regla es la contraria (20 §3).
- Su filtro de «menciones a IA» perseguía el vocabulario. Aquí da **120 falsos positivos**:
  PiecesPHP tiene adaptadores de OpenAI y Groq, un plugin de Gemini y textos de interfaz sobre IA.
  La historia, en cambio, no tiene ni una atribución.

**El traspaso a medias del arquitecto saliente.**
- **El mapa de lotes hasta la MAJOR**, que dio en el chat el 2026-09-13, no estaba en ningún
  archivo. `PENDIENTES.md` remitía a §7 del 20, que era trece días más viejo.
- **El documento de aterrizaje** que prometió para su sucesor no llegó a escribirse.

**El coder escribía documentación.** En el modelo viejo le correspondían las entradas T y el
CHANGELOG. Así se perdieron cinco bloques de rupturas.

**El registro se contradice y, en algunos puntos, miente.**
- `14-deuda-y-limpieza.md` mandaba borrar `Importers`, que el PO decidió conservar.
- T60 atribuía al PO un error de diseño del arquitecto.
- `PENDIENTES.md` daba por pendientes cosas ya delegadas o ejecutadas.

**Un secreto versionado.** El 18 tiene en claro la contraseña de prueba del usuario root local y
su hash.

**Alguien tocó el árbol durante la lectura.** A las 11:07 se vaciaron tres skills copiadas y
desapareció la `HERENCIA.md` del otro proyecto. No lo ordenó el arquitecto.

**El cruce de los 466 turnos del PO contra el registro.**
- De 353 ítems, 251 estaban (71 %), 50 a medias y 43 no.
- Lo que se perdía eran **preferencias de trabajo** y **encargos a futuro**. Las decisiones
  técnicas casi siempre llegaban.
- Dos hallazgos cambiaron reglas:
  - el PO **delegó la instrumentación de análisis** en el arquitecto (2026-09-02), de modo que BD
    no espera al PO;
  - dijo que **los paquetes se versionan con soltura** (2026-08-27), lo que choca con la lista
    de puntos serios.

## Qué se instruyó

Nada de código. `#001` es el saludo: el coder confirma quién es, lee las reglas del disco y
entrega su reporte de BC sin tocar nada.

## Qué reportó el coder

Pendiente. Se registrará en la entrada de BC.

## Qué quedó fuera

- Las correcciones del registro que no eran peligrosas: la tabla de `PENDIENTES.md`. Irán en una
  ronda de documentación.
- Adaptar la skill `full-stack-php-senior` a lo que pidió el PO.
- Volver a verificar el campo `effort` en el frontmatter de los subagentes.
- La reorganización de directorios: es una propuesta, en el tramo del 2026-09-14.
- Los informes brutos del cruce: no se versionan, y mueren con la sesión, como el transcrito.

## Aprendido

- **Un andamiaje copiado trae decisiones ajenas.** El `git push` permitido es el ejemplo. Cada
  regla heredada se lee como si mintiera, hasta comprobarla contra este repositorio.
- **Una guarda de atribución no es una guarda de vocabulario** cuando el producto trabaja con IA.
- **Del PO se pierde más su forma de trabajar que sus decisiones.** Por eso la regla 30 lleva
  ahora «El PO, en sus palabras», con fecha en cada punto.
