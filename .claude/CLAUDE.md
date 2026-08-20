# Claude Code — instrucciones del proyecto

Las reglas núcleo y el contrato de memoria viven en `.agents/rules/` (compartidas con Antigravity vía symlink en `.claude/rules/`). Este archivo es solo para lo que es específico de Claude Code.

## Reglas con scope por ruta

Reglas específicas de un módulo o carpeta van en `.claude/rules/*.md` con frontmatter `paths:` para que solo carguen cuando trabajas en esos archivos — no las mezcles en las reglas núcleo compartidas.
