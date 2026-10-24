<?php

/**
 * SystemHealth.php
 */

namespace PiecesPHP\SystemStatus;

use API\Adapters\FfmpegAudioAdapter;
use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\AppVersion;
use PiecesPHP\Core\InstalledPackages;
use PiecesPHP\Settings\Controllers\SettingsController;
use PiecesPHP\SystemStatus\Controllers\SystemStatusController;
use PiecesPHP\Terminal\TaskRunMapper;
use PiecesPHP\Terminal\CronJobTask;
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
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
     */
    public static function rows(?array $requiredExtensions = null): array
    {
        return array_merge(
            self::frameworkRows(),
            self::packageRows(),
            self::phpRows($requiredExtensions),
            self::databaseRows(),
            self::taskRows(),
            self::folderRows()
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
        ];
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
     */
    private static function frameworkRows(): array
    {
        $rows = [];
        $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Versión'), AppVersion::version(), self::STATE_INFO, 'AppVersion::version()');
        $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Fecha de la versión'), AppVersion::date(), self::STATE_INFO, 'AppVersion::date()');
        $commit = AppVersion::commit();
        $commitOrigin = AppVersion::commitSource() === AppVersion::SOURCE_STAMP ? AppVersion::STAMP_RELATIVE_PATH : '.git/HEAD';
        $rows[] = $commit !== null
            ? self::row(self::GROUP_FRAMEWORK, self::t('Commit'), $commit, self::STATE_INFO, "AppVersion::commit() ({$commitOrigin})")
            : self::row(self::GROUP_FRAMEWORK, self::t('Commit'), self::t('Sin commit conocido'), self::STATE_WARNING, 'AppVersion::commit()');

        $markPath = Distribution::root() . '/' . Distribution::MARK_FILE;
        if (Distribution::isDistribution()) {
            $mark = (string) @file_get_contents($markPath);
            $tag = preg_match('/^etiqueta: (.+)$/m', $mark, $tagMatch) === 1 ? trim($tagMatch[1]) : '';
            $markCommit = preg_match('/^commit: (.+)$/m', $mark, $commitMatch) === 1 ? trim($commitMatch[1]) : '';
            $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Distribución'), trim("{$tag} · {$markCommit}", ' ·'), self::STATE_INFO, Distribution::MARK_FILE);
        } else {
            $rows[] = self::row(self::GROUP_FRAMEWORK, self::t('Distribución'), self::t('No es una distribución'), self::STATE_INFO, Distribution::MARK_FILE);
        }

        $rows[] = self::row(
            self::GROUP_FRAMEWORK,
            self::t('Entorno'),
            AppEnvironment::isConfigured() ? AppEnvironment::get() : self::t('Sin declarar'),
            AppEnvironment::isConfigured() ? self::STATE_OK : self::STATE_WARNING,
            'AppEnvironment::get() (app/config/environment.php)'
        );
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
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
                'composer.json, Composer\InstalledVersions, vendor/composer/installed.json'
            );
        }
        return $rows;
    }

    /**
     * @param string[]|null $requiredExtensions
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
     */
    private static function phpRows(?array $requiredExtensions): array
    {
        $require = InstalledPackages::requireList();
        $rows = [];
        $rows[] = self::row(self::GROUP_PHP, self::t('Versión de PHP'), sprintf(self::t('%s (pide %s)'), PHP_VERSION, $require['php'] ?? '—'), self::STATE_INFO, 'PHP_VERSION, composer.json');

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
            $rows[] = self::row(self::GROUP_PHP, "ext-{$extension}", $loaded ? self::t('Cargada') : self::t('No cargada'), $loaded ? self::STATE_OK : self::STATE_MISSING, "extension_loaded('{$extension}')");
        }

        foreach (self::INI_SETTINGS as $setting) {
            $value = ini_get($setting);
            $rows[] = self::row(self::GROUP_PHP, $setting, $value === false ? '—' : $value, self::STATE_INFO, "ini_get('{$setting}')");
        }

        $opcache = function_exists('opcache_get_status') ? @opcache_get_status(false) : false;
        $opcacheOn = is_array($opcache) && ($opcache['opcache_enabled'] ?? false) === true;
        $rows[] = self::row(self::GROUP_PHP, 'OPcache', $opcacheOn ? self::t('Activo') : self::t('Inactivo'), $opcacheOn ? self::STATE_OK : self::STATE_INFO, 'opcache_get_status()');
        $validates = (bool) ini_get('opcache.validate_timestamps');
        $rows[] = self::row(
            self::GROUP_PHP,
            self::t('OPcache revalida los archivos'),
            $validates ? sprintf(self::t('Sí, cada %s s'), (string) ini_get('opcache.revalidate_freq')) : self::t('No'),
            self::STATE_INFO,
            "ini_get('opcache.validate_timestamps'), ini_get('opcache.revalidate_freq')"
        );

        foreach (['ffmpeg' => FfmpegAudioAdapter::COMMON_PATHS_FFMPEG, 'ffprobe' => FfmpegAudioAdapter::COMMON_PATHS_FFPROBE] as $binary => $paths) {
            $found = FfmpegAudioAdapter::findBinary($paths) !== null;
            $rows[] = self::row(self::GROUP_PHP, $binary, $found ? self::t('Instalado') : self::t('No encontrado'), $found ? self::STATE_OK : self::STATE_WARNING, 'FfmpegAudioAdapter::findBinary()');
        }
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
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
            return [self::row(self::GROUP_DATABASE, self::t('Conexión'), self::t('Sin conexión'), self::STATE_MISSING, 'BaseModel::getDatabase()')];
        }

        $version = (string) $database->query('SELECT VERSION()')->fetchColumn();
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Motor y versión'), $version, self::STATE_INFO, 'SELECT VERSION()');

        $config = get_config('database');
        $charset = is_array($config) && isset($config['default']['charset']) && is_string($config['default']['charset']) ? $config['default']['charset'] : '—';
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Juego de caracteres de la conexión'), $charset, self::STATE_INFO, "\$config['database']['default']['charset']");
        $schemaCharset = (string) $database->query('SELECT DEFAULT_CHARACTER_SET_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = DATABASE()')->fetchColumn();
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Juego de caracteres de la base'), $schemaCharset !== '' ? $schemaCharset : '—', self::STATE_INFO, 'information_schema.SCHEMATA');

        $tables = SettingsController::databaseTables();
        $rows[] = self::row(self::GROUP_DATABASE, self::t('Tablas'), (string) count($tables), self::STATE_INFO, 'SettingsController::databaseTables()');

        //Las que declara un mapper de src/app/classes y la base no tiene: el mismo descubrimiento que scheme-create.
        $declared = [];
        foreach (SchemeSqlTask::discover('all')['creators'] as $creator) {
            $declared[$creator->getTable()] = true;
        }
        $missing = array_values(array_diff(array_keys($declared), $tables));
        sort($missing);
        $rows[] = self::row(
            self::GROUP_DATABASE,
            self::t('Tablas declaradas que la base no tiene'),
            count($missing) > 0 ? implode(', ', $missing) : sprintf(self::t('Ninguna, de %d declaradas'), count($declared)),
            count($missing) > 0 ? self::STATE_WARNING : self::STATE_OK,
            'SchemeSqlTask::discover(\'all\') (scheme-create)'
        );
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
     */
    private static function taskRows(): array
    {
        $rows = [];
        $backups = SettingsController::backupStatus();
        $rows[] = $backups['latest'] !== null
            ? self::row(self::GROUP_TASKS, self::t('Último respaldo'), $backups['latest']->format('d-m-Y H:i'), self::STATE_OK, 'SettingsController::backupStatus()')
            : self::row(self::GROUP_TASKS, self::t('Último respaldo'), self::t('Ninguno hecho por el sistema'), self::STATE_WARNING, 'SettingsController::backupStatus()');
        $rows[] = self::row(
            self::GROUP_TASKS,
            self::t('Siguiente respaldo'),
            $backups['due'] ? self::t('Toca ya') : ($backups['next'] !== null ? $backups['next']->format('d-m-Y H:i') : '—'),
            self::STATE_INFO,
            'SettingsController::backupStatus()'
        );

        $latest = SystemStatusController::registeredRuns(TaskRunMapper::latestByTask());
        foreach (CronJobTask::getCronJobs() as $task) {
            $name = $task->getName();
            if (!isset($latest[$name])) {
                $rows[] = self::row(self::GROUP_TASKS, $name, self::t('Sin ejecuciones anotadas'), self::STATE_WARNING, 'TaskRunMapper::latestByTask()');
                continue;
            }
            $run = $latest[$name];
            $at = strtotime($run['startedAt']);
            $rows[] = self::row(
                self::GROUP_TASKS,
                $name,
                $run['status'] . ($at !== false ? ' · ' . date('d-m-Y H:i', $at) : ''),
                $run['status'] === CronJobTask::STATUS_FAILED ? self::STATE_WARNING : self::STATE_OK,
                'TaskRunMapper::latestByTask()'
            );
        }
        return $rows;
    }

    /**
     * @return array<int,array{group:string,label:string,value:string,state:string,origin:string}>
     */
    private static function folderRows(): array
    {
        $rows = [];
        //secure-keys/ va junto a src/ (getKeyFromSecureKeys()): solo se mira si se puede escribir, nunca su contenido.
        foreach (array_merge(self::writableDirs(), ['../secure-keys']) as $relative) {
            $path = basepath($relative);
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            $rows[] = self::row(
                self::GROUP_FOLDERS,
                $relative,
                $writable ? self::t('Escribible') : ($exists ? self::t('No escribible') : self::t('No existe')),
                $writable ? self::STATE_OK : self::STATE_MISSING,
                'is_writable() · ' . self::WRITABLE_DIRS_FILE
            );
        }
        return $rows;
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
     * @return array{group:string,label:string,value:string,state:string,origin:string}
     */
    private static function row(string $group, string $label, string $value, string $state, string $origin): array
    {
        return ['group' => $group, 'label' => $label, 'value' => $value, 'state' => $state, 'origin' => $origin];
    }

    private static function t(string $text): string
    {
        return __(SystemStatusController::LANG_GROUP, $text);
    }
}
