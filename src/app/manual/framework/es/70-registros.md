---
titulo: Registros
rutas: [logs-admin-index, actions-logs-admin-list, system-status-mail-log]
---

# Registros

Aquí consultas lo que el sistema anota: qué pasó, quién lo hizo y cuándo. Solo ves las partes que tu tipo de cuenta
tiene permitidas.

## Registros
<!-- ve: logs-admin-index -->

Se llega por el botón de engranajes de la barra superior, **«Configuración del sistema» › «Registros y Logs»**,
y la entrada del registro que buscas: **«Errores»**, **«Intentos de ingreso»**, **«Registro de ingresos»**,
**«Usuarios sin ingresos»**, **«Correos enviados»**, **«Tokens y códigos»**, **«Tareas programadas»** o
**«Todos los registros»**. **«Sesiones abiertas»** está en **«Gestión de usuarios»**.

![Registros](img/logs-admin-index.webp)

La pantalla se llama **Registros** y trae una fila de pestañas: **Errores**, **Correos enviados**, **Accesos**,
**Sesiones abiertas**, **Tokens y códigos** y **Tareas programadas**. Cada pestaña muestra un tipo de anotación, y
empieza con su título y una frase que dice qué ves en ella.

### La pestaña Errores

Los errores aparecen agrupados por día, el más reciente primero, con el día como título. Cada anotación muestra la
hora, su gravedad, una referencia, el tipo de error, el mensaje y el archivo donde ocurrió.

- El botón junto a la referencia, **«Copiar referencia»**, la copia para que puedas pegarla donde la necesites; durante
  un momento dice **«Copiado»**.
- **«Detalle»** despliega todo lo que se anotó del error: la petición, los datos adicionales, la traza y el mensaje
  completo con su ruta. Si buscas un error por su referencia, el detalle sale abierto.
- Si no hay ningún error anotado, la pestaña lo dice; si hay errores pero ninguno cumple los filtros, también.

Puedes **«Filtrar»** con estos campos y **«Limpiar»** para volver a ver todo:

| Campo | Obligatorio | Para qué |
| --- | --- | --- |
| Referencia | No | Buscar un error por su referencia. |
| Texto (mensaje o archivo) | No | Buscar por una parte del mensaje o del nombre del archivo. |
| Tipo de excepción | No | Quedarte con un tipo de error. |
| Nivel | No | Quedarte con una gravedad. |
| Día | No | Ver solo un día. |
| Otros archivos | No | Cambiar de registro de errores. |

Al final de la lista, **«Más antiguos»** carga la página siguiente.

### La pestaña Sesiones abiertas

Las sesiones que siguen abiertas, la que empezó más tarde primero. Solo ves las de las cuentas sobre las que tienes
autoridad, y las tuyas.

Puedes **«Filtrar»** con estos campos y **«Limpiar»** para volver a ver todo:

| Campo | Obligatorio | Para qué |
| --- | --- | --- |
| Usuario | No | Buscar por una parte del usuario o del correo. |
| Iniciadas desde | No | Ver solo las que empezaron ese día o después. |

La tabla muestra **Usuario / correo**, **Navegador**, **IP**, **Inicio**, **Última actividad**, **Caduca** y
**Acciones**. Junto al usuario, la etiqueta **«Actual»** marca la sesión desde la que estás mirando, y **«Otras
sesiones»**, las demás sesiones tuyas. En cada fila:

- el botón rojo dice **«Cerrar sesión»** si la sesión es tuya y **«Expulsar»** si es de otra persona; las dos cierran esa
  sesión, después de confirmar;
- **«Extender»** (el botón del calendario) alarga su caducidad. Solo aparece si tu tipo de usuario puede hacerlo sobre
  esa cuenta.

### La pestaña Tokens y códigos

Dos tablas, cada una en su bloque:

- **Vigentes**: los códigos, enlaces y secretos de acceso que todavía sirven, con **Tipo**, **Usuario**, **Creado**,
  **Caduca** y **Acciones**. Las acciones son siempre tres y en el mismo orden; la que no aplica a esa fila se ve
  apagada. **«Restablecer doble factor»** (la persona entrará solo con su contraseña hasta que lo active de nuevo) e
  **«Invalidar ahora»** (el código deja de servir) van en rojo; **«Mostrar»**
  enseña el valor de un código y queda anotado. Cada una te pide confirmación.
- **Historia**: lo que pasó con ellos, con **Fecha**, **Evento**, **Usuario** y **Quién**.

### La pestaña Accesos

Tiene tres vistas: **Intentos de Ingresos**, **Registro de ingreso** y **Usuarios sin Ingreso**. Solo cuentan los
usuarios sobre los que tienes autoridad.

En **Intentos de Ingresos**:

- Arriba eliges el **periodo**: **7**, **14** (el de entrada), **30** o **90 días**, que acaban hoy. Al lado, las fechas
  del periodo y **«Exportar»**, que descarga los intentos del periodo y del filtro que tengas elegido.
- Cuatro cifras del periodo: **Intentos totales**, con cuánto cambió frente al periodo anterior de la misma longitud;
  **Exitosos** y **Fallidos**, con su porcentaje; y **Usuarios únicos**, los usuarios distintos que intentaron entrar.
- La gráfica **Intentos por día**: los fallidos abajo y los exitosos encima, un punto por día, también los días sin
  intentos.
- Sobre la tabla, **Todos**, **Exitosos** y **Fallidos**, con su cifra, filtran la tabla sin recargar la página.
- La tabla muestra **Indicador**, **Usuario ingresado**, **Información**, **Vía**, **IP** y **Fecha**. Los intentos
  fallidos salen en rojo; un ingreso correcto dice **«Ingreso exitoso»**. La fecha dice cuánto hace («hace 3 horas»);
  la fecha exacta sale al pasar el ratón.

**Registro de ingreso** y **Usuarios sin Ingreso** llevan su propio periodo (7, 14, 30 o 90 días) y su **«Exportar»**:

- **Registro de ingreso**: cuántos usuarios entraron en el periodo y cuántos no, con su porcentaje del total, y
  **Horas totales**, el tiempo en la plataforma desde que hay registro (no se guarda por día, así que no cambia con el
  periodo). La tabla lista a quien entró en el periodo: **ID**, **Usuario**, **Nombre**, **Último acceso** (el más
  reciente primero) y **Tiempo en plataforma** («Sin registro» si no hay).
- **Usuarios sin Ingreso**: cuántos no entraron en el periodo y cuántos sí, con su porcentaje, y la tabla de quienes no
  entraron: **ID**, **Usuario** y **Nombre**. Si todos entraron, la pantalla lo dice.

### Las otras pestañas

La pestaña **Tareas programadas** existe en la pantalla, pero este manual todavía no describe su contenido.

## Correos enviados
<!-- ve: system-status-mail-log -->

Es la pestaña **Correos enviados** de **Registros**. Lista los correos que la plataforma envió o intentó enviar. El
cuerpo se guarda cifrado; los adjuntos no se guardan.

![Correos enviados](img/system-status-mail-log.webp)

Arriba, el resumen de las últimas 24 horas: cuántos se entregaron, cuántos quedaron en el buzón de pruebas y cuántos no
llegaron (en rojo si hay alguno).

| Columna | Qué muestra |
| --- | --- |
| Fecha | Cuándo se envió. |
| Destinatarios | A quién iba. |
| Asunto | El asunto del correo. |
| Origen | Qué parte del sistema lo envió. |
| Entrega | Cómo se entregó. |
| Resultado | Si salió bien o falló. |
| Cuerpo | **«Ver cuerpo»** abre el correo en una ventana; **«Cerrar»** o la tecla Esc la cierran. |

Con **«Filtrar»** y **«Limpiar»** acotas la lista; **«Exportar listado actual»** descarga lo que estás viendo.

| Campo | Obligatorio | Para qué |
| --- | --- | --- |
| Desde | No | Primera fecha del rango. |
| Hasta | No | Última fecha del rango. |
| Resultado | No | Quedarte con los que salieron bien o con los que fallaron. |
| Entrega | No | Quedarte con una forma de entrega. |
| Origen | No | Buscar por la parte del sistema que lo envió. |

## Registro de actividad
<!-- ve: actions-logs-admin-list -->

Se llega por **«Últimos movimientos»**, en el menú lateral. Responde a «quién hizo qué en el panel, y cuándo».

![Registro de actividad](img/actions-logs-admin-list.webp)

La tabla muestra: **#**, **Módulo**, **Evento**, **Usuario**, **IP**, **Fecha** y **Acciones**. En cada fila,
**«Ver detalle»** abre el detalle de ese movimiento.

Puedes **«Filtrar»** y **«Limpiar»**, y **«Exportar listado actual»** descarga lo que estás viendo.

| Campo | Obligatorio | Para qué |
| --- | --- | --- |
| Desde | No | Primera fecha del rango. |
| Hasta | No | Última fecha del rango. |
| Tipo | No | Quedarte con un tipo de movimiento. |
| Usuario | No | Ver los movimientos de una persona. |
