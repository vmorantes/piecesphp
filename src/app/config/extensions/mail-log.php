<?php

/**
 * @pcsphp-config clon
 * Qué conviene editar aquí: qué se guarda del cuerpo de cada correo en el registro, y cuántas líneas se conservan. Por omisión, el cuerpo entero y cifrado, y 20.000 líneas.
 */

//@codigo-comentado · No guardar ningún cuerpo: el registro conserva destino, asunto y resultado.
//\PiecesPHP\SystemStatus\Mappers\MailLogMapper::setBodyTransformer(fn(string $body): ?string => null);

//@codigo-comentado · Tachar un patrón antes de guardar, por ejemplo números de tarjeta de 13 a 19 cifras.
//\PiecesPHP\SystemStatus\Mappers\MailLogMapper::setBodyTransformer(
//    fn(string $body): ?string => preg_replace('/\b(?:\d[ -]?){13,19}\b/', '[TACHADO]', $body)
//);

//@codigo-comentado · Conservar menos líneas (o más). Por debajo de 1.000 se queda en 1.000: sin techo.
//\PiecesPHP\SystemStatus\Mappers\MailLogMapper::setMaxRows(5000);
