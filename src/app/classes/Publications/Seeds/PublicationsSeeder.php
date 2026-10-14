<?php

/**
 * PublicationsSeeder.php
 */

namespace Publications\Seeds;

use PiecesPHP\UserSystem\ORM\UsersModel;
use Publications\Mappers\PublicationCategoryMapper;
use Publications\Mappers\PublicationMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;

/**
 * PublicationsSeeder.
 *
 * Publicaciones de prueba, con sus tres imágenes copiadas a una carpeta propia de cada una. Nacen
 * privadas, como las del panel; el limpiado borra la carpeta entera, se llamen como se llamen sus
 * archivos después. Su autor es el usuario principal que ya existe, leído de la base.
 *
 * @package     Publications\Seeds
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class PublicationsSeeder extends ModuleSeeder
{

    /** Dónde guarda sus archivos el módulo, relativo a src/. */
    const UPLOADS = 'statics/uploads/publications';

    /** La imagen que se copia en las tres columnas de imagen. */
    const IMAGE = 'statics/images/generic-image-placeholder.png';

    /** Párrafos con los que se arma el cuerpo, de largo distinto. */
    const PARAGRAPHS = [
        'Este documento resume los resultados del último trimestre y los compara con el mismo periodo del año anterior.',
        'La guía explica, paso a paso, cómo solicitar el servicio en línea: qué documentos preparar, cuánto tarda la respuesta y a quién acudir si algo no sale como se esperaba.',
        'Presentamos el calendario de actividades del semestre.',
        'El equipo técnico revisó las propuestas recibidas y seleccionó las que mejor responden a las necesidades de la comunidad. En las próximas semanas se publicarán los detalles de cada una y el cronograma de ejecución.',
        'Gracias a todas las personas que participaron en la consulta. Sus aportes quedaron recogidos en el informe final.',
    ];

    /**
     * @var int|null El usuario principal, leído una sola vez.
     */
    protected ?int $author = null;

    public function mapper(): string
    {
        return PublicationMapper::class;
    }

    public function markerColumn(): string
    {
        return 'title';
    }

    public function dependsOn(): array
    {
        return [PublicationCategoryMapper::class];
    }

    /**
     * Cada arranque crea su fila de aprobación (`SystemApprovalManager::init()`), que apunta por
     * número de id y por tabla.
     *
     * @return array<string,string|array{column: string, where: array<string,string|int>}>
     */
    public function ownedRows(): array
    {
        return [SystemApprovalsMapper::TABLE => ['column' => 'referenceValue', 'where' => ['referenceTable' => PublicationMapper::TABLE]]];
    }

    public function ownedFolder(): ?array
    {
        return ['base' => self::UPLOADS, 'column' => 'folder'];
    }

    public function defaultCount(): int
    {
        return 8;
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
                throw new \RuntimeException('PARA: no hay un usuario principal en la base para ponerlo de autor de las publicaciones');
            }
        }
        $folder = $this->text('publicacion', $index);
        $paragraphs = [];
        for ($i = 0; $i <= $index % 4; $i++) {
            $paragraphs[] = '<p>' . self::PARAGRAPHS[($index + $i) % count(self::PARAGRAPHS)] . '</p>';
        }
        return [
            'title' => $this->text('publicacion', $index),
            'content' => implode("\n", $paragraphs),
            'author' => $this->author,
            'category' => $context->reference(PublicationCategoryMapper::class, $index),
            'mainImage' => $context->copyToUploads(self::IMAGE, self::UPLOADS, $folder, 'imagen-principal.png', true),
            'thumbImage' => $context->copyToUploads(self::IMAGE, self::UPLOADS, $folder, 'miniatura.png', true),
            'ogImage' => $context->copyToUploads(self::IMAGE, self::UPLOADS, $folder, 'imagen-redes.png', true),
            'folder' => $folder,
            'visits' => 0,
            'createdBy' => $this->author,
            'status' => PublicationMapper::ACTIVE,
        ];
    }

}
