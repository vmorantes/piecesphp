# Prosa comprimida

El PO pidió ahorrar palabras siempre que no dañe la fiabilidad. Regla permanente, activa en toda
sesión. Refuerza lo que ya decía el 20 §2: el PO lee en diagonal, y lo que no está en la prosa
del arquitecto no lo sabe.

## Dónde se comprime

Respuestas al PO en el chat, avisos de estado, mensajes de logística entre sesiones, prosa de
los reportes del coder, `.agents/estado/AHORA.md`.

Cómo: sin relleno, sin cortesías, sin rodeos («básicamente», «simplemente», «en realidad»), sin
narrar lo que se va a hacer con las herramientas, sin tablas ni emojis decorativos. Frases
cortas; fragmentos permitidos. Términos técnicos, rutas, comandos y errores **exactos**. Nunca
abreviaturas inventadas. Patrón: `[cosa] [acción] [motivo]. [siguiente paso].`

No: «¡Claro! El problema que estás viendo probablemente se deba a que…»
Sí: «Falla `routeName()`: sin usuario concede. Arreglo propuesto en P17.»

## Dónde NUNCA se comprime

- **Instrucciones al coder**: son su entrada completa y pueden llegar a otro proveedor.
- **Salidas pegadas**: literales.
- **Documentación** (`.agents/context/`, ADR, bitácora, `CHANGELOG.md`, `source-docs/`).
- **Clasificaciones** CONFIRMADO / SOSPECHA / SIN VERIFICAR: comprimir es justo lo que borra un
  «no lo pude verificar».
- **Avisos de seguridad y confirmaciones de acciones irreversibles.**
- **Secuencias de pasos** donde quitar conectores vuelve ambiguo el orden.
- **Preguntas al PO**: cada una lleva su contexto y su predeterminado (30, «Preguntas al PO»).
- Cuando el PO pide aclarar o repite la pregunta.

Tras la parte clara, se vuelve a comprimir.
