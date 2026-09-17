<?php

//Lo que hace HOY el importador de usuarios, antes del lote 8 (ADR 0022): cada caso marca [SE CONSERVA] o [CAMBIA].
//Escribe usuarios zz-prueba-car-* y los borra en el finally: db-backup antes.

use App\Model\UsersModel;
use Importers\Controller\ImporterController;
use Importers\Managers\ImporterUsers;
use PiecesPHP\Core\Importer\Schema;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/importers-legacy', function ($args) {

    echoTerminal("\e[33m[TEST:ImportersLegacy] Qué hace hoy el importador de usuarios (Importers + Core\\Importer)\e[39m");
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

    $marca = bin2hex(random_bytes(4));
    $prefijo = "zz-prueba-car-{$marca}";
    $temporales = [];

    //El validador real de email consulta el DNS (MX): la suite no depende de la red.
    $importador = function (array $filas, ?array &$insertados = null) {
        $importer = new ImporterUsers($filas);
        $importer->getSchema()->getFieldByName('email')->setValidator(fn($value) => is_string($value) && str_contains($value, '@'));
        if ($insertados !== null) {
            $importer->getSchema()->setAlternativeInsert(function (Schema $instance, array $values) use (&$insertados) {
                $insertados[] = $values;
                return true;
            });
        }
        return $importer;
    };
    $fila = function (string $sufijo, array $extra = []) use ($prefijo): array {
        return array_merge([
            'username' => "{$prefijo}-{$sufijo}",
            'email' => "{$prefijo}-{$sufijo}@localhost.test",
            'password' => 'zz-clave-de-prueba',
            'firstname' => 'Zz',
            'first_lastname' => 'Prueba',
        ], $extra);
    };
    $resumen = fn(array $respuestas): string => implode(' | ', array_map(fn($r) => var_export($r->getSuccess(), true) . ': ' . strip_tags((string) $r->getMessage()), $respuestas));

    try {

        //─── 1/8 · Cabeceras ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[1/8] Cabeceras y columnas');
        $insertados = [];
        $importer = $importador([[
            'USUARIO' => "{$prefijo}-cab",
            'email' => "{$prefijo}-cab@localhost.test",
            'Contraseña' => 'x',
            'primer nombre' => 'Zz',
            'first_lastname' => 'Prueba',
            'zz_columna_no_declarada' => 'valor',
        ]], $insertados);
        $importer->import();
        $respuestas = $importer->getResponses();
        $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === true, 'c1 [SE CONSERVA] la cabecera casa por nombre o por etiqueta, sin distinguir mayúsculas', $resumen($respuestas));
        $check(count($insertados) === 1 && !array_key_exists('zz_columna_no_declarada', $insertados[0]) && !array_key_exists('ZZ_COLUMNA_NO_DECLARADA', $insertados[0]), 'c2 [SE CONSERVA] una columna no declarada se ignora', json_encode($insertados, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $check(count($insertados) === 1 && array_keys($insertados[0]) === ['id', 'username', 'password', 'firstname', 'secondname', 'first_lastname', 'second_lastname', 'email', 'type'], 'c3 [CAMBIA → ruptura 5] las columnas que acepta hoy son id, username, password, firstname, secondname, first_lastname, second_lastname, email y type', json_encode(array_keys($insertados[0] ?? []), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $check(count($insertados) === 1 && password_verify('x', (string) $insertados[0]['password']), 'c4 [SE CONSERVA] la contraseña se guarda con password_hash');
        echoTerminal(' ');

        //─── 2/8 · Una fila válida crea el usuario ──────────────────────────────────────────────────────
        echoTerminal('[2/8] Una fila válida crea el usuario en la base');
        $conHtml = "{$prefijo}-<i>x</i>";
        $importer = $importador([$fila('real', ['username' => $conHtml])]);
        $importer->import();
        $respuestas = $importer->getResponses();
        $creado = UsersModel::isDuplicateUsername($conHtml);
        $check(count($respuestas) === 1 && $respuestas[0]->getSuccess() === true && $creado, 'r1 [SE CONSERVA] la fila válida inserta el usuario en pcsphp_users', $resumen($respuestas));
        echoTerminal(' ');

        //─── 3/8 · Filas independientes ─────────────────────────────────────────────────────────────────
        echoTerminal('[3/8] Una fila inválida entre válidas');
        $insertados = [];
        $importer = $importador([1 => $fila('a'), 2 => array_diff_key($fila('b'), ['username' => true]), 3 => $fila('c')], $insertados);
        $importer->import();
        $respuestas = $importer->getResponses();
        $exitos = array_map(fn($r) => $r->getSuccess(), $respuestas);
        $check($exitos === [true, false, true] && count($insertados) === 2, 'f1 [CAMBIA → ADR 0022 §3] las filas son independientes: la inválida se rechaza y las otras dos se guardan', $resumen($respuestas));
        $check(array_map(fn($r) => $r->getPosition(), $respuestas) === [1, 2, 3], 'f2 [CAMBIA → ruptura 4] cada respuesta lleva la posición (clave de la fila)');
        $totalProcesado = $importer->getTotalProcessed();
        $check($totalProcesado === 3 && $importer->getTotalProcessed() === 0, 'f3 [CAMBIA → ruptura 1] los contadores se reinician al leerlos', "primera lectura {$totalProcesado}");
        echoTerminal(' ');

        //─── 4/8 · Celda vacía ──────────────────────────────────────────────────────────────────────────
        echoTerminal('[4/8] Celda vacía, leída desde un archivo');
        //Una celda que el archivo NO trae no aparece; una que existe sin valor (con formato, como en las plantillas de Excel) sí.
        $csv = sys_get_temp_dir() . "/{$prefijo}.csv";
        $temporales[] = $csv;
        $escrito = file_put_contents($csv, "username,secondname,email\n{$prefijo}-v,,{$prefijo}-v@localhost.test\n");
        $check($escrito !== false, 'banco: el CSV temporal se escribe');
        $leido = (new ImporterController())->readFile($csv);
        $filaLeida = is_array($leido) ? (array_values($leido)[0] ?? []) : [];
        $check(count($filaLeida) === 2 && !array_key_exists('secondname', $filaLeida), 'v1 [SE CONSERVA] una celda que el archivo no trae (CSV «a,,c») no aparece en la fila', json_encode($filaLeida, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $xlsx = sys_get_temp_dir() . "/{$prefijo}.xlsx";
        $temporales[] = $xlsx;
        $libro = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $hoja = $libro->getActiveSheet();
        $hoja->setCellValue('A1', 'username');
        $hoja->setCellValue('B1', 'secondname');
        $hoja->setCellValue('A2', "{$prefijo}-v");
        $hoja->getCell('B2')->getStyle()->getFont()->setBold(true);
        \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($libro, 'Xlsx')->save($xlsx);
        $leido = (new ImporterController())->readFile($xlsx);
        $filaConFormato = is_array($leido) ? (array_values($leido)[0] ?? []) : [];
        $check(($filaConFormato['secondname'] ?? null) === 'NULL', "v2 [CAMBIA → ruptura 5] una celda que existe sin valor (con formato) se lee como la cadena 'NULL'", json_encode($filaConFormato, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        echoTerminal(' ');

        //─── 5/8 · type e id ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[5/8] Columnas type e id (tras #132)');
        $insertados = [];
        $importer = $importador([1 => $fila('t0', ['type' => '0']), 2 => $fila('t2', ['type' => '2']), 3 => $fila('i1', ['id' => 'abc']), 4 => $fila('i2', ['id' => ''])], $insertados);
        $importer->import();
        $respuestas = $importer->getResponses();
        $exitos = array_map(fn($r) => $r->getSuccess(), $respuestas);
        $check(($exitos[0] ?? null) === false && ($exitos[1] ?? null) === true, "t1 [CAMBIA → ruptura 5] la columna type se lee y se VALIDA: '0' se rechaza, '2' pasa (mañana se ignora)", $resumen($respuestas));
        $check(($exitos[2] ?? null) === false && ($exitos[3] ?? null) === true, "t2 [CAMBIA → ruptura 5] la columna id se lee y se VALIDA: 'abc' se rechaza, vacía pasa (mañana se ignora)", $resumen($respuestas));
        echoTerminal(' ');

        //─── 6/8 · Respuesta ────────────────────────────────────────────────────────────────────────────
        echoTerminal('[6/8] Forma de la respuesta y HTML en los mensajes');
        $importer = $importador([$fila('dup', ['username' => $conHtml])], $insertados);
        $importer->import();
        $respuestas = $importer->getResponses();
        $mensaje = (string) ($respuestas[0]->getMessage() ?? '');
        $check(str_contains($mensaje, $conHtml), 'm1 [CAMBIA → ruptura 4] el mensaje lleva el valor del archivo SIN escapar (aquí, <i>)', $mensaje);
        $check(str_contains($mensaje, '</br>'), 'm2 [CAMBIA → ruptura 4] los mensajes se separan con HTML (</br>)', $mensaje);
        $check(array_keys(json_decode((string) json_encode($respuestas[0]), true) ?: []) === ['success', 'message', 'position'], 'm3 [CAMBIA → ruptura 4] cada mensaje serializa success, message y position');
        $reflejo = new \ReflectionMethod(ImporterController::class, 'import');
        $lineas = file((string) $reflejo->getFileName());
        $cuerpo = implode('', array_slice(is_array($lineas) ? $lineas : [], $reflejo->getStartLine() - 1, $reflejo->getEndLine() - $reflejo->getStartLine() + 1));
        $claves = [];
        foreach (token_get_all('<?php ' . $cuerpo) as $t) {
            if (is_array($t) && $t[0] === \T_CONSTANT_ENCAPSED_STRING && preg_match("/^'(success|message|messages|total|inserted)'$/", $t[1], $m) === 1) {
                $claves[$m[1]] = true;
            }
        }
        $check(count($claves) === 5, 'm4 [CAMBIA → ruptura 4] el JSON de la acción tiene success, message, messages, total e inserted', implode(', ', array_keys($claves)));
        echoTerminal(' ');

        //─── 7/8 · Formatos ─────────────────────────────────────────────────────────────────────────────
        echoTerminal('[7/8] Formatos que acepta hoy');
        $html = sys_get_temp_dir() . "/{$prefijo}.html";
        $temporales[] = $html;
        $escrito = file_put_contents($html, "<html><body><table><tr><td>username</td></tr><tr><td>{$prefijo}-html</td></tr></table></body></html>");
        $check($escrito !== false, 'banco: el HTML temporal se escribe');
        $desdeHtml = (new ImporterController())->readFile($html);
        $check(is_array($desdeHtml) && ((array_values($desdeHtml)[0]['username'] ?? null) === "{$prefijo}-html"), 'x1 [CAMBIA → ruptura 6] IOFactory adivina el lector: un HTML con una tabla se importa', json_encode($desdeHtml, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        $check(count($filaLeida) > 0, 'x2 [SE CONSERVA] un CSV se importa (ver v1)');
        echoTerminal(' ');

        //─── 8/8 · DataImportExportUtility ──────────────────────────────────────────────────────────────
        echoTerminal('[8/8] DataImportExportUtility sigue apagado');
        $fuente = (string) file_get_contents(basepath('app/classes/DataImportExportUtility/DataImportExportUtilityRoutes.php'));
        $tokens = array_values(array_filter(token_get_all($fuente), fn($t) => !is_array($t) || !in_array($t[0], [\T_WHITESPACE, \T_COMMENT, \T_DOC_COMMENT], true)));
        $apagado = false;
        foreach ($tokens as $i => $t) {
            if (is_array($t) && $t[0] === \T_CONST && is_array($tokens[$i + 1] ?? null) && $tokens[$i + 1][1] === 'ENABLE' && ($tokens[$i + 2] ?? null) === '=' && is_array($tokens[$i + 3] ?? null) && strtolower($tokens[$i + 3][1]) === 'false') {
                $apagado = true;
            }
        }
        $check($apagado, 'u1 [CAMBIA → ADR 0022 §2] DataImportExportUtilityRoutes: const ENABLE = false (por tokens)');

    } catch (\Throwable $e) {
        $check(false, 'la caracterización corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 200));
    } finally {
        foreach ($temporales as $temporal) {
            if (is_file($temporal)) {
                //RETORNO-IGNORADO: limpieza del banco de la suite; se comprueba abajo con is_file.
                unlink($temporal);
            }
        }
        $modelo = (new UsersModel())->getModel();
        $modelo->resetAll();
        $modelo->delete(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([
            \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::like('username', "{$prefijo}%"),
        ]))->execute();
        $modelo->resetAll();
        $restos = $modelo->select()->where(new \PiecesPHP\Core\Database\ORM\Statements\WhereSegment([
            \PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem::like('username', 'zz-prueba-car-%'),
        ]))->execute() ? count((array) $modelo->result()) : -1;
        $check($restos === 0 && count(array_filter($temporales, 'is_file')) === 0, 'z1 limpieza: 0 usuarios zz-prueba-car-* y 0 temporales', "usuarios {$restos}");
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Caracteriza el importador de usuarios actual antes del lote 8: qué se conserva y qué cambia.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
