# 0010 — El lote 4 se cierra, aparecen tres fugas graves y el repositorio pasa a LF

- **Fecha:** 2026-09-15
- **Mensajes:** `#049`–`#062`
- **Tramo:** `estado/tramos/2026-09-15-1022-lote-4-y-estudio-4b.md`

## Qué se pidió

- **El mapa hasta la MAJOR**, con el tirón de 20 rondas sin el PO. Lo autorizó en sus respuestas
  2.1-2.9, en las que además decidió los lotes 3b, 4b, 4c, 5 y 5b, y el correo con Mailpit.
- **Pasar a LF los cinco repositorios** (ADR 0012), tras preguntar el PO por qué había tantos
  problemas con los finales de línea.

## Qué se encontró que no se esperaba

1. **Una prueba rota dentro de un commit** (`0bff44c4`): la verificación se corrió antes del
   último cambio, que era instrumental. Lo cazó el propio coder en el PASO 0 siguiente. Ahora
   la regla 30 exige verificar después del último cambio.
2. **Tres fugas graves, al medir H1 (SQL y filas crudas en los listados):**
   - **los informes de accesos mandaban el hash bcrypt de cada contraseña.** Causa:
     `UsersModel::fieldsToSelect()` traía la tabla entera. Arreglado en la raíz (`ae869246`),
     con un censo de 44 accesos a `password` por tokens;
   - **`/users/all/` daba el hash de todos a cualquiera con sesión**, confirmado con un usuario
     general. Tenía su propio `SELECT` y no pasaba por lo arreglado (`445713f9`). Lo encontró el
     barrido de 129 rutas de `#055`: un arreglo en la raíz no garantiza que no haya otra raíz;
   - **`app_key` viene con un texto de relleno público** y firma las sesiones. Todo clon que no
     la cambie tiene sesiones falsificables. Es núcleo transversal: espera al PO.
3. **La ruta HTTP del cron falla abierta** sin `secure-keys/cronjob`, porque la clave vacía se
   iguala a la cabecera ausente. Va en el 4b-1.
4. **Cambiar la política de eol deja el índice con el `stat` sucio:** 1.511 archivos marcados,
   9 con contenido. Arreglo: `add --renormalize` de lo que no cambia de contenido.
   **`normaliza-eol` fallaba con rutas no ASCII** porque git las cita: pasa a `-z`.
5. **El login escribe `organization = -10`** a un usuario sin organización. Va a los residuos.

## Qué se instruyó

- `#051`-`#054`: LoginAttempts y SystemApprovals por marcador, y la medición de H1.
- `#055`-`#058`: las fugas de contraseñas.
- `#059`-`#062`: LF.
- Mientras el coder trabajaba, el arquitecto dejó verificados contra el código los diseños de
  3b, 4c, 4b, 5 y 5b, con exploradores en solo lectura y su propia lectura de las líneas que se
  dictan.

## Qué quedó fuera

- H2, el SQL y `rawData` en los 21 listados, que es núcleo transversal y espera al PO.
- `app_key`, que espera al PO.
- Quién puede pedir `/users/all/`, pregunta de producto.

## Qué se aprendió

- **Una fuga se cierra con un barrido, no con un arreglo.** El de `fieldsToSelect()` era
  correcto, y aun así quedaba otra ruta con su propio `SELECT`.
- **Un dato vivo entre dos fotos no es una diferencia del código.** `time_on_platform` y el
  `organization` que escribe el login la produjeron. El coder repitió en una ventana de 3
  segundos, cambiando el archivo servido, y la segunda pasada salió limpia.
- **Una herramienta de git que lee rutas usa `-z`**, o fallará el día que aparezca una tilde.
