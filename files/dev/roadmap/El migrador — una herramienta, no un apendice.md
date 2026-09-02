# El migrador: una herramienta, no un apendice

*Pedido por el PROPIETARIO el 2026-09-02: «un migrador (una herramienta pues)» que lleve una
instalacion anterior a la campana hasta la version nueva.*

*Y CALIBRADO POR EL MISMO, el mismo dia: «Es improbable que se vaya a migrar algo, pero no
imposible. Asi que no es critico, pero si deseable.»*

---

## LO QUE LA CALIBRACION DECIDE: QUE CADUCA Y QUE NO

Que algo sea deseable y no critico no dice «hazlo mas tarde»; dice **haz ahora solo lo que no se
puede hacer mas tarde**. Y aqui la linea es nitida:

| Pieza | Caduca? | Cuando se hace |
| :-- | :-- | :-- |
| `files/dev/column-renames.json` | **SI** | **AHORA**, en el bloque de renombrado |
| Marca de version de ESQUEMA en la base | a medias | con la MAJOR, cuesta poco |
| Detector por huella | **NO** | cuando se quiera |
| El migrador en si | **NO** | cuando se quiera, o nunca |

**POR QUE EL MAPA CADUCA Y LA HERRAMIENTA NO.** El renombrado toca ~247 referencias en un solo
bloque. Reconstruir despues las ocho parejas `viejo -> nuevo` a partir de ese diff cuesta una
tarde; escribirlas mientras se hacen cuesta cinco minutos. **En cambio la herramienta se puede
construir dentro de tres anos sin perder nada: los 79 tags hasta `v7.1.0` conservan todos los
esquemas anteriores, y de ahi sale cualquier huella y cualquier `ALTER TABLE`.** Git es la copia
de seguridad del pasado; el diff de un bloque, no.

> **La regla, y sirve para mas cosas que esta**: aplazar una HERRAMIENTA es gratis cuando sus
> INSUMOS estan preservados. Lo unico que no se puede aplazar es escribir el insumo.

---

## EL PROBLEMA QUE HAY QUE RESOLVER PRIMERO, MEDIDO EL 2026-09-02

**Hoy nada dice en que version esta el ESQUEMA de un clon.**

```php
// src/app/core/bootstrap.php:262
define('APP_VERSION', 'v7.1.0');
define('APP_VERSION_DATE', (new \DateTime('2026-08-20'))->format('Y-m-d'));
```

Escrita A MANO, y la leen **dos vistas**: `about-framework.php:23` y `panel/layout/menu.php:22`.
Es decorativa. Y en `databases/piecesphp_structure.sql` **no hay tabla de migraciones ni marca de
version de esquema**: `grep -i "schema_version|migration"` da cero.

`APP_VERSION` dice **que codigo hay**, no **que base hay** — y despues de una actualizacion a
medias esas dos cosas dejan de coincidir, que es exactamente cuando alguien necesita un migrador.

**Requisito cero**: una marca de version de esquema EN LA BASE, escrita por el migrador y por
`bin/cli scheme-create`.

**Y el huevo y la gallina**: los clones anteriores no la tendran nunca. Hace falta un **detector
por huella** —mirar la forma del esquema y deducir de donde viene: existe `login_attempts`?,
tiene `user_id` o `userID`?, esta `pcsphp_jobs_queue`?—. No es exotico: **la maquina de huellas ya
existe**, es `integrity-signatures.json`.

---

## TRES CAPACIDADES CON CERTEZA DECRECIENTE — y la tercera no se promete

**1 · EL ESQUEMA — SE MIGRA.** Determinista, reversible, verificable. Los `ALTER TABLE` salen
generados de `column-renames.json` y de los demas cambios de la MAJOR.

**2 · CONFIGURACION Y DISPOSICION DE ARCHIVOS — SE MIGRA CON REVISION.** Claves nuevas de
`constants.php`, rutas, permisos. Se PROPONE y el humano confirma. Nunca en silencio.

**3 · EL CODIGO PROPIO DEL CLON — NO SE MIGRA. SE AUDITA.** Un clon tiene sus modulos, sus
vistas, sus llamadas, y nadie puede reescribirlos con garantias. Lo que si se puede es decir
exactamente que va a romper: *«tus archivos nombran `user_id` en 47 sitios, y estos son»*.

> **Y esto ultimo es lo que de verdad lo hace util: el 80% del dolor de actualizar un clon no es
> el `ALTER TABLE`, es no saber que se te va a romper.** Un migrador que SOLO audite ya vale.

---

## CONDICION DE ACEPTACION

**Se prueba contra un clon real restaurado, no contra el arbol de desarrollo.** Un migrador que
solo ha corrido sobre `dev` es un guion que nadie ha ejecutado. La maquina esta: `db-restore` y
LEY 12.

---

## TAMANO Y PRIORIDAD

**Deseable, no critico.** Un bloque para el detector y el esquema; otro para la auditoria del
codigo del clon. **Sin fecha**, y sin coste por esperar — salvo el JSON, que se escribe en el
bloque de renombrado y no espera.
