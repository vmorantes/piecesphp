<?php

/**
 * SystemHealth.php
 */

namespace PiecesPHP\SystemStatus;

use API\Adapters\FfmpegAudioAdapter;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\AppVersion;
use PiecesPHP\Core\Crypto\AppKeyring;
use PiecesPHP\Core\InstalledPackages;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\Terminal\CronJobTask;
use PiecesPHP\Terminal\TaskRunMapper;
use Terminal\Distribution;
use Terminal\Tasks\SchemeSqlTask;

/**
 * SystemHealth - La Salud de la instalación: cada fila es un hecho de esta instalación y de dónde sale.
 *
 * Nunca un valor que comprometa: ni llaves, ni contraseñas, ni host, usuario o nombre de la base, ni rutas absolutas
 * del servidor. Solo «definida», «cargada», «escribible», versiones y cuentas (P179).
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class SystemHealth
{
    const STATE_OK = 'ok';
    const STATE_WARNING = 'aviso';
    const STATE_MISSING = 'falta';
    const STATE_INFO = 'info';

    const GROUP_FRAMEWORK = 'framework';
    const GROUP_PACKAGES = 'packages';
    const GROUP_PHP = 'php';
    const GROUP_DATABASE = 'database';
    const GROUP_TASKS = 'tasks';
    const GROUP_FOLDERS = 'folders';
    const GROUP_SECURITY = 'security';

    /**
     * El bloque «Lo que pide atención» en markdown(): los avisos y las faltas de todos los grupos.
     */
    const GROUP_ATTENTION = 'attention';

    /**
     * La lista de carpetas escribibles, relativa a src/: la leen también los dos guiones de permisos.
     */
    const WRITABLE_DIRS_FILE = 'app/config/writable-dirs.txt';

    /**
     * Los ajustes de PHP que se enseñan, tal cual los da ini_get().
     */
    const INI_SETTINGS = ['memory_limit', 'max_execution_time', 'upload_max_filesize', 'post_max_size'];

    /**
     * Todas las filas, por bloque y en orden.
     *
     * @param string[] $requiredExtensions Para las pruebas: las ext-* que se comprueban; por defecto, las de composer.json.
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    public static function rows(?array $requiredExtensions = null): array
    {
        return array_merge(
            self::frameworkRows(),
            self::packageRows(),
            self::phpRows($requiredExtensions),
            self::databaseRows(),
            self::taskRows(),
            self::folderRows(),
            self::securityRows()
        );
    }

    /**
     * @return array<string,string> Los títulos de los bloques, ya traducidos, por grupo.
     */
    public static function groupTitles(): array
    {
        return [
            self::GROUP_FRAMEWORK => self::t('Framework'),
            self::GROUP_PACKAGES => self::t('Paquetes de PHP'),
            self::GROUP_PHP => self::t('PHP'),
            self::GROUP_DATABASE => self::t('Base de datos'),
            self::GROUP_TASKS => self::t('Respaldos y tareas'),
            self::GROUP_FOLDERS => self::t('Carpetas'),
            self::GROUP_SECURITY => self::t('Seguridad'),
        ];
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function frameworkRows(): array
    {
        $rows = [];
        $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Versión'), AppVersion::version(), self::STATE_INFO, 'AppVersion::version()', 'framework-version');
        $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Fecha de la versión'), AppVersion::date(), self::STATE_INFO, 'AppVersion::date()', 'framework-date');
        $commit = AppVersion::commit();
        $commitOrigin = AppVersion::commitSource() === AppVersion::SOURCE_STAMP ? AppVersion::STAMP_RELATIVE_PATH : '.git/HEAD';
        $rows[] = $commit !== null
            ? self::row(self::GROUP_FRAMEWORK, self::t('Commit'), $commit, self::STATE_INFO, "AppVersion::commit() ({$commitOrigin})", 'framework-commit')
            : self::row(self::GROUP_FRAMEWORK, self::t('Commit'), self::t('Sin commit conocido'), self::STATE_WARNING, 'AppVersion::commit()', 'framework-commit');

        $markPath = Distribution::root() . '/' . Distribution::MARK_FILE;
        if (Distribution::isDistribution()) {
            $mark = (string) @file_get_contents($markPath);
            $tag = preg_match('/^etiqueta: (.+)$/m', $mark, $tagMatch) === 1 ? trim($tagMatch[1]) : '';
            $markCommit = preg_match('/^commit: (.+)$/m', $mark, $commitMatch) === 1 ? trim($commitMatch[1]) : '';
            $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Distribución'), trim("{$tag} · {$markCommit}", ' ·'), self::STATE_INFO, Distribution::MARK_FILE, 'framework-distribution');
        } else {
            $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Distribución'), self::t('No es una distribución'), self::STATE_INFO, Distribution::MARK_FILE, 'framework-distribution');
        }

        $rows[] = self::row(
            self::GROUP_FRAMEWORK,
            self::t('Entorno'),
            AppEnvironment::isConfigured() ? AppEnvironment::get() : self::t('Sin declarar'),
            AppEnvironment::isConfigured() ? self::STATE_OK : self::STATE_WARNING,
            'AppEnvironment::get() (app/config/environment.php)',
            'framework-environment'
        );
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function packageRows(): array
    {
        $rows = [];
        foreach (InstalledPackages::required() as $package) {
            $value = sprintf(self::t('Pide %s · instalada %s'), $package['constraint'], $package['installed'] ? $package['version'] : '—')
                . ($package['license'] !== '' ? ' · ' . $package['license'] : '');
            $rows[] = self::row(
                self::GROUP_PACKAGES,
                InstalledPackages::displayName($package['name']),
                $value,
                $package['installed'] ? self::STATE_OK : self::STATE_MISSING,
                'composer.json, Composer\InstalledVersions, vendor/composer/installed.json',
                'package'
            );
        }
        return $rows;
    }

    /**
     * El estado de la fila «Versión de PHP»: aviso por debajo de la 8.5.13 (CVE-2026-128064).
     *
     * @param int $versionId Como PHP_VERSION_ID
     * @return string
     */
    public static function phpVersionState(int $versionId): string
    {
        return $versionId < 80513 ? self::STATE_WARNING : self::STATE_INFO;
    }

    /**
     * @param string[]|null $requiredExtensions
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function phpRows(?array $requiredExtensions): array
    {
        $require = InstalledPackages::requireList();
        $rows = [];
        $phpVersion = sprintf(self::t('%s (pide %s)'), PHP_VERSION, $require['php'] ?? '—');
        $phpState = self::phpVersionState(PHP_VERSION_ID);
        if ($phpState === self::STATE_WARNING) {
            $phpVersion .= ' · ' . self::t('Esta versión de PHP tiene un fallo de seguridad corregido en la 8.5.13: conviene actualizar el PHP del servidor.');
        }
        $rows[] = self::row(self::GROUP_PHP, self::t('Versión de PHP'), $phpVersion, $phpState, 'PHP_VERSION, PHP_VERSION_ID, composer.json', 'php-version');

        if ($requiredExtensions === null) {
            $requiredExtensions = [];
            foreach (array_keys($require) as $name) {
                if (str_starts_with($name, 'ext-')) {
                    $requiredExtensions[] = substr($name, 4);
                }
            }
        }
        foreach ($requiredExtensions as $extension) {
            $loaded = extension_loaded($extension);
            $rows[] = self::row(self::GROUP_PHP, "ext-{$extension}", $loaded ? self::t('Cargada') : self::t('No cargada'), $loaded ? self::STATE_OK : self::STATE_MISSING, "extension_loaded('{$extension}')", 'php-extension');
        }

        foreach (self::INI_SETTINGS as $setting) {
            $value = ini_get($setting);
            $rows[] = self::row(self::GROUP_PHP, $setting, $value === false ? '—' : $value, self::STATE_INFO, "ini_get('{$setting}')", 'php-ini');
        }

        $opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        $opcacheOn = is_array($opcache) && ($opcache['opcache_enabled'] ?? false) === true;
        $rows[] = self::row(self::GROUP_PHP, 'OPcache', $opcacheOn ? self::t('Activo') : self::t('Inactivo'), $opcacheOn ? self::STATE_OK : self::STATE_INFO, 'opcache_get_status()', 'php-opcache');
        $validates = (bool) ini_get('opcache.validate_timestamps');
        $rows[] = self::row(
            self::GROUP_PHP,
            self::t('OPcache revalida los archivos'),
            $validates ? sprintf(self::t('Sí, cada %s s'), (string) ini_get('opcache.revalidate_freq')) : self::t('No'),
            self::STATE_INFO,
            "ini_get('opcache.validate_timestamps'), ini_get('opcache.revalidate_freq')",
            'php-opcache-revalidate'
        );

        foreach (['ffmpeg' => FfmpegAudioAdapter::COMMON_PATHS_FFMPEG, 'ffprobe' => FfmpegAudioAdapter::COMMON_PATHS_FFPROBE] as $binary => $paths) {
            //Con open_basedir, una ruta de fuera avisaría al mirarla: se miran solo las de dentro, y sin ninguna no aplica.
            $reachable = array_values(array_filter($paths, fn(string $path): bool => path_within_open_basedir($path)));
            if ($reachable === []) {
                $rows[] = self::row(self::GROUP_PHP, $binary, self::t('No aplicable: open_basedir no deja mirar sus rutas'), self::STATE_INFO, 'FfmpegAudioAdapter::COMMON_PATHS_* · open_basedir', 'php-binary');
                continue;
            }
            $found = FfmpegAudioAdapter::findBinary($reachable) !== null;
            $rows[] = self::row(self::GROUP_PHP, $binary, $found ? self::t('Instalado') : self::t('No encontrado'), $found ? self::STATE_OK : self::STATE_WARNING, 'FfmpegAudioAdapter::findBinary()', 'php-binary');
        }
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function databaseRows(): array
    {
        $rows = [];
        $database = null;
        try {
            $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
        } catch (\Throwable) {
            $database = null;
        }
        if ($database === null) {
            return [self::row(self::GROUP_DATABASE, self::t('Conexión'), self::t('Sin conexión'), self::STATE_MISSING, 'BaseModel::getDatabase()', 'database-connection')];
        }

        $version = (string) $database->query('SELECT VERSION()')->fetchColumn();
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Motor y versión'), $version, self::STATE_INFO, 'SELECT VERSION()', 'database-engine');

        $config = get_config('database');
        $charset = is_array($config) && isset($config['default']['charset']) && is_string($config['default']['charset']) ? $config['default']['charset'] : '—';
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Juego de caracteres de la conexión'), $charset, self::STATE_INFO, "\$config['database']['default']['charset']", 'database-charset-connection');
        $schemaCharset = (string) $database->query('SELECT DEFAULT_CHARACTER_SET_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()')->fetchColumn();
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Juego de caracteres de la base'), $schemaCharset !== '' ? $schemaCharset : '—', self::STATE_INFO, 'information_schema.SCHEMATA', 'database-charset-schema');

        $tables = SettingsController::databaseTables();
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Tablas'), (string) count($tables), self::STATE_INFO, 'SettingsController::databaseTables()', 'database-tables');

        //Las que declara un mapper de src/app/classes y la base no tiene: el mismo descubrimiento que scheme-create.
        $declared = [];
        foreach (SchemeSqlTask::discover('all')['creators'] as $creator) {
            $declared[$creator->getTable()] = true;
        }
        $missing = self::missingDeclaredTables(array_keys($declared), $tables, self::databaseViews());
        $rows[] = self::row(
            self::GROUP_DATABASE,
            self::t('Tablas declaradas que la base no tiene'),
            count($missing) > 0 ? implode(', ', $missing) : sprintf(self::t('Ninguna, de %d declaradas'), count($declared)),
            count($missing) > 0 ? self::STATE_WARNING : self::STATE_OK,
            'SchemeSqlTask::discover(\'all\') (scheme-create)',
            'database-missing-tables'
        );
        return $rows;
    }

    /**
     * Las tablas declaradas que la base no tiene ni como tabla ni como vista (un proyecto puede sustituir una tabla por una
     * vista: cuenta como existente), en orden. Pura.
     *
     * @param string[] $declared
     * @param string[] $tables
     * @param string[] $views
     * @return string[]
     */
    public static function missingDeclaredTables(array $declared, array $tables, array $views): array
    {
        $missing = array_values(array_diff($declared, $tables, $views));
        sort($missing);
        return $missing;
    }

    /**
     * Las vistas de la base de la instalación (SettingsController::databaseTables() da solo las tablas).
     *
     * @return string[]
     */
    public static function databaseViews(): array
    {
        $database = (new \PiecesPHP\Core\BaseModel())->getDatabase();
        if ($database === null) {
            return [];
        }
        return array_map('strval', $database->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'VIEW'")->fetchAll(\PDO::FETCH_COLUMN));
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function taskRows(): array
    {
        $rows = [];
        $backups = SettingsController::backupStatus();
        $rows[] = $backups['latest'] !== null
            ? self::row(self::GROUP_TASKS, self::t('Último respaldo'), $backups['latest']->format('d-m-Y H:i'), self::STATE_OK, 'SettingsController::backupStatus()', 'backup-last')
            : self::row(self::GROUP_TASKS, self::t('Último respaldo'), self::t('Ninguno hecho por el sistema'), self::STATE_WARNING, 'SettingsController::backupStatus()', 'backup-last');
        $rows[] = self::row(
            self::GROUP_TASKS,
            self::t('Siguiente respaldo'),
            $backups['due'] ? self::t('Toca ya') : ($backups['next'] !== null ? $backups['next']->format('d-m-Y H:i') : '—'),
            self::STATE_INFO,
            'SettingsController::backupStatus()',
            'backup-next'
        );

        $latest = SystemStatusController::registeredRuns(TaskRunMapper::latestByTask());
        foreach (CronJobTask::getCronJobs() as $task) {
            $name = $task->getName();
            if (!isset($latest[$name])) {
                $rows[] = self::row(self::GROUP_TASKS, $name, self::t('Sin ejecuciones anotadas'), self::STATE_WARNING, 'TaskRunMapper::latestByTask()', 'task-run');
                continue;
            }
            $run = $latest[$name];
            $at = strtotime($run['startedAt']);
            $rows[] = self::row(
                self::GROUP_TASKS,
                $name,
                $run['status'] . ($at !== false ? ' · ' . date('d-m-Y H:i', $at) : ''),
                $run['status'] === CronJobTask::STATUS_FAILED ? self::STATE_WARNING : self::STATE_OK,
                'TaskRunMapper::latestByTask()',
                'task-run'
            );
        }
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function folderRows(): array
    {
        $rows = [];
        foreach (self::writableDirs() as $relative) {
            $path = basepath($relative);
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $rows[] = self::row(
                self::GROUP_FOLDERS,
                $relative,
                $writable ? self::t('Escribible') : ($exists ? self::t('No escribible') : self::t('No existe')),
                $writable ? self::STATE_OK : self::STATE_MISSING,
                'is_writable() · ' . self::WRITABLE_DIRS_FILE,
                'folder-writable'
            );
        }
        //La carpeta de claves, la que usa el framework (AppKeyring::directory(): secure-keys/ o private/secure-keys/, y
        //solo dentro de open_basedir): «Falta» solo si no hay ninguna. Se mira si se puede escribir, nunca su contenido.
        $keys = AppKeyring::directory();
        $label = self::keysLabel($keys, dirname(rtrim(str_replace('\\', '/', basepath('')), '/')));
        $writable = $keys !== null && is_writable($keys);
        $rows[] = self::row(
            self::GROUP_FOLDERS,
            $label,
            $keys === null ? self::t('Falta') : ($writable ? self::t('Escribible') : self::t('No escribible')),
            $keys !== null && $writable ? self::STATE_OK : self::STATE_MISSING,
            'AppKeyring::directory() · is_writable()',
            'folder-keys'
        );
        return $rows;
    }

    /**
     * PHP apagado en las carpetas de subidas (P319), la lista de dominios permitidos y el entorno.
     *
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string,key:string}>
     */
    private static function securityRows(): array
    {
        $rows = [];
        //El .htaccess solo lo lee Apache; con otro servidor (o en la terminal, sin servidor) lo que manda es su configuración.
        $software = $_SERVER['SERVER_SOFTWARE'] ?? null;
        $isApache = is_string($software) && str_contains($software, 'Apache');
        $uploads = get_config('upload_dir');
        //Las dos que protege writeDenyHtaccess() (app/core/extensions/protected-files.php).
        $directories = [is_string($uploads) ? $uploads : basepath('statics/uploads'), basepath('statics/filemanager')];
        foreach ($directories as $directory) {
            //Relativa a src/: nunca una ruta absoluta del servidor (P179).
            $relative = ltrim(str_replace('\\', '/', mb_substr($directory, mb_strlen(rtrim(basepath(''), '/\\')))), '/');
            $label = sprintf(self::t('PHP en %s'), $relative);
            $origin = 'ProtectedUploads::writeDenyHtaccess()';
            if (!$isApache) {
                $rows[] = self::row(self::GROUP_SECURITY, $label, self::t('Depende del servidor: mira la guía de nginx'), self::STATE_INFO, $origin, 'security-uploads-php');
                continue;
            }
            if (!is_dir($directory)) {
                $rows[] = self::row(self::GROUP_SECURITY, $label, self::t('La carpeta no existe'), self::STATE_INFO, $origin, 'security-uploads-php');
                continue;
            }
            $htaccess = rtrim($directory, '/\\') . \DIRECTORY_SEPARATOR . '.htaccess';
            $content = is_file($htaccess) ? @file_get_contents($htaccess) : false;
            $reason = match (true) {
                !is_string($content) => self::t('Sin .htaccess'),
                !str_starts_with($content, ProtectedUploads::DENY_HTACCESS_MARK) => self::t('El .htaccess no es del sistema'),
                !str_contains($content, ProtectedUploads::denyServerScriptsBlock()) => self::t('Sin el bloque que niega PHP'),
                default => null,
            };
            $rows[] = self::row(self::GROUP_SECURITY, $label, $reason ?? self::t('Apagado'), $reason === null ? self::STATE_OK : self::STATE_WARNING, $origin, 'security-uploads-php');
        }

        $allowed = get_config('allowed_hosts');
        $origin = "\$config['allowed_hosts']";
        if (!SystemStatusRoutes::allowedHostsMissing($allowed)) {
            $hosts = array_filter(is_array($allowed) ? $allowed : [], fn($host): bool => is_string($host) && trim($host) !== '');
            $rows[] = self::row(self::GROUP_SECURITY, self::t('Dominios permitidos'), implode(', ', $hosts), self::STATE_OK, $origin, 'security-allowed-hosts');
        } else {
            $rows[] = self::row(self::GROUP_SECURITY, self::t('Dominios permitidos'), self::t('Vacía: cualquier dominio'), is_local() ? self::STATE_INFO : self::STATE_WARNING, $origin, 'security-allowed-hosts');
        }

        $isLocal = AppEnvironment::get() === AppEnvironment::LOCAL;
        $rows[] = AppEnvironment::isConfigured()
            ? self::row(self::GROUP_SECURITY, self::t('Entorno'), $isLocal ? self::t('Local') : self::t('Producción'), self::STATE_INFO, 'AppEnvironment · ' . AppEnvironment::FILE_RELATIVE_PATH, 'security-environment')
            : self::row(self::GROUP_SECURITY, self::t('Entorno'), self::t('Producción (sin environment.php)'), self::STATE_WARNING, 'AppEnvironment · ' . AppEnvironment::FILE_RELATIVE_PATH, 'security-environment');
        return $rows;
    }

    /**
     * Qué mide cada TIPO de fila, qué hacer si está mal y cuándo es una falsa alarma, por la clave de la fila. Cada texto
     * sale del código que calcula la fila (arriba, en este archivo).
     *
     * @return array<string,array{measures:string,ifWrong:string,falseAlarm:string}>
     */
    public static function explanations(): array
    {
        $none = self::t('Ninguna conocida.');
        $info = self::t('Es informativa: no pide nada.');
        $terminalPhp = self::t('El PHP de la terminal y el del servidor web pueden ser distintos (versión, extensiones, php.ini): esta fila mide el PHP que la calcula, el del servidor web si la ves en el panel o la exportas desde él.');
        return [
            'framework-version' => ['measures' => self::t('La versión del framework que corre esta instalación.'), 'ifWrong' => $info, 'falseAlarm' => $none],
            'framework-date' => ['measures' => self::t('La fecha de esa versión.'), 'ifWrong' => $info, 'falseAlarm' => $none],
            'framework-commit' => [
                'measures' => self::t('El commit del código, del sello de la distribución o de .git/HEAD.'),
                'ifWrong' => self::t('«Sin commit conocido»: no hay sello ni repositorio, así que no se sabe de qué commit viene el código. Despliega desde una distribución, que lleva el sello, o desde el repositorio.'),
                'falseAlarm' => self::t('Una copia subida a mano sin .git ni sello: el código puede estar bien; solo falta saber de dónde salió.'),
            ],
            'framework-distribution' => ['measures' => self::t('Si la instalación es una distribución (tiene su marca) y, si lo es, su etiqueta y su commit.'), 'ifWrong' => $info, 'falseAlarm' => $none],
            'framework-environment' => [
                'measures' => self::t('El entorno declarado en app/config/environment.php.'),
                'ifWrong' => self::t('«Sin declarar»: la instalación se trata como producción. Crea app/config/environment.php con su entorno.'),
                'falseAlarm' => $none,
            ],
            'package' => [
                'measures' => self::t('Cada paquete que pide composer.json: la versión que pide, la instalada y su licencia.'),
                'ifWrong' => self::t('«Falta»: el paquete no está instalado. Ejecuta composer install con el composer.json del proyecto.'),
                'falseAlarm' => $none,
            ],
            'php-version' => [
                'measures' => self::t('La versión de PHP y la que pide composer.json. Avisa por debajo de la 8.5.13, que corrige un fallo de seguridad.'),
                'ifWrong' => self::t('Actualiza el PHP del servidor a la 8.5.13 o posterior.'),
                'falseAlarm' => $terminalPhp,
            ],
            'php-extension' => [
                'measures' => self::t('Si cada extensión que pide composer.json (ext-*) está cargada en este PHP.'),
                'ifWrong' => self::t('Instala o activa la extensión en el PHP del servidor web y reinícialo.'),
                'falseAlarm' => self::t('La terminal y el servidor web cargan cada uno su php.ini: que una extensión esté en uno no dice que esté en el otro.'),
            ],
            'php-ini' => [
                'measures' => self::t('Un ajuste de PHP tal cual lo da ini_get(): la memoria, el tiempo de ejecución o el tamaño de las subidas.'),
                'ifWrong' => self::t('Es informativa: si las subidas fallan o las tareas se cortan, súbelo en el php.ini del servidor web.'),
                'falseAlarm' => self::t('La terminal y el servidor web leen cada uno su php.ini: el valor de uno no es el del otro.'),
            ],
            'php-opcache' => [
                'measures' => self::t('Si OPcache está activo en este PHP.'),
                'ifWrong' => self::t('Inactivo no es un error; en producción conviene activarlo.'),
                'falseAlarm' => self::t('En la terminal OPcache suele estar apagado (opcache.enable_cli): eso no dice nada del servidor web.'),
            ],
            'php-opcache-revalidate' => [
                'measures' => self::t('Si OPcache comprueba que los archivos cambiaron y cada cuántos segundos.'),
                'ifWrong' => self::t('Es informativa: con «No», un cambio en el código no se ve hasta reiniciar PHP o vaciar OPcache.'),
                'falseAlarm' => self::t('Justo después de desplegar, durante esos segundos se puede servir el código anterior.'),
            ],
            'php-binary' => [
                'measures' => self::t('Si ffmpeg y ffprobe están en alguna de sus rutas habituales (los usa el audio). Con open_basedir solo se miran las rutas permitidas.'),
                'ifWrong' => self::t('«No encontrado»: instálalo si usas las funciones de audio; si no, no hace falta.'),
                'falseAlarm' => self::t('Con open_basedir sin ninguna de esas rutas, la fila dice «No aplicable» en vez de buscar: no significa que falte. Y puede estar instalado en una ruta que no es de las habituales.'),
            ],
            'database-connection' => [
                'measures' => self::t('Si la aplicación se conecta a la base.'),
                'ifWrong' => self::t('Revisa la configuración de la base y que el servidor de base de datos esté encendido.'),
                'falseAlarm' => $none,
            ],
            'database-engine' => ['measures' => self::t('El motor y la versión de la base.'), 'ifWrong' => $info, 'falseAlarm' => $none],
            'database-charset-connection' => ['measures' => self::t('El juego de caracteres con el que se conecta la aplicación, de la configuración de la base.'), 'ifWrong' => self::t('Es informativa: utf8mb4 guarda cualquier carácter.'), 'falseAlarm' => $none],
            'database-charset-schema' => ['measures' => self::t('El juego de caracteres por omisión de la base.'), 'ifWrong' => self::t('Es informativa: utf8mb4 guarda cualquier carácter.'), 'falseAlarm' => $none],
            'database-tables' => ['measures' => self::t('Cuántas tablas tiene la base.'), 'ifWrong' => $info, 'falseAlarm' => $none],
            'database-missing-tables' => [
                'measures' => self::t('Las tablas que declara algún mapper del código y la base no tiene ni como tabla ni como vista (el mismo descubrimiento que bin/cli scheme-create).'),
                'ifWrong' => self::t('Crea esas tablas: aplica las actualizaciones de databases/actualizaciones/ o el SQL que genera bin/cli scheme-create.'),
                'falseAlarm' => self::t('Una vista con el nombre de la tabla cuenta como existente; no sale aquí.'),
            ],
            'backup-last' => [
                'measures' => self::t('Cuándo hizo el sistema el último respaldo.'),
                'ifWrong' => self::t('«Ninguno hecho por el sistema»: comprueba que la tarea de respaldos esté programada en el cron del servidor, o haz uno desde Respaldos.'),
                'falseAlarm' => self::t('Los respaldos hechos fuera de la aplicación (por ejemplo, desde el panel del servidor) no cuentan aquí.'),
            ],
            'backup-next' => ['measures' => self::t('Cuándo toca el siguiente respaldo.'), 'ifWrong' => $info, 'falseAlarm' => $none],
            'task-run' => [
                'measures' => self::t('La última ejecución anotada de cada tarea programada y su estado.'),
                'ifWrong' => self::t('Sin ejecuciones o con fallo: comprueba que el cron del servidor llame a las tareas y mira su registro en Tareas programadas.'),
                'falseAlarm' => self::t('En una instalación recién hecha, o con el cron recién configurado, todavía no hay ejecuciones.'),
            ],
            'folder-writable' => [
                'measures' => self::t('Si cada carpeta de app/config/writable-dirs.txt existe y PHP puede escribir en ella.'),
                'ifWrong' => self::t('Crea la carpeta o da permiso de escritura al usuario del servidor web.'),
                'falseAlarm' => self::t('La terminal y el servidor web corren con usuarios distintos: una carpeta puede ser escribible para uno y no para el otro. Lo que cuenta para la aplicación es lo que ve el servidor web, el que pinta el panel.'),
            ],
            'folder-keys' => [
                'measures' => self::t('La carpeta de claves que usa el framework (AppKeyring::directory()): secure-keys/ junto a la instalación o, si no está, private/secure-keys/, siempre dentro de open_basedir; y si se puede escribir en ella. Nunca su contenido.'),
                'ifWrong' => self::t('«Falta»: no hay ninguna de las dos, o open_basedir no deja verlas; créala fuera de la carpeta pública. «No escribible»: da permiso de escritura al usuario del servidor web.'),
                'falseAlarm' => self::t('Que la carpeta sea la segunda (private/secure-keys/) no es un fallo: la fila enseña la que se usa. Y la terminal y el servidor web pueden tener open_basedir y usuarios distintos.'),
            ],
            'security-uploads-php' => [
                'measures' => self::t('Si PHP está apagado en las carpetas de subidas por el .htaccess que escribe el sistema.'),
                'ifWrong' => self::t('Sin .htaccess, con uno que no es del sistema o sin el bloque que niega PHP: vuelve a generarlo, o configura el servidor para que no ejecute PHP en esas carpetas.'),
                'falseAlarm' => self::t('Con un servidor que no es Apache (nginx) o desde la terminal, el .htaccess no se lee y la fila dice «Depende del servidor»: manda su configuración.'),
            ],
            'security-allowed-hosts' => [
                'measures' => self::t('La lista de dominios permitidos de la configuración.'),
                'ifWrong' => self::t('Vacía en producción: la instalación responde con cualquier dominio. Añade los tuyos en la configuración.'),
                'falseAlarm' => self::t('En local, vacía es lo normal y sale como información.'),
            ],
            'security-environment' => [
                'measures' => self::t('Si la instalación está en local o en producción, según app/config/environment.php.'),
                'ifWrong' => self::t('Sin environment.php se trata como producción: créalo.'),
                'falseAlarm' => $none,
            ],
        ];
    }

    /**
     * La Salud en Markdown: la vista entera (primero lo que pide atención, después cada grupo en el orden de
     * $groupTitles) o un solo bloque. La cabecera lleva el título, la fecha y hora de generación y la versión del
     * framework; nada más (ni dominio, ni rutas, ni secretos). Pura salvo esa fecha y esa versión.
     *
     * @param array<int,array{group:string,label:string,value:string,state:string,origin:string,key?:string}> $rows
     * @param array<string,string> $groupTitles
     * @param string|null $group Un grupo de $groupTitles, o GROUP_ATTENTION para lo que pide atención; null, todo
     * @return string
     * @throws \InvalidArgumentException Si $group no es ninguno de esos
     */
    public static function markdown(array $rows, array $groupTitles, ?string $group = null): string
    {
        if (!self::isExportGroup($group, $groupTitles)) {
            throw new \InvalidArgumentException(self::unknownGroupMessage((string) $group));
        }

        $states = [
            self::STATE_OK => self::t('Bien'),
            self::STATE_WARNING => self::t('Atención'),
            self::STATE_MISSING => self::t('Falta'),
            self::STATE_INFO => self::t('Información'),
        ];
        $explanations = self::explanations();
        $cell = fn(string $text): string => str_replace(["\r\n", "\r", "\n"], '<br>', self::escapeMarkdown($text));
        $inline = fn(string $text): string => str_replace(["\r\n", "\r", "\n"], ' ', self::escapeMarkdown($text));
        $entries = function (array $blockRows) use ($explanations, $inline): string {
            $lines = [];
            foreach ($blockRows as $row) {
                $explanation = $explanations[$row['key'] ?? ''] ?? null;
                if ($explanation === null) {
                    continue;
                }
                $lines[] = sprintf('- **%s**: %s %s %s %s %s', $inline($row['label']), $explanation['measures'], self::t('Si está mal:'), $explanation['ifWrong'], self::t('Falsa alarma:'), $explanation['falseAlarm']);
            }
            return $lines === [] ? '' : "\n" . implode("\n", $lines) . "\n";
        };

        $out = '# ' . self::t('Salud de la instalación') . "\n\n";
        $out .= self::t('Documento interno: describe esta instalación (versiones, configuración y estado). No lo publiques.') . "\n\n";
        $out .= sprintf(self::t('Generado el %s · framework %s'), date('d-m-Y H:i:s'), AppVersion::version()) . "\n";

        if ($group === null || $group === self::GROUP_ATTENTION) {
            $attention = array_values(array_filter($rows, fn(array $row): bool => in_array($row['state'], [self::STATE_WARNING, self::STATE_MISSING], true)));
            $out .= "\n## " . self::t('Lo que pide atención') . "\n\n";
            if ($attention === []) {
                $out .= self::t('Todo en orden.') . "\n";
            } else {
                $out .= '| ' . self::t('Grupo') . ' | ' . self::t('Dato') . ' | ' . self::t('Estado') . ' | ' . self::t('Valor') . ' | ' . self::t('Origen') . " |\n";
                $out .= "| :-- | :-- | :-- | :-- | :-- |\n";
                foreach ($attention as $row) {
                    $out .= '| ' . $cell($groupTitles[$row['group']] ?? $row['group']) . ' | ' . $cell($row['label']) . ' | ' . $cell($states[$row['state']] ?? $row['state']) . ' | ' . $cell($row['value']) . ' | ' . $cell($row['origin']) . " |\n";
                }
                $out .= $entries($attention);
            }
        }

        foreach ($groupTitles as $key => $title) {
            if ($group !== null && $group !== $key) {
                continue;
            }
            $blockRows = array_values(array_filter($rows, fn(array $row): bool => $row['group'] === $key));
            $out .= "\n## " . $inline($title) . "\n\n";
            $out .= '| ' . self::t('Dato') . ' | ' . self::t('Estado') . ' | ' . self::t('Valor') . ' | ' . self::t('Origen') . " |\n";
            $out .= "| :-- | :-- | :-- | :-- |\n";
            foreach ($blockRows as $row) {
                $out .= '| ' . $cell($row['label']) . ' | ' . $cell($states[$row['state']] ?? $row['state']) . ' | ' . $cell($row['value']) . ' | ' . $cell($row['origin']) . " |\n";
            }
            $out .= $entries($blockRows);
        }

        return $out;
    }

    /**
     * Si $group es un bloque que se puede exportar: null (la vista entera), GROUP_ATTENTION o un grupo de $groupTitles.
     *
     * @param string|null $group
     * @param array<string,string> $groupTitles
     * @return bool
     */
    public static function isExportGroup(?string $group, array $groupTitles): bool
    {
        return $group === null || $group === self::GROUP_ATTENTION || array_key_exists($group, $groupTitles);
    }

    /**
     * El mensaje de un bloque que no existe: lo nombra solo si tiene la forma de un bloque (minúsculas y guiones, hasta
     * 32); si no, no repite lo que se mandó.
     *
     * @param string $group
     * @return string
     */
    public static function unknownGroupMessage(string $group): string
    {
        return preg_match('/^[a-z-]{1,32}$/D', $group) === 1
            ? sprintf(self::t('El bloque «%s» no existe en la salud de la instalación.'), $group)
            : self::t('Ese bloque no existe en la salud de la instalación.');
    }

    /**
     * Escapa para Markdown lo que no es texto: \ ` * _ < > [ ] y |, con una barra invertida delante. Pura.
     *
     * @param string $text
     * @return string
     */
    public static function escapeMarkdown(string $text): string
    {
        return (string) preg_replace('/([\\\\`*_<>\[\]|])/', '\\\\$1', $text);
    }

    /**
     * El nombre de la carpeta de claves en la fila: relativo a la raíz de la instalación («../secure-keys») si está dentro, y
     * si no, solo su nombre (secure-keys o private/secure-keys): nunca una ruta absoluta. Los separadores se igualan antes de
     * comparar (en Windows llega con \). Pura.
     *
     * @param string|null $keys AppKeyring::directory()
     * @param string $root El padre de src/, con /
     * @return string
     */
    public static function keysLabel(?string $keys, string $root): string
    {
        if ($keys === null) {
            return 'secure-keys';
        }
        $keys = rtrim(str_replace('\\', '/', $keys), '/');
        $root = rtrim(str_replace('\\', '/', $root), '/');
        if ($root !== '' && str_starts_with($keys, $root . '/')) {
            return '../' . substr($keys, strlen($root) + 1);
        }
        return str_ends_with($keys, '/private/secure-keys') ? 'private/secure-keys' : 'secure-keys';
    }

    /**
     * @return string[] Las carpetas de app/config/writable-dirs.txt, relativas a src/
     */
    public static function writableDirs(): array
    {
        $lines = @file(basepath(self::WRITABLE_DIRS_FILE), FILE_IGNORE_NEW_LINES);
        $dirs = [];
        foreach (is_array($lines) ? $lines : [] as $line) {
            $line = trim($line);
            if ($line !== '' && !str_starts_with($line, '#')) {
                $dirs[] = $line;
            }
        }
        return $dirs;
    }

    /**
     * @param string $key El TIPO de fila (las de cada extensión, carpeta o tarea comparten uno): su explicación en explanations()
     * @return array{group:string,label:string,value:string,state:string,origin:string,key:string}
     */
    private static function row(string $group, string $label, string $value, string $state, string $origin, string $key): array
    {
        return ['group' => $group, 'label' => $label, 'value' => $value, 'state' => $state, 'origin' => $origin, 'key' => $key];
    }

    private static function t(string $text): string
    {
        return __(SystemStatusController::LANG_GROUP, $text);
    }
}
