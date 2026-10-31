<?php

/**
 * BlobStorageFileAzurePackage.php
 */

namespace API\Adapters\Packages;

use API\APILang;

/**
 * BlobStorageFileAzurePackage.
 *
 * Un archivo leído de Azure Blob Storage: su nombre, su contenido y las cabeceras de la respuesta.
 *
 * @package     API\Adapters\Packages
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class BlobStorageFileAzurePackage
{
    protected string $name;
    protected string $content;
    /**
     * @var array<string,string>
     */
    protected array $properties;
    protected \DateTime $lastModified;

    /**
     * @param string $name El nombre del blob, con su carpeta
     * @param string $content
     * @param array<string,string> $headers Las cabeceras de la respuesta, con los valores unidos con ', '
     * @throws \RuntimeException Si falta Last-Modified o no se puede leer
     */
    public function __construct(string $name, string $content, array $headers)
    {
        $this->name = $name;
        $this->content = $content;
        $this->properties = array_change_key_case($headers, CASE_LOWER);

        $lastModified = \DateTime::createFromFormat('D, d M Y H:i:s T', $this->properties['last-modified'] ?? '', new \DateTimeZone('GMT'));
        if ($lastModified === false) {
            throw new \RuntimeException(sprintf(__(APILang::LANG_GROUP, 'Azure Blob devolvió «%s» sin fecha de modificación.'), $name));
        }
        $this->lastModified = $lastModified;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Las cabeceras de la respuesta, con los nombres en minúsculas.
     *
     * @return array<string,string>
     */
    public function properties(): array
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * @return \DateTime
     */
    public function lastModified(): \DateTime
    {
        return $this->lastModified;
    }

    /**
     * @return string
     */
    public function contentType(): string
    {
        return $this->properties['content-type'] ?? '';
    }

}
