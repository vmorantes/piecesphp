# MEDICION · `EntityMapper` contra `ORM`, cual de los dos

**Pregunta del PROPIETARIO — 2026-08-30**: *«De forma objetiva, cual solucion de ORM de mi paquete
database es mejor, entitymapper o orm?»*

Medido sobre `piecesphp/database` y sobre `piecesphp` (el consumidor real), arbol de trabajo.

## Lo primero: no son dos ORM rivales

Los dos se apoyan en **el mismo motor**, `ORM\ActiveRecord` (1.312 lineas, el constructor de
consultas):

```
ORM\ActiveRecord  (constructor de consultas)
   |- ActiveRecordModel  -> EntityMapper     (camino A)
   `- ActiveRecord       -> ORM              (camino B)  ORM.php:90  $this->model = new ActiveRecord(...)
```

Lo que se compara son **dos capas de entidad sobre el mismo motor**, no dos ORM.

## Adopcion: 30 a 0

31 mappers en `piecesphp/src/app`:

| linaje | mappers |
| :-- | --: |
| `EntityMapperExtensible extends BaseEntityMapper extends EntityMapper` | 16 |
| `BaseEntityMapper extends EntityMapper` | 13 |
| `EntityMapper` directo | 1 |
| `ActiveRecordModel` directo | 1 |
| **`ORM` / `ExtensibleORM`** | **0** |

Y las piezas exclusivas del camino B, en la aplicacion: `ForeignKey` 0 · `CollectionOf` 0 ·
`SQLTypesEnum` 0 · `ORMSchemeCreator` 0 (`SchemeCreator`, el del camino A, en 6 archivos).

**La unica mencion de `ORM` en toda la aplicacion** es una union de tipos en
`Utilities/Helpers/DataTablesHelper.php` (`EntityMapper|ORM`, y un
`$value instanceof ORM || is_subclass_of($value, ORM::class)` en la linea 130). Con 0 mappers de
ORM, **esa rama no se ejecuta nunca**: es codigo muerto con forma de compatibilidad.

## La evidencia que decide, y no son las cifras de tamano

Cuando el PROPIETARIO necesito **meta-propiedades**, `ExtensibleORM` (camino B) ya existia desde
2020 y hacia exactamente eso. No lo uso: escribio `EntityMapperExtensible` dentro de `piecesphp`,
sobre el camino A. `MetaProperty` aparece en 15 archivos de la aplicacion — **todos por el camino
A**. Eso es una preferencia revelada, medida sobre sus propios commits, no una opinion.

## Donde gana cada uno

**Gana `ORM` en DISEÑO** — y gana claramente:

- Campos como objetos tipados con API fluida y enum, frente a un array de cadenas:
  ```php
  // ORM
  (new Field('id', SQLTypesEnum::TYPE_INT))->sqlIsPrimaryKey(true)->sqlAutoIncrement(true);
  // EntityMapper
  'id' => ['type' => 'int', 'primary_key' => true],
  ```
  Un error de dedo en `'primary_key'` no lo ve nadie hasta que corre. En `Field` no compila.
- `ForeignKey`: relaciones declaradas. **El docblock de `EntityMapper` sigue diciendo
  `@todo Agregar diferentes relaciones`.**
- `ORMSchemeCreator`: crear tablas desde el modelo. **El mismo docblock dice
  `@todo Crear tablas automaticamente`.**
- Colecciones tipadas (`FieldCollection`, `CollectionOf`).

Es decir: **el camino B es la respuesta de 2020 a los dos `@todo` que el camino A arrastra
desde 2018, y los resuelve.**

**Gana `EntityMapper` en ESTADO REAL:**

- PHPStan del paquete, 21 errores en 6 archivos: **`EntityMapper.php` tiene 0**. El camino B se
  lleva 14 — `ActiveRecord` 9, `ExtensibleORM` 4, `Field` 1.
- 30 mappers en ~19 modulos, anos de uso. El camino B: cero.
- Tipos de retorno declarados: `EntityMapper` 2/38, `ORM` 1/23, `ActiveRecord` 0/45. **Ahi no gana
  nadie**, los dos estan igual de flojos.

## Veredicto

**Mejor diseñado: `ORM`. Mejor solucion hoy: `EntityMapper`** — y no porque su diseño sea mejor,
sino porque es el unico con consumidores.

Cero adopcion no es un dato neutro. Significa que **el camino B nunca ha sido ejercido**: sus 14
errores de PHPStan no los ha sufrido nadie, y cualquier defecto suyo no esta ausente, esta **sin
descubrir**. Elegirlo hoy es elegir la ruta no recorrida por su plano, no por su terreno.

## Y aqui hay una trampa de clon, que es lo que importa

`piecesphp` es una plantilla que se clona. Quien clone y lea el paquete se encuentra dos caminos
sin ninguna señal de cual es el sostenido, **y el camino B parece el moderno** — tipos, enums,
relaciones, creador de esquemas. Va a elegir ese. Y va a aterrizar en la ruta que nadie ha pisado.

Reparto, con la regla del PROPIETARIO (*corrige una trampa entra; extiende, no*):

- **CORRIGE**: decir cual es el camino sostenido — en el README del paquete, en
  `02-arquitectura.md` (que hoy compara `ActiveRecord` contra `EntityMapper` y **no menciona que el
  camino B tiene cero adopcion**), y en el docblock de `ORM`. Es barato.
- **CORRIGE**: la rama muerta de `ORM` en `DataTablesHelper` — o se retira, o se declara como
  soporte no ejercido. Hoy finge una compatibilidad que nadie ha comprobado.
- **EXTIENDE, y va DESPUES de la MAJOR**: llevar los aciertos del camino B al A —campos como
  objetos, relaciones declaradas, creacion de tablas— o migrar. Cualquiera de las dos es una
  version mayor del paquete `database`, no un retoque.

**Nota**: los dos primeros puntos tocan el repositorio `database`, que SI esta versionado y
etiquetado. Van en un bloque propio de ese paquete, no en uno de `piecesphp`.


---

# SEGUNDA PARTE · Que tan viable es migrar a ORM

**Pregunta del PROPIETARIO — 2026-08-30**: *«Migrar lo que existe a ORM. Revisar que ORM NO FALLE
EN NADA. Hacer una guia de Migracion A>B. Hacer una guia de uso de ORM. Se sincero.»*

Y el motivo que declaro: *«nunca quise tomar el trabajo dinamico de migrar pero siempre fue mi
deseo que ORM ganara porque me quedo mas bonito»*.

**Su gusto acerto sobre el diseño. Lo que el gusto no puede decirle es que la calidad del camino B
esta SIN MEDIR, no comprobada.** Cero consumidores significa que sus defectos estan sin descubrir,
no ausentes. Esas son dos frases distintas y la campaña entera trata de esa diferencia.

## 1. Migrar lo que existe — VIABLE, y menos bloqueado de lo que parece

Lo bueno, medido:

- **Los 8 tipos SQL que usan los 30 mappers estan cubiertos por `SQLTypesEnum`**: `text` 85 ·
  `int` 64 · `datetime` 44 · `bigint` 28 · `varchar` 27 · `json` 23 · `double` 3 · `float` 2.
  **Ni uno falta.** Este era el bloqueo mas probable y no existe.
- Del `getModel()` (112 menciones) solo **4 metodos se encadenan de verdad**: `delete`, `select`,
  `getTable`, `getDatabase`. Tres de los cuatro **no existen** en `ActiveRecordModel` y **si**
  en `ActiveRecord`. En esa direccion la migracion FACILITA, no complica.

Lo que hay que tocar, medido:

| unidad | tamaño |
| :-- | --: |
| Declaraciones `$fields` a reescribir como `FieldCollection` en los 30 mappers | **1.281 lineas** |
| `BaseEntityMapper` + `EntityMapperExtensible` — las dos clases del framework de las que heredan los 30 | **577 lineas, 17 metodos** sin equivalente en B |
| Sitios que llaman API exclusiva de `EntityMapper` | **~68** (`validateType` 36, `castPHPToSQLTypes` 10, `setOptions` 8, `dataToInsert` 4, `changedFields` 3, `dataToUpdate` 3, `seedRowSnapshot` 2, `setThousandsSeparator` 2) |
| `SchemeCreator` -> `ORMSchemeCreator` | 6 archivos |

**Y el hueco de verdad, que NO son los tipos**: el cuarteto `reference_table`,
`reference_primary_key`, `reference_field`, `human_readable_reference_field` — **39 usos cada
uno**. `ForeignKey` (91 lineas) tiene `table`, `name`, `many`, `orm`, y **no tiene campo legible
por humanos**. `ORM::humanReadable()` resuelve por otro mecanismo: a traves de
`$foreign->getORM()`. Los 28 sitios que llaman `humanReadable()` dependen de la semantica del
camino A. **Eso no es traduccion, es rediseño.**

Los 30 mappers no se pueden mover uno a uno: **la unidad de migracion son las dos clases base.**
Hasta que existan en B, no se mueve nada.

## 2. «Revisar que ORM NO FALLE EN NADA» — NO, y este es el punto donde hay que ser duro

**Ese objetivo no es alcanzable y ademas es el objetivo equivocado.** Nadie comprueba que algo no
falle en nada; se comprueba que no falla en lo que se le pregunta. Lo alcanzable es: *que ORM sea
ejercido por los mismos casos que hoy ejercita EntityMapper.*

Estado real del paquete, medido: **111 comprobaciones en 12 suites.**

- Camino A —`UnitTest-EntityMapper` 24 + `UnitTest-SchemeSql` 15—: **39**.
- Camino B —`UnitTest-ActiveRecord` 10 + `Fields` 8 + `Collections` 6 + `Statements` 4—: **28**.
- **No existe `UnitTest-ORM.php`.** La clase `ORM` se ejercita de refilon dentro de
  `UnitTest-ActiveRecord`, compartiendo 10 comprobaciones con `ExtensibleORM`.
- PHPStan: la familia B se lleva **14 de los 21 errores** del paquete; `EntityMapper.php` tiene
  **0**.

**28 comprobaciones sobre un camino con 0 consumidores no dicen «ORM funciona». Dicen «ORM no ha
fallado en las 28 cosas que le hemos preguntado».** Con 30 mappers encima se enfrentaria a
situaciones que nadie le ha planteado.

Y aqui esta el circulo: **la unica prueba real de que ORM aguanta es migrar.** Por eso no se
resuelve revisando. Se resuelve **migrando UN mapper —el mas exigente, uno con referencias y con
meta-propiedades— y viviendo con el en produccion un tiempo.** Eso es un experimento con coste
acotado y veredicto real. «Revisar que no falle» es coste ilimitado y veredicto nulo.

## 3 y 4. Las dos guias — y el orden esta invertido

**La guia de migracion A>B no se puede escribir antes de migrar.** Escrita desde el plan documenta
la intencion; escrita desde la primera migracion real documenta lo que mordio. Y lo que muerde en
una migracion nunca es lo que estaba en el plan.

**La guia de uso de ORM si se puede escribir ya**, y es la unica de las cuatro cosas que vale la
pena aunque no se migre nunca: el camino B existe, un clon lo va a encontrar, y hoy no hay nada que
le diga como usarlo. Ademas obliga a mirar la API desde fuera, que es donde se le ven los huecos
—el de `human_readable_reference_field` habria salido solo—.

## Orden honesto, distinto del propuesto

1. **Guia de uso de ORM.** Barata, util pase lo que pase, y saca huecos.
2. **`UnitTest-ORM` de verdad + los 14 errores de PHPStan de la familia B.** Acotado. El camino
   deja de estar sin pisar.
3. **Migrar UN mapper exigente y vivir con el.** El experimento.
4. **Guia A>B**, escrita desde esa migracion.
5. **Los otros 29**, si el paso 3 dijo que si.

## Reparto — y esto es lo incomodo

**Nada de esto CORRIGE una trampa. Todo EXTIENDE.** Por la regla del PROPIETARIO va **despues de
la MAJOR**, entera. Y la MAJOR depende de que la campaña termine.

Meter esto en la campaña la alargaria mas que cualquier otra cosa pendiente, y el PROPIETARIO ya
dijo que esta cansado. **Se puede querer que ORM gane sin pagarlo ahora.**

Lo unico que si entra por barato, y ya esta anotado en la primera parte: **decir cual es el camino
sostenido**, y **la rama muerta de `ORM` en `DataTablesHelper`**.


## APLAZADO por el PROPIETARIO — 2026-08-30

*«Lo de ORM dejemoslo para despues de la major, quizas en otra mayor.»*

Y una preocupacion que la medicion de ARQUITECTO **no cubria**: *«me preocupa como reemplazar el
lenguaje de `ActiveRecordModel`, lo uso mucho»*.

Es un punto real. La viabilidad se midio por `$fields` (1.281 lineas), clases base (577) y API
exclusiva de `EntityMapper` (~68 sitios). **No se midio el VOCABULARIO DE CONSULTA** —
`select()->where()->orderBy()->execute()->result()` y compañia— que es lo que el PROPIETARIO
escribe a diario y lo que peor se lleva un cambio de capa.

**Requisito para la guia de uso de ORM**: la primera seccion no es «como declarar un campo». Es
**la tabla de equivalencias del lenguaje de consulta, `ActiveRecordModel` a la izquierda**. Si esa
tabla no se puede escribir entera, la migracion no es viable y se sabria antes de tocar nada.
