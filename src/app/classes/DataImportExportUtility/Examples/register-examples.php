<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

//No se incluye desde ningún sitio: es el fragmento que la guía enseña. Los ejemplos no están en el panel.

use DataImportExportUtility\DataImportExportUtilityRoutes;
use DataImportExportUtility\Examples\ExampleUserNamesImportDefinition;
use DataImportExportUtility\Examples\ExampleUsersReportExportDefinition;
/** @var \PiecesPHP\Core\RouteGroup $group El grupo que recibe el routes() del módulo */

// --8<-- [start:register]
DataImportExportUtilityRoutes::importer($group, ExampleUserNamesImportDefinition::class);
DataImportExportUtilityRoutes::exporter($group, ExampleUsersReportExportDefinition::class);
// --8<-- [end:register]
