# El módulo como patrón mecanizable

Registrado, **no ejecutado**. Intención declarada por el PROPIETARIO el 2026-08-26, en dos
mensajes seguidos, mientras el CODER trabajaba el bloque S. Va aquí y no en
`18-siguientes-ventanas.md` porque todavía no existe como trabajo.

Sus palabras: los `staticResolver` de los `*Routes` **podrían ser universalizables**, y apunta a
un futuro cercano donde los módulos sigan un patrón **claro, evidente y fácil de mecanizar**,
para desarrollar más ágil.

---

## Lo que ya está medido, y por qué esto no es una idea suelta

Los hallazgos de las últimas semanas son **uno solo visto desde cinco sitios**:

| Medido | Dónde |
| :-- | :-- |
| 24 `staticResolver()`, 18 de ellos idénticos en 31 líneas (4 en 32; `EventsLog` 51; `Components` 20; `FileManager` 18) | Bloque S |
| 13 controladores con el mismo `$isEdit = $id !== -1` | T114, T120 |
| 59 rutas `-actions-*`, 48 de ellas dos plantillas | T114 |
| 8 ramas gemelas idénticas en 4 módulos | T117 |
| Ningún generador de módulos en todo el árbol | Bloque S |

**El framework SÍ tiene un patrón de módulo. Lo que no tiene es un sitio donde ese patrón viva.**
Vive repartido en 24 copias, y una copia no se corrige: solo se vuelve a copiar. Por eso un
defecto nace ocho veces, un nombre que miente sobrevive años, y arreglar algo cuesta 24 ediciones.

## La mitad que ya está hecha

En el mismo archivo, dos métodos por encima del `staticResolver`:

```php
public static function staticRoute(string $segment = '')
{
    return get_router()->getContainer()->get('staticRouteModulesResolver')(
        self::class, $segment, __DIR__ . '/Statics', self::ENABLE
    );
}
```

La mitad que **construye la URL** ya está centralizada en el contenedor. La que **registra la
ruta** no. Alguien empezó a universalizar esto y se quedó a mitad. Y tras borrar
`serveModuleStatic()` (T115) el handler de los 24 quedó idéntico salvo `__DIR__`, que es
justamente lo que un resolutor universal resuelve solo.

## Generador contra base: son respuestas opuestas

- **Generador** — escupe el código dentro del módulo nuevo. Rápido al crear, y **fabrica la
  duplicación**: 24 copias es lo que produce un generador. Hoy hay lo peor de esta opción sin
  tenerla — la duplicación de un generador con la velocidad del copiar y pegar a mano.
- **Base y convención** — el módulo declara lo que lo diferencia y hereda lo que comparte. Se
  corrige una vez y llega a los 24. Su riesgo es el contrario: que no admita las excepciones y se
  acabe inventando una regla por tipo, que es la señal de que se comparan las cosas equivocadas.

**El criterio que las separa ya está medido**: lo idéntico byte a byte entre 24 copias va a la
base; lo que varía de verdad va al generador, y el generador solo emite lo que obligatoriamente
es un archivo — la carpeta, el mapper, el DDL.

## Lo que hace que no se degrade

> **Un patrón es evidente cuando apartarse de él rompe una puerta.** Hasta entonces es una
> costumbre, y una costumbre se degrada a la velocidad a la que se copia.

Un patrón que solo vive en documentación se pudre: los documentos no tienen puertas (LEY 14). La
comprobación 18 de ramas gemelas y el helper de S3 en `BaseController` son los dos primeros
ladrillos de esto, aunque sus commits no lo digan.

## Las dos trampas al universalizar los `staticResolver`

1. **`self::ENABLE`.** `staticRoute()` ya lo pasa. Un resolutor que lo pierda **cablea todos los
   módulos en encendido** en todos los clones. Es el caso literal ya documentado en
   `bin/phpstan.neon`.
2. **Las excepciones.** Si hace falta una regla especial para `EventsLog`, otra para `Components`
   y otra para `FileManager`, se están comparando las cosas equivocadas. Tiene que **admitir
   rutas extra** —varios módulos añaden su `globals-vars.css`—, no suponer uniformidad.

Y un detalle: uno de los 24 no vive en un `*Routes` sino en
`Importers/Controller/ImporterController.php`. Ese será el que chirríe.

## Requisito de entrada, y por qué no se diseña todavía

Lo que este trabajo necesita como insumo es **el censo de qué es idéntico y qué varía de verdad
entre los 24 módulos**, y ese censo es **E2-b**, que está a medias. Diseñar la base antes es
adivinar cuáles son las excepciones — y las excepciones deciden si la base sirve o si acaba con
una regla por tipo.

**La campaña ya está fabricando el insumo.** No hay que desviarla: hay que terminar E2-b sabiendo
que ese censo tiene ahora un segundo destinatario.

## Sin decidir, del PROPIETARIO

¿Fase propia de esta campaña, después de E6 y con el censo en mano, o campaña aparte que arranca
cuando esta cierre? Cambia dónde se escribe en la hoja de ruta, no lo que se hace ahora.
