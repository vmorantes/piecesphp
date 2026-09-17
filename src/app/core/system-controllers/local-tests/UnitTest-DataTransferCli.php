<?php

//La importación por terminal: todas las negativas antes de importar y las credenciales a un archivo 0600 fuera del proyecto.
//Lanza bin/cli data-transfer-import como proceso aparte (sale con exit), crea usuarios zz-prueba-cli-* y los borra.

use App\Model\UsersModel;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;

CliActions::make('unit-tests:core/data-transfer-cli', function ($args) {

    echoTerminal("\e[33m[TEST:DataTransferCli] Importación por terminal\e[39m");
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

    $marca = bin2hex(random_bytes(3));
    $prefijo = "zz-prueba-cli-{$marca}";
    $proyecto = dirname(basepath());
    $csv = sys_get_temp_dir() . "/{$prefijo}.csv";
    $credenciales = sys_get_temp_dir() . "/zz-cred-{$marca}.html";
    $dentro = "{$proyecto}/zz-cred-{$marca}.html";

    $delPrefijo = function () use ($prefijo): array {
        $m = UsersModel::model();
        $m->resetAll();
        $m->select()->where(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        return array_values((array) $m->result());
    };
    //Salida y código de salida del proceso; los argumentos van escapados uno a uno.
    $tarea = function (array $argumentos) use ($proyecto): array {
        $comando = escapeshellarg("{$proyecto}/bin/cli") . ' data-transfer-import';
        foreach ($argumentos as $nombre => $valor) {
            $comando .= ' ' . escapeshellarg("{$nombre}={$valor}");
        }
        $salida = [];
        $codigo = -1;
        //RETORNO-IGNORADO: el resultado va en $salida y $codigo; el retorno es solo la última línea.
        exec($comando . ' 2>&1', $salida, $codigo);
        return [$codigo, (string) preg_replace('/\e\[[0-9;]*m/', '', implode("\n", $salida))];
    };

    try {
        $root = UsersModel::model();
        $root->resetAll();
        $root->select()->where(['type' => UsersModel::TYPE_USER_ROOT])->execute();
        $idRoot = (int) (((array) $root->result())[0]->id ?? 0);
        $check($idRoot > 0, 'banco: hay un usuario root');
        $escrito = file_put_contents($csv, "username,email,firstname,first_lastname\n{$prefijo}-a,{$prefijo}-a@example.com,Zz,Prueba\n{$prefijo}-b,{$prefijo}-b@example.com,Zz,Prueba\n");
        $check($escrito !== false, 'banco: el CSV de la prueba se escribe');

        echoTerminal('[1/2] Negativas antes de importar');
        [$codigo, $salida] = $tarea(['definition' => 'users', 'file' => $csv, 'as-user' => '999999999', 'credentials-out' => $credenciales]);
        $check($codigo === 1 && str_contains($salida, 'no es un usuario existente') && count($delPrefijo()) === 0, 'c1 as-user inexistente → 1 y nada creado', $salida);
        [$codigo, $salida] = $tarea(['definition' => 'users', 'file' => $csv, 'as-user' => (string) $idRoot]);
        $check($codigo === 1 && str_contains($salida, 'credentials-out') && count($delPrefijo()) === 0, 'c2 sin credentials-out con users → 1 y 0 usuarios creados', $salida);
        [$codigo, $salida] = $tarea(['definition' => 'users', 'file' => $csv, 'as-user' => (string) $idRoot, 'credentials-out' => $dentro]);
        $check($codigo === 1 && str_contains($salida, 'dentro del proyecto') && count($delPrefijo()) === 0 && !file_exists($dentro), 'c3 credentials-out dentro del proyecto → 1, nada creado y ningún archivo', $salida);
        echoTerminal(' ');

        echoTerminal('[2/2] Importación con credenciales fuera del proyecto');
        [$codigo, $salida] = $tarea(['definition' => 'users', 'file' => $csv, 'as-user' => (string) $idRoot, 'credentials-out' => $credenciales]);
        $creados = $delPrefijo();
        $check($codigo === 0 && count($creados) === 2 && str_contains($salida, "Credenciales en: {$credenciales}"), 'c4 → 0, 2 usuarios creados y la salida da la ruta', $salida);
        $permisos = is_file($credenciales) ? (fileperms($credenciales) & 0777) : -1;
        $contenido = is_file($credenciales) ? (string) file_get_contents($credenciales) : '';
        $check($permisos === 0600 && substr_count($contenido, 'class="card"') === 2, 'c5 el archivo tiene permisos 0600 y las 2 fichas', sprintf('%o', $permisos));
        $claves = [];
        if (preg_match_all('#<dt>Contraseña</dt><dd>([^<]+)</dd>#u', $contenido, $m) > 0) {
            $claves = array_map(fn($c) => htmlspecialchars_decode($c, ENT_QUOTES), $m[1]);
        }
        $check(count($claves) === 2 && count(array_filter($claves, fn($c) => str_contains($salida, $c))) === 0, 'c6 ninguna contraseña aparece en la salida de la terminal');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300));
    } finally {
        $ids = array_map(fn($u) => (int) $u->id, $delPrefijo());
        if (count($ids) > 0) {
            $perfiles = UserProfileMapper::model();
            $perfiles->resetAll();
            $perfiles->delete(new WhereSegment([new WhereItem('belongsTo', WhereItem::IN_OPERATOR, '(' . implode(',', $ids) . ')')]))->execute();
            $usuarios = UsersModel::model();
            $usuarios->resetAll();
            $usuarios->delete(new WhereSegment([WhereItem::like('username', "{$prefijo}%")]))->execute();
        }
        foreach ([$csv, $credenciales, $dentro] as $ruta) {
            if (is_file($ruta)) {
                //RETORNO-IGNORADO: limpieza del banco de la suite; se comprueba abajo con is_file.
                unlink($ruta);
            }
        }
        $check(count($delPrefijo()) === 0 && !is_file($csv) && !is_file($credenciales) && !is_file($dentro), 'z1 limpieza: 0 usuarios de la prueba y 0 archivos');
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('La importación por terminal comprueba todo antes de importar y deja las credenciales en un archivo 0600 fuera del proyecto.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
