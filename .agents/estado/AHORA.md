# Ahora

- **Actualizado:** 2026-09-14 16:07 (medido con `date`). A las 15:18 hubo una interrupción, sin
  pérdidas. El PO delegó P22 y P23: «Resuelve P22 y P23 como tu prefieras».
- **Último mensaje:** `#026 · ARQ`, en vuelo: lote 3, bloque 1, la auditoría de datos de las
  subidas. El próximo número es `#027`.
- **`#025`: BD y el lote 2, cerrados.**
  - Comprobaciones 27 y 28 en verify-integrity, las dos vistas fallar.
  - Todo en verde; PHPStan en 744.
  - Bitácora 0006. Los dos lotes salen del mapa.
- **`#021` (lote 2, bloque 1): completado**, en `1184f229`.
  - `bin/censo-sql-identificadores` da 0 CONFIRMADO, 8 REVISAR y 102 DESCARTADO en 138
    posiciones. Ningún identificador llega de la petición.
  - Canario de 27 caras; la provocación se vio fallar.
- **Tramo en curso:** [`tramos/2026-09-14-1441-mapa-a-la-major.md`](tramos/2026-09-14-1441-mapa-a-la-major.md).
- **Tramo anterior:** [`tramos/2026-09-14-1105-traspaso-y-andamiaje.md`](tramos/2026-09-14-1105-traspaso-y-andamiaje.md),
  cerrado.
- **Informe del estado del proyecto:** [`informe-2026-09-14-estado-del-proyecto.md`](informe-2026-09-14-estado-del-proyecto.md)
- **Sesiones:** arquitecto `PiecesPHPUpgrade-Arquitecto-Main`, coder `PiecesPHPUpgrade-Coder-Main`.
- **Rama:** `dev`, en `7b45c761`. Hay 36 commits sin empujar.
- **Paquetes:** los cuatro, en `dev`, cada uno con commits de instrumental sin empujar:
  - database, 2;
  - datastructures, 1;
  - geojson, 1;
  - html, 1.

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
- dependencias, builds, servidores y credenciales. Excepción: las herramientas de análisis
  (ADR 0007).

Si la herramienta del coder pide confirmación al commitear, la da el PO en esa sesión.

**Trabajo nombrado por el PO:** el mapa, `../docs/roadmap.md`, en su orden. Orden del PO:
«Trabaja. Adelante.» (2026-09-14).

## Espera al PO

1. **Subir cuando quieras**: los commits de hoy aquí y en los cuatro paquetes, y las dos ramas
   `dev` nuevas.

**Resueltas por delegación (2026-09-14).** Ya están en `pendientes.md`, en el mapa (lote 5b) y en
el ADR 0008. Se commitean en el PASO 1 de `#022`.

- **P23 → ADR 0008.** En los cuatro paquetes hermanos, y solo ahí (su lock no se versiona), la
  guarda dejará actualizar `piecesphp/*` con `--working-dir`. Después, una ronda nivela html:
  `composer update piecesphp/datastructures phpstan/phpstan:2.2.12 rector/rector:2.6.6`.
- **P22 → lote 5b, «Tokens genéricos»**, a continuación de OTP. Medido:
  - **La constante no es la frontera de confianza.** Todo JWT de `TokenModel` y de
    `GenericTokenController` se lee de la fila de la base de datos, nunca de la petición.
    Pasar las dos constantes (`KEY_BASE_JWT`, `KEY_JWT`) a `app_key` es higiene y entra en el
    lote.
  - **El defecto real es otro:**
    - la URL genérica lleva el `id` cifrado con `BaseHashEncryption::encrypt($id, self::class)`,
      un cifrado aditivo con una clave pública: los `id` se pueden calcular;
    - la ruta es pública (`validate_session` es falso en `commentary`);
    - `entryPoint()` carga la fila sin mirar su tipo y, si el JWT no verifica, la **borra**
      (`GenericTokenController.php:205`).
  - **SOSPECHA fuerte, sin provocar**: un anónimo podría borrar filas de tokens de cualquier
    tipo, incluidas las recuperaciones de contraseña pendientes. Depende de que
    `BaseToken::isExpire()` devuelva algo verdadero ante una firma ajena (`return $exp;`, línea
    del `else`). Se confirma con una prueba sin base de datos.
  - En el framework nadie crea tokens genéricos (`createTokenURL()` no tiene llamadores), pero
    la función viaja a cada clon.

Siguen abiertas en `docs/pendientes.md`: qué es el geovisor, el francés, el rol 50 con nombre
`null` y `Components`.

## En curso

**`#026` — lote 3, bloque 1: auditoría de los DATOS de las subidas.**
- Solo lectura: el coder mide y reporta una tabla por módulo, sin tocar código.
- Además commitea lo del arquitecto: la bitácora 0006, el mapa, `CHANGELOG.md` (BD y
  trinquetes 27 y 28), `pendientes.md` y el estado.

Si se corta ahora, no hay nada del producto en juego. Como mucho, quedan sin commitear los
documentos del arquitecto.

## Siguiente

- `.agents/context/21-pruebas-y-puertas.md` sigue enumerando 16 comprobaciones y hay 28. Va con
  E6 (lote 9).
- **El lote 3: subidas.** Medido por el arquitecto en solo lectura:
  - hay nueve `UPLOAD_DIR` (documents, document-types, categories, news-categories,
    organizations, built-in-banner, helpers-system/generic, system-approval y publications);
  - solo publications está en `protect()` (`config/final-configurations-includes/protected-files.php`),
    y con un validador que devuelve `true` y la sesión comentada;
  - el validador recibe `(Request, string $filePath)` y devuelve `bool`.
  **Plan:**
  - Bloque 1: auditar los DATOS, que es lo que pidió el PO. Por módulo: qué guarda, quién llega
    hoy a sus archivos (sin `.htaccess`, Apache los sirve directo: `src/.htaccess:47-49`), si
    los nombres se adivinan y si quedan restos en `tmp/`. Solo medir.
  - Con esa tabla, **P24 al PO**: qué validador lleva cada módulo. El 20 §7 dice que lo decide
    él. Predeterminado a proponer: sesión y permiso de la ruta del módulo para los privados;
    los que muestra la zona pública, sin proteger.
  - Bloque 2: enchufar los validadores y una puerta que falle si un `UPLOAD_DIR` declarado no
    está en `protect()`.
- El lote 2 no cruza al paquete database: con 0 CONFIRMADO no hay nada que cerrar allí.

## Para una sesión nueva

1. **Lo primero que se da al PO** al empezar o retomar el trabajo, cada día, son las dos órdenes
   de renombrado (regla 30, «Nombres de sesión»):
   `/rename PiecesPHPUpgrade-Arquitecto-Main` y `/rename PiecesPHPUpgrade-Coder-Main`. Después
   se comprueba en la lista de sesiones que están puestas.
2. Lee `../HERENCIA.md` y el informe del estado del proyecto.
3. **Las horas de este archivo salen de `date`**, no de una estimación.
