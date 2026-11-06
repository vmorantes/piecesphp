# El banco de pruebas de correo

La pantalla **«Correos del sitio»** (Configuración del sistema › Sistema) enseña, uno debajo de otro, **todos los
correos que el sitio puede enviar**, cada uno con datos de muestra y pintado por el mismo camino que el envío real: lo
que ves es lo que llega. La ven root y el administrador general.

## Qué enseña cada correo

Nombre, cuándo sale, a quién, asunto, su clave y el cuerpo tal como llega, dentro de un `iframe` aislado que no ejecuta
scripts y toma el alto del correo. Una navegación fija lista los correos agrupados por módulo, marca el que estás viendo
y lleva a cada uno (en el móvil, un desplegable arriba).

**Solo salen los correos de los módulos activos**: cada módulo registra los suyos dentro de su interruptor de
activación, así que uno apagado no aparece y su «Enviar prueba» se rechaza.

## Declarar los correos de tu proyecto

Cada correo es una **definición**, una subclase de `PiecesPHP\MailCatalog\MailDefinition`, y se registra con
`MailCatalog::register()` desde el módulo que lo envía:

```php
use PiecesPHP\MailCatalog\MailDefinition;

class InscripcionRecibidaMail extends MailDefinition
{
    public function key(): string { return 'inscripcion-recibida'; }
    public function name(): string { return __('inscripciones', 'Inscripción recibida'); }
    public function trigger(): string { return __('inscripciones', 'Al enviar el formulario de inscripción.'); }
    public function recipients(): string { return __('inscripciones', 'La persona que se inscribe.'); }

    public function subject(array $data): string
    {
        return __('inscripciones', 'Recibimos tu inscripción') . ': ' . self::text($data, 'curso');
    }

    public function sampleData(): array
    {
        return ['nombre' => 'Laura Gómez', 'curso' => 'Fotografía básica'];
    }

    public function render(array $data): string
    {
        //El envío real pasa su controlador en 'renderer'; la vista previa usa uno propio.
        return (string) self::renderer($data, fn() => self::viewRenderer())->render('mailing/template_base', [
            //Todo dato de la persona, escapado: la plantilla pinta `text` como HTML.
            'text' => sprintf(__('inscripciones', 'Hola, %s: te escribiremos con la fecha de inicio.'), escape_html(self::text($data, 'nombre'))),
        ], false);
    }
}
```

```php
use PiecesPHP\MailCatalog\MailCatalog;

//En las rutas del módulo, dentro de su interruptor de activación:
if (INSCRIPCIONES_ENABLED) {
    MailCatalog::register(new InscripcionRecibidaMail(), 'Inscripciones');
}
```

```php
//En el envío real, la misma definición construye asunto y cuerpo:
$definicion = new InscripcionRecibidaMail();
$datos = ['nombre' => $nombre, 'curso' => $curso, 'renderer' => $this];
$mailer->Subject = $definicion->subject($datos);
$mailer->Body = $definicion->render($datos);
```

- **Un solo camino de render**: no copies la plantilla en dos sitios. Si cambias el correo, cambias la definición.
- **Datos de muestra inventados**, nunca de la base. Una muestra no puede dejar un enlace o botón sin texto ni un
  marcador sin sustituir: la suite `core/mail-catalog` lo comprueba con los del framework, y conviene que la de tu proyecto
  haga lo mismo con los suyos.
- **Textos con `__()`**, como el resto del panel.
- El segundo argumento de `register()` es el nombre del módulo con el que se agrupa en la navegación. Se escribe en
  español: la pantalla lo traduce con el grupo `mail-catalog`, y si no está ahí, sale tal cual.
- **Escapa todo dato de la persona** que metas en el HTML del correo (`escape_html()`): la plantilla lo pinta tal cual.

## Enviar una prueba

«Enviar prueba» manda el correo, con sus datos de muestra, a la dirección del campo **«Destinatario del correo de
prueba»** (de entrada, la de quien mira). Solo funciona cuando el correo **se retiene**: con la «Entrega del correo» en
«Retenido: no sale de esta máquina», o en «Según el entorno» en una instalación de desarrollo (Configuración › Correo).
Entonces va por el **servidor de pruebas** configurado allí, no por la entrega real. El asunto lleva `[PRUEBA] ` delante
y el envío queda en el registro de acciones con su destinatario. El servidor lo vuelve a comprobar aunque el botón no se
pinte: sin retener, responde 409; con una dirección mala, 400; con una clave que no está registrada, 404.

Que el servidor de pruebas sea de verdad local depende de cómo lo configures: el framework no lo comprueba todavía.

## Ningún correo sin declarar

`bin/cli verify-integrity` (comprobación 63) busca en el código todo lo que envía correo (`new Mailer`, PHPMailer,
`mail()`, `mb_send_mail()`, asignaciones a `->Body`, `->msgHTML(`) y falla si un envío no construye su cuerpo con una
definición. Mira el código, no lo registrado: un correo de un módulo apagado también tiene que estar declarado.
