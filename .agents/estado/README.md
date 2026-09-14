# Estado

Qué está pasando en el proyecto, escrito para el Product Owner y para cualquier sesión que
retome el trabajo. **Volátil pero versionado**: se reescribe y se poda; git guarda lo anterior.

| Archivo | Qué es |
| --- | --- |
| [`AHORA.md`](AHORA.md) | Qué está en curso, qué espera tu decisión, qué sigue, qué número de mensaje toca. Siempre al día |
| [`tramos/`](tramos/) | Un archivo por tanda de trabajo continua: rondas, commits, hallazgos, fallos, duración |

Lo escribe solo el arquitecto (regla `.agents/rules/60-estado.md`, decisión en
`.agents/docs/adr/0002`). Se conservan los 10 tramos más recientes.
