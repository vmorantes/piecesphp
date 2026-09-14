# Dos ideas sobre cronjobs y colas

Registradas, **no ejecutadas**. Van aquí, en `files/dev/roadmap/`, y no en
`18-siguientes-ventanas.md`: ese documento es el registro de lo que se ha hecho y medido, y esto
es lo contrario — trabajo que todavía no existe.

---

## IDEA 1 · Que los cronjobs se encarguen también de las colas

**Para no obligar a varias entradas de crontab por proyecto.**

Viable hoy **sin construir nada**: basta con registrar el drenaje de la cola como un
`CronJobTask` más.

### A favor

- Una sola entrada de crontab por despliegue.
- **Ya está resuelto en casa lo que suele romper esto**: `ProcessQueueTask` (líneas 75-98) tiene
  directorio de cerrojos, orden FIFO y tope de procesos, así que dos invocaciones no se pisan.
  Sirve de modelo para lo que le falta a `CronJobTask`.

### En contra, y va escrito

- **Una sola entrada es también un solo punto de fallo.** Hoy, si `run-cronjobs` muere, las colas
  siguen drenando por su cuenta. Unificados, no.
- **El drenaje es el palo largo.** Con `--limit 60` y trabajos de duración desconocida, el tic
  puede pasarse del minuto. El cerrojo evita que se apilen, pero **no** evita que una tarea con
  `dailyAt("03:00")` pierda su minuto exacto — y hoy perder el minuto es silencio, no error
  (ver la observación 4.4 en `18-siguientes-ventanas.md`).

---

## IDEA 2 · Volver vía operativa el endpoint HTTP de cronjobs

**Lanzable desde el GUI para tareas críticas.**

### Requisito previo

Los cuatro puntos de la sección de observaciones sobre el endpoint (T63 en
`18-siguientes-ventanas.md`). En particular **4.3 — no hay cerrojo ni libro de ejecución**, que
es **LA MISMA PIEZA** que le falta a la IDEA 1. Arreglada una vez, sirve a las dos.

### Y una condición de forma

Si se lanza desde el panel, **se invoca la clase de la tarea del lado del servidor con la sesión
normal**. Nunca enlazando el endpoint con la llave en la URL: hoy la llave se acepta también por
parámetro GET, y un enlace en el panel convertiría un riesgo latente en uno real.
