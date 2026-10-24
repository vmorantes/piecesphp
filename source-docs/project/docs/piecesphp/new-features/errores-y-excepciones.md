# Errores y excepciones

Cómo falla una aplicación hecha con PiecesPHP, qué ve quien la usa y qué queda anotado. Desde la `9.0.0` hay **dos
clases de error**, y elegir bien cuál lanzas es la mitad del trabajo.

## Graves y suaves

| | Graves | Suaves |
| :-- | :-- | :-- |
| **Qué son** | Excepciones que nadie captura, `E_ERROR`, `E_PARSE`, `E_RECOVERABLE_ERROR`, `E_USER_ERROR` | `E_WARNING`, `E_NOTICE`, `E_DEPRECATED` (y `E_USER_DEPRECATED`), y lo que anotas con `log_soft_error()`; también `E_USER_WARNING` y `E_USER_NOTICE`, que no rompen ni en local |
| **En producción** | La petición termina: página o JSON 500 con la referencia | **La petición sigue**; queda anotado con su referencia |
| **En local** | Igual, y además ves el detalle | **Rompe, como un grave**, para que lo arregles (salvo `log_soft_error()`, que nunca rompe) |
| **Se anota** | Siempre, una entrada por vez | Una entrada por firma (tipo, archivo, línea) y día, con un contador de repeticiones |

**La regla para elegir**: si seguir adelante deja algo a medias o mal hecho, lanza una excepción (grave). Si lo que falla
no impide terminar bien, anótalo como suave.

## Lanzar una excepción

Usa la de PHP que diga lo que pasó (`\InvalidArgumentException`, `\RuntimeException`, `\DomainException`…) o la base
del framework, `PiecesPHP\Core\Exceptions\BaseException`, cuando quieras adjuntar datos para quien investigue:

```php
use PiecesPHP\Core\Exceptions\BaseException;

$exception = new BaseException('El importador no reconoce la columna «fecha_campo»');
$exception->setExtraData(['archivo' => $nombreArchivo, 'columna' => $columna]);
throw $exception;
```

**Envuelve, no tapes**: si capturas una excepción para lanzar otra más clara, pásale la original como `previous`. El
registro guarda la cadena entera, con la traza de cada una:

```php
try {
    $cliente->enviar($datos);
} catch (\Throwable $e) {
    throw new \RuntimeException('No se pudo enviar la solicitud al proveedor', 0, $e);
}
```

**Para responder 403 o 404 a una petición** no lances una excepción genérica: usa las respuestas del enrutador
(`NotFoundException` de `PiecesPHP\Core\Routing\Slim3Compatibility\Exception`) o deja que los permisos de la ruta hagan su
trabajo. Un 404 o un 403 no es un error del sistema.

## Anotar algo suave

```php
$referencia = log_soft_error('El proveedor de mapas no respondió; se pinta sin teselas', [
    'proveedor' => 'mapbox',
    'estado' => $codigoHttp,
]);
```

Devuelve la referencia de la entrada, y `''` si no se pudo anotar. **No rompe nunca**, tampoco en local. Dos llamadas
desde la misma línea el mismo día cuentan como una sola entrada con su contador, y se queda el primer mensaje.

## Qué ve quien usa la aplicación

- **Fuera de local**, una excepción que nadie captura solo enseña un mensaje genérico y su **referencia**
  (`ERR-20261010-A1B2C3`); en JSON, `{"success": false, "message": "…", "reference": "ERR-…"}`. Ni el mensaje, ni el
  archivo, ni la traza.
- **En local**, todo.

Pídele a quien te reporte un fallo **la referencia**. Con ella, en **Registros › Errores** (solo root), la escribes en el
buscador o abres `?tab=errors&ref=ERR-…`, y tienes la entrada entera: tipo, mensaje, archivo y línea, la traza, las
excepciones previas, los datos adicionales y el contexto de la petición (método, ruta, dirección y usuario).

## Lo que NUNCA va en un mensaje ni en los datos adicionales

Contraseñas, tokens, claves de API, el contenido de un formulario, datos personales de terceros. El registro lo lee root
y se copia en los respaldos. El contexto de la petición se guarda **sin** la cadena de consulta, sin cabeceras y sin
cuerpo, precisamente por eso; no lo metas tú a mano.

## Dónde queda

- `src/app/logs/error.log.jsonl`: una entrada por línea; solo crece, se escribe con cerrojo y rota por tamaño a
  `logs/olds/`.
- `src/app/logs/error-traces/`: cada traza distinta, guardada una vez y nombrada por su huella.
- `src/app/logs/error-soft/`: los contadores de los suaves, uno por firma y día.
- **Cuánto duran**: la tarea diaria «Purgar registros viejos» borra los contadores de suaves de más de 30 días
  (`GenericHandler::SOFT_RETENTION_DAYS`) y las trazas de más de 90 (`TRACE_RETENTION_DAYS`). Una entrada cuya traza
  ya se purgó se sigue abriendo, sin la traza.
- `bin/cli clean-logs` vacía todo lo anterior de una vez.

Root ve además un aviso flotante, «Hay N errores nuevos desde tu última visita», que lleva a Registros › Errores; se
oculta desde Avisos del sistema.
