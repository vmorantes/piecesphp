# Campos que no salen nunca

Una tabla puede declarar **qué columnas suyas no se serializan jamás**. El framework lo respeta por su cuenta, en todas
las vías por las que un dato puede salir, y sin que nadie tenga que acordarse.

Nació de un defecto real y conviene contarlo, porque explica por qué la pieza está donde está: **cuando una pantalla
pedía los datos de algo que apunta a un usuario** —una noticia y su autor, un documento y quien lo creó— **el framework
devolvía la ficha entera de ese usuario, con su contraseña cifrada dentro.** No se filtraba porque **tres controladores
se acordaban de taparlo a mano**. Nada obligaba al cuarto.

## Cómo se usa

En el mapper, una constante:

```php
class UsersModel extends EntityMapperExtensible
{
    /**
     * Columnas que no se serializan nunca, por ninguna vía.
     */
    const HIDDEN_FIELDS = ['password', 'meta', 'failedAttempts', 'sessionsValidFrom'];
}
```

Y ya está. No hay que tocar ningún controlador ni ninguna vista.

> [!IMPORTANT]
> **La constante la lee `BaseEntityMapper`**, que es de donde heredan todos los mappers del framework
> —`EntityMapperExtensible` incluido, porque hereda de él—. **Un mapper que herede directamente del `EntityMapper` del
> paquete `database` la declara y no pasa nada, sin aviso.** En el framework no hay ninguno así: el único que extiende
> ese `EntityMapper` de frente es `BaseEntityMapper`. Si tu módulo crea un mapper desde cero, hereda de
> `BaseEntityMapper`.

**Hoy lo declaran dos tablas del framework**: la de usuarios, con las cuatro de arriba, y la de los secretos del doble
factor, con `secret`, `oneUseCode` y `twoAuthFactorSecurityCode`.

## Qué cubre, y son CUATRO vías

Un dato puede salir de un mapper por más caminos de los que parece. Los cuatro están cerrados:

| Vía | Cómo se ve en el código |
| :-- | :-- |
| **Pedirlo directo** | `$mapper->humanReadable()`, `$mapper->jsonSerialize()`, `json_encode($mapper)` |
| **Pedirlo anidado** | Otro mapper que lo referencia: pedir la noticia trae a su autor expandido |
| **La meta-propiedad** | Un mapper guardado dentro del JSON de `meta`, como el administrador de una organización |
| **La conversión a texto** | `"$mapper"`, `(string) $mapper`, `echo $mapper` |

> [!IMPORTANT]
> **Se oculta SIEMPRE, no solo cuando el mapper va anidado.** Podría parecer suficiente filtrar en la expansión —que es
> donde estaba la fuga—, pero entonces el día que alguien pida el mapper directamente el dato sale. **Un campo declarado
> no se serializa, punto.**

## Lo que NO hace, y hay que saberlo

- **No esconde la columna de la base.** El campo sigue existiendo, se lee y se escribe con normalidad: lo único que
  cambia es que **no viaja** cuando el mapper se convierte en datos.
- **No oculta la ESTRUCTURA.** La descripción de los campos —tipos, longitudes, claves— sigue saliendo completa, con la
  columna oculta incluida. Es la definición de la tabla, no su contenido: ahí no hay ningún valor.
- **No sustituye a una lista blanca.** Si una respuesta de tu API debe entregar cinco campos concretos, **dilos tú**:
  esto evita que salga lo que nunca debe salir, no decide lo que tu pantalla necesita.
- **No arregla lo que ya está guardado** (ver la nota del final).

## Dos detalles de comportamiento que conviene conocer

### En las tablas del panel, la celda va VACÍA y no se salta

El ayudante de tablas, en su modo de mapper, construye la fila **por posición**: una celda por campo, en el orden en que
la tabla los declara. Así que un campo oculto **no se puede saltar**: si lo hiciera, todas las columnas siguientes se
correrían una posición y el ancho de la fila dejaría de cuadrar con el orden de columnas, que es el que usan la
ordenación y la búsqueda. La celda va **vacía**, y la fila conserva su forma.

### La conversión a texto falla CERRADA

Si por cualquier motivo no puede filtrar, **devuelve un objeto vacío**, nunca el volcado completo. Un filtro que ante la
duda enseña todo no es un filtro.

> [!NOTE]
> **El ORM usa esta conversión también al GUARDAR, pero no como podría parecer.** Solo la usa si una columna de texto
> declarada como **referencia** recibe un registro de otra clase; **ninguna de las 39 referencias del framework es de
> texto**, así que ese camino no se alcanza aquí. Y asignar un registro a una columna de texto **normal nunca guarda su
> JSON**, ni antes ni ahora: el ORM **rechaza el objeto antes de convertirlo** —lo sustituye por el valor por defecto de
> la columna, y si no hay ninguno que sirva, lanza—.

## Si clonas el framework

- **Declara en tu tabla lo que no deba salir nunca**: contraseñas, secretos, tokens, telemetría de seguridad, marcas
  internas. Cuesta una línea y deja de depender de que cada pantalla se acuerde.
- **Las protecciones a mano que ya tengas, déjalas.** Las tres del framework se quedaron a propósito: dos capas no
  estorban, y el día que alguien rompa la de abajo la de arriba sigue tapando.
- Y está vigilado, en el framework, por la suite `unit-tests:core/hidden-fields`:

```bash
bin/cli unit-tests:core/hidden-fields --local
```

**Lo que la hace fiable es cómo mide**: pone un **valor señuelo** en cada campo oculto y busca **el valor** en la
salida, no solo el nombre. Así caza la fuga aunque alguien renombre el campo. Y se ha visto fallar: **vaciando la
declaración, doce de sus veintidós comprobaciones se ponen en rojo**, en las cuatro vías.
