<?php

/**
 * FormUploadRejectedException.php
 */

namespace PiecesPHP\Core\Forms;

/**
 * FormUploadRejectedException - El archivo subido no pasa la validación (tipo, tamaño, sin archivo).
 *
 * El mensaje es para quien sube: el módulo la convierte en su SafeException para que llegue al usuario sin registrarse
 * como error interno.
 *
 * @package     PiecesPHP\Core\Forms
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class FormUploadRejectedException extends \Exception
{
}
