# Los registros: CUATRO, y por que

*El PROPIETARIO delego la decision el 2026-09-02: «Eres el arquitecto. Si con lo que te digo
decides que necesitamos dos o tres o cuatro logs, entonces no hay problema. El asunto es que
sean UTILES, ROBUSTOS y AMIGABLES.»*

*Esta hoja SUSTITUYE la decision (a)/(b) de `EventsLog — robustecerlo…`, que queda como
medicion. Decide ARQUITECTO. LEY 14: la correccion se escribe aparte y enlazada.*

---

## LO QUE YA HAY — y nadie lo habia contado junto

De 32 tablas, **cuatro tienen forma de registro** y ninguna sabe de las otras:

| Tabla | Que es de verdad | Estado |
| :-- | :-- | :-- |
| `actions_log` | acciones de usuario | 1 productor: `APIController` |
| `login_attempts` | intentos de acceso | vivo |
| `pcsphp_tickets_log` | **NO es un registro**: es un buzon de contacto (name, email, message, type) | mal llamado |
| `pcsphp_jobs_queue` | una cola, no un registro | adyacente |

Y fuera de la base: **`log_exception()` con 181 llamadas** que terminan en
`GenericHandler->logging($plainLog)` — **archivo plano, ninguna vista**.

**EL HALLAZGO QUE DECIDE EL DISENO**: `actions_log` y `login_attempts` se escribieron por
separado y **repiten el mismo error tres veces cada una**:

- una bolsa `longtext` para lo que no cabe (`meta`, `extra_data`),
- **ningun indice por fecha** —`actions_log` solo indexa `createdBy`; `login_attempts`, `user_id`—,
- `utf8mb4_bin`, con lo que `perez` no encuentra `Pérez`.

> **Dos equipos del mismo autor, en dos momentos distintos, cometieron el mismo fallo por
> separado.** Eso no se arregla escribiendo mejor el tercero: se arregla con un instrumental
> comun. Es la medicion la que lo dice, no una preferencia.

**Y una incoherencia que ya existe**: `login_attempts.user_id` **es nulable**;
`actions_log.createdBy` es `NOT NULL`. El framework ya resolvio «el actor puede no existir» en
una tabla y no en la otra.

---

## EL CRITERIO — y es lo unico que hace de esto arquitectura y no gusto

> **Dos sucesos van al MISMO registro cuando comparten SUS COLUMNAS y SUS PREGUNTAS.**
> Si comparten columnas pero no preguntas, es una tabla comoda que nadie sabe consultar.
> Si comparten preguntas pero no columnas, es un `longtext` esperando a ocurrir.

Aplicado, dan **CUATRO**:

### 1. ACCIONES — `actions_log`
**Que**: lo que un USUARIO hace en su flujo. **Actor obligatorio**: si no hay usuario, no es una
accion de usuario.
**Su pregunta**: *«que hizo Fulano, y cuando».*
**Ya tiene lo mejor del conjunto**: un catalogo de mensajes con variables y traducible
(`LogsMapper:93`) y propiedades meta tipadas (`MetaProperty`, en su constructor). Le faltan
productores: hoy tiene cinco mensajes y una equivalencia de modulo.

### 2. ACCESO — `login_attempts`
**Que**: intentos de entrar, con o sin exito. **Actor OPCIONAL**, y por eso no es lo mismo que 1:
cuando se escribe la fila **todavia no se sabe quien es**, o no es nadie.
**Su pregunta**: *«esta alguien probando contrasenas, y contra quien».* Es de seguridad, no de
historial.
**Y ya tiene consumidor pendiente en campana**: el throttle de OTP por usuario e IP.

### 3. ERRORES — hoy archivo plano, 181 llamadas
**Que**: lo que se rompe.
**Su pregunta NO es cronologica**: *«que se esta rompiendo, donde, y CUANTAS VECES»*. Se lee
**agrupado por firma** —tipo, archivo, linea, mensaje normalizado—, no como lista. Un log de
errores presentado como lista cronologica es ilegible a la tercera semana.
Por eso no puede vivir con 1 ni con 2: **su unidad de lectura es distinta.**

### 4. SALIDAS — todo lo que sale de la casa
**Que**: correo **y** las demas integraciones. En `src/composer.json` ya hay Mailjet, Mailgun,
Mautic, HubSpot, OpenAI y Azure Blob; el correo tiene ademas cuatro vias internas.
**Su pregunta**: *«salio, que contesto, hay que reintentar, y cuadra con su panel».*
**Columnas compartidas de verdad**: destino, peticion, codigo y texto de respuesta, intento,
identificador del proveedor, duracion. **Y LEY 31 se les aplica identica.**

> **AQUI ESTA LA DECISION NO OBVIA, Y LA DEFIENDO: EL CORREO NO TIENE REGISTRO PROPIO.**
> El PROPIETARIO pidio «un log de correos especifico», y lo que pidio —completo, filtrable,
> cabeceras, respuestas de error— **lo tiene igual** siendo una CLASE dentro de Salidas, con su
> cuerpo tipado: destinatarios, asunto, cabeceras, message-id, intentos. Lo que se comparte es
> la maquinaria cara: reintento, conciliacion, rotacion, vista.
>
> **La objecion honesta**: el correo tiene campos que ninguna otra integracion tiene. **La
> respuesta**: esos campos son el CUERPO, tipado por clase — que es exactamente lo que
> `MetaProperty` ya hace en `LogsMapper`. El NUCLEO es comun. Si al construirlo resulta que el
> cuerpo del correo no se parece a ningun otro EN SUS PREGUNTAS, se parte. **Se mide entonces,
> no se decide ahora.**

---

## LO QUE SE RECHAZO, Y POR QUE

- **UNO solo** (el registro unico que se penso el 2026-09-02): fue mi propuesta y la retiro. Una
  sola tabla con `meta longtext` es exactamente como acabo `actions_log`.
- **DOS** (acciones+acceso y errores+salidas): junta un actor obligatorio con uno opcional, y
  junta una lectura agrupada por firma con una cronologica. Dos vistas dentro de una tabla.
- **TRES** (acciones, errores, correo): deja fuera las cinco integraciones que ya existen, y el
  dia que HubSpot no responda no habra donde mirar. **LEY 31 no es una regla de correo.**
- **CINCO** (separando correo de integraciones): duplica reintento y conciliacion, que es la
  parte cara.

---

## LO QUE LOS CUATRO COMPARTEN — el instrumental, que es un bloque propio y va PRIMERO

**No la tabla: el mecanismo.** Sin esto, cuatro registros son cuatro veces el mismo trabajo, que
es justo lo que la medicion de arriba demuestra que ya paso dos veces.

1. **Un componente de listado con filtros** y una **vista de detalle parametrizada**.
2. **UNA tarea de rotacion en `bin/cli`** que reciba QUE tabla, con QUE politica de retencion, y
   DONDE exporta. **En este orden: se exporta, se comprueba el archivo, y SOLO ENTONCES se
   borra.**
3. **El nucleo de columnas**: `createdAt` **CON INDICE**, `kind`, `severity`, `source` (modulo),
   `route`, `ip`, `correlationId`, `outcome`. Lo que se filtre dentro del JSON se promueve a
   columna generada `STORED` e indexada — MariaDB 10.11 lo permite.
4. **`correlationId` en los cuatro.** Sin el, una accion, el error que provoco y el correo que no
   salio son tres filas en tres tablas sin nada que las ate. **Es la pieza que convierte cuatro
   registros en un sistema**, y es la unica razon por la que cuatro no es peor que uno.
5. **Las dos lecturas de cada fila**: la HUMANA —la frase del catalogo, traducida— y la TECNICA
   —cuerpo, cabeceras, respuestas, tiempos—. Es literalmente lo que pidio el PROPIETARIO: claro
   para devs y para usuarios finales medios/avanzados.
6. **`utf8mb4_bin` se revisa** en los cuatro.

---

## «AMIGABLE» TIENE UNA PRUEBA, Y SE ESCRIBE AQUI

Un registro no es amigable porque tenga filtros bonitos. Es amigable cuando **responde una
pregunta en vez de mostrar filas**:

> *«¿Le llego el correo a Fulano ayer?»* debe resolverse con **un filtro, no con una consulta**.
> Si hace falta escribir SQL, o mirar el panel del proveedor, el registro no esta terminado.

Esa frase es la prueba de aceptacion del lote.

---

## Y UNA COSA QUE NO ES UN REGISTRO

`pcsphp_tickets_log` **no es un log**: es un buzon de contacto —`name`, `email`, `message`,
`type`—. Se le quita el nombre. Cuesta una migracion y evita que el proximo que lea el esquema
crea que hay cinco registros.

---

## `actions_log` ES ADEMAS LA BOLSA DEL CLON — decidido por el PROPIETARIO, 2026-09-02

*«`actions_log`, si se mejora, seguira sirviendo para meter en esa bolsa registros especificos de
clones, por ejemplo.»*

Es correcto y ademas **ya esta previsto en el codigo**: `MSG_GENERIC => '%message%'` existe en el
catalogo desde el principio. El framework es una plantilla que se clona, y un clon tendra sucesos
que el framework no puede anticipar. Ese es el papel.

> **PERO ES EXACTAMENTE ASI COMO NACIO EL `meta longtext`.** Una bolsa sin disciplina se llena de
> texto libre y a los dos anos no se puede filtrar. Lo que separa una bolsa util de un vertedero
> es UNA regla:

**EL CLON EXTIENDE DECLARANDO, NO VOLCANDO.** Anadir un suceso propio es:

1. **una clave en el catalogo** de mensajes, con su frase y sus variables —traducible, que es la
   mitad humana de la lectura—, y
2. **las propiedades meta TIPADAS** que necesite, con `addMetaProperty(new MetaProperty(TIPO,
   ...), 'nombre')`, que es el mecanismo que ya existe en el constructor de `LogsMapper`.

**`MSG_GENERIC` es la EXCEPCION, no la puerta.** Un suceso que se repite y sigue entrando por
`GENERIC` es una clave que falta por declarar, y el instrumental deberia poder decir cuantas
filas `GENERIC` hay: **si crecen, alguien esta usando la bolsa como vertedero.** Eso es un
contador, y es barato.

Y esto es tambien lo que hace de la extension algo **documentable**: la guia de creacion de
modulos gana un apartado de tres lineas —declara tu clave, declara tus metas tipadas— en vez de
un «escribe lo que quieras en `meta`».

## LAS VISTAS QUE YA EXISTEN ENTRAN EN EL LOTE — 2026-09-02, senaladas por el PROPIETARIO

*«Ya hay multiples registros del modulo de usuario, de logins y cosas por el estilo. Esas vistas
estan feas esteticamente y codificadas regular. Eso tambien puede ser para el lote de logs.»*

**MEDIDO**: `LoginAttemptsController` son **402 lineas** con cuatro metodos —`reportsAccess` y
tres exportaciones: `attemptsExport`, `notLoggedExport`, `loggedExport`—, y sus vistas son
**tres archivos** en `view/panel/pages/login-reports/`:

    attempts.php    80 lineas
    logged.php      89 lineas
    not-logged.php  91 lineas

**El mismo esqueleto escrito tres veces a mano**: breadcrumb, `header-options`, `columns two`,
`section-title`, tarjetas. Comparando `attempts` con `logged`, **48 de sus lineas son identicas
caracter por caracter**; el resto es la misma estructura con otro contenido. Tres vistas que se
diferencian por un filtro.

**Y llevan erratas que ya cargan peso**: `class="titlle-info"`, `class="tittle"`. **37
apariciones** de `tittle`/`titlle` en vistas y hojas de estilo del arbol. No es una falta de
ortografia suelta: **la hoja de estilo selecciona por esa errata**, asi que corregirla es un
cambio coordinado de vista y CSS. Es un ejemplo exacto de «codificado regular».

**CONSECUENCIA PARA EL LOTE**: el punto 1 del instrumental —el componente de listado con filtros
y la vista de detalle parametrizada— **tiene su primer cliente antes de escribirse**. Estas tres
vistas son la prueba de que hace falta: son lo que pasa cuando cada registro se dibuja a mano.
Al construir el instrumental, **se rehacen sobre el**, no se retocan.

## TAMANO

**CINCO bloques, despues de la MAJOR** (EXTIENDE):
1. **El instrumental comun** —nucleo, listado, detalle, rotacion con exportacion previa—.
2. **Acciones**: indices, politica de la clave ajena, y los productores que le faltan.
3. **Errores**: tabla, firma, agrupacion, y los 181 `log_exception` entrando por la puerta.
4. **Salidas**: correo primero —que es lo que el PROPIETARIO pidio— y las cinco integraciones
   detras.
5. **ACCESO**: rehacer las tres vistas de `login-reports` sobre el instrumental, unificar las
   tres exportaciones, y las erratas `tittle`/`titlle` con su CSS.

**El unico trozo que NO espera** es el defecto de `ContactFormsController:265`, que devuelve el
log SMTP al cliente. Ese CORRIGE, y se cae en AZ.
