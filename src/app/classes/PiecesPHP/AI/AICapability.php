<?php

/**
 * AICapability.php
 */

namespace PiecesPHP\AI;

/**
 * AICapability - Una función de IA que un módulo registra (ADR 0074): la traducción, la voz a texto y las que traiga un
 * proyecto. Declara el tipo de proveedor que necesita y sus valores por omisión; la configuración de Configuración › IA
 * los sustituirá (ronda 3). Inmutable.
 *
 * @package     PiecesPHP\AI
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AICapability
{
    const KEY_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';

    /**
     * Un proveedor de chat (OpenAI, Mistral).
     */
    const PROVIDER_CHAT = 'chat';

    /**
     * Un proveedor de voz a texto (Groq).
     */
    const PROVIDER_SPEECH = 'speech';

    const PROVIDER_TYPES = [
        self::PROVIDER_CHAT,
        self::PROVIDER_SPEECH,
    ];

    /**
     * Sin periodo: lo gastado desde siempre (o desde el último reinicio).
     */
    const PERIOD_ACCUMULATED = 'accumulated';

    /**
     * Por mes natural.
     */
    const PERIOD_MONTHLY = 'monthly';

    /**
     * Cada tantos días.
     */
    const PERIOD_EVERY_DAYS = 'every-days';

    /**
     * Cada tantos meses.
     */
    const PERIOD_EVERY_MONTHS = 'every-months';

    const PERIODS = [
        self::PERIOD_ACCUMULATED,
        self::PERIOD_MONTHLY,
        self::PERIOD_EVERY_DAYS,
        self::PERIOD_EVERY_MONTHS,
    ];

    /**
     * @var string
     */
    private $key;

    /**
     * @var \Closure():string
     */
    private $name;

    /**
     * @var string
     */
    private $providerType;

    /**
     * @var bool
     */
    private $enabledByDefault;

    /**
     * @var int[]
     */
    private $defaultUserTypes;

    /**
     * @var int
     */
    private $defaultQuota;

    /**
     * @var string
     */
    private $defaultPeriod;

    /**
     * @param string $key Solo minúsculas, cifras y guiones
     * @param callable():string $name Devuelve el nombre ya traducido: se llama al pintar, no al registrar
     * @param string $providerType PROVIDER_CHAT o PROVIDER_SPEECH
     * @param bool $enabledByDefault Encendida mientras nadie diga otra cosa
     * @param int[] $defaultUserTypes Los tipos de usuario que la usan (root, siempre)
     * @param int $defaultQuota Tokens por periodo; 0 = sin límite
     * @param string $defaultPeriod Uno de PERIODS
     * @throws \InvalidArgumentException si la key, el tipo de proveedor, la cuota o el periodo no valen
     */
    public function __construct(string $key, callable $name, string $providerType, bool $enabledByDefault = true, array $defaultUserTypes = [], int $defaultQuota = 0, string $defaultPeriod = self::PERIOD_ACCUMULATED)
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new \InvalidArgumentException("La key de capacidad de IA «{$key}» no es válida.");
        }
        if (!in_array($providerType, self::PROVIDER_TYPES, true)) {
            throw new \InvalidArgumentException("La capacidad de IA «{$key}» pide un tipo de proveedor desconocido: «{$providerType}».");
        }
        if ($defaultQuota < 0) {
            throw new \InvalidArgumentException("La capacidad de IA «{$key}» no puede tener una cuota negativa.");
        }
        if (!in_array($defaultPeriod, self::PERIODS, true)) {
            throw new \InvalidArgumentException("La capacidad de IA «{$key}» pide un periodo desconocido: «{$defaultPeriod}».");
        }
        $this->key = $key;
        $this->name = \Closure::fromCallable($name);
        $this->providerType = $providerType;
        $this->enabledByDefault = $enabledByDefault;
        $this->defaultUserTypes = array_values(array_map('intval', $defaultUserTypes));
        $this->defaultQuota = $defaultQuota;
        $this->defaultPeriod = $defaultPeriod;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * El nombre, traducido al idioma de ahora.
     *
     * @return string
     */
    public function name(): string
    {
        return (string) ($this->name)();
    }

    /**
     * @return string
     */
    public function providerType(): string
    {
        return $this->providerType;
    }

    /**
     * @return bool
     */
    public function enabledByDefault(): bool
    {
        return $this->enabledByDefault;
    }

    /**
     * @return int[]
     */
    public function defaultUserTypes(): array
    {
        return $this->defaultUserTypes;
    }

    /**
     * @return int
     */
    public function defaultQuota(): int
    {
        return $this->defaultQuota;
    }

    /**
     * @return string
     */
    public function defaultPeriod(): string
    {
        return $this->defaultPeriod;
    }
}
