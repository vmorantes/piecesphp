<?php

/**
 * ExampleUsersReportExportDefinition.php
 */

namespace DataImportExportUtility\Examples;

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\DataImportExportUtilityLang;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItemGroup;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\DateRange;
use PiecesPHP\Core\DataTransfer\Export\ExportColumn;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportDefinition;
use PiecesPHP\Core\DataTransfer\Export\ExportParameter;
use PiecesPHP\Core\DataTransfer\Export\ExportResult;
use PiecesPHP\Core\DataTransfer\Export\ExportSheet;

/**
 * ExampleUsersReportExportDefinition - Ejemplo de la guía: un informe de usuarios con filtros, dos hojas, totales e imagen.
 *
 * No está registrado en el panel: la guía lo incrusta y su suite lo registra en su proceso.
 *
 * @package     DataImportExportUtility\Examples
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class ExampleUsersReportExportDefinition extends ExportDefinition
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
        return 'example-users-report';
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return __(self::LANG_GROUP, 'Informe de usuarios (ejemplo)');
    }

    /**
     * @return string
     */
    public function description(): string
    {
        return __(self::LANG_GROUP, 'Informe de usuarios con filtros, resumen por tipo y totales (ejemplo de la guía).');
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
     * Nivel 2: vista previa, elegir columnas y filtros guardados.
     *
     * @return int
     */
    public function interfaceLevel(): int
    {
        return self::INTERFACE_EXTENDED;
    }

    // --8<-- [start:export-parameters]
    /**
     * Cada filtro llega ya validado y tipado a rows(): un valor inválido es un 400 antes de consultar nada.
     *
     * @return ExportParameter[]
     */
    public function parameters(): array
    {
        return [
            //En la URL son dos claves: created_from y created_to.
            ExportParameter::dateRange('created', __(self::LANG_GROUP, 'Creado')),
            //Lista blanca: solo llegan claves de UsersModel::TYPES_USERS.
            ExportParameter::multiChoice('types', __(self::LANG_GROUP, 'Tipos'), UsersModel::TYPES_USERS),
            //Tres estados: sí, no o sin filtro (null).
            ExportParameter::boolean('active', __(self::LANG_GROUP, 'Activo')),
            ExportParameter::text('search', __(self::LANG_GROUP, 'Usuario o correo contiene'))
                ->help(__(self::LANG_GROUP, 'Busca el texto tal cual: % y _ no son comodines.')),
        ];
    }
    // --8<-- [end:export-parameters]

    // --8<-- [start:export-columns]
    /**
     * @param ExportContext $context
     * @return ExportColumn[]
     */
    public function columns(ExportContext $context): array
    {
        return [
            new ExportColumn('username', __(self::LANG_GROUP, 'Usuario')),
            new ExportColumn('email', __(self::LANG_GROUP, 'Correo')),
            //transform() calcula el valor desde la fila entera; la fila no necesita una key «fullName».
            (new ExportColumn('fullName', __(self::LANG_GROUP, 'Nombre completo')))
                ->transform(fn(array $row) => trim(implode(' ', array_filter([
                    $row['firstname'] ?? null,
                    $row['secondname'] ?? null,
                    $row['first_lastname'] ?? null,
                    $row['second_lastname'] ?? null,
                ], fn($part) => is_string($part) && $part !== '')))),
            (new ExportColumn('typeLabel', __(self::LANG_GROUP, 'Tipo')))
                ->transform(fn(array $row) => UsersModel::TYPES_USERS[(int) ($row['type'] ?? -1)] ?? (string) ($row['type'] ?? '')),
            (new ExportColumn('active', __(self::LANG_GROUP, 'Activo')))->asBoolean()->align(ExportColumn::ALIGN_CENTER),
            (new ExportColumn('createdAt', __(self::LANG_GROUP, 'Creado')))->asDateTime(),
        ];
    }
    // --8<-- [end:export-columns]

    // --8<-- [start:export-rows]
    /**
     * Por páginas y avanzando por id: nunca la tabla entera en memoria. Cada valor del usuario va por marcador.
     *
     * @param ExportContext $context
     * @return iterable<array<string,mixed>>
     */
    public function rows(ExportContext $context): iterable
    {
        $lastID = 0;
        do {
            $model = UsersModel::model();
            $model->resetAll();
            $model->select(['id', 'username', 'email', 'firstname', 'secondname', 'firstLastname', 'secondLastname', 'type', 'status', 'createdAt'])
                ->where($this->filters($context, $lastID))
                ->orderBy('id ASC')
                ->execute(false, 1, $this->pageSize);
            $page = (array) $model->result();

            foreach ($page as $user) {
                $lastID = (int) $user->id;
                yield [
                    'username' => $user->username,
                    'email' => $user->email,
                    'firstname' => $user->firstname,
                    'secondname' => $user->secondname,
                    'first_lastname' => $user->firstLastname,
                    'second_lastname' => $user->secondLastname,
                    'type' => (int) $user->type,
                    'active' => (int) $user->status === UsersModel::STATUS_USER_ACTIVE,
                    'createdAt' => $user->createdAt,
                ];
            }
        } while (count($page) === $this->pageSize);
    }

    /**
     * Los filtros como grupos AND; dentro de un grupo, OR. Ningún valor entra en el texto del SQL.
     *
     * @param ExportContext $context
     * @param int $afterID
     * @return WhereSegment
     */
    private function filters(ExportContext $context, ?int $afterID = null): WhereSegment
    {
        $where = new WhereSegment();
        if ($afterID !== null) {
            $where->addCritery(new WhereItem('id', WhereItem::GREATER_THAN_OPERATOR, $afterID));
        }

        $created = $context->get('created');
        if ($created instanceof DateRange && $created->from() !== null) {
            $where->addCritery(new WhereItem('createdAt', WhereItem::GREATER_OR_EQUAL_OPERATOR, $created->from()->format('Y-m-d H:i:s')));
        }
        if ($created instanceof DateRange && $created->to() !== null) {
            $where->addCritery(new WhereItem('createdAt', WhereItem::LESS_OR_EQUAL_OPERATOR, $created->to()->format('Y-m-d H:i:s')));
        }

        //IN no pasa por marcador en esta biblioteca: un igual por tipo, unidos con OR.
        $types = (array) $context->get('types');
        if (count($types) > 0) {
            $items = [];
            foreach (array_values($types) as $index => $type) {
                $items[] = WhereItem::isEqual('type', (int) $type, $index < count($types) - 1 ? WhereItem::OR_OPERATOR : '');
            }
            $where->addGroup(new WhereItemGroup($items));
        }

        $active = $context->get('active');
        if ($active === true) {
            $where->addCritery(WhereItem::isEqual('status', UsersModel::STATUS_USER_ACTIVE));
        } elseif ($active === false) {
            $where->addCritery(WhereItem::isNotEqual('status', UsersModel::STATUS_USER_ACTIVE));
        }

        $search = $context->get('search');
        if (is_string($search) && $search !== '') {
            //Sin escapar, «%» o «_» del usuario serían comodines de LIKE.
            $pattern = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';
            $where->addGroup(new WhereItemGroup([
                WhereItem::like('username', $pattern, WhereItem::OR_OPERATOR),
                WhereItem::like('email', $pattern),
            ]));
        }

        return $where;
    }
    // --8<-- [end:export-rows]

    // --8<-- [start:export-sheets]
    /**
     * Solo el XLSX: el CSV es siempre la hoja principal de columns()/rows(), sin títulos ni totales.
     *
     * @param ExportContext $context
     * @return ExportSheet[]
     */
    public function sheets(ExportContext $context): array
    {
        //mainSheet() ya aplica la elección de columnas del formulario; aquí solo se le añade lo de alrededor.
        $main = $this->mainSheet($context)
            ->heading($this->title())
            ->appliedFilters($context)
            ->generatedAt()
            ->image(basepath('statics/images/logo.png'), 'F1', 40)
            ->total('username', ExportSheet::AGGREGATE_COUNT);

        //El resumen se calcula aquí, en PHP: las fórmulas del motor no cruzan hojas.
        $counts = $this->countByType($context);
        $all = array_sum($counts);
        $summary = [];
        foreach ($counts as $type => $count) {
            $summary[] = [
                'type' => UsersModel::TYPES_USERS[$type] ?? (string) $type,
                'count' => $count,
                'share' => $all > 0 ? $count / $all : 0,
            ];
        }

        return [
            $main,
            (new ExportSheet(__(self::LANG_GROUP, 'Resumen por tipo'), [
                new ExportColumn('type', __(self::LANG_GROUP, 'Tipo')),
                (new ExportColumn('count', __(self::LANG_GROUP, 'Cantidad')))->asInteger(),
                (new ExportColumn('share', __(self::LANG_GROUP, 'Porcentaje')))->asPercent(1),
            ], $summary))
                ->heading(__(self::LANG_GROUP, 'Resumen por tipo'))
                ->total('count', ExportSheet::AGGREGATE_SUM),
        ];
    }

    /**
     * @param ExportContext $context
     * @return array<int,int> tipo => cantidad, con los mismos filtros que la hoja principal
     */
    private function countByType(ExportContext $context): array
    {
        $model = UsersModel::model();
        $model->resetAll();
        $model->select(['type', 'COUNT(id) AS total']);
        //Sin ningún filtro no hay WHERE: un segmento vacío daría SQL inválido.
        $where = $this->filters($context);
        if ($where->countCriteria() > 0) {
            $model->where($where);
        }
        $model->groupBy('type')
            ->orderBy('type ASC')
            ->execute();
        $counts = [];
        foreach ((array) $model->result() as $row) {
            $counts[(int) $row->type] = (int) $row->total;
        }
        return $counts;
    }
    // --8<-- [end:export-sheets]

    // --8<-- [start:export-file-name]
    /**
     * Sin extensión: la pone el controlador según el formato. El controlador limpia lo que no sirve en un nombre.
     *
     * @param ExportContext $context
     * @return string
     */
    public function fileName(ExportContext $context): string
    {
        $created = $context->get('created');
        if (!$created instanceof DateRange || $created->isEmpty()) {
            return sprintf(__(self::LANG_GROUP, 'Informe de usuarios %s'), __(self::LANG_GROUP, 'todos'));
        }
        $range = sprintf(
            __(self::LANG_GROUP, '%s a %s'),
            $created->from() !== null ? $created->from()->format('Y-m-d') : '…',
            $created->to() !== null ? $created->to()->format('Y-m-d') : '…'
        );
        return sprintf(__(self::LANG_GROUP, 'Informe de usuarios %s'), $range);
    }
    // --8<-- [end:export-file-name]

    // --8<-- [start:export-after-export]
    /**
     * Se llama con el archivo ya generado y antes de enviarlo; si lanza, no se descarga nada.
     *
     * @param ExportContext $context
     * @param ExportResult $result
     * @return void
     */
    public function afterExport(ExportContext $context, ExportResult $result): void
    {
        //Aquí iría marcar como exportados los registros incluidos, en UNA transacción: si falla, no hay descarga.
        //Este informe no marca nada; $result trae formato, nombre, bytes y filas.
    }
    // --8<-- [end:export-after-export]

    /**
     * Es un informe: no se reimporta.
     *
     * @return string|null
     */
    public function importDefinition(): ?string
    {
        return null;
    }
}
