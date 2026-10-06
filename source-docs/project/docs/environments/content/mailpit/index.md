# Mailpit — el correo de desarrollo

**Qué es.** Un servidor de correo falso: acepta cualquier envío por SMTP y, en vez de entregarlo,
lo guarda y te lo enseña en una página web. Sirve para probar los correos de una instalación de
desarrollo sin mandarle nada a nadie de verdad.

**Por qué lo usamos.** Probar correo de otra forma significa, o bien tener credenciales reales de
un buzón (y arriesgarse a escribirle a una persona), o bien no probarlo. Con Mailpit se ve el
correo tal como sale: asunto, remitente, destinatarios, HTML, texto plano y adjuntos.

## 1. Instalarlo sin tocar el sistema

Todo en tu usuario, sin `sudo`:

```bash
mkdir -p ~/.local/bin
curl -sL https://raw.githubusercontent.com/axllent/mailpit/develop/install.sh | bash -s -- ~/.local/bin
~/.local/bin/mailpit version
```

Para que arranque solo con la sesión, un servicio de usuario en
`~/.config/systemd/user/mailpit.service`:

```ini
[Unit]
Description=Mailpit
After=network.target

[Service]
ExecStart=%h/.local/bin/mailpit --listen 127.0.0.1:8025 --smtp 127.0.0.1:1025 --database %h/.local/share/mailpit/mailpit.db --disable-version-check
Restart=on-failure

[Install]
WantedBy=default.target
```

```bash
mkdir -p ~/.local/share/mailpit
systemctl --user daemon-reload
systemctl --user enable --now mailpit
systemctl --user status mailpit
```

- **Web:** <http://127.0.0.1:8025>
- **SMTP:** `127.0.0.1:1025`, sin usuario ni contraseña y sin cifrado.
- Escucha solo en `127.0.0.1`: nadie de fuera llega a él.
- **`--disable-version-check`** impide que Mailpit consulte en internet si hay una versión nueva. **Las suites de
  correo del framework lo exigen** —sin él paran antes de enviar, con «Mailpit no comprueba versiones»—, porque un
  entorno de pruebas no debe salir a la red. *(Corregido el 2026-10-05: esta guía arrancaba Mailpit sin esa opción, y
  con ella las suites fallaban.)* Si ya lo tenía arrancado, añádala a su servicio y reinícielo:
  `systemctl --user daemon-reload && systemctl --user restart mailpit`.
- La base guarda los mensajes entre reinicios. Si la borras, se borran los correos.

## 2. Usarlo con PiecesPHP: la entrega del correo

!!! warning "Esta sección cambió el 2026-10-05"

    Hasta el 2026-10-03 la decidía un **modo de pruebas** (Automático, Siempre encendido, Apagado), y
    así lo describía esta página. **Ese modo ya no decide nada**: lo sustituye la **entrega del
    correo**, que se declara y que, si no se declara, **retiene**.

No hace falta tocar la configuración SMTP real. En el panel, **Integraciones → Correo**, apartado
«Entrega del correo y sumidero de pruebas», el campo **Entrega del correo** (`mail_delivery`) tiene
tres valores:

| Valor | Qué hace |
| :-- | :-- |
| **Según el entorno (sin declarar, se retiene)** — de fábrica | Retiene si la instalación es `local` y envía de verdad si es `production`. **Y si no existe `src/app/config/environment.php`, retiene** |
| **Retenido: no sale de esta máquina** | Sea cual sea el entorno |
| **Real: sale por el SMTP de arriba** | El correo sale por el SMTP configurado en la misma pantalla |

**Retenido significa esto, en este orden:**

1. El correo va al **servidor de pruebas** de la misma pantalla (`127.0.0.1:1025` de fábrica, sin
   autenticación ni cifrado), que es Mailpit.
2. **Si Mailpit no está escuchando, el correo no se pierde ni sale**: se guarda como archivo `.eml`
   en `src/app/logs/mail-outbox/`, y el envío **no cuenta como fallo**. Puedes trabajar sin Mailpit
   arriba y abrir esos `.eml` con cualquier cliente de correo.
3. **Nunca cae al correo del sistema** (`mail()` o `sendmail`), aunque el SMTP no responda.

**Cada correo lleva la cabecera `X-Tags`** con el nombre de la instalación: Mailpit agrupa por ella,
así que una misma Mailpit puede servir a varios clones y cada uno se ve por separado.

**Los avisos que lo vigilan**, en «Avisos del sistema», para el usuario principal:

| Aviso | Cuándo salta |
| :-- | :-- |
| `mail-sin-declarar` | No existe `environment.php`: la instalación no ha dicho qué es, y por eso retiene |
| `mail-retenido-en-produccion` | Es `production` y la entrega es «Retenido»: tus usuarios no reciben nada |
| `mail-real-en-local` | Es `local` y la entrega es «Real»: el correo sale de tu máquina de desarrollo |

Desde la terminal, el diagnóstico completo:

```bash
bin/cli mail-doctor
```

Dice el entorno, la entrega declarada y la que se aplica de verdad, si Mailpit responde, cuántos
mensajes hay en el buzón en disco y el resumen del registro de correos. **No toca la red.** Con
`smtp=yes` abre además una conexión al SMTP configurado y la corta sin enviar nada.

## 3. Comprobar un envío

1. Haz la acción que manda el correo (recuperar contraseña, alta, formulario de contacto…).
2. Abre <http://127.0.0.1:8025> y ábrelo: verás el HTML tal como llega, el texto plano, las
   cabeceras y los adjuntos.
3. Para vaciar el buzón, el botón «Delete all» de la propia web.
4. **Para tener algo que mirar sin hacer cada flujo a mano**, `bin/cli mail-demo` manda un correo de cada plantilla
   a `demo-correos@localhost.test`; búsquelos en Mailpit con `to:demo-correos@localhost.test`.
5. **Cada envío queda además en el registro de correos** del panel (**Sistema → Registro de correos**): a
   quién, con qué asunto, desde qué archivo y línea, y si llegó, quedó en el buzón en disco o no llegó.

Desde la terminal, sin navegador:

```bash
curl -s http://127.0.0.1:8025/api/v1/messages | head -c 400   # los últimos mensajes
```

## 4. Las pruebas del framework

Las suites de correo envían contra Mailpit. **Sin Mailpit escuchando en `127.0.0.1:1025` no se
saltan: fallan, y dicen por qué.** `core/mail-senders` y `core/mail-senders-db` paran antes de enviar
nada y dan la orden de arranque. Y como `core/mail-log` corre dentro de `bin/cli gates`, **sin
Mailpit `bin/verify` acaba en rojo**: el correo de su prueba va al buzón en disco en vez de llegar, y
la prueba que espera «entregado» falla. Nunca dan un verde falso.

## 5. Cuándo NO usar Mailpit

Para comprobar que un correo llega **de verdad** a un buzón externo (entregabilidad, SPF, DKIM,
carpeta de correo no deseado) hace falta un envío real. En ese caso, el único destino permitido en
las pruebas de este proyecto son buzones públicos `zz-prueba-…@mailinator.com`, y cada envío se
enumera en el reporte.

## 6. Si algo no funciona

| Síntoma | Qué mirar |
| :-- | :-- |
| No llega a Mailpit, y no hay error | Mailpit no está arriba (`systemctl --user status mailpit`): el correo está en `src/app/logs/mail-outbox/` |
| El correo sale de verdad | La entrega está en «Real», o la instalación es `production` con «Según el entorno». `bin/cli mail-doctor` lo dice en una línea |
| No sale nada en producción | Falta `src/app/config/environment.php`, o la entrega está en «Retenido». Lo dice el aviso del panel |
| La web no abre | El servicio escucha en `127.0.0.1:8025`; si trabajas en otra máquina, un túnel SSH |
| Un correo «no llegó» | El registro de correos dice el motivo en su fila; y `src/app/logs/error.plain.log`, si el fallo fue antes del SMTP |
