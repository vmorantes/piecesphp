<?php

/**
 * AIProviderRecord.php
 */

namespace PiecesPHP\AI\Providers;

/**
 * AIProviderRecord - Un registro de proveedor de IA (ADR 0077 §1): un tipo del catálogo, un sobrenombre y su clave ya
 * resuelta. Inmutable.
 *
 * La clave NO es una propiedad del objeto: vive en un WeakMap de la clase, así que ni var_export (que ignora
 * __debugInfo), ni print_r, ni var_dump, ni json_encode, ni una serialización la pueden enseñar. Solo key() la da.
 *
 * @package     PiecesPHP\AI\Providers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class AIProviderRecord
{
    const ID_PATTERN = '/^[a-z0-9]+(-[a-z0-9]+)*$/';
    const KEY_HEADER_PATTERN = '/^[A-Za-z0-9-]{1,64}$/';

    /**
     * De dónde sale la clave (ADR 0077, revisión del 14): escrita en Configuración › IA (sellada en ai_provider_keys) o del
     * archivo de claves por su nombre (getKeyFromSecureKeys()).
     */
    const KEY_SOURCE_STORED = 'stored';
    const KEY_SOURCE_FILE = 'file';
    const KEY_SOURCES = [self::KEY_SOURCE_STORED, self::KEY_SOURCE_FILE];

    /**
     * Los tipos cuyo nombre en el archivo puede ser el propio tipo (los de api-keys.php).
     */
    const KEY_FILE_OWN_TYPES = ['openai', 'mistral', 'groq'];

    /**
     * Las claves de cada registro.
     *
     * @var \WeakMap<AIProviderRecord,string>|null
     */
    private static $keys = null;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $type;

    /**
     * El sobrenombre (alias()).
     *
     * @var string
     */
    private $nickname;

    /**
     * @var string|null
     */
    private $baseUrl;

    /**
     * @var string|null
     */
    private $keyHeader;

    /**
     * @var list<string>
     */
    private $models;

    /**
     * @var list<string>|null
     */
    private $onlyFor;

    /**
     * @var string Una de KEY_SOURCES
     */
    private $keySource;

    /**
     * @var string|null El nombre en el archivo de claves; solo con KEY_SOURCE_FILE
     */
    private $keyFile;

    /**
     * @param string $id Minúsculas, cifras y guiones
     * @param string $type Un tipo de AIProviderCatalog
     * @param string $alias El sobrenombre; no vacío
     * @param string $key La clave ya resuelta ('' = sin clave)
     * @param string|null $baseUrl Solo «Personalizado», y obligatoria en él (AIProviderCatalog::isValidCustomBaseUrl())
     * @param string|null $keyHeader Solo «Personalizado»: la cabecera que lleva la clave; null = Authorization: Bearer
     * @param list<string> $models Los modelos que se conocen de él (informativo)
     * @param list<string>|null $onlyFor Las capacidades del catálogo a las que se limita; null, las de su tipo
     * @param string $keySource Una de KEY_SOURCES
     * @param string|null $keyFile El nombre en el archivo de claves (isValidKeyFile() con su tipo); obligatorio con KEY_SOURCE_FILE y null con la otra
     * @throws \InvalidArgumentException si algo no vale; el mensaje nunca lleva la clave
     */
    public function __construct(string $id, string $type, string $alias, #[\SensitiveParameter] string $key, ?string $baseUrl = null, ?string $keyHeader = null, array $models = [], ?array $onlyFor = null, string $keySource = self::KEY_SOURCE_STORED, ?string $keyFile = null)
    {
        if (preg_match(self::ID_PATTERN, $id) !== 1 || strlen($id) > 64) {
            throw new \InvalidArgumentException("El identificador de proveedor «{$id}» no es válido.");
        }
        if (AIProviderCatalog::get($type) === null) {
            throw new \InvalidArgumentException("El proveedor «{$id}» tiene un tipo que no está en el catálogo: «{$type}».");
        }
        if (trim($alias) === '') {
            throw new \InvalidArgumentException("El proveedor «{$id}» no tiene sobrenombre.");
        }
        if ($type === AIProviderCatalog::TYPE_CUSTOM) {
            if ($baseUrl === null || !AIProviderCatalog::isValidCustomBaseUrl($baseUrl)) {
                throw new \InvalidArgumentException("El proveedor personalizado «{$id}» necesita una dirección https://, o http://localhost o http://127.0.0.1.");
            }
            if ($keyHeader !== null && preg_match(self::KEY_HEADER_PATTERN, $keyHeader) !== 1) {
                throw new \InvalidArgumentException("El proveedor personalizado «{$id}» tiene una cabecera de clave que no vale.");
            }
        } elseif ($baseUrl !== null || $keyHeader !== null) {
            throw new \InvalidArgumentException("El proveedor «{$id}» es de tipo «{$type}»: la dirección y la cabecera son las de su tipo.");
        }
        foreach ($models as $model) {
            if (!is_string($model) || trim($model) === '') {
                throw new \InvalidArgumentException("El proveedor «{$id}» tiene un modelo que no es un texto.");
            }
        }
        if (!in_array($keySource, self::KEY_SOURCES, true)) {
            throw new \InvalidArgumentException("El proveedor «{$id}» tiene un origen de la clave que no vale.");
        }
        if ($keySource === self::KEY_SOURCE_FILE ? ($keyFile === null || !self::isValidKeyFile($keyFile, $type)) : $keyFile !== null) {
            throw new \InvalidArgumentException("El proveedor «{$id}» tiene un nombre en el archivo de claves que no vale para su tipo: el del tipo (openai, mistral o groq) o ai-<tipo>-….");
        }
        $this->id = $id;
        $this->type = $type;
        $this->nickname = trim($alias);
        $this->baseUrl = $baseUrl !== null ? rtrim($baseUrl, '/') : null;
        $this->keyHeader = $keyHeader;
        $this->models = array_values($models);
        $this->onlyFor = $onlyFor !== null ? array_values($onlyFor) : null;
        $this->keySource = $keySource;
        $this->keyFile = $keyFile;
        self::$keys ??= new \WeakMap();
        self::$keys[$this] = $key;
    }

    /**
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function alias(): string
    {
        return $this->nickname;
    }

    /**
     * La dirección base efectiva: la propia en un «Personalizado», la de su tipo en los demás. Sin barra final.
     *
     * @return string
     */
    public function baseUrl(): string
    {
        return $this->baseUrl ?? rtrim((string) (AIProviderCatalog::get($this->type)['baseUrl'] ?? ''), '/');
    }

    /**
     * La cabecera de la clave; null = Authorization: Bearer.
     *
     * @return string|null
     */
    public function keyHeader(): ?string
    {
        return $this->keyHeader ?? (AIProviderCatalog::get($this->type)['keyHeader'] ?? null);
    }

    /**
     * @return list<string>
     */
    public function models(): array
    {
        return $this->models;
    }

    /**
     * De dónde sale la clave: una de KEY_SOURCES.
     *
     * @return string
     */
    public function keySource(): string
    {
        return $this->keySource;
    }

    /**
     * El nombre en el archivo de claves; null si la clave está escrita.
     *
     * @return string|null
     */
    public function keyFile(): ?string
    {
        return $this->keyFile;
    }

    /**
     * Si un registro de ese tipo puede pedir ese nombre al archivo de claves: el del propio tipo (solo openai, mistral y
     * groq) o ai-<tipo>-…, en minúsculas, cifras y guiones. Un tipo que no está en el catálogo no vale.
     *
     * @param string $name
     * @param string $type
     * @return bool
     */
    public static function isValidKeyFile(string $name, string $type): bool
    {
        if (AIProviderCatalog::get($type) === null) {
            return false;
        }
        //Atado al tipo: un «Personalizado» no puede pedir la llave de OpenAI (ni otra del archivo) y mandarla a su dirección.
        return ($name === $type && in_array($type, self::KEY_FILE_OWN_TYPES, true)) || preg_match('/^ai-' . preg_quote($type, '/') . '-[a-z0-9][a-z0-9-]{0,40}\z/', $name) === 1;
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return self::$keys[$this] ?? '';
    }

    /**
     * @return bool
     */
    public function hasKey(): bool
    {
        return trim($this->key()) !== '';
    }

    /**
     * Si sabe hacer esa capacidad del catálogo: la tiene su tipo y no la excluye su límite.
     *
     * @param string $capability Una de AIProviderCatalog::CAPABILITY_*
     * @return bool
     */
    public function supports(string $capability): bool
    {
        return AIProviderCatalog::supports($this->type, $capability) && ($this->onlyFor === null || in_array($capability, $this->onlyFor, true));
    }

    /**
     * Lo que enseñan var_dump y print_r: sin la clave, solo si la tiene.
     *
     * @return array<string,mixed>
     */
    public function __debugInfo(): array
    {
        return ['id' => $this->id, 'type' => $this->type, 'alias' => $this->nickname, 'baseUrl' => $this->baseUrl(), 'keyHeader' => $this->keyHeader(), 'hasKey' => $this->hasKey(), 'models' => $this->models, 'onlyFor' => $this->onlyFor, 'keySource' => $this->keySource, 'keyFile' => $this->keyFile];
    }

    /**
     * Un registro no se serializa: su clave no viajaría y volvería sin ella.
     *
     * @return array<string,mixed>
     * @throws \LogicException siempre
     */
    public function __serialize(): array
    {
        throw new \LogicException('Un registro de proveedor de IA no se serializa.');
    }
}
