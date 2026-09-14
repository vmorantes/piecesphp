# IDEA · Mejorar el CLI y sus ayudas (`--help`)

**Intención declarada por el PROPIETARIO — 2026-08-24. SIN RESOLVER.**

## La regla, que es la misma que gobierna la vista «Sistema»

**Un `--help` escrito a mano se pudre. Uno derivado de lo que la tarea declara —parámetros,
valores por defecto— se mantiene solo.**

Hoy cada `TerminalTaskAbstract` ya declara su `$description` como un `StringArray` con los
parámetros escritos **a mano dentro del texto**:

```php
$this->description = new StringArray([
    "EMITE el SQL de creación...\r\n",
    "\tParámetros:\r\n",
    "\t  module=<Nombre>  módulo bajo src/app/classes, o `all`. Obligatorio\r\n",
]);
```

Eso es exactamente el patrón que falla: **el parámetro está declarado en la prosa, no en una
estructura**, así que nada comprueba que la ayuda corresponda a lo que la tarea lee con
`getArgument()`.

## Lo que habría que poder derivar

- Los parámetros que la tarea **realmente** lee.
- Su valor por defecto **real**, el que está en el segundo argumento de `getArgument()`.
- Cuáles son obligatorios.
- Y una comprobación que falle cuando la ayuda y el código no coincidan — del mismo tipo que la
  comprobación 11 de `verify-integrity`.
