<?php

/**
 * AIUnavailableException.php
 */

namespace PiecesPHP\AI;

/**
 * AIUnavailableException - La puerta no deja usar una capacidad (apagada, sin permiso o sin cuota). El mensaje es el motivo,
 * ya traducido, para enseñarlo tal cual.
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIUnavailableException extends \RuntimeException
{
}
