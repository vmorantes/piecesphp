# .agents

Notas de trabajo para agentes de IA que operan sobre este repositorio. **No es la
documentación del framework.**

| Directorio | Contenido |
| :-- | :-- |
| `context/` | Contexto técnico del proyecto: arquitectura, convenciones, recetas y planes de trabajo. Ver [`context/README.md`](./context/README.md) |
| `skills/` | Habilidades invocables durante el desarrollo |
| `agents/`, `personas/` | Perfiles de agente para tareas concretas |

## Relación con `source-docs/`

Son dos públicos distintos y ambos se mantienen:

- **`source-docs/`** — documentación para personas. Se publica como sitio MkDocs.
  Explica cómo desplegar, cómo usar el framework y cómo funcionan sus subsistemas.
- **`.agents/context/`** — contexto para agentes. Más denso, con rutas de archivo,
  números de línea, inventarios y decisiones de diseño que a una persona le sobran pero
  a un agente le ahorran una exploración completa del repositorio.

Se solapan a propósito. La regla que los mantiene sanos es una sola:

> **Ninguno de los dos puede mentir.** Si un cambio de código invalida un documento de
> cualquiera de los dos, se corrige en el mismo commit. Si se contradicen entre sí, gana
> el código y se arreglan los dos.

Los documentos de `context/` que describen planes de trabajo llevan su estado en la
cabecera; cuando el trabajo se completa, el documento se marca como ejecutado en vez de
borrarse: el registro de por qué se tomó una decisión vale más que el plan.
