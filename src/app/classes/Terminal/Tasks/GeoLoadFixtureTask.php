<?php

/**
 * GeoLoadFixtureTask.php
 */

namespace Terminal\Tasks;

use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\Database;
use PiecesPHP\Core\Database\EntityMapperExtensible;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
use SystemApprovals\Mappers\SystemApprovalsMapper;
use SystemApprovals\Util\Packages\OrganizationApprovalHandler;
use SystemApprovals\Util\Packages\UsersApprovalHandler;
use Terminal\Seeding\SeedPlanner;

/**
 * GeoLoadFixtureTask.
 *
 * La carga de prueba del geovisor: miles de personas (con perfil completo y público) y organizaciones repartidas por
 * Barranquilla, aprobadas, para medir el mapa con volumen. Sin escribir=yes ni limpiar=yes, SOLO SIMULA. Sus textos
 * llevan `zz-carga-`, que la siembra (`zz-siembra-`) no toca, y se deshace con limpiar=yes.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class GeoLoadFixtureTask extends TerminalTaskAbstract
{

    const PREFIX = 'zz-carga-';
    const DEFAULT_COUNT = 10000;
    const MAX_COUNT = 20000;
    const BATCH = 500;

    /** Una de cada cinco filas es una organización; el resto, personas. */
    const ORGANIZATIONS_EVERY = 5;

    /** Barranquilla: oeste, sur, este, norte. */
    const BBOX = [-74.90, 10.90, -74.75, 11.05];

    /** La fila de índice 0 de cada tipo, para ver el escapado (L18). */
    const HTML_NAME = '<b>zz-carga-html</b>';

    /** La logo de las organizaciones: un archivo versionado, sin copiar nada a disco. */
    const LOGO = 'statics/images/default-avatar-org.jpg';

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "La carga de prueba del geovisor: personas y organizaciones en Barranquilla. Sin escribir=yes ni limpiar=yes, SOLO SIMULA.\r\n",
            "\tParámetros:\r\n",
            "\t  cantidad=<n>     puntos en total, de 1 a " . self::MAX_COUNT . " (por defecto " . self::DEFAULT_COUNT . "): 80 % personas y 20 % organizaciones\r\n",
            "\t  escribir=yes     escribe, solo en la instalación LOCAL y en una transacción\r\n",
            "\t  limpiar=yes      borra lo escrito (prefijo " . self::PREFIX . ") y lo que cuelga de ello",
        ]);
        $this->route = "{$startRoute}/geo-load-fixture[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'geo-load-fixture';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $data = TerminalData::instance();
        $mode = SeedTask::mode($data->getArgument('escribir', ''), $data->getArgument('limpiar', ''));
        if ($mode === null) {
            echoTerminal("\e[31mERROR:\e[39m escribir=yes y limpiar=yes no van juntos: pide uno.");
            exit(1);
        }
        $count = $data->getArgument('cantidad', '');
        $count = is_scalar($count) ? trim((string) $count) : '';
        $count = $count === '' ? self::DEFAULT_COUNT : (preg_match('/^\d+$/', $count) === 1 ? (int) $count : -1);
        if ($count < 1 || $count > self::MAX_COUNT) {
            echoTerminal("\e[31mERROR:\e[39m cantidad=<n> tiene que ser un entero de 1 a " . self::MAX_COUNT . '.');
            exit(1);
        }
        echoTerminal("\e[32m*** Carga del geovisor (" . ($mode === SeedTask::MODE_SIMULATE ? 'simulación' : $mode) . ") ***\e[39m");
        if ($mode !== SeedTask::MODE_SIMULATE && !SeedPlanner::isLocalInstallation()) {
            echoTerminal("\e[31mPARA: la carga solo escribe y borra en la instalación LOCAL (terminal con --local y environment.php en «local»), y esta no lo es.\e[39m");
            exit(1);
        }

        $start = microtime(true);
        try {
            if ($mode === SeedTask::MODE_CLEAN) {
                self::printCounts('== Limpiado en la base LOCAL, en una transacción', self::clean());
            } elseif ($mode === SeedTask::MODE_WRITE) {
                self::printCounts('== Escrito en la base LOCAL, en una transacción (los textos llevan «' . self::PREFIX . '»)', self::write($count));
            } else {
                foreach (self::simulate($count) as $line) {
                    echoTerminal($line);
                }
            }
        } catch (\Throwable $e) {
            echoTerminal("\e[31mPARA: {$e->getMessage()}\e[39m");
            exit(2);
        }
        echoTerminal(sprintf('tiempo: %.1f s', microtime(true) - $start));
        echoTerminal("\e[32m*** Carga del geovisor (" . ($mode === SeedTask::MODE_SIMULATE ? 'simulación: no se escribió nada' : $mode) . "), tarea finalizada ***\e[39m");
        exit(0);
    }

    /**
     * Cuántas personas y cuántas organizaciones salen de una cantidad.
     *
     * @param int $count
     * @return array{0: int, 1: int}
     */
    public static function split(int $count): array
    {
        $organizations = intdiv($count, self::ORGANIZATIONS_EVERY);
        return [$count - $organizations, $organizations];
    }

    /**
     * La posición del punto `$index` de una serie: repartida por el encuadre con una secuencia de baja discrepancia,
     * igual en cada carga y nunca en (0,0).
     *
     * @param int $index
     * @param int $series 0 personas, 1 organizaciones: dos series que no se pisan.
     * @return array{0: float, 1: float} [longitud, latitud]
     */
    public static function position(int $index, int $series): array
    {
        [$west, $south, $east, $north] = self::BBOX;
        $n = $index + 1 + $series * 50000;
        $x = fmod(0.5 + $n * 0.7548776662466927, 1.0);
        $y = fmod(0.5 + $n * 0.5698402909980532, 1.0);
        return [round($west + $x * ($east - $west), 6), round($south + $y * ($north - $south), 6)];
    }

    /**
     * Las filas de la carga, sin ids: las de usuario, perfil y organización van en el orden en que se escriben.
     *
     * @param int $count
     * @param array{city: int, country: int, author: int} $refs
     * @param string $password
     * @return array{organizations: array<int,array<string,mixed>>, users: array<int,array<string,mixed>>, profiles: array<int,array<string,mixed>>}
     */
    public static function rows(int $count, array $refs, string $password): array
    {
        [$persons, $organizations] = self::split($count);
        $now = date('Y-m-d H:i:s');
        $rows = ['organizations' => [], 'users' => [], 'profiles' => []];
        for ($i = 0; $i < $organizations; $i++) {
            $number = str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT);
            [$lng, $lat] = self::position($i, 1);
            $rows['organizations'][] = [
                'name' => ($i === 0 ? self::HTML_NAME . ' ' : '') . self::PREFIX . "organizacion-{$number}",
                'nit' => self::PREFIX . "nit-{$number}",
                'size' => array_keys(OrganizationMapper::SIZES)[$i % count(OrganizationMapper::SIZES)],
                'activitySector' => 'Sector de prueba',
                'actionLines' => [array_keys(OrganizationMapper::ACTION_LINES)[$i % count(OrganizationMapper::ACTION_LINES)]],
                'esal' => $i % 2 === 0 ? OrganizationMapper::ESAL_YES : OrganizationMapper::ESAL_NO,
                'country' => $refs['country'],
                'city' => $refs['city'],
                'address' => 'Calle ' . (1 + $i % 99) . ' # ' . (1 + $i % 60) . '-' . (10 + $i % 80),
                'phoneCode' => '+57',
                'phone' => '605557' . str_pad((string) ($i % 10000), 4, '0', STR_PAD_LEFT),
                'informativeEmail' => self::PREFIX . "organizacion-{$number}@example.invalid",
                'longitude' => $lng,
                'latitude' => $lat,
                'affiliatedInstitutions' => [],
                'logo' => self::LOGO,
                'langData' => new \stdClass(),
                'folder' => self::PREFIX . "organizacion-{$number}",
                'createdBy' => $refs['author'],
                'createdAt' => $now,
                'status' => OrganizationMapper::ACTIVE,
                'publicVisible' => 1,
            ];
        }
        $types = UsersModel::TYPES_USER_SHOULD_HAVE_PROFILE;
        for ($i = 0; $i < $persons; $i++) {
            $number = str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT);
            [$lng, $lat] = self::position($i, 0);
            $username = self::PREFIX . "persona-{$number}";
            $rows['users'][] = [
                'username' => $username,
                'password' => $password,
                'firstname' => $i === 0 ? self::HTML_NAME : self::PREFIX . 'persona',
                'firstLastname' => $number,
                'email' => "{$username}@example.invalid",
                'type' => $types[$i % count($types)],
                //El índice de la organización; write() lo cambia por su id.
                'organization' => $organizations > 0 ? $i % $organizations : null,
                'status' => UsersModel::STATUS_USER_ACTIVE,
                'failedAttempts' => 0,
                'createdAt' => $now,
                'modifiedAt' => $now,
            ];
            $rows['profiles'][] = [
                'jobPosition' => self::PREFIX . "cargo-{$number}",
                'phoneCode' => '+57',
                'phoneNumber' => '605558' . str_pad((string) ($i % 10000), 4, '0', STR_PAD_LEFT),
                'nationality' => 'Colombiana',
                'country' => $refs['country'],
                'city' => $refs['city'],
                'latitude' => $lat,
                'longitude' => $lng,
                'affiliatedInstitutions' => [],
                'publicVisible' => 1,
                'createdBy' => $refs['author'],
                'createdAt' => $now,
            ];
        }
        return $rows;
    }

    /**
     * Lo que se escribiría, sin tocar la base.
     *
     * @param int $count
     * @return string[] Las líneas que imprime la tarea.
     */
    public static function simulate(int $count): array
    {
        $lines = [];
        [$persons, $organizations] = self::split($count);
        $rows = self::rows($count, ['city' => 0, 'country' => 0, 'author' => 0], '<hash>');
        $lines[] = "== Lo que se escribiría (nada se escribe): {$count} puntos en el encuadre " . implode(', ', self::BBOX) . ' (oeste, sur, este, norte)';
        $lines[] = sprintf('  %s: %d fila(s) (personas)', UsersModel::TABLE, $persons);
        $lines[] = sprintf('  %s: %d fila(s) (sus perfiles, completos y públicos)', UserProfileMapper::TABLE, $persons);
        $lines[] = sprintf('  %s: %d fila(s)', OrganizationMapper::TABLE, $organizations);
        $lines[] = sprintf('  %s: %d fila(s) (aprobadas)', SystemApprovalsMapper::TABLE, $persons + $organizations);
        $lines[] = '  (la ciudad, su país y el autor se leen de la base al escribir; aquí salen a 0, y `organization` es el índice de la organización)';
        foreach (['users', 'profiles', 'organizations'] as $kind) {
            if (count($rows[$kind]) > 0) {
                $lines[] = "  primera fila de {$kind}:";
                foreach ($rows[$kind][0] as $column => $value) {
                    $lines[] = sprintf('      %-22s %s', $column, is_scalar($value) || $value === null ? var_export($value, true) : (json_encode($value) ?: ''));
                }
            }
        }
        return $lines;
    }

    /**
     * Escribe en UNA transacción y por lotes. Los ids se leen después por la marca, no se suponen consecutivos.
     *
     * @param int $count
     * @return array<string,array{before: int, after: int}>
     * @throws \RuntimeException Con «PARA» si ya hay carga o si falta una referencia que no se puede inventar.
     */
    public static function write(int $count): array
    {
        $database = self::database();
        if (self::existingCount() > 0) {
            throw new \RuntimeException('ya hay filas de la carga (' . self::PREFIX . '): límpialas antes con limpiar=yes');
        }
        $refs = self::references();
        //Una sola contraseña para todos, aleatoria y no guardada: nadie entra con ellos.
        $rows = self::rows($count, $refs, password_hash(bin2hex(random_bytes(24)), \PASSWORD_DEFAULT));
        $tables = [OrganizationMapper::TABLE, UsersModel::TABLE, UserProfileMapper::TABLE, SystemApprovalsMapper::TABLE];
        $before = array_combine($tables, array_map([SeedPlanner::class, 'countRows'], $tables));
        $now = date('Y-m-d H:i:s');

        $database->beginTransaction();
        try {
            $codes = [];
            foreach ($rows['organizations'] as $index => $row) {
                do {
                    $code = OrganizationMapper::generateCode();
                } while (isset($codes[$code]));
                $codes[$code] = true;
                $rows['organizations'][$index]['code'] = $code;
            }
            $organizationIDs = self::insert(OrganizationMapper::class, $rows['organizations'], 'nit');
            self::stampSlugs(OrganizationMapper::TABLE, $organizationIDs, [OrganizationMapper::class, 'getEncryptIDForSlug']);

            foreach ($rows['users'] as $index => $row) {
                $organization = $row['organization'];
                $rows['users'][$index]['organization'] = $organization === null ? OrganizationMapper::INITIAL_ID_GLOBAL : $organizationIDs[$rows['organizations'][$organization]['nit']];
            }
            $userIDs = self::insert(UsersModel::class, $rows['users'], 'username');

            foreach ($rows['profiles'] as $index => $row) {
                $rows['profiles'][$index]['belongsTo'] = $userIDs[$rows['users'][$index]['username']];
            }
            $profileIDs = self::insert(UserProfileMapper::class, $rows['profiles'], 'belongsTo');
            self::stampSlugs(UserProfileMapper::TABLE, $profileIDs, [UserProfileMapper::class, 'getEncryptIDForSlug']);

            //Aprobadas ya: sin su fila, el arranque crearía una pendiente por cada una y el mapa no las enseñaría.
            $approvals = [];
            foreach ([[UsersModel::TABLE, $userIDs, UsersApprovalHandler::getContentTypes()[0]], [OrganizationMapper::TABLE, $organizationIDs, OrganizationApprovalHandler::getContentTypes()[0]]] as [$table, $ids, $alias]) {
                foreach ($ids as $id) {
                    $approvals[] = [
                        'referenceAlias' => $alias,
                        'referenceValue' => (string) $id,
                        'referenceTable' => $table,
                        'referenceDate' => $now,
                        'createdBy' => UsersModel::SYSTEM_USER_ID,
                        'createdAt' => $now,
                        'status' => SystemApprovalsMapper::STATUS_APPROVED,
                        'approvalAt' => $now,
                        'approvalBy' => UsersModel::SYSTEM_USER_ID,
                    ];
                }
            }
            self::insert(SystemApprovalsMapper::class, $approvals, null);
            $database->commit();
        } catch (\Throwable $e) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw new \RuntimeException('la carga falló y se deshizo entera: ' . $e->getMessage(), 0, $e);
        }

        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = ['before' => $before[$table], 'after' => SeedPlanner::countRows($table)];
        }
        return $counts;
    }

    /**
     * Borra la carga y lo que cuelga de ella: las aprobaciones por tabla e id, y toda fila con una clave foránea a
     * uno de sus usuarios (perfiles, sesiones…) o a una de sus organizaciones. Sin carga, no borra nada y no falla.
     *
     * @return array<string,array{before: int, after: int}>
     */
    public static function clean(): array
    {
        $database = self::database();
        $userIDs = self::idsLike(UsersModel::TABLE, 'username');
        $organizationIDs = self::idsLike(OrganizationMapper::TABLE, 'nit');
        $referencing = [];
        foreach ([UsersModel::TABLE => $userIDs, OrganizationMapper::TABLE => $organizationIDs] as $table => $ids) {
            $statement = $database->prepare('SELECT TABLE_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ? AND REFERENCED_COLUMN_NAME = ? ORDER BY TABLE_NAME, COLUMN_NAME');
            $statement->execute([$table, 'id']);
            foreach ($statement->fetchAll(\PDO::FETCH_NUM) as [$referencingTable, $column]) {
                if (preg_match('/^\w+$/', (string) $referencingTable) !== 1 || preg_match('/^\w+$/', (string) $column) !== 1) {
                    throw new \RuntimeException("«{$referencingTable}.{$column}» no es un nombre de tabla o columna válido");
                }
                //Los usuarios de la carga se borran antes que sus organizaciones: su referencia ya no cuenta.
                if ($referencingTable !== UsersModel::TABLE) {
                    $referencing[] = [(string) $referencingTable, (string) $column, $ids];
                }
            }
        }
        $tables = array_values(array_unique(array_merge([SystemApprovalsMapper::TABLE], array_column($referencing, 0), [UsersModel::TABLE, OrganizationMapper::TABLE])));
        $before = array_combine($tables, array_map([SeedPlanner::class, 'countRows'], $tables));

        $database->beginTransaction();
        try {
            foreach ([UsersModel::TABLE => $userIDs, OrganizationMapper::TABLE => $organizationIDs] as $table => $ids) {
                foreach (array_chunk($ids, self::BATCH) as $chunk) {
                    $database->prepare('DELETE FROM `' . SystemApprovalsMapper::TABLE . '` WHERE referenceTable = ? AND referenceValue IN (' . implode(', ', array_fill(0, count($chunk), '?')) . ')')
                        ->execute(array_merge([$table], array_map('strval', $chunk)));
                }
            }
            foreach ($referencing as [$table, $column, $ids]) {
                foreach (array_chunk($ids, self::BATCH) as $chunk) {
                    $database->prepare("DELETE FROM `{$table}` WHERE `{$column}` IN (" . implode(', ', array_fill(0, count($chunk), '?')) . ')')->execute($chunk);
                }
            }
            foreach ([UsersModel::TABLE => $userIDs, OrganizationMapper::TABLE => $organizationIDs] as $table => $ids) {
                foreach (array_chunk($ids, self::BATCH) as $chunk) {
                    $database->prepare("DELETE FROM `{$table}` WHERE id IN (" . implode(', ', array_fill(0, count($chunk), '?')) . ')')->execute($chunk);
                }
            }
            $database->commit();
        } catch (\Throwable $e) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw new \RuntimeException('el limpiado falló y se deshizo entero: ' . $e->getMessage(), 0, $e);
        }

        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = ['before' => $before[$table], 'after' => SeedPlanner::countRows($table)];
        }
        if (self::existingCount() > 0) {
            throw new \RuntimeException('el limpiado terminó y aún quedan filas ' . self::PREFIX);
        }
        return $counts;
    }

    /**
     * Inserta por lotes las filas tal como las guardaría su mapper (`dataToInsert()`, con su `meta`) y devuelve los
     * ids leídos por la marca, en el orden de las filas.
     *
     * @param class-string<EntityMapperExtensible> $class
     * @param array<int,array<string,mixed>> $rows
     * @param string|null $marker La columna única por fila con la que se leen los ids; null, sin ids.
     * @return array<string,int> Marca => id
     */
    protected static function insert(string $class, array $rows, ?string $marker): array
    {
        if (count($rows) === 0) {
            return [];
        }
        $database = self::database();
        $required = SeedPlanner::requiredFields($class);
        $table = SeedPlanner::tableOf($class);
        $columns = null;
        $values = [];
        foreach ($rows as $index => $row) {
            $missing = array_values(array_diff($required, array_keys($row)));
            if (count($missing) > 0) {
                throw new \RuntimeException("la fila #" . ($index + 1) . " de {$table} no trae campos obligatorios: " . implode(', ', $missing));
            }
            /** @var EntityMapperExtensible $mapper */
            $mapper = new $class();
            foreach ($row as $column => $value) {
                $mapper->$column = $value;
            }
            //`meta` es mágica: la escribe save(), y aquí no hay save().
            if (array_key_exists('meta', $class::getFields())) {
                $mapper->__set('meta', $mapper->metaValueToSave());
            }
            $data = $mapper->dataToInsert()->getData();
            $columns ??= array_keys($data);
            if (array_keys($data) !== $columns) {
                throw new \RuntimeException("la fila #" . ($index + 1) . " de {$table} no tiene las mismas columnas que la primera");
            }
            $values[] = array_values($data);
        }
        $placeholders = '(' . implode(', ', array_fill(0, count($columns), '?')) . ')';
        foreach (array_chunk($values, self::BATCH) as $chunk) {
            $database->prepare("INSERT INTO `{$table}` (" . implode(', ', $columns) . ') VALUES ' . implode(', ', array_fill(0, count($chunk), $placeholders)))
                ->execute(array_merge(...$chunk));
        }
        if ($marker === null) {
            return [];
        }
        $wanted = array_map(static fn(array $row): string => (string) $row[$marker], $rows);
        $ids = [];
        foreach (array_chunk($wanted, self::BATCH) as $chunk) {
            $statement = $database->prepare("SELECT `{$marker}`, id FROM `{$table}` WHERE `{$marker}` IN (" . implode(', ', array_fill(0, count($chunk), '?')) . ')');
            $statement->execute($chunk);
            foreach ($statement->fetchAll(\PDO::FETCH_NUM) as [$mark, $id]) {
                $ids[(string) $mark] = (int) $id;
            }
        }
        if (count($ids) !== count($rows)) {
            throw new \RuntimeException("{$table}: se escribieron " . count($rows) . ' filas y se leen ' . count($ids) . " por «{$marker}»");
        }
        return $ids;
    }

    /**
     * El `preferSlug` que el save() de su mapper pone tras el alta: lleva el id cifrado.
     *
     * @param string $table
     * @param array<string,int> $ids
     * @param callable(int): string $slug
     * @return void
     */
    protected static function stampSlugs(string $table, array $ids, callable $slug): void
    {
        $statement = self::database()->prepare("UPDATE `{$table}` SET preferSlug = ? WHERE id = ?");
        foreach ($ids as $id) {
            $statement->execute([$slug($id), $id]);
        }
    }

    /**
     * Lo que no se inventa: una ciudad que no sea sembrada (Barranquilla si está), su país y el usuario principal.
     *
     * @return array{city: int, country: int, author: int}
     * @throws \RuntimeException
     */
    protected static function references(): array
    {
        $database = self::database();
        $statement = $database->query("SELECT c.id, s.country FROM locations_cities c JOIN locations_states s ON s.id = c.state WHERE c.name NOT LIKE 'zz-%' ORDER BY c.name = 'Barranquilla' DESC, c.id LIMIT 1");
        $city = $statement !== false ? $statement->fetch(\PDO::FETCH_NUM) : false;
        if (!is_array($city)) {
            throw new \RuntimeException('no hay ninguna ciudad en Ubicaciones que no sea sembrada: el perfil completo la exige');
        }
        $statement = $database->prepare('SELECT id FROM `' . UsersModel::TABLE . '` WHERE type = ? ORDER BY id LIMIT 1');
        $statement->execute([UsersModel::TYPE_USER_ROOT]);
        $author = $statement->fetchColumn();
        if (!is_numeric($author)) {
            throw new \RuntimeException('no hay un usuario principal que poner de autor');
        }
        return ['city' => (int) $city[0], 'country' => (int) $city[1], 'author' => (int) $author];
    }

    /**
     * @param string $title
     * @param array<string,array{before: int, after: int}> $counts
     * @return void
     */
    protected static function printCounts(string $title, array $counts): void
    {
        echoTerminal($title);
        foreach ($counts as $table => $count) {
            echoTerminal(sprintf('  %s: antes %d / después %d (%+d)', $table, $count['before'], $count['after'], $count['after'] - $count['before']));
        }
    }

    /**
     * Las filas de la carga que hay ahora: usuarios y organizaciones con la marca.
     *
     * @return int
     */
    public static function existingCount(): int
    {
        return count(self::idsLike(UsersModel::TABLE, 'username')) + count(self::idsLike(OrganizationMapper::TABLE, 'nit'));
    }

    /**
     * @param string $table
     * @param string $column
     * @return int[]
     */
    protected static function idsLike(string $table, string $column): array
    {
        $statement = self::database()->prepare("SELECT id FROM `{$table}` WHERE `{$column}` LIKE ? ORDER BY id");
        $statement->execute([self::PREFIX . '%']);
        return array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @return Database
     */
    protected static function database(): Database
    {
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            throw new \RuntimeException('no hay conexión con la base de datos');
        }
        return $database;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new GeoLoadFixtureTask($startRoute, $namePrefix);
        return new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
    }

}
