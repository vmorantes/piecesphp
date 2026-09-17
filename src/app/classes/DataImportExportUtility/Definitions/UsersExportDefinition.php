<?php

/**
 * UsersExportDefinition.php
 */

namespace DataImportExportUtility\Definitions;

use App\Model\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
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
     * @return ExportColumn[]
     */
    public function columns(): array
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
     * @return iterable<array<string,scalar|null>>
     */
    public function rows(): iterable
    {
        $lastID = 0;
        do {
            $model = UsersModel::model();
            $model->resetAll();
            $model->select(['id', 'username', 'email', 'firstname', 'secondname', 'first_lastname', 'second_lastname', 'type', 'organization'])
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
                    'first_lastname' => $user->first_lastname,
                    'second_lastname' => $user->second_lastname,
                    'type' => UsersModel::TYPES_USERS[$type] ?? (string) $type,
                    'organization' => $user->organization !== null ? (int) $user->organization : null,
                ];
            }
        } while (count($page) === $this->pageSize);
    }
}
