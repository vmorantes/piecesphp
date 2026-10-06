<?php

/**
 * UsersExportDefinition.php
 */

namespace DataImportExportUtility\Definitions;

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;

/**
 * UsersExportDefinition - Exportación de usuarios con las mismas columnas que la importación, para poder reimportar.
 *
 * Sin contraseñas: al reimportar se generan.
 *
 * @package     DataImportExportUtility\Definitions
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UsersExportDefinition extends ExportDefinition
{

    const LANG_GROUP = DataImportExportUtilityLang::LANG_GROUP;

    /**
     * @var int
     */
    private $pageSize;

    /**
     * @param int $pageSize Filas por consulta; se cambia solo en pruebas
     */
    public function __construct(int $pageSize = 500)
    {
        $this->pageSize = max(1, $pageSize);
    }

    /**
     * @return string
     */
    public function key(): string
    {
        return 'users';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return __(self::LANG_GROUP, 'Usuarios');
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return __(self::LANG_GROUP, 'Descarga los usuarios con las columnas del importador, para editarlos o volver a importarlos.');
    }

    /**
     * @return string|null
     */
    public function importDefinition(): ?string
    {
        return UsersImportDefinition::class;
    }

    /**
     * @return int[]
     */
    public function allowedUserTypes(): array
    {
        return [
            UsersModel::TYPE_USER_ROOT,
            UsersModel::TYPE_USER_ADMIN_GRAL,
        ];
    }

    /**
     * Las keys y etiquetas de UsersImportDefinition, sin la contraseña.
     *
     * @param ExportContext $context
     * @return ExportColumn[]
     */
    public function columns(ExportContext $context): array
    {
        $columns = [];
        foreach ((new UsersImportDefinition())->columns() as $column) {
            if ($column->key() === 'password') {
                continue;
            }
            $columns[] = new ExportColumn($column->key(), $column->label());
        }
        return $columns;
    }

    /**
     * Por páginas, avanzando por id, para no cargar la tabla entera en memoria.
     *
     * @param ExportContext $context
     * @return iterable<array<string,scalar|null>>
     */
    public function rows(ExportContext $context): iterable
    {
        //Los códigos ya vistos: las organizaciones son pocas y así no se pregunta una vez por usuario.
        $codes = [];
        $organizationCell = function (?int $organizationID) use (&$codes) {
            if ($organizationID === null) {
                return null;
            }
            if (!array_key_exists($organizationID, $codes)) {
                $organization = OrganizationMapper::getBy($organizationID, 'id');
                $code = $organization !== null && isset($organization->code) && is_string($organization->code) ? $organization->code : '';
                //Sin código —una instalación a medio migrar— sale el id: perder la organización sería peor, y el
                //importador acepta las dos formas.
                $codes[$organizationID] = OrganizationMapper::codeIsWellFormed($code) ? $code : (string) $organizationID;
            }
            return $codes[$organizationID];
        };
        $lastID = 0;
        do {
            $model = UsersModel::model();
            $model->resetAll();
            $model->select(['id', 'username', 'email', 'firstname', 'secondname', 'firstLastname', 'secondLastname', 'type', 'organization'])
                ->where(new WhereSegment([new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $lastID)]))
                ->orderBy('id ASC')
                ->execute(false, 1, $this->pageSize);
            $page = (array) $model->result();

            foreach ($page as $user) {
                $lastID = (int) $user->id;
                $type = (int) $user->type;
                yield [
                    'username' => $user->username,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'secondname' => $user->secondname,
                    'first_lastname' => $user->firstLastname,
                    'second_lastname' => $user->secondLastname,
                    'type' => UsersModel::TYPES_USERS[$type] ?? (string) $type,
                    //EL CÓDIGO, NO EL ID (P62): este archivo se edita a mano, y un identificador interno no se edita.
                    'organization' => $organizationCell($user->organization !== null ? (int) $user->organization : null),
                ];
            }
        } while (count($page) === $this->pageSize);
    }
}
