<?php

/**
 * ErrorLogReader.php
 */

namespace PiecesPHP\Logs;

use PiecesPHP\Core\CustomErrorsHandlers\ErrorSeverity;
use PiecesPHP\Core\CustomErrorsHandlers\GenericHandler;
use PiecesPHP\Core\Logs\ExpiredSessionsLog;

/**
 * ErrorLogReader - Lee el registro de errores que escribe GenericHandler, en sus dos formatos: el actual
 * (`error.log.jsonl`, una entrada por línea y la traza por su huella en `error-traces/`) y el anterior
 * (`error.log.json`, el JSON entero por días), con los rotados de olds/ de los dos (ADR 0060 §6).
 *
 * Los rotados suman decenas de megas: se leen de uno en uno, del más nuevo al más viejo, y se para en cuanto la página
 * está llena. Por eso no hay total, solo «hay más».
 *
 * @package     PiecesPHP\Logs
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
final class ErrorLogReader
{
    const PAGE_SIZE = 50;

    /**
     * La forma de GenericHandler::__construct(): ERR-AAAAMMDD-XXXXXX.
     */
    const REFERENCE_PATTERN = '/^ERR-[0-9]{8}-[0-9A-F]{6}$/';

    /**
     * Los demás archivos que se pueden ver, por su clave en ?file=. Nada más de logs/.
     */
    const PLAIN_FILES = [
        'deprecations' => 'deprecations.log',
        'expired-sessions' => ExpiredSessionsLog::FILE_NAME,
    ];

    const PLAIN_LINES = 500;

    /**
     * Lo que se lee del final de un archivo de texto para sacar sus últimas líneas.
     */
    const PLAIN_TAIL_BYTES = 1048576;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $backupDirectory;

    /**
     * @var array<string,array<mixed>> Trazas ya leídas en esta petición, por huella
     */
    private $traces = [];

    /**
     * @param string|null $directory Por omisión, LOG_ERRORS_PATH
     * @param string|null $backupDirectory Por omisión, LOG_ERRORS_BACKUP_PATH
     */
    public function __construct(?string $directory = null, ?string $backupDirectory = null)
    {
        $this->directory = rtrim($directory ?? (string) constant('LOG_ERRORS_PATH'), '/\\');
        $this->backupDirectory = rtrim($backupDirectory ?? (string) constant('LOG_ERRORS_BACKUP_PATH'), '/\\');
    }

    /**
     * Los dos niveles (ADR 0060). Una entrada del formato anterior sin nivel es grave: entonces todo abortaba.
     */
    const LEVELS = [ErrorSeverity::LEVEL_GRAVE, ErrorSeverity::LEVEL_SOFT];

    /**
     * El actual y los rotados, de los dos formatos, del más reciente al más viejo. Por fecha de modificación: el
     * nombre de los rotados viejos lleva la hora en formato de 12 horas y no ordena.
     *
     * @return string[]
     */
    public function files(): array
    {
        $files = [];
        foreach ([$this->directory . '/' . GenericHandler::FILE_ENTRIES, $this->directory . '/error.log.json'] as $current) {
            if (is_file($current)) {
                $files[] = $current;
            }
        }
        foreach (['/error.log.*.jsonl', '/error.log.*.json'] as $pattern) {
            $olds = glob($this->backupDirectory . $pattern);
            $files = array_merge($files, is_array($olds) ? array_values(array_filter($olds, 'is_file')) : []);
        }
        usort($files, fn(string $a, string $b): int => (int) filemtime($b) <=> (int) filemtime($a));
        return $files;
    }

    /**
     * Una página de errores, del más reciente al más viejo.
     *
     * @param array{text?:string,type?:string,day?:string,level?:string} $filters `day` en DD-MM-AAAA o AAAA-MM-DD;
     * `type`, exacto; `level`, uno de LEVELS
     * @param int $page Desde 1
     * @return array{entries:array<int,array{day:string,entry:array<string,mixed>}>,page:int,hasMore:bool}
     */
    public function page(array $filters, int $page): array
    {
        $page = max(1, $page);
        $skip = ($page - 1) * self::PAGE_SIZE;
        $wanted = self::PAGE_SIZE + 1;
        $text = mb_strtolower(trim((string) ($filters['text'] ?? '')));
        $type = trim((string) ($filters['type'] ?? ''));
        $level = in_array($filters['level'] ?? '', self::LEVELS, true) ? (string) $filters['level'] : '';
        $day = self::dayKey((string) ($filters['day'] ?? ''));

        $found = [];
        foreach ($this->files() as $file) {
            foreach ($this->decode($file) as $logDay => $entries) {
                if ($day !== null && $logDay !== $day) {
                    continue;
                }
                foreach ($entries as $entry) {
                    if ($text !== '' && !str_contains(mb_strtolower($entry['message'] . ' ' . $entry['file']), $text)) {
                        continue;
                    }
                    //Exacto: sale de la lista de types(), no se teclea.
                    if ($type !== '' && $entry['type'] !== $type) {
                        continue;
                    }
                    if ($level !== '' && $entry['level'] !== $level) {
                        continue;
                    }
                    if ($skip > 0) {
                        $skip--;
                        continue;
                    }
                    $found[] = ['day' => $logDay, 'entry' => $this->withTraces($entry)];
                    if (count($found) >= $wanted) {
                        break 3;
                    }
                }
            }
        }

        return [
            'entries' => array_slice($found, 0, self::PAGE_SIZE),
            'page' => $page,
            'hasMore' => count($found) > self::PAGE_SIZE,
        ];
    }

    /**
     * La entrada de esa referencia, en el actual o en un rotado, con su traza y las de sus previas reconstruidas.
     *
     * @param string $reference
     * @return array{day:string,entry:array<string,mixed>}|null null si no existe o no tiene la forma de una referencia
     */
    public function findByReference(string $reference): ?array
    {
        //Se pega a mano y a veces en minúsculas; la referencia se escribe en mayúsculas (GenericHandler).
        $reference = strtoupper(trim($reference));
        if (preg_match(self::REFERENCE_PATTERN, $reference) !== 1) {
            return null;
        }
        foreach ($this->files() as $file) {
            //Antes de decodificar: casi ningún archivo la lleva, y decodificar es lo caro.
            $content = file_get_contents($file);
            if ($content === false || !str_contains($content, $reference)) {
                continue;
            }
            foreach ($this->decodeContent($content, self::isLines($file)) as $logDay => $entries) {
                foreach ($entries as $entry) {
                    if ($entry['reference'] === $reference) {
                        return ['day' => $logDay, 'entry' => $this->withTraces($entry)];
                    }
                }
            }
        }
        return null;
    }

    /**
     * Cuántas entradas hay posteriores a ese instante, contando como posterior un suave que se repitió después. Solo
     * lee los archivos modificados desde un día antes: un suave solo se agrupa dentro de su día.
     *
     * @param int $since Marca de tiempo Unix
     * @return array{count:int,newest:int} newest: la marca de la más reciente, 0 si no hay ninguna
     */
    public function newerThan(int $since): array
    {
        $count = 0;
        $newest = 0;
        foreach ($this->files() as $file) {
            if ((int) filemtime($file) < $since - 86400) {
                break;
            }
            foreach ($this->decode($file) as $entries) {
                foreach ($entries as $entry) {
                    $at = max($entry['at'], $entry['lastAt']);
                    if ($at > $since) {
                        $count++;
                        $newest = max($newest, $at);
                    }
                }
            }
        }
        return ['count' => $count, 'newest' => $newest];
    }

    /**
     * Las últimas PLAIN_LINES líneas de uno de PLAIN_FILES.
     *
     * @param string $key
     * @return string|null null si la clave no es de PLAIN_FILES; '' si el archivo no existe o está vacío
     */
    public function tail(string $key): ?string
    {
        if (!array_key_exists($key, self::PLAIN_FILES)) {
            return null;
        }
        $file = $this->directory . '/' . self::PLAIN_FILES[$key];
        $size = is_file($file) ? filesize($file) : false;
        if ($size === false || $size === 0) {
            return '';
        }
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            return '';
        }
        $offset = max(0, $size - self::PLAIN_TAIL_BYTES);
        $chunk = fseek($handle, $offset) === 0 ? stream_get_contents($handle) : false;
        //RETORNO-IGNORADO: solo se leyó; un cierre fallido no cambia lo leído.
        fclose($handle);
        if (!is_string($chunk)) {
            return '';
        }
        $lines = preg_split('/\R/', rtrim($chunk, "\r\n"));
        $lines = is_array($lines) ? $lines : [];
        //Si se leyó desde la mitad, la primera línea está cortada.
        if ($offset > 0) {
            array_shift($lines);
        }
        return implode("\n", array_slice($lines, -self::PLAIN_LINES));
    }

    /**
     * Un día, como lo da el calendario del panel (DD-MM-AAAA) o en AAAA-MM-DD, a la clave de día del registro (d-m-Y).
     *
     * @param string $day
     * @return string|null
     */
    public static function dayKey(string $day): ?string
    {
        foreach (['d-m-Y', 'Y-m-d'] as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $day);
            if ($date !== false && $date->format($format) === $day) {
                return $date->format('d-m-Y');
            }
        }
        return null;
    }

    /**
     * Los tipos de excepción que hay en el registro leído (el actual y los rotados), ordenados: las opciones del filtro.
     *
     * @return string[]
     */
    public function types(): array
    {
        $types = [];
        foreach ($this->files() as $file) {
            foreach ($this->decode($file) as $entries) {
                foreach ($entries as $entry) {
                    if ($entry['type'] !== '') {
                        $types[$entry['type']] = true;
                    }
                }
            }
        }
        $types = array_keys($types);
        sort($types);
        return $types;
    }

    /**
     * La traza guardada con esa huella; [] si no está.
     *
     * @param string $fingerprint
     * @return array<mixed>
     */
    public function trace(string $fingerprint): array
    {
        if (preg_match('/^[0-9a-f]{64}$/', $fingerprint) !== 1) {
            return [];
        }
        if (!array_key_exists($fingerprint, $this->traces)) {
            $content = @file_get_contents($this->directory . '/' . GenericHandler::DIRECTORY_TRACES . '/' . $fingerprint . '.json');
            $frames = is_string($content) ? json_decode($content, true) : null;
            $this->traces[$fingerprint] = is_array($frames) ? $frames : [];
        }
        return $this->traces[$fingerprint];
    }

    /**
     * @param array<string,mixed> $entry
     * @return array<string,mixed>
     */
    private function withTraces(array $entry): array
    {
        //tracePurged: tenía traza (su huella) y ya no está (la retención la borró). Sin huella no hubo traza que purgar.
        if ($entry['traceFingerprint'] !== '') {
            $entry['trace'] = $this->trace($entry['traceFingerprint']);
            $entry['tracePurged'] = $entry['trace'] === [];
        }
        foreach ($entry['previous'] as $index => $previous) {
            $entry['previous'][$index]['trace'] = $this->trace($previous['traceFingerprint']);
            $entry['previous'][$index]['tracePurged'] = $previous['traceFingerprint'] !== '' && $entry['previous'][$index]['trace'] === [];
        }
        return $entry;
    }

    /**
     * @param string $file
     * @return bool
     */
    private static function isLines(string $file): bool
    {
        return str_ends_with($file, '.jsonl');
    }

    /**
     * @param string $file
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function decode(string $file): array
    {
        $content = file_get_contents($file);
        return $content === false ? [] : $this->decodeContent($content, self::isLines($file));
    }

    /**
     * Día => entradas normalizadas, de la más reciente a la más vieja. Lo que no tiene la forma esperada se descarta:
     * una línea a medias no tumba las demás.
     *
     * @param string $content
     * @param bool $lines El formato actual, una entrada por línea
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function decodeContent(string $content, bool $lines): array
    {
        $days = [];
        if ($lines) {
            $rows = preg_split('/\R/', trim($content));
            foreach (array_reverse(is_array($rows) ? $rows : []) as $row) {
                $entry = json_decode($row, true);
                if (is_array($entry) && isset($entry['day']) && is_string($entry['day'])) {
                    $days[$entry['day']][] = $this->normalize($entry, $entry['day']);
                }
            }
            return $days;
        }
        $log = json_decode($content, true);
        if (!is_array($log)) {
            return [];
        }
        foreach ($log as $day => $entries) {
            if (!is_string($day) || !is_array($entries)) {
                continue;
            }
            foreach ($entries as $entry) {
                if (is_array($entry)) {
                    $days[$day][] = $this->normalize($entry, $day);
                }
            }
        }
        return $days;
    }

    /**
     * @param array<mixed> $entry
     * @param string $day d-m-Y
     * @return array<string,mixed>
     */
    private function normalize(array $entry, string $day): array
    {
        $text = fn(array $from, string $key): string => isset($from[$key]) && is_scalar($from[$key]) ? (string) $from[$key] : '';
        $at = \DateTime::createFromFormat('!d-m-Y H:i:s', $day . ' ' . mb_substr($text($entry, 'time'), 0, 8));
        $level = in_array($entry['level'] ?? null, self::LEVELS, true) ? (string) $entry['level'] : ErrorSeverity::LEVEL_GRAVE;
        $softKey = $text($entry, 'softKey');
        $counter = $softKey !== '' ? GenericHandler::softCounter($this->directory, $softKey) : null;
        $previous = [];
        foreach (isset($entry['previous']) && is_array($entry['previous']) ? $entry['previous'] : [] as $item) {
            if (is_array($item)) {
                $previous[] = [
                    'type' => $text($item, 'type'),
                    'message' => $text($item, 'message'),
                    'code' => $text($item, 'code'),
                    'file' => $text($item, 'file'),
                    'line' => $text($item, 'line'),
                    'traceFingerprint' => $text($item, 'traceFingerprint'),
                    'trace' => [],
                    'tracePurged' => false,
                ];
            }
        }
        $context = isset($entry['context']) && is_array($entry['context']) ? $entry['context'] : [];
        return [
            'time' => $text($entry, 'time'),
            'at' => $at !== false ? $at->getTimestamp() : 0,
            'reference' => $text($entry, 'reference'),
            'level' => $level,
            'type' => $text($entry, 'type'),
            'message' => $text($entry, 'message'),
            'code' => $text($entry, 'code'),
            'file' => $text($entry, 'file'),
            'line' => $text($entry, 'line'),
            'extraData' => $entry['extraData'] ?? [],
            //El formato anterior trae la traza dentro; el actual, su huella (withTraces() la resuelve).
            'trace' => isset($entry['trace']) && is_array($entry['trace']) ? $entry['trace'] : [],
            'traceFingerprint' => $text($entry, 'traceFingerprint'),
            'tracePurged' => false,
            'previous' => $previous,
            'context' => [
                'method' => $text($context, 'method'),
                'route' => $text($context, 'route'),
                'url' => $text($context, 'url'),
                'userID' => $text($context, 'userID'),
            ],
            'count' => $counter['count'] ?? 1,
            'lastAt' => $counter['lastAt'] ?? 0,
        ];
    }
}
