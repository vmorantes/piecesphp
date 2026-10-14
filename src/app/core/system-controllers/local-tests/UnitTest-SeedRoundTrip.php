<?php

//Ida y vuelta de la siembra contra la base LOCAL y su disco: escribe TODOS los sembradores, comprueba y deshace. Si algo
//queda, el bloque [z] lo limpia y lo dice; y si al empezar ya había algo sembrado, no hay veredicto.

//La sesión del usuario principal la fija la suite y la restaura al final: varios mappers la exigen al guardar, y una
//suite no pasa por el inicio de sesión forzado de la terminal.

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\BuiltIn\Banner\Mappers\BuiltInBannerMapper;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\UserSystem\ORM\UsersModel;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\SystemApprovalManager;
use Terminal\Seeding\ModuleSeeder;
use Terminal\Seeding\SeedContext;
use Terminal\Seeding\SeedPlanner;

CliActions::make('unit-tests:core/seed-round-trip', function ($args) {

    echoTerminal("\e[33m[TEST:SeedRoundTrip] Lo sembrado sube las cuentas, lleva el prefijo, sus archivos y sus aprobaciones, y el limpiado lo devuelve todo\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): bool {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
        return $condition;
    };
    $balance = function () use (&$passed, &$failed): array {
        $total = $passed + $failed;
        echoTerminal($failed === 0
            ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
            : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");
        return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];
    };

    $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
    $ordered = SeedPlanner::order(SeedPlanner::discover('all'));
    //Las tablas que se miran salen de los sembradores descubiertos: las suyas y las de sus filas propias.
    $tables = [];
    $bases = [];
    foreach ($ordered as $seeder) {
        $tables[] = SeedPlanner::tableOf($seeder->mapper());
        $tables = array_merge($tables, array_keys($seeder->ownedRows()));
        $owned = $seeder->ownedFolder();
        if ($owned !== null) {
            $bases[] = $owned['base'];
        }
    }
    $tables = array_values(array_unique($tables));
    sort($tables);
    $bases = array_values(array_unique($bases));
    $counts = function () use ($tables): array {
        $result = [];
        foreach ($tables as $table) {
            $result[$table] = SeedPlanner::countRows($table);
        }
        return $result;
    };
    $seededTotal = fn (): int => array_sum(array_map('count', SeedPlanner::seededIds($ordered)));
    $seededFolders = function () use ($bases): array {
        $found = [];
        foreach ($bases as $base) {
            $found = array_merge($found, glob(basepath($base) . '/' . ModuleSeeder::PREFIX . '*') ?: []);
        }
        return $found;
    };

    echoTerminal('[canario] El punto de partida se puede leer y es limpio');
    $start = $counts();
    $check(SeedPlanner::isLocalInstallation(), 'c1 es la instalación local', 'is_local() y environment.php');
    $check(count($ordered) > 2 && $start[UsersModel::TABLE] > 0 && $start[OrganizationMapper::TABLE] > 0, 'c2 hay sembradores y las cuentas de partida se leen', count($ordered) . ' sembradores · ' . (json_encode($start) ?: ''));
    if (!$check($seededTotal() === 0 && count($seededFolders()) === 0, 'c3 no hay nada sembrado antes de empezar, ni filas ni carpetas', "{$seededTotal()} fila(s) y " . count($seededFolders()) . ' carpeta(s) con el prefijo')) {
        echoTerminal("\e[31m Ya había algo sembrado: las cuentas de partida no serían las de verdad. Límpialo con bin/cli seed module=all limpiar=yes. \e[39m");
        return $balance();
    }
    echoTerminal(' ');

    $previousUser = get_config('current_user');
    $previousStored = get_config('pcsphp_current_user_stored');
    $rootId = (new SeedContext())->existingUserId(UsersModel::TYPE_USER_ROOT) ?? 0;
    set_config('current_user', (object) ['id' => $rootId]);
    set_config('pcsphp_current_user_stored', null);
    $decoyId = null;
    $count = 2;
    try {
        echoTerminal('[1] La siembra, de todos los sembradores');
        $written = SeedPlanner::write($ordered, $count, new SeedContext([], true));
        $afterWrite = $counts();
        $short = array_filter($ordered, static fn (ModuleSeeder $s): bool => count($written[$s->mapper()] ?? []) !== $count);
        $check(count($short) === 0, "1a cada uno de los " . count($ordered) . " sembradores escribe {$count} filas", implode(', ', array_map(static fn (ModuleSeeder $s): string => get_class($s), $short)));
        $rise = [];
        foreach ($ordered as $seeder) {
            $table = SeedPlanner::tableOf($seeder->mapper());
            $rise[$table] = ($rise[$table] ?? 0) + $count;
        }
        $wrong = array_filter(array_keys($rise), static fn (string $table): bool => $afterWrite[$table] !== $start[$table] + $rise[$table]);
        $check(count($wrong) === 0, '1b las cuentas de las tablas sembradas suben exactamente en lo sembrado', implode(', ', array_map(static fn (string $t): string => "{$t} {$start[$t]} -> {$afterWrite[$t]}", $wrong)));

        $markers = [];
        $folders = [];
        foreach ($ordered as $seeder) {
            $ids = $written[$seeder->mapper()] ?? [];
            if (count($ids) === 0 || $database === null) {
                continue;
            }
            $statement = $database->prepare('SELECT `' . $seeder->markerColumn() . '` FROM `' . SeedPlanner::tableOf($seeder->mapper()) . '` WHERE id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')');
            $statement->execute($ids);
            $markers = array_merge($markers, $statement->fetchAll(\PDO::FETCH_COLUMN));
            $owned = $seeder->ownedFolder();
            if ($owned !== null) {
                $statement = $database->prepare('SELECT `' . $owned['column'] . '` FROM `' . SeedPlanner::tableOf($seeder->mapper()) . '` WHERE id IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')');
                $statement->execute($ids);
                foreach ($statement->fetchAll(\PDO::FETCH_COLUMN) as $folder) {
                    $folders[] = basepath($owned['base'] . '/' . $folder);
                }
            }
        }
        $unprefixed = array_filter($markers, static fn ($value): bool => !str_starts_with((string) $value, ModuleSeeder::PREFIX));
        $check(count($markers) === $count * count($ordered) && count($unprefixed) === 0, '1c todas las filas sembradas llevan el prefijo ' . ModuleSeeder::PREFIX, count($markers) . ' leídas, ' . count($unprefixed) . ' sin prefijo');
        $empty = array_filter($folders, static fn (string $folder): bool => count(\PiecesPHP\Core\Statics\ProtectedUploads::listFiles($folder)) === 0);
        $check(count($folders) === $count * count($bases) && count($empty) === 0, '1d cada fila con carpeta propia la tiene en disco, con sus archivos', count($folders) . ' carpeta(s), ' . count($empty) . ' vacía(s) o ausente(s)');

        $orgIds = $written[OrganizationMapper::class] ?? [];
        $userIds = $written[UsersModel::class] ?? [];
        $references = [];
        if ($database !== null && count($userIds) > 0) {
            $statement = $database->prepare('SELECT u.organization, (SELECT COUNT(*) FROM `' . OrganizationMapper::TABLE . '` o WHERE o.id = u.organization) AS found FROM `' . UsersModel::TABLE . '` u WHERE u.id IN (' . implode(', ', array_fill(0, count($userIds), '?')) . ')');
            $statement->execute($userIds);
            $references = $statement->fetchAll(\PDO::FETCH_ASSOC);
        }
        $broken = array_filter($references, static fn (array $row): bool => (int) $row['found'] !== 1 || !in_array((int) $row['organization'], $orgIds, true));
        $check(count($references) === $count && count($broken) === 0, '1e cada usuario sembrado apunta a una organización sembrada que existe', json_encode($references) ?: '');

        //Lo que hace cualquier arranque de la aplicación: crear las aprobaciones de lo nuevo.
        SystemApprovalManager::init();
        //Las que se esperan, de los sembradores que declaran sus aprobaciones como filas propias.
        $approvals = 0;
        $expectedApprovals = 0;
        foreach ($ordered as $seeder) {
            $owned = $seeder->ownedRows()[SystemApprovalsMapper::TABLE] ?? null;
            $ids = $written[$seeder->mapper()] ?? [];
            if (!is_array($owned) || $database === null || count($ids) === 0) {
                continue;
            }
            $expectedApprovals += count($ids);
            $statement = $database->prepare('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue IN (' . implode(', ', array_fill(0, count($ids), '?')) . ')');
            $statement->execute(array_merge([(string) ($owned['where']['referenceTable'] ?? '')], $ids));
            $approvals += (int) $statement->fetchColumn();
        }
        $check($expectedApprovals >= 2 * $count && $approvals === $expectedApprovals, '1f el arranque crea una aprobación por cada fila sembrada que la tiene', "{$approvals} de {$expectedApprovals}");

        //El señuelo: otra tabla, el MISMO número que una organización sembrada. El limpiado no lo puede tocar.
        if ($database !== null && count($orgIds) > 0) {
            $statement = $database->prepare('INSERT INTO `' . SystemApprovalsMapper::TABLE . '` (referenceAlias, referenceValue, referenceTable, referenceDate, createdBy, createdAt, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $statement->execute([ModuleSeeder::PREFIX . 'senuelo', (string) $orgIds[0], 'zz_siembra_senuelo', date('Y-m-d H:i:s'), $rootId, date('Y-m-d H:i:s'), SystemApprovalsMapper::STATUS_PENDING]);
            $decoyId = (int) $database->lastInsertId();
        }
        echoTerminal(' ');

        echoTerminal('[2] El limpiado, de la base y del disco');
        $report = SeedPlanner::clean($ordered, $cleanedFolders);
        $afterClean = $counts();
        $expected = $start;
        $expected[SystemApprovalsMapper::TABLE] += 1;
        $differ = array_filter(array_keys($start), static fn (string $t): bool => $afterClean[$t] !== $expected[$t]);
        $check(count($differ) === 0, '2a todas las tablas vuelven a las de partida, aprobaciones incluidas (más el señuelo)', implode(', ', array_map(static fn (string $t): string => "{$t} {$expected[$t]} -> {$afterClean[$t]}", $differ)));
        $survives = false;
        if ($database !== null && $decoyId !== null) {
            $statement = $database->prepare('SELECT COUNT(*) FROM `' . SystemApprovalsMapper::TABLE . '` WHERE id = ?');
            $statement->execute([$decoyId]);
            $survives = (int) $statement->fetchColumn() === 1;
        }
        $check($survives, '2b la aprobación señuelo, de otra tabla con el mismo número, sobrevive al limpiado', "id {$decoyId}");
        $check($seededTotal() === 0, '2c no queda ninguna fila con el prefijo en las tablas sembradas');
        $left = $seededFolders();
        $removed = array_filter($cleanedFolders ?? [], static fn (array $f): bool => $f['status'] === SeedPlanner::FOLDER_REMOVED);
        $check(count($left) === 0 && count($removed) === count($folders), '2d no queda ninguna carpeta zz-siembra-* en las ' . count($bases) . ' bases, y el limpiado dice cada una', count($left) . ' quedan; ' . count($removed) . ' borradas de ' . count($folders));
        $removedRows = array_sum(array_map(static fn (array $table): int => count($table['ids']), $report));
        $check($removedRows >= $count * count($ordered) + $expectedApprovals,'2e el limpiado enumera lo que borró, aprobaciones incluidas', "{$removedRows} fila(s)");
        $again = SeedPlanner::clean($ordered);
        $check(array_sum(array_map(static fn (array $t): int => count($t['ids']), $again)) === 0, '2f limpiar sin nada sembrado no borra nada y no falla');
        echoTerminal(' ');

        echoTerminal('[3] Un sembrador que falla a mitad no deja ni filas ni archivos');
        $failing = new class extends ModuleSeeder {
            public function mapper(): string
            {
                return BuiltInBannerMapper::class;
            }
            public function markerColumn(): string
            {
                return 'title';
            }
            public function ownedFolder(): ?array
            {
                return ['base' => 'statics/uploads/built-in-banner', 'column' => 'folder'];
            }
            public function row(int $index, SeedContext $context): array
            {
                $folder = $this->text('banner-que-falla', $index);
                $image = $context->copyToUploads('statics/images/generic-image-placeholder.png', 'statics/uploads/built-in-banner', $folder, 'imagen.png', false);
                if ($index > 0) {
                    throw new \RuntimeException('fallo provocado tras copiar');
                }
                return ['title' => $folder, 'desktopImage' => $image, 'folder' => $folder, 'createdBy' => $context->existingUserId(UsersModel::TYPE_USER_ROOT)];
            }
        };
        $message = '';
        try {
            SeedPlanner::write([$failing], 2, new SeedContext([], true));
        } catch (\RuntimeException $e) {
            $message = $e->getMessage();
        }
        $check(str_contains($message, 'se deshizo entera') && str_contains($message, 'fallo provocado'), '3a la escritura falla y se deshace', $message ?: 'no falló');
        $check(count(SeedPlanner::seededIds([$failing])[BuiltInBannerMapper::TABLE] ?? []) === 0, '3b no queda la fila que sí se había guardado');
        $check(count(glob(basepath('statics/uploads/built-in-banner') . '/' . ModuleSeeder::PREFIX . '*') ?: []) === 0, '3c no queda ninguno de los archivos que se copiaron');
    } catch (\RuntimeException $e) {
        $check(false, 'la ida y vuelta no termina', $e->getMessage());
    } finally {
        set_config('current_user', $previousUser);
        set_config('pcsphp_current_user_stored', $previousStored);
    }
    echoTerminal(' ');

    echoTerminal('[z] Limpieza');
    $leftRows = $seededTotal();
    if ($leftRows > 0) {
        try {
            SeedPlanner::clean($ordered);
        } catch (\RuntimeException $e) {
            echoTerminal("   \e[31m{$e->getMessage()}\e[39m");
        }
    }
    if ($database !== null && $decoyId !== null) {
        $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE id = ? AND referenceTable = ?')->execute([$decoyId, 'zz_siembra_senuelo']);
    }
    $check($seededTotal() === 0 && count($seededFolders()) === 0, 'z1 no queda nada sembrado al terminar, ni filas ni carpetas', $leftRows > 0 ? "quedaban {$leftRows} fila(s) y se intentaron limpiar" : '');
    $check($counts() === $start, 'z2 todas las tablas, aprobaciones incluidas, quedan como al empezar');

    return $balance();

})->setDescription('La siembra de todos los sembradores contra la base local y su disco: sube las cuentas, crea sus archivos y sus aprobaciones, y el limpiado lo devuelve todo a la de partida sin tocar lo ajeno.')->setEffects([CliActions::EFFECT_DATABASE])->register();
