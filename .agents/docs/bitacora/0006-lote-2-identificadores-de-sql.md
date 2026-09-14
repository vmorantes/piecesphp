# 0006 — Lote 2: identificadores de SQL, y el cierre de BD

- **Fecha:** 2026-09-14
- **Pedido por:** Product Owner, «Trabaja. Adelante.» (el mapa, lote 2). El cierre de BD, por
  delegación: «Resuelve P22 y P23 como tu prefieras».
- **ADR relacionados:** 0008 (sincronizar el entorno local de los paquetes hermanos)
- **Bloques:** BE, BF y BG. **Mensajes:** `#020`–`#025`
- **Commits en piecesphp:**
  - BE: `84975bf4` `340214ef` `aadd2256` `1184f229`;
  - BF: `10cce958` `a72afb0f` `f1039081` `19204809`;
  - BG: `329e30cb` `c82f9277` `2fd458b2` `4cd0639c` `146e6915` `80ef9ab8` `2a1a6755`.
- **En html:** `8d61814`.

## Qué se pidió

El mapa planeaba el lote 2 como una lista blanca de identificadores para `prepare`, `select`,
`setTable` y `custom_order`. Lo último grande de SQL, en 2-3 bloques.

## Qué se encontró al explorar

- **Una lista blanca por forma rompería usos legítimos.** `select()`, `get()`, `rowCount()` y
  `setTable()` interpolan por contrato y admiten expresiones (`COUNT(*) AS total`). Viven en el
  paquete database.
- **Una lista blanca sacada de esta instalación es falsa para los clones** (20 §7, `region`).
- **Nadie miraba esas posiciones.** Por la regla heredada del bloque AN (primero el instrumento,
  luego la cifra, luego el arreglo), el lote empezó por un censo.
- **Un defecto visible:** la dirección de `custom_order` entraba en el `ORDER BY` sin normalizar,
  mientras la de la petición sí se normalizaba.

## Qué se instruyó y qué reportó el coder

- **BE (`#020`→`#021`): `bin/censo-sql-identificadores`.**
  - Resultado: 0 CONFIRMADO, 8 REVISAR y 102 DESCARTADO.
  - Canario de 27 caras, visto caer al inutilizar una familia.
  - Mira el argumento de `select`, `setTable` y `rowCount`, los dos últimos de `get`, la tabla
    de `join` y sus variantes, y las claves `select_fields`, `columns_order` y `custom_order`.
  - **Ningún identificador llega de la petición.** El lote no cruza al paquete database.
- **BF (`#022`→`#023`): la dirección de `custom_order`** se normaliza a ASC o DESC en
  `generateOrderBy()`, con prueba de rechazo: 58/58 con el arreglo y 56/58 sin él.
  Los 15 controladores ya pasaban ASC o DESC, así que no cambia nada de lo que funciona. En la
  misma ronda, el ADR 0008 y la nivelación de html, que se detuvo porque su cifra bajó de 3 a 1.
- **BG (`#024`→`#025`):**
  - **html se registra como «2 murieron»**, con la causa SIN VERIFICAR: cambiaron a la vez el
    analizador y datastructures 3.1.0 → 4.0.0. BD queda cerrado.
  - **`metodos()` deja de contar dos veces** lo que hay dentro de una función anónima. Se vio
    caer su cara de canario antes del arreglo. Cifras: concatenado de 248 a 247 llamadas
    (REVISAR 105 → 104); interpolado, A de 223 a 213; identificadores, de 138 a 136
    posiciones.
  - **Trinquetes nuevos**, cada uno visto fallar al provocarlo:
    - comprobación 27: ningún identificador viene de la petición;
    - comprobación 28: las seis C de `processFromQuery()` quedan declaradas con un `count`
      exacto, y lo que no esté declarado no crece.

## Qué quedó fuera

- **La subcadena en la guarda de duplicados de `generateOrderBy()`**: un orden por defecto que se
  pierde sin error. Candidata al lote 10.
- **`bin/cli unit-tests:<suite>` sale con 0 aunque la suite falle.** `gates` no se engaña.
- **El resto del procesador de PHPStan de los paquetes**, igualado solo en el trinquete.
- **Los documentos que cuentan las comprobaciones de `verify-integrity`**: son 28.

## Aprendido

- **Medir antes de construir cambió el lote.** De «lista blanca en el ORM, cruzando al paquete»
  se pasó a «un censo y un trinquete». La cifra que protege es 0, y ahora la vigila una
  máquina.
- **La justificación de una declaración también se verifica.** El arquitecto escribió que las
  seis C llegaban por `columns`; tres llegan por `order` (H1 de `#025`). Las dos vías están
  acotadas, pero el porqué estaba incompleto.
- **Un `count` exacto** cierra, en este censo, el hueco de «el count mayor pasa en silencio».
