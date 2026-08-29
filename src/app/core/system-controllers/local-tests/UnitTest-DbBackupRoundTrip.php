<?php

//Un volcado del que no se puede restaurar no es un respaldo. Ver T45.

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Terminal\CliActions;

CliActions::make('unit-tests:core/db-backup-round-trip', function ($args) {

    $langGroup = 'TestPCSPHP-Lang';
    echoTerminal("\e[33m[TEST:db-backup] El viaje de ida y vuelta del respaldo\e[39m");
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

    $db = (new BaseModel())->getDatabase();
    if ($db === null) {
        echoTerminal("   \e[31mSin conexión: la suite no puede correr.\e[39m");
        return ['success' => false, 'message' => 'sin conexión'];
    }

    //Un usuario propio, con contraseña conocida: no se toca ninguno real.
    $username = 'zz_backup_' . bin2hex(random_bytes(4));
    $plain = 'zz-' . bin2hex(random_bytes(6));
    $hash = password_hash($plain, \PASSWORD_DEFAULT);
    $insert = $db->prepare('INSERT INTO pcsphp_users (username, password, firstname, first_lastname, email, type, status) VALUES (?,?,?,?,?,?,1)');
    $insert->execute([$username, $hash, 'ZZ', 'Backup', $username . '@example.invalid', 1]);
    $userID = (int) $db->lastInsertId();

    $dumpDirectory = basepath('dumps');
    $before = glob($dumpDirectory . '/*.sql') ?: [];

    //En subproceso porque `db-backup` termina con exit(): invocarla aquí mataría la suite.
    $repoRoot = dirname(rtrim(str_replace('\\', '/', basepath('')), '/'));
    @exec(escapeshellarg($repoRoot . '/bin/cli') . ' db-backup gz=no 2>/dev/null');
    $after = array_values(array_diff(glob($dumpDirectory . '/*.sql') ?: [], $before));
    $dump = count($after) > 0 ? $after[0] : null;

    try {

        $check($dump !== null, 'db-backup genera un archivo');
        if ($dump === null) {
            return ['success' => false, 'message' => 'sin volcado'];
        }

        $sql = (string) file_get_contents($dump);
        $enDump = null;
        //El exportador separa los campos con «', '», no con «','».
        if (preg_match("/'" . preg_quote($username, '/') . "',\s*'([^']*)'/", $sql, $matches) === 1) {
            $enDump = $matches[1];
        }

        $check($enDump !== null, 'el usuario de prueba aparece en el volcado');

        //ESTO ES LO QUE FIJA EL DEFECTO: el volcado cifraba el hash y nadie lo descifraba al
        //restaurar, así que password_verify fallaba siempre y NADIE PODÍA ENTRAR.
        $check(
            $enDump === $hash,
            'el hash viaja INTACTO al volcado',
            $enDump !== null ? 'en el volcado hay «' . mb_substr($enDump, 0, 24) . '…» y el hash es «' . mb_substr($hash, 0, 24) . '…»' : ''
        );
        $check(
            $enDump !== null && password_verify($plain, $enDump),
            'password_verify contra lo que hay EN EL VOLCADO devuelve true',
            'una restauración con esto dejaría a todos sin poder entrar'
        );

        //Y EL VIAJE ENTERO, que es lo que de verdad se prometía: restaurar y entrar. Leer el
        //archivo no basta —un volcado puede tener el hash bien y no restaurar—.
        $tmpDatabase = 'pcs_zz_backup_round_trip';
        $originalDatabase = (string) $db->getDatabaseName();
        try {
            $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`");
            $db->exec("CREATE DATABASE `{$tmpDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin");

            $bloque = static function (string $inicio) use ($sql): string {
                $i = strpos($sql, $inicio);
                if ($i === false) {
                    return '';
                }
                $j = $i;
                $comilla = false;
                $escape = false;
                $total = strlen($sql);
                while ($j < $total) {
                    $ch = $sql[$j];
                    if ($escape) {
                        $escape = false;
                    } elseif ($ch === '\\') {
                        $escape = true;
                    } elseif ($ch === "'") {
                        $comilla = !$comilla;
                    } elseif ($ch === ';' && !$comilla) {
                        return substr($sql, $i, $j - $i);
                    }
                    $j++;
                }
                return '';
            };

            $create = $bloque('CREATE TABLE IF NOT EXISTS `pcsphp_users` (');
            //Sin claves ajenas: la tabla se restaura sola en una base vacía.
            $create = implode("\n", array_filter(
                explode("\n", $create),
                static fn (string $line): bool => strpos($line, 'CONSTRAINT') === false && strpos($line, 'FOREIGN KEY') === false
            ));
            $create = (string) preg_replace('/,(\s*\n\s*\))/', '$1', $create);
            $insert = $bloque('INSERT INTO `pcsphp_users`');

            foreach ([$create, $insert] as $statement) {
                if (trim($statement) === '') {
                    continue;
                }
                $db->exec(str_replace('`pcsphp_users`', "`{$tmpDatabase}`.`pcsphp_users`", $statement));
            }

            $restored = $db->prepare("SELECT password FROM `{$tmpDatabase}`.`pcsphp_users` WHERE username = ?");
            $restored->execute([$username]);
            $restoredHash = (string) $restored->fetchColumn();

            $check(
                $restoredHash !== '' && password_verify($plain, $restoredHash),
                'RESTAURADO en una base de usar y tirar, el usuario PUEDE ENTRAR',
                'password_verify contra la base restaurada devuelve false: la copia no sirve de red'
            );

        } finally {
            $db->exec("DROP DATABASE IF EXISTS `{$tmpDatabase}`");
            $db->exec("USE `{$originalDatabase}`");
        }

    } finally {
        $db->prepare('DELETE FROM pcsphp_users WHERE id = ?')->execute([$userID]);
        $db->prepare('DELETE FROM user_system_profile WHERE belongsTo = ?')->execute([$userID]);
        //LA FILA DE APROBACIÓN NO LA ESCRIBE ESTA SUITE, PERO ES SUYA: al arrancar la app,
        //`SystemApprovalManager::init()` rellena una por cada usuario que no la tenga, y el
        //subproceso de arriba arranca mientras el usuario de prueba existe. Ver T140.
        $db->prepare('DELETE FROM system_approvals_elements WHERE referenceTable = ? AND referenceValue = ?')
            ->execute(['pcsphp_users', (string) $userID]);
        foreach ($after as $file) {
            @unlink($file);
        }
        echoTerminal('');
        echoTerminal('   usuario y volcado de prueba borrados');
    }

    echoTerminal('');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('Comprueba que un volcado de db-backup se puede restaurar y permite entrar.')->setEffects([CliActions::EFFECT_DATABASE, CliActions::EFFECT_FILES])->register();
