<?php

/**
 * SystemAlert.php
 */

namespace PiecesPHP\SystemStatus;

/**
 * SystemAlert - Un aviso del sistema: qué dice, cuándo está activo, a quién se enseña y cómo se arregla.
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SystemAlert
{
    const SEVERITY_INFO = 'info';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_DANGER = 'danger';

    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $severity;

    /**
     * @var callable():string
     */
    private $message;

    /**
     * @var callable():bool
     */
    private $isActive;

    /**
     * @var bool
     */
    private $dismissible;

    /**
     * @var int[]
     */
    private $audience;

    /**
     * @var bool
     */
    private $showAsNag;

    /**
     * @var string|null
     */
    private $fixRoute;

    /**
     * @var string|null
     */
    private $fixLabel;

    /**
     * @param string $key kebab-case
     * @param string $severity info|warning|danger
     * @param callable():string $message El texto ya traducido
     * @param callable():bool $isActive Se evalúa en cada consulta: que sea barato si el aviso va como nag
     * @param bool $dismissible Si root puede ocultarlo
     * @param int[] $audience Tipos de usuario que lo ven
     * @param bool $showAsNag Si sale como aviso flotante en el panel
     * @param string|null $fixRoute Nombre de la ruta donde se arregla
     * @param string|null $fixLabel Texto del enlace a esa ruta
     * @throws \InvalidArgumentException
     */
    public function __construct(string $key, string $severity, callable $message, callable $isActive, bool $dismissible, array $audience, bool $showAsNag = false, ?string $fixRoute = null, ?string $fixLabel = null)
    {
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $key) !== 1) {
            throw new \InvalidArgumentException("La key «{$key}» del aviso no está en kebab-case.");
        }
        if (!in_array($severity, [self::SEVERITY_INFO, self::SEVERITY_WARNING, self::SEVERITY_DANGER], true)) {
            throw new \InvalidArgumentException("Gravedad «{$severity}» no válida: info, warning o danger.");
        }
        $audience = array_values(array_filter($audience, 'is_int'));
        if (count($audience) === 0) {
            throw new \InvalidArgumentException("El aviso «{$key}» no tiene a quién enseñarse.");
        }
        $this->key = $key;
        $this->severity = $severity;
        $this->message = $message;
        $this->isActive = $isActive;
        $this->dismissible = $dismissible;
        $this->audience = $audience;
        $this->showAsNag = $showAsNag;
        $this->fixRoute = $fixRoute;
        $this->fixLabel = $fixLabel;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function severity(): string
    {
        return $this->severity;
    }

    /**
     * @return string
     */
    public function message(): string
    {
        return (string) ($this->message)();
    }

    /**
     * Sin proteger: quien pregunta es el registro, que atrapa lo que lance.
     *
     * @return bool
     */
    public function evaluateActive(): bool
    {
        return (bool) ($this->isActive)();
    }

    /**
     * @return bool
     */
    public function isDismissible(): bool
    {
        return $this->dismissible;
    }

    /**
     * @return int[]
     */
    public function audience(): array
    {
        return $this->audience;
    }

    /**
     * @return bool
     */
    public function showAsNag(): bool
    {
        return $this->showAsNag;
    }

    /**
     * @return string|null
     */
    public function fixRoute(): ?string
    {
        return $this->fixRoute;
    }

    /**
     * @return string|null
     */
    public function fixLabel(): ?string
    {
        return $this->fixLabel;
    }
}
