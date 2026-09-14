# EventsLog: robustecerlo, y antes decidir que es

*Pedido por el PROPIETARIO el 2026-09-02: «robustecer esa solucion de EventsLog, desde una
estetica consistente porque ya esta desfasada hasta posibilidad de rotacion previa exportacion y
otras cosas como vistas de detalle y que la tabla no se cargue tanto».*

*Medido por ARQUITECTO el mismo dia. **Lo medido dice que esto no es una mejora: es terminar un
modulo que nunca se termino.***

---

## LO MEDIDO

**El modulo entero son 1.133 lineas** en 13 archivos: un mapper, un controlador, un helper, UNA
vista (`Views/log/list.php`), un JS y un SCSS. **No hay vista de detalle.** No hay rotacion, ni
exportacion, ni purga: `grep -i "purge|rotat|retention|DELETE FROM|truncate"` sobre el modulo da
**cero**.

**HAY TRES REGISTROS EN EL FRAMEWORK Y NO SE HABLAN ENTRE ELLOS:**

| Registro | Donde escribe | Cuantos escriben |
| :-- | :-- | --: |
| `log_exception()` (`AppHelpers:2695`) | ARCHIVO PLANO | **181 llamadas** |
| `actions_log` via `LogsMapper` | base de datos | **1 productor**: `APIController` |
| `Mailer::$log` | MEMORIA, muere con la peticion | 1 lector, y devuelve al cliente |

**Ese es el hallazgo, y es mayor que la estetica**: el modulo que se llama «registro de eventos»
tiene UN productor, mientras 181 sitios escriben a un archivo plano que ninguna vista lee.
Su propio catalogo —`MSG_UPDATE_PROFILE`, `MSG_REQUEST_PASSWORD_RECOVERY`, `MSG_GENERIC`…—
demuestra que se penso para mas y se quedo a medias.

**LA TABLA, `actions_log`:**

```sql
`textMessage` text NOT NULL,
`textMessageVariables` longtext NOT NULL,
`referenceColumn` text DEFAULT NULL,
`referenceValue` text DEFAULT NULL,
`referenceSource` text DEFAULT NULL,
`createdBy` bigint(20) NOT NULL,
`createdAt` datetime NOT NULL,
`meta` longtext DEFAULT NULL,
PRIMARY KEY (`id`),
KEY `createdBy` (`createdBy`),
CONSTRAINT ... FOREIGN KEY (`createdBy`) REFERENCES `pcsphp_users` (`id`)
) ... COLLATE=utf8mb4_bin;
```

Cinco cosas, y las cinco explican por que «la tabla se carga»:

1. **`createdBy NOT NULL` con clave ajena.** Lo que no tiene usuario NO SE PUEDE REGISTRAR: cron,
   tareas de CLI, y cualquier cosa anterior al login. **Es la causa raiz del unico productor.**
2. **Sin indice por `createdAt`.** Un log se ordena y se filtra por fecha SIEMPRE, y aqui eso es
   un recorrido completo. En una tabla que solo crece.
3. **Las columnas de referencia son `text`**, que no se indexa sin prefijo. Filtrar por referencia
   tambien recorre.
4. **Todo texto libre**: no hay columnas tipadas sobre las que filtrar. El «meta» es `longtext`.
5. **`utf8mb4_bin`**: la comparacion distingue mayusculas y acentos. Buscar `perez` no encuentra
   `Pérez`.

---

## DECIDIDO POR EL PROPIETARIO — 2026-09-02: **(b), TRES REGISTROS Y UN MISMO RIGOR**

Hubo un rodeo por (a) y merece quedar escrito, porque el rodeo aclaro el diseno. Decision final,
en sus palabras:

> *«Es (b), son registros generados por el flujo de un usuario. Porque la vista de log de
> "errores" es algo que tenemos en el horizonte, por cierto con el mismo rigor de visualizacion
> amigable, rotacion, etc.»*

**TRES REGISTROS, CADA UNO CON SU ESQUEMA:**

| Registro | Que guarda | Estado hoy |
| :-- | :-- | :-- |
| **Acciones** — `actions_log` / EventsLog | lo que hace un USUARIO en su flujo | 1 productor, 5 mensajes en catalogo |
| **Errores** | lo que falla | **181 `log_exception()` a ARCHIVO PLANO**, ninguna vista |
| **Correo** | lo que sale por las cuatro vias | no existe |

**Y LA FRASE QUE LO ORDENA TODO ES «EL MISMO RIGOR».** Visualizacion amigable, rotacion con
exportacion previa, vista de detalle, filtros. Tres veces.

> **CONSECUENCIA DE DISENO, Y ES LA UNICA QUE (b) OBLIGA A RESOLVER: SIN PUERTA COMUN, (b) ES
> TRES VECES EL MISMO TRABAJO.** Lo que se comparte NO es la tabla —esa fue la equivocacion de
> ARQUITECTO al proponer (a)— sino **el mecanismo**: un solo componente de listado con filtros,
> una sola vista de detalle parametrizada, y **una sola tarea de rotacion** en `bin/cli` que
> reciba que tabla rota, con que politica y donde exporta. Tres esquemas, un instrumental.

**LO QUE SOBREVIVE DEL RODEO POR (a)**, porque no dependia de la unificacion:

- **Indice por `createdAt`.** Hoy el unico indice es `createdBy`, y un log se filtra por fecha
  siempre. Vale para los tres.
- **Columnas tipadas para lo que se filtra**, y JSON solo para lo que no. En MariaDB 10.11 lo que
  se filtre dentro del JSON puede promoverse a columna generada `STORED` e indexarse.
- **`utf8mb4_bin`**: buscar `perez` no encuentra `Pérez`. Vale para los tres.
- **Paginacion por clave, no por `OFFSET`.**
- **Las dos lecturas de la misma fila** —la humana, con el catalogo de mensajes traducible que YA
  EXISTE (`LogsMapper:93`), y la tecnica, que es la vista de detalle que falta—. Vale para los
  tres, y es literalmente «claro para devs y para usuarios finales medios/avanzados».
- **`correlationId`**: sin el, un error, la accion que lo provoco y el correo que no salio son
  tres filas en tres tablas sin nada que las ate. **Con (b) esto pasa de comodidad a
  REQUISITO.**

**LO QUE CAMBIA RESPECTO DE (a):**

- **`createdBy NOT NULL` SE QUEDA en acciones**, y ahora esta justificado: si no hay usuario, no
  es una accion de usuario y va a otro registro. La decision del PROPIETARIO **elimina** el
  problema en vez de aflojarlo. `actorType` deja de hacer falta aqui.
- **PERO LA CLAVE AJENA NECESITA POLITICA.** `createdBy` referencia `pcsphp_users(id)` sin
  `ON DELETE` declarado: **borrar un usuario borra o bloquea su historial de acciones**, que es
  lo contrario de para lo que existe un registro de acciones. Se decide: `ON DELETE SET NULL` con
  el nombre copiado en la fila, borrado logico del usuario, o restriccion explicita. **Sin
  decidirlo, el registro miente el dia que se borra a alguien.**
- **`MSG_REQUEST_PASSWORD_RECOVERY` ya esta en el catalogo** y ocurre SIN SESION, aunque el
  usuario exista y se conozca por su correo. Es el caso frontera de (b) y hay que mirarlo: o el
  actor se resuelve por correo antes de escribir, o ese mensaje pertenece a otro registro.

## LO QUE HABRIA QUE HACER, y el orden importa

**LA DECISION YA ESTA TOMADA: (a).** Lo que sigue se ordena bajo esa decision.

**DESPUES, lo que el PROPIETARIO enumero:**

- **Estetica consistente.** Un SCSS de 2019 contra el resto del panel.
- **Vista de detalle.** Hoy solo hay lista.
- **Que la tabla no se cargue**: indice por fecha, columnas tipadas para lo que se filtra, y
  paginacion por clave y no por `OFFSET` —que en una tabla grande empeora segun avanzas—.
- **Rotacion CON EXPORTACION PREVIA**, y en este orden: se exporta, se comprueba el archivo, y
  solo entonces se borra. Lo natural es `bin/cli`, junto a `scheme-create` y `snapshot`, que es
  donde ya viven las tareas de mantenimiento. **Y su politica de retencion escrita**, que es la
  pregunta que nadie hace hasta que la tabla pesa cuatro gigas.
- **Y lo que ARQUITECTO anade**: quien puede VER el registro. Un registro de eventos es un mapa
  de la actividad de todo el mundo, y hoy su control de acceso no se ha mirado.

---

## TAMANO

**TRES bloques**, y el corte lo da (b): (1) **el instrumental comun** —listado con filtros,
vista de detalle, y la tarea de rotacion con exportacion previa en `bin/cli`—, que es lo unico
que evita hacer el trabajo tres veces; (2) **acciones**, poniendo al dia `actions_log`, sus
indices, la politica de la clave ajena y sus productores; (3) **errores**, dandole tabla y vista
a los 181 `log_exception` que hoy van a archivo plano. **El correo tiene su propia entrada de
roadmap** y usa el mismo instrumental. Va **despues de la MAJOR**: EXTIENDE.

**Salvo una cosa, que CORRIGE y por tanto no espera**: `createdBy NOT NULL` no es una decision de
diseno, es un impedimento — y es la razon medible de que un modulo del framework lleve anos con
un solo productor.
