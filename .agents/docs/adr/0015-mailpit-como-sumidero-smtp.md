# 0015 — Mailpit como sumidero SMTP local para las pruebas de correo

- **Estado:** Aceptada (sin implementar: es el lote 7c)
- **Fecha:** 2026-09-15
- **Decide:** Product Owner (2.7), con la verificación del arquitecto
- **Estructural:** sí (una herramienta externa entra en el flujo de pruebas)

## En cristiano

Para probar los correos del framework sin mandarlos a nadie se usa Mailpit. Es un programa que
hace de servidor de correo falso en la propia máquina: recibe los correos y los enseña en una
página web local, sin entregarlos. Se descarga como un único archivo, se arranca solo mientras
duran las pruebas y se apaga después. Es la capa 2 de la ventana de correo (lote 7c). La capa 3,
la entrega real, sigue yendo a Mailinator (ADR 0011).

## Contexto

- El lote 7c pide probar los 10 envíos de correo en tres capas (`18` T7):
  1. composición sin red;
  2. sumidero SMTP local;
  3. entrega real revisada a mano.
- **El PO, en 2.7 (2026-09-15)**, formalizado: que se usen las dependencias si son seguras y no
  implican registrarse en ningún sitio.
- **Verificado por el arquitecto** en el repositorio oficial (`github.com/axllent/mailpit`, su
  `LICENSE` y la API pública de versiones, el 2026-09-15):
  - licencia MIT (Ralph Slooten);
  - no pide cuenta ni registro;
  - un solo binario estático que corre sin root ni servicio;
  - desarrollo activo: última versión v1.31.1, del 2026-09-05, con 8 archivos, uno por
    plataforma (aquí, `mailpit-linux-amd64.tar.gz`).
  - **Por defecto escucha en `0.0.0.0`** (SMTP 1025 y web 8025).
  - **NO publica archivo de sumas de verificación ni firmas.**
  - **SIN VERIFICAR:** que no envíe telemetría. La página no la menciona.
- Medido también: en esta máquina no hay nada escuchando en los puertos 25, 465, 587, 1025 ni
  8025, y no hay `sendmail`, `postfix` ni `exim4`. La caída a `asGoDaddy()` no entregaría nada.

## Decisión

Se usa Mailpit, descargado como binario de su página oficial de versiones a `/tmp`, sin instalar
nada en el sistema.
- Se arranca solo durante las pruebas, **atado a `127.0.0.1`**
  (`--listen 127.0.0.1:8025 --smtp 127.0.0.1:1025`), y se para al terminar.
- Las pruebas apuntan el Mailer del framework a `127.0.0.1:1025` **en tiempo de ejecución**, sin
  tocar la configuración SMTP guardada.
- La versión y el `sha256` del binario descargado van en el reporte.

## Alternativas descartadas

| Alternativa | Por qué no |
| --- | --- |
| MailHog | Hace lo mismo, pero lleva años sin mantenerse. Mailpit es su sucesor de hecho |
| Solo Mailinator | Es entrega real con el SMTP real: no sirve para muchos envíos ni para correos con datos de prueba variados |
| Un servidor SMTP de Python improvisado | Es código propio que mantener, y no enseña el correo renderizado |
| Instalarlo con el gestor de paquetes o como servicio | Cambia el sistema del PO: prohibido (`40-salvaguardas.md` §3) |

## Consecuencias

- **Lo bueno:** se prueba el envío de punta a punta sin mandar nada fuera, y el correo se puede
  inspeccionar (HTML, cabeceras y adjuntos) con su API local.
- **Lo malo:**
  - es un binario de terceros ejecutado en la máquina del PO, y **el proyecto no publica sumas de
    verificación ni firmas**. El `sha256` que se apunta es el registro de lo que se descargó ese
    día, **no una verificación contra el origen**. Por eso se ata a `127.0.0.1` y se borra al
    terminar;
  - telemetría sin verificar. Si se detecta tráfico saliente, se para y se avisa.

## Reversión

1. Borrar el binario de `/tmp` y parar el proceso, si queda alguno.
2. Quitar de las pruebas la redirección del Mailer.

Completa y sin efecto sobre el repositorio, porque el binario no se versiona.

## Verificación

- El reporte del 7c lleva la versión, el `sha256`, la orden exacta de arranque (con `127.0.0.1`)
  y la comprobación con `ss -ltnp` de que solo escucha en local.
- El proceso ya no existe al terminar.

## Fe de erratas (2026-09-16, `#127`)

La decisión no cambia: Mailpit sigue siendo el sumidero SMTP de las pruebas. Cambian dos hechos que el
ADR dio por ciertos sin haberlos medido, y se añade una condición de arranque.

- **La descarga SÍ se puede verificar contra el origen.** El proyecto no publica un archivo de sumas,
  pero GitHub guarda un `digest` por cada archivo de una release, y se consulta en
  `https://api.github.com/repos/axllent/mailpit/releases/tags/<versión>`. Medido para la v1.31.1:
  `mailpit-linux-amd64.tar.gz`, 10689714 bytes, `sha256:87d2652abd7c17dc99029147ff62ac671afdf7fd76630f7faf5b14125211c709`,
  que coincide con lo descargado. No es una firma del autor, pero sí prueba que se descargó lo que GitHub
  sirve para esa release. Desde ahora, **la verificación compara el `sha256` con ese `digest`**.
- **Mailpit sale a la red por defecto para comprobar si hay versión nueva.** Lo delató su propia API:
  `GET /api/v1/info` devolvió `"LatestVersion":"v1.31.1"`, un dato que solo se obtiene preguntando fuera
  de la máquina. El subcomando `mailpit version` también muestra esa información. No es telemetría de
  uso ni se enviaron correos, pero es tráfico saliente. **Condición nueva de arranque:** siempre con
  `--disable-version-check`, y comprobando que `/api/v1/info` trae `"LatestVersion":"disabled"`, que es la
  forma en que Mailpit dice que no preguntó (medido en `#129`). Cualquier otro valor, una versión, es que
  salió a la red. El subcomando `version` no se vuelve a usar.
- **Funciones que salen a la red si se usan**, según `mailpit --help`: el comprobador de enlaces y las
  capturas de HTML de la interfaz (`--allow-internal-http-requests`, `--allow-untrusted-tls` y
  `--disable-link-check-rate-limit`). Solo se disparan desde la interfaz o su API; las pruebas no las
  llaman.

La orden de arranque correcta es:

```
./mailpit --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --disable-version-check
```
