<?php

/**
 * DocumentsSeeder.php
 */

namespace Documents\Seeds;

use Documents\Mappers\DocumentsMapper;
use Forms\DocumentTypes\Mappers\DocumentTypesMapper;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * DocumentsSeeder.
 *
 * Documentos de prueba, con el documento y su imagen copiados a una carpeta propia de cada uno.
 * Nacen privados, como los del panel. Su autor es el usuario principal que ya existe, leído de la base.
 *
 * @package     Documents\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class DocumentsSeeder extends ModuleSeeder
{

    /** Dónde guarda sus archivos el módulo, relativo a src/. */
    const UPLOADS = 'statics/uploads/documents';

    /** Descripciones con las que se reparte la columna `description`, de largo distinto. */
    const DESCRIPTIONS = [
        'Formato para solicitar el certificado.',
        'Reglamento interno vigente, con las modificaciones aprobadas en la última asamblea.',
        'Instructivo de diligenciamiento del formulario de inscripción, con ejemplos de cada campo y las preguntas más frecuentes.',
        'Acta de la reunión ordinaria.',
        'Política de tratamiento de datos personales.',
        'Plantilla del informe mensual de actividades.',
    ];

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return DocumentsMapper::class;
    }

    public function markerColumn(): string
    {
        return 'documentName';
    }

    public function dependsOn(): array
    {
        return [DocumentTypesMapper::class];
    }

    public function ownedFolder(): ?array
    {
        return ['base' => self::UPLOADS, 'column' => 'folder'];
    }

    public function defaultCount(): int
    {
        return 6;
    }

    /**
     * @param int $index
     * @param SeedContext $context
     * @return array<string,mixed>
     * @throws \RuntimeException Si no hay un usuario principal que poner de autor.
     */
    public function row(int $index, SeedContext $context): array
    {
        if ($this->author === null) {
            $this->author = $context->existingUserId(UsersModel::TYPE_USER_ROOT);
            if ($this->author === null) {
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de los documentos');
            }
        }
        $folder = $this->text('documento', $index);
        return [
            'documentType' => $context->reference(DocumentTypesMapper::class, $index),
            'documentName' => $this->text('documento', $index),
            'description' => self::DESCRIPTIONS[$index % count(self::DESCRIPTIONS)],
            'document' => $context->copyToUploads('statics/images/document-placeholder.png', self::UPLOADS, $folder, 'documento.png', true),
            'documentImage' => $context->copyToUploads('statics/images/generic-image-placeholder.png', self::UPLOADS, $folder, 'imagen.png', true),
            'folder' => $folder,
            'createdBy' => $this->author,
        ];
    }

}
