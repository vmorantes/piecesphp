# Registro de sesiones caducadas

Cuando llega una petición con el token de sesión **ya vencido**, el framework puede dejar una línea de registro para
que usted pueda depurar una renovación de sesión. **Está apagado de serie, y no escribe nada hasta que lo enciende.**

> **Si viene de la `v7.x`:** esa versión guardaba su registro en otro formato, en `src/app/logs/expired-sessions/`.
> Esos archivos **no se borran al actualizar** —no nos corresponde borrar datos de su instalación—, pero
> **conviene vaciar esa carpeta**: contienen datos de sesión. `bin/cli clean-logs` ya no los toca: los cuenta y
> se lo dice.

---

## 🔧 Encenderlo

Una opción de configuración, falsa por omisión:

```php
// src/app/config/extensions/…  (o donde su clon fije sus opciones)
set_config('log_expired_sessions', true);
```

**Tiene que ser el booleano `true`.** Una cadena `"1"` no lo enciende, a propósito: una configuración mal escrita no
debe ponerse a registrar sin que nadie lo haya pedido.

---

## 📄 Qué escribe

Una línea por petición en `src/app/logs/expired-sessions.log`, con el formato de los demás registros de la casa:

```
[2026-10-02 12:02:38.684923] [usuario 186760] [ruta admin] [ip ::1] [iat 2026-10-02 12:02:38] [exp 2026-10-02 11:02:38] [renovable no] /mi-proyecto/admin/
```

| Campo | Qué es |
| --- | --- |
| `usuario` | El id que traía el token, o `anónimo` si no traía ninguno |
| `ruta` | El nombre de la ruta pedida (el mismo que identifica su permiso) |
| `ip` | De quién venía la petición |
| `iat` / `exp` | Cuándo se emitió el token y cuándo venció, en fechas legibles |
| `renovable` | Si esa ruta está en la lista de las que renuevan un token caducado |
| (al final) | La URL pedida |

**El token NO se escribe nunca**, ni entero ni troceado, ni su `aud` ni su `data`. La clase que escribe el registro
**no recibe el token**: no puede escribirlo aunque alguien se despiste. Y la URL se sanea antes de escribirla: si
llevara algo con forma de JWT, se sustituye por `«token-omitido»` (con las comillas angulares).

---

## ♻️ No crece sin fin

El registro **rota por tamaño**: al pasar de 1 MB se mueve a `expired-sessions.log.1` y empieza uno nuevo. **Nunca hay
más de dos archivos.** `bin/cli clean-logs` los vacía.

> **Por qué cambió:** el formato viejo recorría la carpeta entera **en cada petición** con el token caducado, para
> borrar lo de más de 30 días. Medido en una instalación con 574 archivos: **1,78 ms por petición**, y creciendo con el
> historial, para borrar **cero** archivos (ninguno llegaba a los 30 días). El registro nuevo cuesta 0,005 ms, y
> apagado no toca el disco.

---

## 🧩 Para quien programa

`PiecesPHP\Core\Logs\ExpiredSessionsLog`, en `src/app/core/psr4/PiecesPHP/Core/Logs/`. Lo llama `src/index.php` cuando
detecta la sesión caducada; no hace falta llamarlo a mano.

> ⚠️ **Si toca esa parte de `index.php`:** la decisión de **revocación de sesiones** necesita el token caducado ya
> decodificado. Hoy el token caducado se decodifica siempre, al margen de que el registro esté encendido o apagado, y
> la revocación lee de ahí. **No cuelgue esa decisión de algo que solo existe cuando se está registrando.**
