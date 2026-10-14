<?php

/**
 * SeedPlanner.php
 */

namespace Terminal\Seeding;

use News\Mappers\NewsMapper;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\Database\SchemeCreator;
use PiecesPHP\UserSystem\ORM\UsersModel;
use Terminal\Tasks\SchemeCreateTask;

/**
 * SeedPlanner.
 *
 * Descubre los seeders, resuelve su orden y genera las filas. El recorrido de carpetas y la
 * lista de descartados son los de `SchemeSqlTask`: dos listas serían dos verdades.
 *
 * @package     Terminal\Seeding
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SeedPlanner
{

    /** La carpeta, dentro de cada módulo, donde viven sus seeders. */
    const SEEDS_DIRECTORY = 'Seeds';

    /** Lo que el limpiado dice de cada carpeta propia. */
    const FOLDER_REMOVED = 'borrada';
    const FOLDER_MISSING = 'no existía';
    const FOLDER_FAILED = 'NO SE PUDO BORRAR';

    /**
     * Los seeders de un módulo, o de todos con `all`.
     *
     * @param string $module
     * @return ModuleSeeder[]
     */
    public static function discover(string $module = 'all'): array
    {
        $root = rtrim(str_replace('\\', '/', basepath('')), '/') . '/app/classes';
        $bases = mb_strtolower($module) === 'all' ? (glob($root . '/*', GLOB_ONLYDIR) ?: []) : [$root . '/' . $module];

        $seeders = [];
        foreach ($bases as $base) {
            if (!is_dir($base)) {
                continue;
            }
            /** @var string[] $directories */
            $directories = \Closure::bind(static fn () => SchemeCreateTask::mapperDirectoriesUnder($base), null, SchemeCreateTask::class)();
            foreach ($directories as $directory) {
                if (basename($directory) !== self::SEEDS_DIRECTORY) {
                    continue;
                }
                foreach (glob($directory . '/*.php') ?: [] as $file) {
                    /** @var string|null $class */
                    $class = \Closure::bind(static fn () => SchemeCreateTask::declaredClassOf($file), null, SchemeCreateTask::class)();
                    if ($class === null || !class_exists($class)) {
                        continue;
                    }
                    $reflection = new \ReflectionClass($class);
                    if ($reflection->isAbstract() || !$reflection->isSubclassOf(ModuleSeeder::class)) {
                        continue;
                    }
                    $seeder = $reflection->newInstance();
                    if ($seeder instanceof ModuleSeeder) {
                        $seeders[get_class($seeder)] = $seeder;
                    }
                }
            }
        }
        ksort($seeders);
        return array_values($seeders);
    }

    /**
     * Lo que hay y lo que falta: los mappers que descubre `scheme-create` contra los que tienen seeder.
     *
     * @param ModuleSeeder[] $seeders
     * @param string $module
     * @return array{modules: string[], mappers: array<string,string>, with: string[], without: string[], skipped: string[]}
     */
    public static function universe(array $seeders, string $module = 'all'): array
    {
        /** @var array{creators: SchemeCreator[], mappers: EntityMapper[], skipped: string[]} $found */
        $found = \Closure::bind(static fn () => SchemeCreateTask::discover($module), null, SchemeCreateTask::class)();
        $mappers = [];
        $modules = [];
        foreach ($found['mappers'] as $i => $mapper) {
            $class = get_class($mapper);
            $mappers[$class] = $found['creators'][$i]->getTable();
            $modules[explode('\\', $class)[0]] = true;
        }
        ksort($mappers);
        $seeded = [];
        foreach ($seeders as $seeder) {
            $seeded[$seeder->mapper()] = true;
        }
        $with = array_values(array_filter(array_keys($mappers), static fn (string $class): bool => isset($seeded[$class])));
        $without = array_values(array_filter(array_keys($mappers), static fn (string $class): bool => !isset($seeded[$class])));
        $modules = array_keys($modules);
        sort($modules);
        return ['modules' => $modules, 'mappers' => $mappers, 'with' => $with, 'without' => $without, 'skipped' => $found['skipped']];
    }

    /**
     * Los seeders en el orden en que se pueden sembrar: cada uno después de lo que necesita.
     *
     * @param ModuleSeeder[] $seeders
     * @return ModuleSeeder[]
     * @throws \RuntimeException Con «PARA» si hay un ciclo o una dependencia sin seeder.
     */
    public static function order(array $seeders): array
    {
        $byMapper = [];
        foreach ($seeders as $seeder) {
            $byMapper[$seeder->mapper()] = $seeder;
        }
        $state = [];
        $ordered = [];
        $visit = static function (string $mapper, array $path) use (&$visit, &$state, &$ordered, $byMapper): void {
            if (($state[$mapper] ?? '') === 'hecho') {
                return;
            }
            if (($state[$mapper] ?? '') === 'en curso') {
                throw new \RuntimeException('PARA: hay un ciclo de dependencias: ' . implode(' -> ', array_merge($path, [$mapper])));
            }
            $state[$mapper] = 'en curso';
            foreach ($byMapper[$mapper]->dependsOn() as $dependency) {
                if (!isset($byMapper[$dependency])) {
                    throw new \RuntimeException("PARA: el seeder de {$mapper} depende de {$dependency}, que no tiene seeder: no se inventa una referencia");
                }
                $visit($dependency, array_merge($path, [$mapper]));
            }
            $state[$mapper] = 'hecho';
            $ordered[] = $byMapper[$mapper];
        };
        foreach (array_keys($byMapper) as $mapper) {
            $visit($mapper, []);
        }
        return $ordered;
    }

    /**
     * Genera las filas, en orden, sin escribir nada.
     *
     * @param ModuleSeeder[] $ordered Ya pasados por `order()`.
     * @param int|null $count Filas por tabla; null es la cantidad por defecto de cada seeder.
     * @param SeedContext $context
     * @return array<string,array<int,array<string,mixed>>> Mapper => filas.
     * @throws \RuntimeException Con «PARA» si una fila no cubre los campos obligatorios.
     */
    public static function simulate(array $ordered, ?int $count, SeedContext $context): array
    {
        $result = [];
        foreach ($ordered as $seeder) {
            $mapper = $seeder->mapper();
            $required = self::requiredFields($mapper);
            $result[$mapper] = [];
            $total = $count ?? $seeder->defaultCount();
            for ($index = 0; $index < $total; $index++) {
                $row = $seeder->row($index, $context);
                $missing = array_values(array_diff($required, array_keys($row)));
                if (count($missing) > 0) {
                    throw new \RuntimeException('PARA: el seeder de ' . $mapper . ' no genera campos obligatorios: ' . implode(', ', $missing));
                }
                $context->addSeeded($mapper, $row);
                $result[$mapper][] = $row;
            }
        }
        return $result;
    }

    /**
     * Si esta es la instalación LOCAL: la terminal con `--local`, que además elige el bloque local
     * de `database.php`, y el `environment.php` de la instalación diciendo `local`. Hacen falta las
     * dos, porque la bandera sola la puede pasar cualquiera en cualquier máquina.
     *
     * @return bool
     */
    public static function isLocalInstallation(): bool
    {
        return is_local() && app_environment() === AppEnvironment::LOCAL;
    }

    /**
     * Escribe de verdad, en orden y en UNA transacción: si una fila falla, no queda ninguna.
     *
     * @param ModuleSeeder[] $ordered Ya pasados por `order()`.
     * @param int|null $count Filas por tabla; null es la cantidad por defecto de cada seeder.
     * @param SeedContext $context Uno en modo escritura.
     * @return array<string,int[]> Mapper => ids escritos, en orden.
     * @throws \RuntimeException Con «PARA» si no es la instalación local, si ya hay algo sembrado o si una fila falla.
     */
    public static function write(array $ordered, ?int $count, SeedContext $context): array
    {
        if (!self::isLocalInstallation()) {
            throw new \RuntimeException('PARA: la siembra solo escribe en la instalación LOCAL (terminal con --local y environment.php en «local»), y esta no lo es.');
        }
        if ($context->isSimulation()) {
            throw new \RuntimeException('PARA: write() necesita un contexto de escritura.');
        }
        $already = array_filter(self::seededIds($ordered), static fn (array $ids): bool => count($ids) > 0);
        if (count($already) > 0) {
            throw new \RuntimeException('PARA: ya hay filas sembradas (' . implode(', ', array_map(static fn (string $t, array $ids): string => count($ids) . ' en ' . $t, array_keys($already), $already)) . '). Límpialas antes con limpiar=yes.');
        }
        $database = self::database();
        $database->beginTransaction();
        $written = [];
        try {
            foreach ($ordered as $seeder) {
                $class = $seeder->mapper();
                $required = self::requiredFields($class);
                $written[$class] = [];
                $total = $count ?? $seeder->defaultCount();
                for ($index = 0; $index < $total; $index++) {
                    $row = $seeder->row($index, $context);
                    $missing = array_values(array_diff($required, array_keys($row)));
                    if (count($missing) > 0) {
                        throw new \RuntimeException('el seeder de ' . $class . ' no genera campos obligatorios: ' . implode(', ', $missing));
                    }
                    $mapper = new $class();
                    foreach ($row as $column => $value) {
                        $mapper->$column = $value;
                    }
                    if (!$mapper->save()) {
                        throw new \RuntimeException('la fila #' . ($index + 1) . ' de ' . $class . ' no se guardó');
                    }
                    //`id` es mágica en EntityMapper y el tipo genérico no la declara: el save() la deja puesta.
                    $id = $mapper->__get('id');
                    if (!is_numeric($id)) {
                        throw new \RuntimeException('la fila #' . ($index + 1) . ' de ' . $class . ' se guardó sin id');
                    }
                    $context->addSeeded($class, $row + ['id' => (int) $id]);
                    $written[$class][] = (int) $id;
                }
            }
            $database->commit();
        } catch (\Throwable $e) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            //Un fallo no puede dejar archivos: lo copiado se retira con las mismas guardas que el limpiado.
            $left = $context->discardCopies();
            throw new \RuntimeException('PARA: la siembra falló y se deshizo entera: ' . $e->getMessage() . (count($left) > 0 ? '. Y NO se pudieron retirar estas carpetas: ' . implode(', ', $left) : ''), 0, $e);
        }
        return $written;
    }

    /**
     * Deshace la siembra: en el orden INVERSO, solo las filas cuya columna marcadora empieza por el
     * prefijo, y por id; antes de cada una, las que la aplicación creó por ella (`ownedRows()`). Si
     * no hay nada sembrado, no borra nada y no falla.
     *
     * Las carpetas propias (`ownedFolder()`) se comprueban TODAS antes de tocar nada, y se borran
     * después de confirmar las filas: una carpeta que no pasa una guarda para el limpiado entero.
     *
     * @param ModuleSeeder[] $ordered Ya pasados por `order()`.
     * @param array<int,array{path: string, status: string}>|null $folders Sale con cada carpeta y lo que se hizo con ella (FOLDER_*).
     * @return array<string,array{before: int, ids: int[], after: int}> Tabla => cuentas e ids borrados.
     * @throws \RuntimeException Con «PARA» si no es la instalación local, si una carpeta no pasa sus guardas o si un borrado falla.
     */
    public static function clean(array $ordered, ?array &$folders = null): array
    {
        if (!self::isLocalInstallation()) {
            throw new \RuntimeException('PARA: el limpiado solo borra en la instalación LOCAL (terminal con --local y environment.php en «local»), y esta no lo es.');
        }
        $database = self::database();
        $ids = self::seededIds($ordered);
        $folders = [];
        $targets = [];
        foreach ($ordered as $seeder) {
            $owned = $seeder->ownedFolder();
            $table = self::tableOf($seeder->mapper());
            $seededIds = $ids[$table] ?? [];
            if ($owned === null || count($seededIds) === 0) {
                continue;
            }
            if (preg_match('/^\w+$/', $owned['column']) !== 1) {
                throw new \RuntimeException("PARA: la columna de carpeta «{$owned['column']}» de " . get_class($seeder) . ' no es un nombre de columna');
            }
            $select = $database->prepare('SELECT `' . $owned['column'] . '` FROM `' . $table . '` WHERE id IN (' . implode(', ', array_fill(0, count($seededIds), '?')) . ') ORDER BY id');
            $select->execute($seededIds);
            foreach ($select->fetchAll(\PDO::FETCH_COLUMN) as $value) {
                $value = is_scalar($value) ? (string) $value : '';
                $targets[] = ['base' => $owned['base'], 'value' => $value, 'path' => self::ownedFolderPath($owned['base'], $value)];
            }
        }
        $report = [];
        $database->beginTransaction();
        try {
            foreach (array_reverse($ordered) as $seeder) {
                $table = self::tableOf($seeder->mapper());
                $seededIds = $ids[$table] ?? [];
                foreach ($seeder->ownedRows() as $ownedTable => $owned) {
                    $ownedColumn = is_array($owned) ? (string) ($owned['column'] ?? '') : $owned;
                    $where = is_array($owned) ? ($owned['where'] ?? []) : [];
                    foreach (array_merge([$ownedTable, $ownedColumn], array_keys($where)) as $name) {
                        if (preg_match('/^\w+$/', (string) $name) !== 1) {
                            throw new \RuntimeException("PARA: «{$name}» de las filas propias de {$ownedTable} no es un nombre de tabla o columna válido");
                        }
                    }
                    $ownedIds = [];
                    if (count($seededIds) > 0) {
                        $conditions = implode('', array_map(static fn ($column): string => ' AND `' . $column . '` = ?', array_keys($where)));
                        $select = $database->prepare('SELECT id FROM `' . $ownedTable . '` WHERE `' . $ownedColumn . '` IN (' . implode(', ', array_fill(0, count($seededIds), '?')) . ')' . $conditions . ' ORDER BY id');
                        $select->execute(array_merge($seededIds, array_values($where)));
                        $ownedIds = array_map('intval', $select->fetchAll(\PDO::FETCH_COLUMN));
                    }
                    $ownedBefore = self::countRows($ownedTable);
                    $deleteOwned = $database->prepare('DELETE FROM `' . $ownedTable . '` WHERE id = ?');
                    foreach ($ownedIds as $id) {
                        $deleteOwned->execute([$id]);
                    }
                    //Dos seeders pueden declarar la misma tabla (las aprobaciones): se acumula, no se pisa.
                    $previous = $report[$ownedTable] ?? null;
                    $report[$ownedTable] = [
                        'before' => $previous['before'] ?? $ownedBefore,
                        'ids' => array_merge($previous['ids'] ?? [], $ownedIds),
                        'after' => self::countRows($ownedTable),
                    ];
                }
                $before = self::countRows($table);
                $delete = $database->prepare('DELETE FROM `' . $table . '` WHERE id = ?');
                foreach ($seededIds as $id) {
                    $delete->execute([$id]);
                }
                $report[$table] = ['before' => $before, 'ids' => $seededIds, 'after' => self::countRows($table)];
            }
            $database->commit();
        } catch (\Throwable $e) {
            if ($database->inTransaction()) {
                $database->rollBack();
            }
            throw new \RuntimeException('PARA: el limpiado falló y se deshizo entero: ' . $e->getMessage(), 0, $e);
        }
        foreach ($targets as $target) {
            $relative = $target['base'] . '/' . $target['value'];
            try {
                //Se vuelve a comprobar justo antes: entre la validación y aquí la carpeta pudo cambiar.
                $path = self::ownedFolderPath($target['base'], $target['value']);
            } catch (\RuntimeException $e) {
                $folders[] = ['path' => $relative, 'status' => self::FOLDER_FAILED . ': ' . $e->getMessage()];
                continue;
            }
            if (!file_exists($path)) {
                $folders[] = ['path' => $relative, 'status' => self::FOLDER_MISSING];
                continue;
            }
            $folders[] = ['path' => $relative, 'status' => self::removeFolder($path) ? self::FOLDER_REMOVED : self::FOLDER_FAILED];
        }
        return $report;
    }

    /**
     * La ruta de la carpeta propia de una fila sembrada, con todas sus guardas: `base` dentro de
     * `statics/uploads/`, el nombre con el prefijo y sin barras ni puntos, la ruta real dentro de la
     * de `base`, y ningún enlace simbólico en la carpeta ni dentro. La carpeta puede no existir.
     *
     * @param string $base Relativa a `$root`.
     * @param string $value El nombre de la carpeta, tal como está en la fila.
     * @param string|null $root Por defecto, `basepath()`; las pruebas pasan un temporal propio.
     * @return string
     * @throws \RuntimeException Con «PARA» si una guarda falla.
     */
    public static function ownedFolderPath(string $base, string $value, ?string $root = null): string
    {
        $root ??= rtrim(str_replace('\\', '/', basepath('')), '/');
        if (preg_match('#^statics/uploads/[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*$#', $base) !== 1) {
            throw new \RuntimeException("PARA: la base «{$base}» no está dentro de statics/uploads/: no se borra nada");
        }
        if (preg_match('/^' . preg_quote(ModuleSeeder::PREFIX, '/') . '[A-Za-z0-9_-]+$/', $value) !== 1) {
            throw new \RuntimeException("PARA: la carpeta «{$value}» no es una carpeta sembrada (prefijo " . ModuleSeeder::PREFIX . ', sin barras ni puntos): no se borra nada');
        }
        $realUploads = realpath($root . '/statics/uploads');
        $realBase = realpath($root . '/' . $base);
        if ($realUploads === false || $realBase === false) {
            //Sin la base no puede existir la carpeta.
            return $root . '/' . $base . '/' . $value;
        }
        if (!str_starts_with($realBase . '/', $realUploads . '/')) {
            throw new \RuntimeException("PARA: la base «{$base}» resuelve fuera de statics/uploads/: no se borra nada");
        }
        $path = $realBase . '/' . $value;
        if (is_link($path)) {
            throw new \RuntimeException("PARA: «{$base}/{$value}» es un enlace simbólico: no se borra nada");
        }
        if (!file_exists($path)) {
            return $path;
        }
        $real = realpath($path);
        if ($real === false || !is_dir($real) || !str_starts_with($real, $realBase . '/')) {
            throw new \RuntimeException("PARA: «{$base}/{$value}» no es una carpeta dentro de su base: no se borra nada");
        }
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($real, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($items as $item) {
            if ($item instanceof \SplFileInfo && $item->isLink()) {
                throw new \RuntimeException("PARA: dentro de «{$base}/{$value}» hay un enlace simbólico ({$item->getPathname()}): no se borra nada");
            }
        }
        return $real;
    }

    /**
     * Borra una carpeta entera, de dentro afuera. Solo se llama con una ruta que ya pasó por
     * `ownedFolderPath()`: aquí no se vuelve a comprobar nada.
     *
     * @param string $path
     * @return bool Si la carpeta ya no está.
     */
    public static function removeFolder(string $path): bool
    {
        $items = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($items as $item) {
            if (!$item instanceof \SplFileInfo) {
                continue;
            }
            //@: un fallo es un aviso, y en local un aviso aborta.
            if ($item->isDir() && !$item->isLink()) {
                //RETORNO-IGNORADO: lo que no se pudo borrar lo dice el file_exists() del final.
                @rmdir($item->getPathname());
            } else {
                //RETORNO-IGNORADO: lo que no se pudo borrar lo dice el file_exists() del final.
                @unlink($item->getPathname());
            }
        }
        //RETORNO-IGNORADO: el retorno de la función es el file_exists() de la línea siguiente.
        @rmdir($path);
        return !file_exists($path);
    }

    /**
     * Los ids de lo sembrado, por tabla: las filas cuya columna marcadora empieza por el prefijo.
     *
     * @param ModuleSeeder[] $seeders
     * @return array<string,int[]> Tabla => ids, de menor a mayor.
     */
    public static function seededIds(array $seeders): array
    {
        $database = self::database();
        $result = [];
        foreach ($seeders as $seeder) {
            $table = self::tableOf($seeder->mapper());
            $column = $seeder->markerColumn();
            if (preg_match('/^\w+$/', $column) !== 1) {
                throw new \RuntimeException("PARA: la columna marcadora «{$column}» de " . get_class($seeder) . ' no es un nombre de columna');
            }
            $statement = $database->prepare('SELECT id FROM `' . $table . '` WHERE `' . $column . '` LIKE ? ORDER BY id');
            $statement->execute([ModuleSeeder::PREFIX . '%']);
            $result[$table] = array_map('intval', $statement->fetchAll(\PDO::FETCH_COLUMN));
        }
        return $result;
    }

    /**
     * @param string $table
     * @return int
     */
    public static function countRows(string $table): int
    {
        $statement = self::database()->prepare('SELECT COUNT(*) FROM `' . $table . '`');
        $statement->execute();
        $count = $statement->fetchColumn();
        return is_numeric($count) ? (int) $count : 0;
    }

    /**
     * @return \PiecesPHP\Core\Database\Database
     */
    protected static function database(): \PiecesPHP\Core\Database\Database
    {
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            throw new \RuntimeException('PARA: no hay conexión con la base de datos.');
        }
        return $database;
    }

    /**
     * Los campos sin los que el ORM no guarda: los que `EntityMapper::validateFieldValue()` rechaza
     * con «no acepta valores nulos» si llegan vacíos. Son los que no son la clave primaria ni `meta`
     * (el ORM los hace nulables), no admiten null y tienen `default` null.
     *
     * Se leen de una instancia, cuyo constructor ya rellenó cada opción ausente con su valor por
     * defecto: la clave `default` existe SIEMPRE, así que lo que cuenta es su valor, no si está.
     *
     * @param class-string<EntityMapper> $mapper
     * @return string[]
     */
    public static function requiredFields(string $mapper): array
    {
        $reflection = new \ReflectionClass($mapper);
        $fields = $reflection->getProperty('fields')->getValue($reflection->newInstance());
        $required = [];
        foreach (is_array($fields) ? $fields : [] as $name => $definition) {
            if (!is_array($definition) || ($definition['primary_key'] ?? false) === true || ($definition['meta'] ?? false) === true) {
                continue;
            }
            if (($definition['null'] ?? false) === true || ($definition['default'] ?? null) !== null) {
                continue;
            }
            $required[] = (string) $name;
        }
        return $required;
    }

    /**
     * La tabla de un mapper, la misma que usa `scheme-create`.
     *
     * @param class-string<EntityMapper> $mapper
     * @return string
     */
    public static function tableOf(string $mapper): string
    {
        $instance = new $mapper();
        return (new SchemeCreator($instance))->getTable();
    }

    /**
     * El sembrador tiene que verse a sí mismo antes de enseñar nada: ve los campos obligatorios de
     * un mapper que los tiene, con cantidad 0 no genera filas, una dependencia sobre un mapper sin
     * seeder se detecta, y un ciclo también.
     *
     * @param ModuleSeeder[] $seeders Los descubiertos.
     * @return string|null El motivo si falla; null si está bien.
     */
    public static function canary(array $seeders): ?string
    {
        if (!in_array('username', self::requiredFields(UsersModel::class), true)) {
            return 'no ve los campos obligatorios: «username» no sale como obligatorio de ' . UsersModel::class;
        }
        $empty = self::simulate(self::order($seeders), 0, new SeedContext());
        $rows = array_sum(array_map('count', $empty));
        if ($rows !== 0) {
            return "con cantidad=0 genera {$rows} fila(s)";
        }
        $orphan = new class extends ModuleSeeder {
            public function mapper(): string
            {
                return \PiecesPHP\UserSystem\ORM\UsersModel::class;
            }
            public function markerColumn(): string
            {
                return 'id';
            }
            public function dependsOn(): array
            {
                return [NewsMapper::class];
            }
            public function row(int $index, SeedContext $context): array
            {
                return [];
            }
        };
        try {
            self::order([$orphan]);
            return 'una dependencia sobre un mapper sin seeder no se detecta';
        } catch (\RuntimeException $e) {
            if (!str_contains($e->getMessage(), 'no tiene seeder')) {
                return 'la dependencia sin seeder da otro error: ' . $e->getMessage();
            }
        }
        $self = new class extends ModuleSeeder {
            public function mapper(): string
            {
                return NewsMapper::class;
            }
            public function markerColumn(): string
            {
                return 'id';
            }
            public function dependsOn(): array
            {
                return [NewsMapper::class];
            }
            public function row(int $index, SeedContext $context): array
            {
                return [];
            }
        };
        try {
            self::order([$self]);
            return 'un seeder que depende de sí mismo no se detecta como ciclo';
        } catch (\RuntimeException $e) {
            if (!str_contains($e->getMessage(), 'ciclo')) {
                return 'el ciclo da otro error: ' . $e->getMessage();
            }
        }
        return null;
    }

}
