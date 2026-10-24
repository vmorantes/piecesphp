# Estado

Qué está pasando en el proyecto, escrito para el Product Owner y para cualquier sesión que
retome el trabajo. **Volátil pero versionado**: se reescribe y se poda; git guarda lo anterior.

| Archivo | Qué es |
| --- | --- |
| [`AHORA.md`](AHORA.md) | Qué está en curso, qué espera tu decisión, qué sigue, qué número de mensaje toca. Siempre al día |
| [`tramos/`](tramos/) | Un archivo por tanda de trabajo continua: rondas, commits, hallazgos, fallos, duración |
| [`PO.md`](PO.md) | Lo que espera al PO: preguntas abiertas, sus acciones, lo «sin prisa» y el buzón de avisos |
| `informe-*.md` | Informes cerrados de una versión o de un estado (históricos: no son trabajo pendiente) |
| `propuesta-*.md` | Propuestas al PO ya resueltas, del 2026-09-16 al 18 (históricas; G20 pide su regla de poda) |

Lo escribe solo el arquitecto (regla `.agents/rules/60-estado.md`, decisión en
`.agents/docs/adr/0002`). Se conservan los 10 tramos más recientes.
