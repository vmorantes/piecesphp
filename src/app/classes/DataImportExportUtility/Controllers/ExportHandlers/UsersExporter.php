<?php

/**
 * UsersExporter.php
 */

namespace DataImportExportUtility\Controllers\ExportHandlers;

use App\Model\UsersModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * UsersExporter.
 *
 * @package     DataImportExportUtility\Controllers\ExportHandlers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2024
 */
class UsersExporter
{

    /**
     * @return BaseExportData
     */
    public static function exportData()
    {

        $whereString = null;
        $table = UsersModel::TABLE;
        $where = [
            "{$table}.type = " . UsersModel::TYPE_USER_GENERAL,
        ];

        if (count($where) > 0) {
            $whereString = trim(implode(' ', $where));
        }

        //idPadding
        //fullname
        //names
        //lastNames
        //typeName
        //statusText
        //documentTypeText
        //documentAndType
        //schoolName
        //gradeName
        //groupName
        $selectFields = UsersModel::fieldsToSelect();

        $customOrder = [
            'idPadding ASC',
        ];

        $model = UsersModel::model();

        $model->select($selectFields);

        if ($whereString !== null) {
            $model->having($whereString);
        }

        $model->orderBy($customOrder);

        $model->execute();

        $result = $model->result();

        $usersByID = [];
        $columns = [
            [
                'name' => 'ID',
                'data' => function ($element) {
                    return $element->idPadding;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Documento',
                'data' => function ($element) {
                    return '';
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Nombre 1',
                'data' => function ($element) {
                    return $element->firstname;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Nombre 2',
                'data' => function ($element) {
                    return $element->secondname;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Apellido 1',
                'data' => function ($element) {
                    return $element->first_lastname;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Apellido 2',
                'data' => function ($element) {
                    return $element->second_lastname;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Usuario',
                'data' => function ($element) {
                    return $element->username;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Email',
                'data' => function ($element) {
                    return $element->email;
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Colegio',
                'data' => function ($element) {
                    return '';
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Grado',
                'data' => function ($element) {
                    return '';
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
            [
                'name' => 'Grupo',
                'data' => function ($element) {
                    return '';
                },
                'dataType' => \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING2,
            ],
        ];

        $spreadsheet = new Spreadsheet();
        $writer = new Xlsx($spreadsheet);

        $spreadsheet->setActiveSheetIndex(0);
        $activeSheet = $spreadsheet->getActiveSheet();

        foreach ($columns as $index => $columnConfig) {
            $activeSheet->setCellValue(excelColumnByIndex($index) . '1', $columnConfig['name']);
        }

        $indexColumn = 0;
        $indexRow = 2;

        foreach ($result as $e) {
            if (false) {
                //Saltar según alguna condición
                continue;
            }
            foreach ($columns as $index => $columnConfig) {
                $activeSheet->setCellValueExplicit(excelColumnByIndex($index) . $indexRow, $columnConfig['data']($e), $columnConfig['dataType']);
            }
            $indexRow++;
        }

        $firstColumn = 'A';
        $lastRow = $spreadsheet->getActiveSheet()->getHighestRow();
        $lastColumn = $spreadsheet->getActiveSheet()->getHighestColumn();
        $lastColumnIndex = indexByExcelColumn($lastColumn);

        //Definir ancho de columna - INICIO
        $maxColumnWidth = 30;
        for ($indexColumn = 0; $indexColumn <= $lastColumnIndex; $indexColumn++) {
            $activeSheet->getColumnDimension(excelColumnByIndex($indexColumn))->setAutoSize(true);
        }
        $activeSheet->calculateColumnWidths();
        for ($indexColumn = 0; $indexColumn <= $lastColumnIndex; $indexColumn++) {
            $dimensions = $activeSheet->getColumnDimension(excelColumnByIndex($indexColumn));
            if ($dimensions->getWidth() > $maxColumnWidth) {
                $dimensions->setAutoSize(false);
                $dimensions->setWidth($maxColumnWidth);
            }
        }
        //Definir ancho de columna - FIN

        //Envolver texto y centrar vertical/horizontalmente - INICIO
        $activeSheet->getStyle("{$firstColumn}1:{$lastColumn}{$lastRow}")->getAlignment()->setVertical('center');
        $activeSheet->getStyle("{$firstColumn}1:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal('center');
        $activeSheet->getStyle("{$firstColumn}1:{$lastColumn}{$lastRow}")->getAlignment()->setWrapText(true);
        //Envolver texto y centrar vertical/horizontalmente - FIN

        $fileName = "Usuarios - Exportado el " . date('d-m-Y h i A') . '.xlsx';

        ob_start();
        $writer->save('php://output');
        $fileData = ob_get_contents();
        ob_end_clean();

        $exportData = new BaseExportData($fileName, $fileData);
        return $exportData;
    }

}
