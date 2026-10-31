<?php

/**
 * GenericHandler.php
 */
namespace PiecesPHP\Core\CustomErrorsHandlers;

use Throwable;

/**
 * GenericHandler - Anota un error en el registro de errores (ADR 0060 §6).
 *
 * - `error.log.jsonl`: una entrada JSON por línea, añadida al final con LOCK_EX. Nunca se reescribe: dos errores a la
 *   vez dejan dos entradas íntegras. Al pasar de $maxSizeMB se rota a `olds/`.
 * - `error-traces/<huella>.json`: cada traza una vez, por su huella. Si ya existe no se toca.
 * - `error-soft/<AAAAMMDD>/<firma>`: el contador de un suave. La primera aparición del día crea el archivo (con la
 *   referencia de su entrada) y escribe la entrada; las demás solo le añaden un byte. Repeticiones = bytes tras la
 *   referencia; la última = fecha de modificación.
 *
 * El formato anterior (`error.log.json`, el JSON entero por días) ya no se escribe; ErrorLogReader lo sigue leyendo.
 *
 * Lo que escribe una prueba lleva `context.test` con el id de su corrida y se poda al terminar (ADR 0063): ver testRun()
 * y pruneTestRun(). Un error sin esa marca no se poda nunca.
 *
 * @category     ErrorsHandlers
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class GenericHandler
{
    const FILE_ENTRIES = 'error.log.jsonl';
    const FILE_PLAIN = 'error.plain.log';
    const FILE_UNIQUE = 'error.unique.message.log';
    const DIRECTORY_TRACES = 'error-traces';
    const DIRECTORY_SOFT = 'error-soft';

    /**
     * Lo que ocupa la cabecera de un contador de suaves: la referencia (ERR-AAAAMMDD-XXXXXX) y un salto.
     */
    const SOFT_COUNTER_HEADER = 20;

    /**
     * Excepciones previas que se guardan como máximo: una cadena más larga es un bucle de envoltorios.
     */
    const MAX_PREVIOUS = 10;

    /**
     * Cuántos días se guardan los contadores de suaves y las trazas (pruneStorage(), tarea diaria; G8).
     */
    const SOFT_RETENTION_DAYS = 30;
    const TRACE_RETENTION_DAYS = 90;

    /**
     * El id de la corrida de pruebas en curso (ADR 0063): `t<pid>-<12 hex>`. Por entorno en la terminal (lo pone gates o la
     * suite sola) y por esta cabecera en una petición de una suite, que solo se acepta en una instalación local.
     */
    const TEST_RUN_ENV = 'PCSPHP_TEST_RUN';
    const TEST_RUN_HEADER = 'X-PCSPHP-Test-Run';
    const TEST_RUN_PATTERN = '/^t([0-9]+)-[0-9a-f]{12}$/';

    /**
     * @var Throwable
     */
    protected $exception;
    /**
     * @var int
     */
    protected $maxSizeMB = 1;
    /**
     * @var \DateTime
     */
    protected $date = null;
    /**
     * @var string
     */
    protected $fileLocation = '';
    /**
     * @var string
     */
    protected $oldFileLocation = '';
    /**
     * @var string
     */
    protected $directory = '';
    /**
     * @var string
     */
    protected $directoryBackup = '';
    /**
     * @var string
     */
    protected $fileLocationPlain = '';
    /**
     * @var string
     */
    protected $oldFileLocationPlain = '';
    /**
     * @var string
     */
    protected $fileLocationUniqueMessage = '';
    /**
     * @var string Código que el usuario puede reportar y que identifica la entrada del log (P56)
     */
    private $reference = '';
    /**
     * @var string ErrorSeverity::LEVEL_*
     */
    private $level = ErrorSeverity::LEVEL_GRAVE;
    /**
     * @var string|null El nivel de PHP de un suave (E_WARNING…) en lugar de la clase de la excepción
     */
    private $typeName = null;
    /**
     * @var array<mixed>
     */
    private $softContext = [];
    /**
     * @var bool Un error que salta mientras se anota un suave no vuelve a entrar: iría en bucle
     */
    private static $writingSoft = false;
    /**
     * @var string|null El nombre de la ruta de la petición en curso, para el contexto
     */
    private static $routeName = null;

    /**
     * @param Throwable $e
     * @param string|null $directory Por omisión, LOG_ERRORS_PATH (y su olds/, LOG_ERRORS_BACKUP_PATH). Las pruebas
     * dan un temporal
     * @return static
     * @throws \TypeError
     */
    public function __construct(Throwable $e, ?string $directory = null)
    {

        $this->exception = $e;

        $date = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)));
        $date = $date !== false ? $date : new \DateTime();
        $dateTimeZone = new \DateTimeZone(date_default_timezone_get());
        $date->setTimezone($dateTimeZone);

        $this->date = $date;
        $this->reference = 'ERR-' . $date->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

        if ($directory !== null) {
            $this->directory = rtrim($directory, '/\\');
            $this->directoryBackup = $this->directory . '/olds';
        } else {
            if (!defined('LOG_ERRORS_PATH')) {
                define('LOG_ERRORS_PATH', realpath(__DIR__ . '/../../../../../logs'));
            }
            if (!defined('LOG_ERRORS_BACKUP_PATH')) {
                define('LOG_ERRORS_BACKUP_PATH', realpath(__DIR__ . '/../../../../../logs/olds'));
            }
            $this->directory = rtrim((string) LOG_ERRORS_PATH, '/\\');
            $this->directoryBackup = rtrim((string) LOG_ERRORS_BACKUP_PATH, '/\\');
        }

        self::ensureDirectory($this->directory);
        self::ensureDirectory($this->directoryBackup);

        $this->fileLocation = $this->directory . '/' . self::FILE_ENTRIES;
        $this->oldFileLocation = $this->directoryBackup . '/error.log.{{DATE}}.jsonl';

        $this->fileLocationPlain = $this->directory . '/' . self::FILE_PLAIN;
        $this->oldFileLocationPlain = $this->directoryBackup . '/error.plain.{{DATE}}.log';

        $this->fileLocationUniqueMessage = $this->directory . '/' . self::FILE_UNIQUE;
    }

    /**
     * Anota un error suave (ADR 0060) y devuelve su referencia. No lanza nunca: si no puede anotarlo, devuelve ''.
     *
     * Si ese suave (tipo, archivo, línea) ya se anotó hoy, no crea otra entrada: suma una repetición y devuelve la
     * referencia de la primera.
     *
     * @param Throwable $e
     * @param string $typeName El nivel de PHP (E_WARNING…) o el origen (log_soft_error)
     * @param array<mixed> $context Va a extraData
     * @param string|null $directory Ver __construct()
     * @return string
     */
    public static function logSoft(Throwable $e, string $typeName, array $context = [], ?string $directory = null): string
    {
        if (self::$writingSoft) {
            return '';
        }
        self::$writingSoft = true;
        try {
            $handler = new static($e, $directory);
            $handler->level = ErrorSeverity::LEVEL_SOFT;
            $handler->typeName = $typeName;
            $handler->softContext = $context;
            return $handler->recordSoft();
        } catch (\Throwable) {
            return '';
        } finally {
            self::$writingSoft = false;
        }
    }

    /**
     * La ruta de la petición en curso; la fija el middleware global de index.php.
     *
     * @param string|null $routeName
     * @return void
     */
    public static function setRouteName(?string $routeName): void
    {
        self::$routeName = $routeName;
    }

    /**
     * El contexto mínimo de la petición (ADR 0060 §6). NUNCA el cuerpo, las cabeceras ni la cadena de consulta: pueden
     * llevar secretos.
     *
     * @return array{method:string,route:string|null,url:string|null,userID:int|null}
     */
    public static function requestContext(): array
    {
        $isCli = PHP_SAPI === 'cli' && !isset($_SERVER['REQUEST_METHOD']);
        $url = null;
        if (!$isCli && isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])) {
            $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
            $path = is_string($_SERVER['REQUEST_URI'] ?? null) ? $_SERVER['REQUEST_URI'] : '/';
            $path = explode('#', explode('?', $path, 2)[0], 2)[0];
            $url = ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . $path;
        }
        $userID = null;
        if (function_exists('get_config')) {
            try {
                $currentUser = get_config('current_user');
                $userID = is_object($currentUser) && isset($currentUser->id) && is_numeric($currentUser->id) ? (int) $currentUser->id : null;
            } catch (\Throwable) {
                $userID = null;
            }
        }
        return [
            'method' => $isCli ? 'CLI' : (is_string($_SERVER['REQUEST_METHOD'] ?? null) ? $_SERVER['REQUEST_METHOD'] : ''),
            'route' => self::$routeName,
            'url' => $url,
            'userID' => $userID,
        ];
    }

    /**
     * El id de la corrida de pruebas que escribe ahora, o null si no es una prueba.
     *
     * La cabecera solo vale con is_local(): fuera, cualquiera podría hacer pasar un error real por uno de prueba y
     * conseguir que se pode.
     *
     * @return string|null
     */
    public static function testRun(): ?string
    {
        $header = $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', self::TEST_RUN_HEADER))] ?? null;
        $isLocal = static function (): bool {
            try {
                return function_exists('is_local') && is_local();
            } catch (\Throwable) {
                return false;
            }
        };
        return self::testRunFrom(getenv(self::TEST_RUN_ENV), $header, PHP_SAPI === 'cli', $isLocal);
    }

    /**
     * La decisión de testRun(), sin leer el proceso: el entorno manda; la cabecera, solo en una petición web y local.
     *
     * @param mixed $environment
     * @param mixed $header
     * @param bool $isCli
     * @param callable():bool $isLocal Se pregunta solo si hay una cabecera que valga
     * @return string|null
     */
    public static function testRunFrom(mixed $environment, mixed $header, bool $isCli, callable $isLocal): ?string
    {
        if (is_string($environment) && preg_match(self::TEST_RUN_PATTERN, $environment) === 1) {
            return $environment;
        }
        if ($isCli || !is_string($header) || preg_match(self::TEST_RUN_PATTERN, $header) !== 1) {
            return null;
        }
        return $isLocal() ? $header : null;
    }

    /**
     * Abre una corrida de pruebas en este proceso si no hay ya una, y la deja en el entorno para los procesos hijos.
     *
     * @return array{run:string,created:bool} created: la abrió esta llamada, así que la poda le toca a quien llamó
     */
    public static function startTestRun(): array
    {
        $run = getenv(self::TEST_RUN_ENV);
        if (is_string($run) && preg_match(self::TEST_RUN_PATTERN, $run) === 1) {
            return ['run' => $run, 'created' => false];
        }
        $run = 't' . getmypid() . '-' . bin2hex(random_bytes(6));
        //RETORNO-IGNORADO: si el entorno no se escribe, nada sale marcado y nada se poda: el registro queda como sin ADR 0063.
        putenv(self::TEST_RUN_ENV . '=' . $run);
        return ['run' => $run, 'created' => true];
    }

    /**
     * Quita del registro las entradas de esa corrida y las de las corridas cuyo proceso ya no vive (ADR 0063 §2-§3), con
     * sus trazas y sus contadores de suaves si nadie más los cita. Una entrada sin marca no se toca nunca, ni la de otra
     * corrida viva.
     *
     * Reescribe con el mismo cerrojo que appendEntry(), a un temporal y con rename(): el archivo queda entero o no
     * cambia. Quien esperaba el cerrojo ve otro inodo y vuelve a abrir.
     *
     * También los dos registros planos, error.plain.log y error.unique.message.log, por bloques: cada uno, con su propia
     * POSCONDICIÓN, queda entero o no cambia. Los rotados (olds/) no se podan.
     *
     * @param string $run
     * @param string|null $directory Por omisión, LOG_ERRORS_PATH; las pruebas dan un temporal
     * @return array{entries:int,traces:int,soft:int,plain:int,unique:int,error:string|null}
     */
    public static function pruneTestRun(string $run, ?string $directory = null): array
    {
        $result = ['entries' => 0, 'traces' => 0, 'soft' => 0, 'plain' => 0, 'unique' => 0, 'error' => null];
        if ($directory === null) {
            $directory = defined('LOG_ERRORS_PATH') ? (string) LOG_ERRORS_PATH : (string) realpath(__DIR__ . '/../../../../../logs');
        }
        $directory = rtrim($directory, '/\\');
        $errors = [];
        foreach (['plain' => self::FILE_PLAIN, 'unique' => self::FILE_UNIQUE] as $key => $name) {
            [$result[$key], $error] = self::pruneBlocks($directory . '/' . $name, $key === 'plain', $run);
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        $result['error'] = $errors !== [] ? implode('; ', $errors) : null;
        $file = $directory . '/' . self::FILE_ENTRIES;
        if (!is_file($file)) {
            return $result;
        }
        $handle = @fopen($file, 'r+b');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            $errors[] = 'no se pudo abrir ni echar el cerrojo a ' . $file;
            $result['error'] = implode('; ', $errors);
            if ($handle !== false) {
                //RETORNO-IGNORADO: no se escribió nada; cerrar o no, el registro sigue como estaba.
                fclose($handle);
            }
            return $result;
        }
        $kept = '';
        $removed = [];
        $fingerprints = [];
        $softKeys = [];
        try {
            $content = stream_get_contents($handle);
            $content = is_string($content) ? $content : '';
            foreach (explode("\n", $content) as $line) {
                if ($line === '') {
                    continue;
                }
                $entry = json_decode($line, true);
                $mark = is_array($entry) && is_array($entry['context'] ?? null) ? ($entry['context']['test'] ?? null) : null;
                if (!is_string($mark) || !self::isPrunable($mark, $run)) {
                    $kept .= $line . "\n";
                    continue;
                }
                $result['entries']++;
                $removed[] = $line;
                $fingerprints[] = (string) ($entry['traceFingerprint'] ?? '');
                foreach (is_array($entry['previous'] ?? null) ? $entry['previous'] : [] as $previous) {
                    $fingerprints[] = is_array($previous) ? (string) ($previous['traceFingerprint'] ?? '') : '';
                }
                if (is_string($entry['softKey'] ?? null)) {
                    $softKeys[] = $entry['softKey'];
                }
            }
            //POSCONDICIÓN, aparte del filtro de arriba: si una sola línea que se iba a quitar no lleva la marca de una corrida
            //podable, no se escribe nada. Así un fallo del filtro aborta en vez de borrar errores reales (#1603).
            $unmarked = self::unmarkedAmong($removed, $run);
            if ($unmarked > 0) {
                $errors[] = "poda abortada: {$unmarked} entradas sin marca";
                $result['error'] = implode('; ', $errors);
                $result['entries'] = 0;
                return $result;
            }
            if ($result['entries'] > 0) {
                $temporary = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
                if (@file_put_contents($temporary, $kept) !== strlen($kept) || !@rename($temporary, $file)) {
                    //RETORNO-IGNORADO: si el temporal no se escribió entero, no queda nada que conservar.
                    @unlink($temporary);
                    $errors[] = 'no se pudo reescribir ' . $file . ': no se podó nada';
                    $result['error'] = implode('; ', $errors);
                    $result['entries'] = 0;
                    return $result;
                }
                //RETORNO-IGNORADO: el permiso deja escribir al otro usuario del sistema; el archivo sirve igual.
                @chmod($file, 0664);
            }
        } finally {
            self::release($handle);
        }

        //Una traza la puede citar una entrada que se queda, también en olds/: solo se borra la que nadie cita.
        $citations = $kept;
        foreach (glob($directory . '/olds/error.log.*.jsonl') ?: [] as $old) {
            $citations .= (string) @file_get_contents($old);
        }
        foreach (array_unique(array_filter($fingerprints)) as $fingerprint) {
            $path = $directory . '/' . self::DIRECTORY_TRACES . '/' . $fingerprint . '.json';
            if (preg_match('/^[0-9a-f]{64}$/', $fingerprint) === 1 && !str_contains($citations, $fingerprint) && is_file($path)) {
                $result['traces'] += @unlink($path) ? 1 : 0;
            }
        }
        //La marca entra en la firma del suave (§4): su contador es solo de la corrida.
        foreach (array_unique($softKeys) as $softKey) {
            $path = $directory . '/' . self::DIRECTORY_SOFT . '/' . $softKey;
            if (preg_match('/^[0-9]{8}\/[0-9a-f]{64}$/', $softKey) === 1 && !str_contains($citations, $softKey) && is_file($path)) {
                $result['soft'] += @unlink($path) ? 1 : 0;
            }
        }
        return $result;
    }

    /**
     * Poda un registro plano por bloques: una cabecera "[fecha] …" y las líneas que la siguen hasta la próxima (un mensaje
     * con saltos de línea no deja restos). Mismo cerrojo, temporal y rename que la poda del principal.
     *
     * @param string $file
     * @param bool $plain error.plain.log (la marca tras "[ref X]") o error.unique.message.log (la marca cierra la cabecera)
     * @param string $run
     * @return array{0:int,1:string|null} Los bloques quitados y el error, si lo hubo
     */
    private static function pruneBlocks(string $file, bool $plain, string $run): array
    {
        if (!is_file($file)) {
            return [0, null];
        }
        $handle = @fopen($file, 'r+b');
        if ($handle === false || !flock($handle, LOCK_EX)) {
            if ($handle !== false) {
                //RETORNO-IGNORADO: no se escribió nada; cerrar o no, el registro sigue como estaba.
                fclose($handle);
            }
            return [0, 'no se pudo abrir ni echar el cerrojo a ' . $file];
        }
        try {
            $content = stream_get_contents($handle);
            $content = is_string($content) ? $content : '';
            //Byte a byte: cada línea conserva su salto, y lo que se queda se escribe tal cual estaba.
            $blocks = [];
            foreach (preg_split('/(?<=\n)/', $content, -1, \PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
                if ($blocks === [] || preg_match('/^\[[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9:.]{15}\] \[/', $line) === 1) {
                    $blocks[] = '';
                }
                $blocks[count($blocks) - 1] .= $line;
            }
            $kept = '';
            $removed = [];
            foreach ($blocks as $block) {
                $mark = self::blockMark($block, $plain);
                if ($mark !== null && self::isPrunable($mark, $run)) {
                    $removed[] = $block;
                } else {
                    $kept .= $block;
                }
            }
            //POSCONDICIÓN, aparte del filtro: si un solo bloque que se iba a quitar no lleva la marca de una corrida podable,
            //no se escribe nada en este archivo (#1603).
            $unmarked = 0;
            foreach ($removed as $block) {
                $mark = self::blockMark($block, $plain);
                if ($mark === null || !str_contains($block, " [test {$mark}]") || !self::isPrunable($mark, $run)) {
                    $unmarked++;
                }
            }
            if ($unmarked > 0) {
                return [0, "poda de {$file} abortada: {$unmarked} bloques sin marca"];
            }
            if ($removed === []) {
                return [0, null];
            }
            $temporary = $file . '.' . bin2hex(random_bytes(6)) . '.tmp';
            if (@file_put_contents($temporary, $kept) !== strlen($kept) || !@rename($temporary, $file)) {
                //RETORNO-IGNORADO: si el temporal no se escribió entero, no queda nada que conservar.
                @unlink($temporary);
                return [0, 'no se pudo reescribir ' . $file . ': no se podó nada'];
            }
            //RETORNO-IGNORADO: el permiso deja escribir al otro usuario del sistema; el archivo sirve igual.
            @chmod($file, 0664);
            return [count($removed), null];
        } finally {
            self::release($handle);
        }
    }

    /**
     * La marca de un bloque de un registro plano, o null. En el plano va justo tras "[ref X]" en la cabecera; en el único,
     * al final de la primera línea de la firma, seguida de las cuatro líneas de traza que cierran el bloque: ningún
     * mensaje puede ponerla ahí.
     *
     * @param string $block
     * @param bool $plain
     * @return string|null
     */
    private static function blockMark(string $block, bool $plain): ?string
    {
        $pattern = $plain
            ? '/^\[[^\]\n]*\] \[[^\]\n]*\] \[ref [^\]\n]*\] \[test (t[0-9]+-[0-9a-f]{12})\] /'
            : '/ \[test (t[0-9]+-[0-9a-f]{12})\]\n(?: {5}in [^\n]*\n){4}$/';
        return preg_match($pattern, $block, $matched) === 1 ? $matched[1] : null;
    }

    /**
     * Cuántas de esas líneas NO llevan la marca de una corrida que se puede podar.
     *
     * @param string[] $lines
     * @param string $run
     * @return int
     */
    private static function unmarkedAmong(array $lines, string $run): int
    {
        $unmarked = 0;
        foreach ($lines as $line) {
            $entry = json_decode($line, true);
            $context = is_array($entry) ? ($entry['context'] ?? null) : null;
            $mark = is_array($context) ? ($context['test'] ?? null) : null;
            if (!is_string($mark) || preg_match(self::TEST_RUN_PATTERN, $mark) !== 1 || !self::isPrunable($mark, $run)) {
                $unmarked++;
            }
        }
        return $unmarked;
    }

    /**
     * La de esta corrida, o la de una corrida cuyo proceso ya no vive.
     *
     * @param string $mark
     * @param string $run
     * @return bool
     */
    private static function isPrunable(string $mark, string $run): bool
    {
        if ($mark === $run) {
            return true;
        }
        if (preg_match(self::TEST_RUN_PATTERN, $mark, $matched) !== 1) {
            return false;
        }
        $pid = (int) $matched[1];
        $alive = function_exists('posix_kill') ? (posix_kill($pid, 0) || posix_get_last_error() === 1) : is_dir('/proc/' . $pid);
        return !$alive;
    }

    /**
     * Los marcos sin argumentos: pesan, repiten y pueden llevar datos personales.
     *
     * @param array<int,array<string,mixed>> $trace
     * @return array<int,array{file:string,line:int|string,function:string,class?:string}>
     */
    public static function cleanTrace(array $trace): array
    {
        $clean = [];
        foreach ($trace as $frame) {
            $cleanFrame = [
                'file' => isset($frame['file']) && is_string($frame['file']) ? $frame['file'] : 'unknown',
                'line' => isset($frame['line']) && is_int($frame['line']) ? $frame['line'] : '?',
                'function' => isset($frame['function']) && is_string($frame['function']) ? $frame['function'] : 'unknown',
            ];
            if (isset($frame['class']) && is_string($frame['class'])) {
                $cleanFrame['class'] = $frame['class'];
            }
            $clean[] = $cleanFrame;
        }
        return $clean;
    }

    /**
     * @param string $type
     * @param string $file
     * @param int|string $line
     * @param array<mixed> $frames Los de cleanTrace()
     * @return string sha256
     */
    public static function traceFingerprint(string $type, string $file, int|string $line, array $frames): string
    {
        return hash('sha256', (string) json_encode([$type, $file, (string) $line, $frames], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR));
    }

    /**
     * Repeticiones y última aparición de un suave, por su softKey (AAAAMMDD/firma).
     *
     * @param string $directory
     * @param string $softKey
     * @return array{count:int,lastAt:int}|null
     */
    public static function softCounter(string $directory, string $softKey): ?array
    {
        if (preg_match('/^[0-9]{8}\/[0-9a-f]{64}$/', $softKey) !== 1) {
            return null;
        }
        $path = rtrim($directory, '/\\') . '/' . self::DIRECTORY_SOFT . '/' . $softKey;
        clearstatcache(true, $path);
        $size = @filesize($path);
        $lastAt = @filemtime($path);
        if ($size === false || $lastAt === false) {
            return null;
        }
        return ['count' => max(1, $size - self::SOFT_COUNTER_HEADER), 'lastAt' => $lastAt];
    }

    /**
     * Borra los contadores de suaves de los días de más de SOFT_RETENTION_DAYS y las trazas no tocadas en más de
     * TRACE_RETENTION_DAYS. Solo dentro de esas dos carpetas y solo lo que tiene su forma (un día de 8 cifras; una
     * huella sha256 con .json): nada más. Una entrada cuya traza se purgó se sigue abriendo, sin la traza.
     *
     * @param \DateTime|null $now
     * @param string|null $directory Por omisión, LOG_ERRORS_PATH; las pruebas dan un temporal
     * @return array{soft: int, traces: int} Contadores y trazas borrados
     */
    public static function pruneStorage(?\DateTime $now = null, ?string $directory = null): array
    {
        $now = $now !== null ? clone $now : new \DateTime();
        if ($directory === null) {
            $directory = defined('LOG_ERRORS_PATH') ? (string) LOG_ERRORS_PATH : (string) realpath(__DIR__ . '/../../../../../logs');
        }
        $directory = rtrim($directory, '/\\');
        $removed = ['soft' => 0, 'traces' => 0];

        $oldestDay = (clone $now)->modify('-' . self::SOFT_RETENTION_DAYS . ' days')->format('Ymd');
        foreach (glob($directory . '/' . self::DIRECTORY_SOFT . '/*', \GLOB_ONLYDIR) ?: [] as $day) {
            $name = basename($day);
            if (preg_match('/^[0-9]{8}$/', $name) !== 1 || $name >= $oldestDay) {
                continue;
            }
            foreach (glob($day . '/*') ?: [] as $counter) {
                $removed['soft'] += is_file($counter) && @unlink($counter) ? 1 : 0;
            }
            //RETORNO-IGNORADO: un día que no se vacía se queda; la tarea de mañana lo intenta otra vez.
            @rmdir($day);
        }

        $oldestTrace = (clone $now)->modify('-' . self::TRACE_RETENTION_DAYS . ' days')->getTimestamp();
        foreach (glob($directory . '/' . self::DIRECTORY_TRACES . '/*.json') ?: [] as $trace) {
            if (preg_match('/^[0-9a-f]{64}\.json$/', basename($trace)) !== 1) {
                continue;
            }
            $modified = @filemtime($trace);
            if ($modified !== false && $modified < $oldestTrace) {
                $removed['traces'] += @unlink($trace) ? 1 : 0;
            }
        }
        return $removed;
    }

    /**
     * Vacía el registro nuevo: las entradas, las trazas y los contadores. Lo usa clean-logs, que también vacía olds/
     * (sin olds/ ninguna traza tiene ya quien la cite).
     *
     * @param string $directory
     * @return int Archivos de traza y de contador retirados
     */
    public static function clearStorage(string $directory): int
    {
        $directory = rtrim($directory, '/\\');
        $removed = 0;
        if (is_file($directory . '/' . self::FILE_ENTRIES)) {
            //RETORNO-IGNORADO: vaciar es lo que pide clean-logs; si falla, el archivo sigue legible.
            @file_put_contents($directory . '/' . self::FILE_ENTRIES, '', LOCK_EX);
        }
        foreach (glob($directory . '/' . self::DIRECTORY_TRACES . '/*.json') ?: [] as $trace) {
            $removed += @unlink($trace) ? 1 : 0;
        }
        foreach (glob($directory . '/' . self::DIRECTORY_SOFT . '/*', \GLOB_ONLYDIR) ?: [] as $day) {
            foreach (glob($day . '/*') ?: [] as $counter) {
                $removed += @unlink($counter) ? 1 : 0;
            }
            //RETORNO-IGNORADO: un día que no se vacía se queda; el siguiente clean-logs lo intenta otra vez.
            @rmdir($day);
        }
        return $removed;
    }

    /**
     * El código de referencia de este error: va al log y es lo único que ve el usuario fuera de local.
     *
     * @return string
     */
    public function reference(): string
    {
        return $this->reference;
    }

    /**
     * @return Throwable
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Anota un error grave: siempre crea su entrada, porque su referencia la tiene un usuario.
     *
     * @param bool $plainLog
     * @return void
     */
    public function logging(bool $plainLog = true): void
    {
        $this->appendEntry($this->buildEntry());
        if ($plainLog) {
            $this->appendPlain();
        }
    }

    /**
     * Registra un mensaje de error único.
     *
     * Si el mensaje completo (incluyendo cabecera y traza de 4 niveles) ya existe en el log, no se vuelve a añadir.
     * La firma de unicidad se basa en las 5 líneas del reporte (sin el timestamp).
     *
     * @return void
     */
    public function loggingUniqueMessage(): void
    {
        $classException = $this->exception::class;
        $message = $this->exception->getMessage();
        $file = $this->exception->getFile();
        $line = $this->exception->getLine();

        // Código de excepción
        $codeException = '-';
        try {
            $codeException = $this->exception->getCode();
        } catch (\Throwable) {}

        //La marca va DENTRO de la firma, al final de la cabecera (ADR 0063 §4): delante, la firma real sería subcadena de la marcada.
        $run = self::testRun();

        // Construir la firma de 5 líneas (sin el timestamp inicial)
        // Línea 1: Cabecera
        $headerSignature = sprintf("[%s] [%s] %s in %s:%s%s\n", $classException, $codeException, $message, $file, $line, $run !== null ? " [test {$run}]" : '');

        // Líneas 2 a 5: Traza
        $traceLines = "";
        $trace = $this->exception->getTrace();
        for ($i = 0; $i < 4; $i++) {
            if (isset($trace[$i])) {
                $traceFile = $trace[$i]['file'] ?? 'unknown';
                $traceLine = $trace[$i]['line'] ?? '?';
                $traceLines .= sprintf("     in %s:%s\n", $traceFile, $traceLine);
            } else {
                $traceLines .= "     in unknown:?\n";
            }
        }

        $fullSignature = $headerSignature . $traceLines;

        // Si no existe el flujo exacto, lo añadimos con el timestamp y la referencia de esta, la PRIMERA aparición.
        // Van delante de la firma: la búsqueda de duplicados mira solo la firma, y así no cambia.
        $logEntry = sprintf("[%s] [ref %s] %s", $this->date->format('Y-m-d H:i:s.u'), $this->reference, $fullSignature);
        //RETORNO-IGNORADO: el registro único es un resumen; la entrada completa ya está en el principal.
        self::appendLocked($this->fileLocationUniqueMessage, $logEntry, fn(string $content): bool => str_contains($content, $fullSignature));
    }

    /**
     * @return string
     */
    private function recordSoft(): string
    {
        $type = $this->typeName ?? $this->exception::class;
        //La marca de la prueba entra en la firma: podar el suave de una prueba no se lleva el contador de uno real (ADR 0063 §4).
        $run = self::testRun();
        $signature = hash('sha256', $type . '|' . $this->exception->getFile() . '|' . $this->exception->getLine() . ($run !== null ? '|' . $run : ''));
        $softKey = $this->date->format('Ymd') . '/' . $signature;
        $dayDirectory = $this->directory . '/' . self::DIRECTORY_SOFT . '/' . $this->date->format('Ymd');
        self::ensureDirectory($dayDirectory);
        $counter = $this->directory . '/' . self::DIRECTORY_SOFT . '/' . $softKey;

        if (self::createOnce($counter, $this->reference . "\n.")) {
            $entry = $this->buildEntry();
            $entry['softKey'] = $softKey;
            $this->appendEntry($entry);
            $this->appendPlain();
            return $this->reference;
        }

        if (!is_file($counter)) {
            //No se pudo crear el contador: la entrada se anota igual, sin agrupar.
            $this->appendEntry($this->buildEntry());
            return $this->reference;
        }

        //RETORNO-IGNORADO: una repetición que no se cuenta no pierde nada más; la entrada ya existe.
        @file_put_contents($counter, '.', FILE_APPEND | LOCK_EX);
        $head = @file_get_contents($counter, false, null, 0, self::SOFT_COUNTER_HEADER - 1);
        if (is_string($head) && preg_match('/^ERR-[0-9]{8}-[0-9A-F]{6}$/', $head) === 1) {
            $this->reference = $head;
        }
        return $this->reference;
    }

    /**
     * @return array<string,mixed>
     */
    private function buildEntry(): array
    {
        $type = $this->typeName ?? $this->exception::class;

        $codeException = '-';
        try {
            $codeException = $this->exception->getCode();
        } catch (\Throwable) {}

        $previous = [];
        $current = $this->exception->getPrevious();
        while ($current !== null && count($previous) < self::MAX_PREVIOUS) {
            $previous[] = [
                'type' => $current::class,
                'message' => $current->getMessage(),
                'code' => $current->getCode(),
                'file' => $current->getFile(),
                'line' => $current->getLine(),
                'traceFingerprint' => $this->storeTrace($current::class, $current->getFile(), $current->getLine(), $current->getTrace()),
            ];
            $current = $current->getPrevious();
        }

        $extraData = $this->softContext;
        if ($extraData === [] && method_exists($this->exception, 'extraData')) {
            $extraData = call_user_func([$this->exception, 'extraData']);
        }

        $context = self::requestContext();
        $run = self::testRun();
        if ($run !== null) {
            $context['test'] = $run;
        }

        return [
            'day' => $this->date->format('d-m-Y'),
            'time' => $this->date->format('H:i:s.u'),
            'reference' => $this->reference,
            'level' => $this->level,
            'type' => $type,
            'message' => $this->exception->getMessage(),
            'code' => $codeException,
            'file' => $this->exception->getFile(),
            'line' => $this->exception->getLine(),
            'extraData' => $extraData,
            'traceFingerprint' => $this->storeTrace($type, $this->exception->getFile(), $this->exception->getLine(), $this->exception->getTrace()),
            'previous' => $previous,
            'context' => $context,
        ];
    }

    /**
     * Guarda la traza por su huella, una sola vez, y devuelve la huella.
     *
     * @param string $type
     * @param string $file
     * @param int $line
     * @param array<int,array<string,mixed>> $trace
     * @return string
     */
    private function storeTrace(string $type, string $file, int $line, array $trace): string
    {
        $frames = self::cleanTrace($trace);
        $fingerprint = self::traceFingerprint($type, $file, $line, $frames);
        $directory = $this->directory . '/' . self::DIRECTORY_TRACES;
        $path = $directory . '/' . $fingerprint . '.json';
        if (!is_file($path)) {
            self::ensureDirectory($directory);
            $encoded = json_encode($frames, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR);
            if (is_string($encoded)) {
                self::createOnce($path, $encoded);
            }
        }
        return $fingerprint;
    }

    /**
     * Añade una línea al final con LOCK_EX, rotando antes si el archivo pasa del límite. Si otro proceso lo rotó
     * mientras se esperaba el cerrojo, se vuelve a abrir el nuevo.
     *
     * @param array<string,mixed> $entry
     * @return bool
     */
    private function appendEntry(array $entry): bool
    {
        $flags = \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_INVALID_UTF8_SUBSTITUTE | \JSON_PARTIAL_OUTPUT_ON_ERROR;
        $line = json_encode($entry, $flags);
        if (!is_string($line)) {
            $entry['extraData'] = '(no serializable)';
            $line = json_encode($entry, $flags);
        }
        if (!is_string($line)) {
            return false;
        }
        $line .= "\n";
        $maxBytes = $this->maxSizeMB * 1024 * 1024;

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $handle = @fopen($this->fileLocation, 'ab');
            if ($handle === false) {
                return false;
            }
            if (!flock($handle, LOCK_EX)) {
                self::release($handle);
                return false;
            }
            clearstatcache(true, $this->fileLocation);
            $onPath = @stat($this->fileLocation);
            $opened = fstat($handle);
            if ($onPath === false || $opened === false || $onPath['ino'] !== $opened['ino']) {
                self::release($handle);
                continue;
            }
            if ($opened['size'] > $maxBytes) {
                $rotated = str_replace('{{DATE}}', $this->date->format('Y-m-d H-i-s.u'), $this->oldFileLocation);
                //RETORNO-IGNORADO: si no rota, la vuelta siguiente lo intenta y al final se escribe en el mismo.
                @rename($this->fileLocation, $rotated);
                self::release($handle);
                $maxBytes = PHP_INT_MAX;
                continue;
            }
            $written = fwrite($handle, $line);
            $flushed = fflush($handle);
            self::release($handle);
            //RETORNO-IGNORADO: el permiso deja escribir al otro usuario del sistema (web o terminal); la entrada ya está.
            @chmod($this->fileLocation, 0664);
            return $written === strlen($line) && $flushed;
        }
        return false;
    }

    /**
     * @return void
     */
    private function appendPlain(): void
    {
        $classException = $this->typeName ?? $this->exception::class;
        $codeException = '-';
        try {
            $codeException = $this->exception->getCode();
        } catch (\Throwable) {}

        $traceSummary = [];
        foreach ($this->exception->getTrace() as $i => $frame) {
            if ($i > 2) {
                break;
            }
            $file = $frame['file'] ?? 'unknown';
            $line = $frame['line'] ?? '?';
            $function = $frame['function'];
            $traceSummary[] = "{$file}:{$line} ({$function})";
        }
        $traceString = count($traceSummary) > 0 ? " | Trace: " . implode(" <- ", $traceSummary) : "";

        $run = self::testRun();
        $plainLogEntry = sprintf(
            "[%s] [%s] [ref %s]%s [%s] %s in %s:%s%s\n",
            $this->date->format('Y-m-d H:i:s.u'),
            $classException,
            $this->reference,
            $run !== null ? " [test {$run}]" : '',
            $codeException,
            $this->exception->getMessage(),
            $this->exception->getFile(),
            $this->exception->getLine(),
            $traceString
        );

        $plainLogExists = file_exists($this->fileLocationPlain);
        $plainLogSizeMB = $plainLogExists ? filesize($this->fileLocationPlain) / 1024 / 1024 : 0;

        if ($plainLogSizeMB > $this->maxSizeMB) {
            $file_old_output_plain = str_replace(
                '{{DATE}}',
                $this->date->format('d-m-Y h-i-s.u'),
                $this->oldFileLocationPlain
            );
            rename($this->fileLocationPlain, $file_old_output_plain);
            @chmod($file_old_output_plain, 0664);
        }

        //RETORNO-IGNORADO: el plano es una copia legible; la entrada completa ya está en el principal.
        self::appendLocked($this->fileLocationPlain, $plainLogEntry);
    }

    /**
     * Añade con el cerrojo de la poda: si mientras se esperaba el archivo se reescribió (otro inodo), se vuelve a abrir,
     * como appendEntry(); si no, lo escrito iría al archivo viejo y se perdería.
     *
     * @param string $file
     * @param string $text
     * @param (callable(string):bool)|null $skip Con el contenido actual, bajo el cerrojo: true si no hay que escribir
     * @return bool
     */
    private static function appendLocked(string $file, string $text, ?callable $skip = null): bool
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $handle = @fopen($file, 'ab');
            if ($handle === false) {
                return false;
            }
            if (!flock($handle, LOCK_EX)) {
                self::release($handle);
                return false;
            }
            clearstatcache(true, $file);
            $onPath = @stat($file);
            $opened = fstat($handle);
            if ($onPath === false || $opened === false || $onPath['ino'] !== $opened['ino']) {
                self::release($handle);
                continue;
            }
            if ($skip !== null && $skip((string) @file_get_contents($file))) {
                self::release($handle);
                return true;
            }
            $written = fwrite($handle, $text);
            $flushed = fflush($handle);
            self::release($handle);
            //RETORNO-IGNORADO: el permiso deja escribir al otro usuario del sistema (web o terminal); la línea ya está.
            @chmod($file, 0664);
            return $written === strlen($text) && $flushed;
        }
        return false;
    }

    /**
     * Crea el archivo con ese contenido solo si no existe, de forma atómica: se escribe aparte y se enlaza, y link()
     * falla si el destino ya está. Nadie lee nunca un archivo a medio escribir.
     *
     * @param string $path
     * @param string $content
     * @return bool true si lo creó esta llamada
     */
    private static function createOnce(string $path, string $content): bool
    {
        if (is_file($path)) {
            return false;
        }
        $temporary = $path . '.' . bin2hex(random_bytes(6)) . '.tmp';
        if (@file_put_contents($temporary, $content) === false) {
            return false;
        }
        //RETORNO-IGNORADO: el permiso deja escribir al otro usuario del sistema; sin él, el archivo sirve igual.
        @chmod($temporary, 0664);
        $created = @link($temporary, $path);
        //RETORNO-IGNORADO: un temporal que no se borra no estorba; clean-logs vacía la carpeta.
        @unlink($temporary);
        return $created;
    }

    /**
     * Suelta el cerrojo y cierra.
     *
     * @param resource $handle
     * @return void
     */
    private static function release($handle): void
    {
        //RETORNO-IGNORADO: lo escrito ya se midió (o no se escribió nada); soltar y cerrar no lo cambia.
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    /**
     * @param string $directory
     * @return void
     */
    private static function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            //RETORNO-IGNORADO: otro proceso puede crearlo a la vez; lo que importa es que exista después.
            @mkdir($directory, 0775, true);
        }
    }
}
