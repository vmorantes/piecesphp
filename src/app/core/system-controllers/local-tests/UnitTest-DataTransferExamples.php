<?php

//Los ejemplos que la guía incrusta funcionan de verdad, y sus marcadores de fragmento existen: si falta uno, la guía no compila.
//Escribe usuarios zz-ejemplo-* de verdad y los borra en el finally: db-backup antes.

use PiecesPHP\UserSystem\ORM\UsersModel;
use DataImportExportUtility\Controllers\DataTransferController;
use DataImportExportUtility\DataImportExportUtilityRoutes;
use DataImportExportUtility\Examples\ExampleUserNamesImportDefinition;
use DataImportExportUtility\Examples\ExampleUsersReportExportDefinition;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Core\DataTransfer\Export\ExportContext;
use PiecesPHP\Core\DataTransfer\Export\ExportParameterException;
use PiecesPHP\Core\DataTransfer\Export\SpreadsheetExportWriter;
use PiecesPHP\Core\DataTransfer\Import\ImportArtifacts;
use PiecesPHP\Core\DataTransfer\Import\ImportRunner;
use PiecesPHP\Core\DataTransfer\Source\ArrayRowSource;
use PiecesPHP\Core\RouteGroup;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/data-transfer-examples', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferExamples] Ejemplos de la guía\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    //Cuenta las llamadas a persist() sin tocar el ejemplo.
    if (!class_exists('ZzCountingUserNamesImport', false)) {
        class ZzCountingUserNamesImport extends ExampleUserNamesImportDefinition
        {
            /** @var int */
            public static $persistCalls = 0;
            public function persist(array $rows): ?ImportArtifacts
            {
                self::$persistCalls++;
                return parent::persist($rows);
            }
        }
    }

    $marca = 'zz-ejemplo-' . bin2hex(random_bytes(3));
    $dir = sys_get_temp_dir() . '/' . $marca;
    if (!mkdir($dir)) {
        throw new \RuntimeException("No se puede crear «{$dir}».");
    }
    $delPrefijo = function () use ($marca): array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select()->where(new WhereSegment([WhereItem::like('username', "{$marca}%")]))->execute();
        return array_values((array) $m->result());
    };
    $nombres = function (string $username): ?array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select(['firstname', 'secondname', 'firstLastname', 'secondLastname'])->where(new WhereSegment([WhereItem::isEqual('username', $username)]))->execute();
        $row = ((array) $m->result())[0] ?? null;
        $data = is_object($row) ? (array) $row : null;
        return $data !== null ? [(string) ($data['firstname'] ?? ''), (string) ($data['secondname'] ?? ''), (string) ($data['firstLastname'] ?? ''), (string) ($data['secondLastname'] ?? '')] : null;
    };
    $importar = function (array $rows, bool $dryRun = false) {
        $headers = ['username', 'firstname', 'secondname', 'first_lastname', 'second_lastname'];
        return (new ImportRunner())->run(new ZzCountingUserNamesImport(), new ArrayRowSource($headers, $rows), $dryRun);
    };
    $previoUsuario = get_config('current_user');
    $previoGuardado = get_config('pcsphp_current_user_stored');

    try {
        $root = UsersModel::model();
        $root->resetAll();
        $root->select(['id', 'username'])->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $filaRoot = ((array) $root->result())[0] ?? null;
        $datosRoot = is_object($filaRoot) ? (array) $filaRoot : [];
        $usernameRoot = (string) ($datosRoot['username'] ?? '');
        set_config('current_user', (object) ['id' => (int) ($datosRoot['id'] ?? 0)]);
        set_config('pcsphp_current_user_stored', null);

        //─── a · Registro ───────────────────────────────────────────────────────────────────────────────
        echoTerminal('[a] Registro, solo en este proceso');
        DataImportExportUtilityRoutes::importer(new RouteGroup('zz-ejemplos'), ExampleUserNamesImportDefinition::class);
        DataImportExportUtilityRoutes::exporter(new RouteGroup('zz-ejemplos'), ExampleUsersReportExportDefinition::class);
        $check(array_key_exists('example-user-names', DataImportExportUtilityRoutes::importers()) && array_key_exists('example-users-report', DataImportExportUtilityRoutes::exporters()), 'a1 los dos ejemplos se registran');
        echoTerminal(' ');

        //─── b · Importador ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[b] Importador de nombres');
        $plantilla = "{$dir}/plantilla.xlsx";
        DataTransferController::writeImportTemplate(new ExampleUserNamesImportDefinition(), 'xlsx', $plantilla);
        $hoja = IOFactory::load($plantilla)->getActiveSheet();
        $check((string) $hoja->getCell('A1')->getValue() === 'Usuario' && $hoja->getStyle('A1')->getFont()->getBold() && str_contains($hoja->getComment('A1')->getText()->getPlainText(), 'Ejemplo: ana.perez') && $hoja->getStyle('B1')->getFont()->getBold() && !$hoja->getStyle('C1')->getFont()->getBold(), 'b1 plantilla XLSX: obligatorias en negrita, opcionales no, y comentarios');

        ZzCountingUserNamesImport::$persistCalls = 0;
        $informe = $importar([[$usernameRoot, 'Zz', '', 'Prueba', '']], true);
        $check($usernameRoot !== '' && $informe->isDryRun() && $informe->invalidRows() === 0 && $informe->validRows() === 1 && ZzCountingUserNamesImport::$persistCalls === 0 && !$informe->persisted(), 'b2 simulacro con un usuario real: 0 errores y sin persist', (string) json_encode($informe, JSON_UNESCAPED_UNICODE));
        $informe = $importar([["{$marca}-nadie", 'A', '', 'B', ''], [$usernameRoot, 'A', '', 'B', ''], [mb_strtoupper($usernameRoot), 'A', '', 'B', '']], true);
        $errores = array_map(fn($r) => $r->errors(), $informe->rowResults());
        $check(count($errores) === 3 && str_contains(implode(' ', $errores[0]), 'no existe') && $errores[1] === [] && str_contains(implode(' ', $errores[2]), 'se repite en la fila 2'), 'b3 usuario inexistente y usuario repetido → errores de fila', (string) json_encode($errores, JSON_UNESCAPED_UNICODE));

        //De verdad, sin transacción de la prueba: persist() tiene la suya.
        $crear = function (string $sufijo) use ($marca): string {
            $u = new UsersModel();
            $u->username = "{$marca}-{$sufijo}";
            $u->email = "{$marca}-{$sufijo}@example.com";
            $u->password = password_hash('zz-Clave-1', \PASSWORD_DEFAULT);
            $u->firstname = 'Original';
            $u->firstLastname = 'Original';
            $u->type = UsersModel::TYPE_USER_GENERAL;
            $u->status = UsersModel::STATUS_USER_ACTIVE;
            $u->failedAttempts = 0;
            $u->organization = \Organizations\Mappers\OrganizationMapper::INITIAL_ID_GLOBAL;
            $u->createdAt = new \DateTime();
            $u->modifiedAt = $u->createdAt;
            if (!$u->save()) {
                throw new \RuntimeException("No se pudo crear {$sufijo}.");
            }
            return (string) $u->username;
        };
        $uno = $crear('uno');
        $dos = $crear('dos');
        $informe = $importar([[$uno, 'Ana', 'María', 'Pérez', 'Gómez'], [$dos, 'Luis', '', 'Díaz', '']]);
        $check($informe->persisted() && $nombres($uno) === ['Ana', 'María', 'Pérez', 'Gómez'] && $nombres($dos) === ['Luis', '', 'Díaz', ''], 'b4 importación real: los nombres cambian en la base', (string) json_encode([$nombres($uno), $nombres($dos)], JSON_UNESCAPED_UNICODE));

        //La última fila no cabe en la columna: el UPDATE falla en la base, después de haber actualizado la primera.
        $informe = $importar([[$uno, 'Cambiado', '', 'Cambiado', ''], [$dos, str_repeat('x', 5000), '', 'Díaz', '']]);
        $check(!$informe->persisted() && str_contains(implode(' ', $informe->headerErrors()), 'no se cambió ningún usuario'), 'b5 un fallo de la base en la última fila → no se guarda y el mensaje no trae datos internos', (string) json_encode($informe, JSON_UNESCAPED_UNICODE));
        $check($nombres($uno) === ['Ana', 'María', 'Pérez', 'Gómez'] && $nombres($dos) === ['Luis', '', 'Díaz', ''], 'b6 todo o nada: la primera fila vuelve a como estaba', (string) json_encode([$nombres($uno), $nombres($dos)], JSON_UNESCAPED_UNICODE));
        echoTerminal(' ');

        //─── c · Informe ────────────────────────────────────────────────────────────────────────────────
        echoTerminal('[c] Informe de usuarios');
        $informeDef = new ExampleUsersReportExportDefinition(1);
        $contexto = $informeDef->buildContext(['search' => $marca, 'types' => [(string) UsersModel::TYPE_USER_GENERAL], 'active' => 'yes'], null);
        $xlsx = "{$dir}/informe.xlsx";
        $filas = (new SpreadsheetExportWriter())->toXlsx($informeDef, $contexto, $xlsx);
        $libro = IOFactory::load($xlsx);
        $principal = $libro->getSheet(0);
        $resumen = $libro->getSheet(1);
        $check($filas === 2 && $libro->getSheetNames() === ['Informe de usuarios (ejemplo)', 'Resumen por tipo'], 'c1 dos hojas y 2 filas en la principal (paginando de 1 en 1)', (string) json_encode([$filas, $libro->getSheetNames()], JSON_UNESCAPED_UNICODE));
        $check((string) $principal->getCell('A1')->getValue() === 'Informe de usuarios (ejemplo)' && str_starts_with((string) $principal->getCell('A2')->getValue(), 'Filtros: Tipos: Usuario general; Activo: Sí; Usuario o correo contiene: ' . $marca), 'c2 título y filtros aplicados con sus etiquetas', (string) $principal->getCell('A2')->getValue());
        $dibujos = $principal->getDrawingCollection();
        $check(count($dibujos) === 1 && ($dibujos[0] ?? null) !== null && $dibujos[0]->getCoordinates() === 'F1', 'c3 imagen en F1');
        //Filas: 1 título, 2 filtros, 3 fecha, 4 vacía, 5 encabezado, 6-7 datos, 8 total.
        $check($principal->getCell('A8')->getValue() === '=COUNTA(A6:A7)' && (int) $principal->getCell('A8')->getCalculatedValue() === 2, 'c4 total COUNT como fórmula', (string) $principal->getCell('A8')->getValue());
        $check((string) $principal->getCell('A6')->getValue() === $uno && (string) $principal->getCell('C6')->getValue() === 'Ana María Pérez Gómez' && (string) $principal->getCell('D6')->getValue() === 'Usuario general' && (string) $principal->getCell('E6')->getValue() === 'Sí' && $principal->getCell('F6')->getDataType() === DataType::TYPE_NUMERIC, 'c5 transform, etiqueta de tipo, booleano y fecha como número de Excel');
        //Resumen: 1 título, 2 vacía, 3 encabezado, 4 datos, 5 total.
        $check((string) $resumen->getCell('A4')->getValue() === 'Usuario general' && (int) $resumen->getCell('B4')->getValue() === 2 && abs((float) $resumen->getCell('C4')->getValue() - 1.0) < 1e-9 && $resumen->getCell('B5')->getValue() === '=SUM(B4:B4)', 'c6 resumen por tipo: cantidad, porcentaje calculado en PHP y SUM', (string) json_encode([$resumen->getCell('A4')->getValue(), $resumen->getCell('B4')->getValue(), $resumen->getCell('C4')->getValue(), $resumen->getCell('B5')->getValue()]));
        $csv = "{$dir}/informe.csv";
        (new SpreadsheetExportWriter())->toCsv($informeDef, $contexto, $csv);
        $lineas = file($csv, FILE_IGNORE_NEW_LINES) ?: [];
        $cabecera = str_getcsv(ltrim($lineas[0] ?? '', "\xEF\xBB\xBF"), ',', '"', '');
        $check(count($lineas) === 3 && $cabecera === ['Usuario', 'Correo', 'Nombre completo', 'Tipo', 'Activo', 'Creado'], 'c7 CSV: solo la hoja principal, sin título ni totales', (string) json_encode($lineas, JSON_UNESCAPED_UNICODE));
        try {
            $informeDef->buildContext(['types' => ['99']], null);
            $check(false, 'c8 un tipo que no existe → ExportParameterException', 'no lanzó');
        } catch (ExportParameterException $e) {
            $check(true, 'c8 un tipo que no existe → ExportParameterException');
        }
        $comodin = $informeDef->buildContext(['search' => '%'], null);
        $check(iterator_count((function () use ($informeDef, $comodin) {
            yield from $informeDef->rows($comodin);
        })()) === 0 && iterator_count((function () use ($informeDef) {
            yield from $informeDef->rows(new ExportContext(['created' => null, 'types' => [], 'active' => null, 'search' => null], null));
        })()) > 2, 'c9 search «%» no es comodín: 0 filas, cuando sin filtro hay usuarios');
        $check(str_starts_with($informeDef->fileName($contexto), 'Informe de usuarios todos') && str_contains($informeDef->fileName($informeDef->buildContext(['created_from' => '2026-01-01'], null)), '2026-01-01 a …'), 'c10 nombre de archivo según el rango', $informeDef->fileName($contexto));
        echoTerminal(' ');

        //─── d · Marcadores ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[d] Marcadores de fragmento para la guía');
        $carpeta = basepath('app/classes/DataImportExportUtility/Examples');
        $esperados = [
            'import-columns' => 'ExampleUserNamesImportDefinition.php',
            'import-validate-all' => 'ExampleUserNamesImportDefinition.php',
            'import-persist' => 'ExampleUserNamesImportDefinition.php',
            'export-parameters' => 'ExampleUsersReportExportDefinition.php',
            'export-columns' => 'ExampleUsersReportExportDefinition.php',
            'export-rows' => 'ExampleUsersReportExportDefinition.php',
            'export-sheets' => 'ExampleUsersReportExportDefinition.php',
            'export-file-name' => 'ExampleUsersReportExportDefinition.php',
            'export-after-export' => 'ExampleUsersReportExportDefinition.php',
            'register' => 'register-examples.php',
        ];
        $mal = [];
        foreach ($esperados as $nombre => $archivo) {
            $lineasArchivo = file("{$carpeta}/{$archivo}", FILE_IGNORE_NEW_LINES) ?: [];
            $starts = array_keys(array_filter($lineasArchivo, fn($l) => trim($l) === "// --8<-- [start:{$nombre}]"));
            $ends = array_keys(array_filter($lineasArchivo, fn($l) => trim($l) === "// --8<-- [end:{$nombre}]"));
            if (count($starts) !== 1 || count($ends) !== 1 || $starts[0] >= $ends[0]) {
                $mal[] = $nombre;
                continue;
            }
            echoTerminal(sprintf('   %s: %s líneas %d-%d', $nombre, $archivo, $starts[0] + 1, $ends[0] + 1));
        }
        $check($mal === [], 'd1 los diez fragmentos, cada uno con un start y un end en orden', implode(', ', $mal));
        $registro = (string) file_get_contents("{$carpeta}/register-examples.php");
        $check(str_contains($registro, 'DataImportExportUtilityRoutes::importer($group, ExampleUserNamesImportDefinition::class);') && str_contains($registro, 'DataImportExportUtilityRoutes::exporter($group, ExampleUsersReportExportDefinition::class);'), 'd2 register-examples.php registra las dos clases');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    } finally {
        set_config('current_user', $previoUsuario);
        set_config('pcsphp_current_user_stored', $previoGuardado);
        $ids = array_map(fn($u) => (int) $u->id, $delPrefijo());
        if (count($ids) > 0) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            $perfiles->delete(new WhereSegment([new WhereItem('belongsTo', WhereItem::IN_OPERATOR, '(' . implode(',', $ids) . ')')]))->execute();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$marca}%")]))->execute();
        }
        foreach (glob("{$dir}/*") ?: [] as $file) {
            //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
            @unlink($file);
        }
        //RETORNO-IGNORADO: limpieza del temporal propio de la prueba.
        @rmdir($dir);
        $check(count($delPrefijo()) === 0 && !is_dir($dir), 'z1 limpieza: 0 usuarios zz-ejemplo-* y 0 temporales');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Los ejemplos de importador e informe de la guía funcionan, y sus marcadores de fragmento existen.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
