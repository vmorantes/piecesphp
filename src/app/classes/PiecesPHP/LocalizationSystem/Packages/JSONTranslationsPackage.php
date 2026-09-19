<?php

/**
 * JSONTranslationsPackage.php
 */

namespace PiecesPHP\LocalizationSystem\Packages;

use JsonSerializable;
use PiecesPHP\Core\Validation\Validator;

/**
 * JSONTranslationsPackage.
 *
 * @package     PiecesPHP\LocalizationSystem\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class JSONTranslationsPackage implements JsonSerializable
{

    protected \DateTime $updated;
    protected array $data = [];

    public function __construct(\DateTime $updated, array $data = [])
    {
        $this->updated = $updated;
        $this->data = $data;
    }

    /**
     * Obtiene la fecha de actualización
     *
     * @return \DateTime
     */
    public function getUpdated(): \DateTime
    {
        return $this->updated;
    }

    /**
     * Obtiene los datos de las traducciones
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Establece los datos de las traducciones
     *
     * @param array $data
     * @return static
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        $this->updated = new \DateTime();
        return $this;
    }

    /**
     * Crea un objeto JSONTranslationsPackage desde un JSON
     *
     * @param string $json
     * @return static
     */
    public static function createFromJSON(string $json): static
    {
        $json = json_decode($json, true);
        $json = is_array($json) ? $json : [];
        $updated = $json['updated'] ?? null;
        $updated = Validator::isDate($updated, 'Y-m-d H:i:s') ? new \DateTime($updated) : new \DateTime('1990-01-01 00:00:00');
        $data = $json['data'] ?? [];
        return new static($updated, $data);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'data' => $this->data,
            'updated' => $this->updated->format('Y-m-d H:i:s'),
        ];
    }

}
