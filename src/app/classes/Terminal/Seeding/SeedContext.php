<?php

/**
 * SeedContext.php
 */

namespace Terminal\Seeding;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\Database\EntityMapper;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * SeedContext.
 *
 * Lo que un seeder ve mientras genera: lo ya sembrado de sus dependencias, las referencias a
 * esas filas y las lecturas que no se pueden inventar. En simulación, donde iría un id o una
 * contraseña devuelve un marcador; al escribir, el id real y un hash de una clave que no se guarda.
 *
 * @package     Terminal\Seeding
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class SeedContext
{

    /**
     * @var array<string,array<int,array<string,mixed>>> Mapper => filas generadas, en orden.
     */
    protected array $seeded = [];

    /**
     * @var array<string,string> Mapper => tabla, para escribir los marcadores.
     */
    protected array $tables = [];

    /**
     * @var bool Si escribe de verdad.
     */
    protected bool $write = false;

    /**
     * @var array<string,array{0: string, 1: string}> Las carpetas que creó esta siembra: «base/carpeta» => [base, carpeta].
     */
    protected array $createdFolders = [];

    /**
     * @param array<string,string> $tables Mapper => tabla.
     * @param bool $write
     */
    public function __construct(array $tables = [], bool $write = false)
    {
        $this->tables = $tables;
        $this->write = $write;
    }

    /**
     * @return bool
     */
    public function isSimulation(): bool
    {
        return !$this->write;
    }

    /**
     * La contraseña de un usuario sembrado. En simulación, el marcador; al escribir, el hash de una
     * clave aleatoria que no se guarda ni se imprime en ningún sitio.
     *
     * @return string
     */
    public function passwordHash(): string
    {
        return $this->write ? password_hash(bin2hex(random_bytes(24)), \PASSWORD_DEFAULT) : '<hash>';
    }

    /**
     * Copia un archivo del repositorio a la carpeta de subidas de una fila sembrada y devuelve lo que
     * va en la columna: `base/carpeta/nombre`, siempre con el nombre público. En simulación no escribe.
     *
     * @param string $source Relativa a src/, dentro de `statics/images/`.
     * @param string $base Relativa a src/, dentro de `statics/uploads/`.
     * @param string $folder La carpeta sembrada (con el prefijo) y, si hace falta, subcarpetas: `zz-siembra-x/attachments`.
     * @param string $name `nombre.extensión`.
     * @param bool $private Con el sufijo de lo privado en disco, como nacen las subidas de esa tabla.
     * @return string
     * @throws \RuntimeException Con «PARA» si una guarda falla, si la carpeta ya existía o si no se pudo copiar.
     */
    public function copyToUploads(string $source, string $base, string $folder, string $name, bool $private): string
    {
        $root = rtrim(str_replace('\\', '/', basepath('')), '/');
        if (preg_match('#^statics/images/[A-Za-z0-9_-]+(/[A-Za-z0-9_-]+)*\.[A-Za-z0-9]{1,8}$#', $source) !== 1 || !is_file($root . '/' . $source)) {
            throw new \RuntimeException("PARA: el archivo de origen «{$source}» no es un archivo de statics/images/");
        }
        $segments = explode('/', $folder);
        $top = (string) array_shift($segments);
        //Comprueba la base y el nombre de la carpeta con las mismas guardas que el limpiado.
        $topPath = SeedPlanner::ownedFolderPath($base, $top);
        foreach ($segments as $segment) {
            if (preg_match('/^[A-Za-z0-9_-]+$/', $segment) !== 1) {
                throw new \RuntimeException("PARA: la subcarpeta «{$segment}» de «{$folder}» no es válida");
            }
        }
        if (preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9]{1,8}$/', $name) !== 1) {
            throw new \RuntimeException("PARA: el nombre «{$name}» no es válido");
        }
        $relative = $base . '/' . $folder . '/' . $name;
        if (!$this->write) {
            return $relative;
        }
        $key = $base . '/' . $top;
        if (!isset($this->createdFolders[$key])) {
            if (file_exists($topPath)) {
                throw new \RuntimeException("PARA: la carpeta «{$key}» ya existe y no es de esta siembra: límpiala antes");
            }
            $this->createdFolders[$key] = [$base, $top];
        }
        $directory = $root . '/' . $base . '/' . $folder;
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new \RuntimeException("PARA: no se pudo crear la carpeta «{$base}/{$folder}»");
        }
        $target = $root . '/' . $relative;
        $target = $private ? ProtectedUploads::privatePath($target) : $target;
        if (!@copy($root . '/' . $source, $target)) {
            throw new \RuntimeException("PARA: no se pudo copiar «{$source}» a «{$relative}»");
        }
        return $relative;
    }

    /**
     * Retira las carpetas que creó esta siembra, con las guardas del limpiado. La llama `write()`
     * cuando deshace la transacción.
     *
     * @return string[] Las carpetas que NO se pudieron retirar, con el motivo.
     */
    public function discardCopies(): array
    {
        $left = [];
        foreach ($this->createdFolders as $key => [$base, $top]) {
            try {
                $path = SeedPlanner::ownedFolderPath($base, $top);
                if (file_exists($path) && !SeedPlanner::removeFolder($path)) {
                    $left[] = $key;
                }
            } catch (\RuntimeException $e) {
                $left[] = $key . ' (' . $e->getMessage() . ')';
            }
        }
        $this->createdFolders = [];
        return $left;
    }

    /**
     * @param class-string<EntityMapper> $mapper
     * @param array<string,mixed> $row
     * @return void
     */
    public function addSeeded(string $mapper, array $row): void
    {
        $this->seeded[$mapper][] = $row;
    }

    /**
     * @param class-string<EntityMapper> $mapper
     * @return array<int,array<string,mixed>>
     */
    public function seeded(string $mapper): array
    {
        return $this->seeded[$mapper] ?? [];
    }

    /**
     * El id de una fila ya sembrada de `$mapper`, repartiendo `$index` entre las que haya. En
     * simulación es un marcador; al escribir, el id que dio la base. Nunca un id inventado.
     *
     * @param class-string<EntityMapper> $mapper
     * @param int $index
     * @return string|int
     * @throws \RuntimeException Si no hay ninguna fila sembrada de ese mapper.
     */
    public function reference(string $mapper, int $index): string|int
    {
        $rows = $this->seeded($mapper);
        if (count($rows) === 0) {
            throw new \RuntimeException("PARA: se pide una referencia a {$mapper}, que no tiene filas sembradas");
        }
        $position = $index % count($rows);
        if ($this->write) {
            $id = $rows[$position]['id'] ?? null;
            if (!is_int($id)) {
                throw new \RuntimeException("PARA: la fila sembrada #" . ($position + 1) . " de {$mapper} no tiene id");
            }
            return $id;
        }
        $table = $this->tables[$mapper] ?? $mapper;
        return '<id de ' . $table . ' #' . ($position + 1) . '>';
    }

    /**
     * El id de un usuario que YA existe en la base, del tipo pedido. Es una lectura: hay
     * columnas que no admiten un usuario sembrado sin crear un ciclo.
     *
     * @param int $type
     * @return int|null
     */
    public function existingUserId(int $type): ?int
    {
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            return null;
        }
        $statement = $database->prepare('SELECT id FROM `' . UsersModel::TABLE . '` WHERE type = ? ORDER BY id LIMIT 1');
        $statement->execute([$type]);
        $id = $statement->fetchColumn();
        return is_numeric($id) ? (int) $id : null;
    }

}
