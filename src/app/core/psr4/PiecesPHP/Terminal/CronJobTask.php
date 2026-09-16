<?php

/**
 * CronJobTask.php
 */

namespace PiecesPHP\Terminal;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use Exception;
use PiecesPHP\Core\StringManipulate;

/**
 * CronJobTask
 *
 * Wrapper para representar una tarea que será ejecutada a través de un CronJob.
 * Permite definir una condición de ejecución, la función encargada de ejecutar
 * el proceso y retornar una respuesta estándar comprensiva.
 *
 * Con un método de programación (onMinute, hourly, dailyAt, weeklyOn) la tarea tiene FRANJA: la
 * última hora programada que ya pasó. run() la ejecuta una vez por franja, la reintenta si falla
 * (maxAttempts, por defecto 3) y la recupera si el cron llega tarde, dentro de la ventana
 * (recoveryWindow, por defecto 60 minutos). Solo la última franja: nunca se encadenan atrasadas.
 * El estado de cada tarea se guarda en app/cache/cronjobs/<slug>.json y un .lock evita dos
 * ejecuciones a la vez. Sin método de programación (solo la condición o when()), la tarea se
 * comporta como siempre: corre cada vez que su condición da true, sin franja, estado ni reintentos.
 *
 * @package     PiecesPHP\Terminal
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class CronJobTask
{

    const STATUS_DUE = 'due';
    const STATUS_EXECUTED = 'executed';
    const STATUS_FAILED = 'failed';
    const STATUS_LOCKED = 'locked';
    const STATUS_ALREADY_DONE = 'already-done';
    const STATUS_OUTSIDE_WINDOW = 'outside-window';
    const STATUS_EXHAUSTED = 'exhausted';
    const STATUS_NOT_DUE = 'not-due';
    const STATUS_NO_STATE = 'no-state';

    const DEFAULT_RECOVERY_WINDOW = 60;
    const DEFAULT_MAX_ATTEMPTS = 3;
    const SLOT_FORMAT = 'Y-m-d H:i';

    protected static string $configKey = 'SystemCronjobs';

    /**
     * @var string Nombre identificador de la tarea para el TasksRuns
     */
    protected string $name;

    /**
     * @var callable Función que se evalúa para saber si se debe ejecutar la tarea
     */
    protected $executionCondition;

    /**
     * @var callable Función que ejecuta la tarea
     */
    protected $taskHandler;

    /**
     * @var DateTime Instancia inyectable para evaluar fechas. Principalmente usada para testing y métodos de encolado.
     */
    protected DateTime $evalDate;

    /**
     * @var (\Closure(DateTimeImmutable): DateTimeImmutable)|null De «ahora» a la última hora programada que ya pasó
     */
    protected ?\Closure $slotResolver = null;

    /**
     * @var array<callable> Las condiciones de when() posteriores al método de programación
     */
    protected array $extraConditions = [];

    protected int $recoveryWindowMinutes = self::DEFAULT_RECOVERY_WINDOW;

    protected int $maxAttemptsPerSlot = self::DEFAULT_MAX_ATTEMPTS;

    /**
     * @var string|null Si es null, app/cache/cronjobs
     */
    protected ?string $stateDirectory = null;

    /**
     * @param string $name
     * @param callable $taskHandler
     * @param callable|null $executionCondition Opcional. Si retorna false, la tarea se omite.
     */
    public function __construct(string $name, callable $taskHandler,  ? callable $executionCondition = null)
    {
        $this->name = $name;
        $this->taskHandler = $taskHandler;
        $this->executionCondition = $executionCondition ?? fn() => true;
        $this->evalDate = new DateTime();
    }

    /**
     * Obtiene el nombre de la tarea.
     *
     * @return string
     */
    public function getName() : string
    {
        return $this->name;
    }

    /**
     * Permite inyectar una fecha específica para la evaluación en lugar de "ahora".
     *
     * @param DateTime $date
     * @return self
     */
    public function setEvalDate(DateTime $date): self
    {
        $this->evalDate = $date;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getEvalDate(): DateTime
    {
        return $this->evalDate;
    }

    // ─── Helpers Estructurales (Fluent Scheduling Interface) ─────────────────────────────────────────────────────────

    /**
     * Retorna una instancia usando fábrica para habilitar el uso fluido desde la creación sin paréntesis sueltos
     */
    public static function make(string $name, callable $taskHandler): self
    {
        return new self($name, $taskHandler);
    }

    /**
     * Ejecuta la tarea siempre a un minuto fijo de cada hora (Ej: al minuto 15 de toda hora).
     * @param int $minute
     * @return self
     */
    public function onMinute(int $minute): self
    {
        $this->executionCondition = function () use ($minute) {
            $currentMinute = (int) $this->getEvalDate()->format('i');
            return $currentMinute === $minute;
        };
        $this->setSlotResolver(function (DateTimeImmutable $now) use ($minute): DateTimeImmutable {
            $slot = $now->setTime((int) $now->format('H'), $minute, 0);
            return $slot > $now ? $slot->sub(new DateInterval('PT1H')) : $slot;
        });
        return $this;
    }

    /**
     * Ejecuta la tarea al inicio de la hora. Equivalente a ->onMinute(0)
     * @return self
     */
    public function hourly(): self
    {
        return $this->onMinute(0);
    }

    /**
     * Ejecuta la tarea todos los días a una hora exacta H:i
     * @param string $time Ej: "03:30" o "15:00"
     * @return self
     */
    public function dailyAt(string $time): self
    {
        $this->executionCondition = function () use ($time) {
            return $this->getEvalDate()->format('H:i') === $time;
        };
        [$hour, $minute] = self::parseTime($time);
        $this->setSlotResolver(function (DateTimeImmutable $now) use ($hour, $minute): DateTimeImmutable {
            $slot = $now->setTime($hour, $minute, 0);
            return $slot > $now ? $slot->sub(new DateInterval('P1D')) : $slot;
        });
        return $this;
    }

    /**
     * Ejecuta la tarea un día específico de la semana a una hora exacta
     * @param int $dayOfWeek 0 (Domingo) al 6 (Sábado)
     * @param string $time Ej: "03:30"
     * @return self
     */
    public function weeklyOn(int $dayOfWeek, string $time = '00:00'): self
    {
        if ($dayOfWeek < 0 || $dayOfWeek > 6) {
            throw new \InvalidArgumentException("weeklyOn: el día debe estar entre 0 (domingo) y 6 (sábado); llegó {$dayOfWeek}.");
        }
        $this->executionCondition = function () use ($dayOfWeek, $time) {
            $isCorrectDay = (int) $this->getEvalDate()->format('w') === $dayOfWeek;
            $isCorrectTime = $this->getEvalDate()->format('H:i') === $time;
            return $isCorrectDay && $isCorrectTime;
        };
        [$hour, $minute] = self::parseTime($time);
        $this->setSlotResolver(function (DateTimeImmutable $now) use ($dayOfWeek, $hour, $minute): DateTimeImmutable {
            $daysBack = (((int) $now->format('w') - $dayOfWeek) % 7 + 7) % 7;
            $slot = $now->setTime($hour, $minute, 0)->sub(new DateInterval("P{$daysBack}D"));
            return $slot > $now ? $slot->sub(new DateInterval('P7D')) : $slot;
        });
        return $this;
    }

    /**
     * Agrega una condición adicional usando un operador lógico AND virtual
     * @param callable $condition Recibe por parámetro la propia instancia de CronJobTask para acceder a evalDate
     * @return self
     */
    public function when(callable $condition): self
    {
        $previousCondition = $this->executionCondition;
        $this->executionCondition = function () use ($previousCondition, $condition) {
            $prevValue = (bool) call_user_func($previousCondition);
            // Pasamos $this a la nueva condición para que puedan usar public methods como getEvalDate() localmente.
            return $prevValue && (bool) call_user_func($condition, $this);
        };
        $this->extraConditions[] = $condition;
        return $this;
    }

    /**
     * Minutos después de la franja en los que todavía se ejecuta (o se reintenta). Por defecto, 60.
     * @param int $minutes
     * @return self
     */
    public function recoveryWindow(int $minutes): self
    {
        $this->recoveryWindowMinutes = max(0, $minutes);
        return $this;
    }

    /**
     * Intentos por franja; al agotarlos, la tarea espera a la franja siguiente. Por defecto, 3.
     * @param int $attempts
     * @return self
     */
    public function maxAttempts(int $attempts): self
    {
        $this->maxAttemptsPerSlot = max(1, $attempts);
        return $this;
    }

    /**
     * Directorio del estado y del bloqueo. Por defecto, app/cache/cronjobs (las pruebas usan uno propio).
     * @param string $directory
     * @return self
     */
    public function setStateDirectory(string $directory): self
    {
        $this->stateDirectory = $directory;
        return $this;
    }

    // ───────────────────────────────────────────────────────────────────────────────────────────────────────────────

    /**
     * Verifica si la tarea debe ejecutarse.
     *
     * @return bool
     */
    public function shouldExecute(): bool
    {
        return (bool) call_user_func($this->executionCondition);
    }

    /**
     * Ejecuta la tarea y devuelve un arreglo comprensivo.
     *
     * @return array
     */
    public function execute(): array
    {
        if (!$this->shouldExecute()) {
            return [
                'success' => false,
                'message' => 'Omitida. No cumple con la condición de ejecución.',
                'skipped' => true,
            ];
        }

        return $this->runHandler();
    }

    /**
     * Si la tarea tiene franja: la definió onMinute, hourly, dailyAt o weeklyOn.
     *
     * @return bool
     */
    public function hasSchedule(): bool
    {
        return $this->slotResolver !== null;
    }

    /**
     * La última hora programada que ya pasó respecto a $now, o null si la tarea no tiene franja.
     *
     * @param DateTime $now
     * @return DateTime|null
     */
    public function lastDueSlot(DateTime $now): ?DateTime
    {
        if ($this->slotResolver === null) {
            return null;
        }
        return DateTime::createFromImmutable(($this->slotResolver)(DateTimeImmutable::createFromMutable($now)));
    }

    /**
     * Si toca ejecutarla ahora.
     *
     * @param DateTime $now
     * @return bool
     */
    public function isDue(DateTime $now): bool
    {
        return $this->dueStatus($now) === self::STATUS_DUE;
    }

    /**
     * STATUS_DUE si toca; si no, el motivo: STATUS_ALREADY_DONE, STATUS_OUTSIDE_WINDOW, STATUS_EXHAUSTED o STATUS_NOT_DUE.
     * No escribe nada.
     *
     * @param DateTime $now
     * @return string
     */
    public function dueStatus(DateTime $now): string
    {
        if ($this->slotResolver === null) {
            return $this->shouldExecute() ? self::STATUS_DUE : self::STATUS_NOT_DUE;
        }
        return $this->slotStatus($now, $this->readState(false));
    }

    /**
     * Ejecuta la tarea si toca, con bloqueo, y guarda su estado. Nunca lanza la excepción de la tarea.
     *
     * @param DateTime $now
     * @return array{status: string, success: bool, message: string, attempt: int, maxAttempts: int, slot: string|null}
     */
    public function run(DateTime $now): array
    {
        if ($this->slotResolver === null) {
            //Sin franja, como siempre: sin estado, sin bloqueo y sin reintentos.
            if (!$this->shouldExecute()) {
                return $this->runResult(self::STATUS_NOT_DUE, false, self::describeStatus(self::STATUS_NOT_DUE), 0, null);
            }
            [$success, $message] = $this->runHandlerSafely();
            if (!$success) {
                self::logLine("cron «{$this->name}»: falló: {$message}");
            }
            return $this->runResult($success ? self::STATUS_EXECUTED : self::STATUS_FAILED, $success, $message, 1, null);
        }

        [$lock, $handle] = $this->acquireLock();
        if ($handle === null) {
            $message = $lock === self::STATUS_LOCKED ? 'En curso: otra ejecución tiene el bloqueo.' : 'Sin directorio de estado escribible: no se ejecuta.';
            if ($lock === self::STATUS_NO_STATE) {
                self::logLine("cron «{$this->name}»: {$message}");
            }
            return $this->runResult($lock, false, $message, 0, null);
        }

        try {
            $state = $this->readState(true);
            $slot = $this->lastDueSlot($now);
            $slotKey = $slot !== null ? $slot->format(self::SLOT_FORMAT) : null;
            $attempt = $slotKey !== null && $state['lastAttemptSlot'] === $slotKey ? $state['attemptsForSlot'] : 0;
            $status = $this->slotStatus($now, $state);
            if ($status !== self::STATUS_DUE || $slotKey === null) {
                return $this->runResult($status, false, self::describeStatus($status), $attempt, $slotKey);
            }

            //EL INTENTO SE ANOTA ANTES DE EJECUTAR: sin estado escrito, una tarea diaria correría cada minuto de la ventana.
            $attempt++;
            $state['lastAttemptSlot'] = $slotKey;
            $state['attemptsForSlot'] = $attempt;
            $state['lastAttemptAt'] = $now->format('Y-m-d H:i:s');
            if (!$this->writeState($state)) {
                self::logLine("cron «{$this->name}»: no se pudo guardar el estado en {$this->getStatePath()}; no se ejecuta.");
                return $this->runResult(self::STATUS_NO_STATE, false, 'No se pudo guardar el estado: no se ejecuta.', $attempt - 1, $slotKey);
            }

            [$success, $message] = $this->runHandlerSafely();
            $state['lastResult'] = $success ? 'success' : 'failed';
            $state['lastError'] = $success ? null : $message;
            if ($success) {
                $state['lastSuccessSlot'] = $slotKey;
            } else {
                self::logLine("cron «{$this->name}»: falló el intento {$attempt} de {$this->maxAttemptsPerSlot} de la franja {$slotKey}: {$message}");
            }
            if (!$this->writeState($state)) {
                self::logLine("cron «{$this->name}»: no se pudo guardar el resultado en {$this->getStatePath()}.");
            }

            return $this->runResult($success ? self::STATUS_EXECUTED : self::STATUS_FAILED, $success, $message, $attempt, $slotKey);
        } finally {
            if (!$this->releaseLock($handle)) {
                self::logLine("cron «{$this->name}»: no se pudo liberar el bloqueo {$this->getLockPath()}.");
            }
        }
    }

    /**
     * El estado guardado de la tarea. Sin archivo, o con uno ilegible, «sin estado». Solo lee.
     *
     * @return array{lastSuccessSlot: string|null, lastAttemptSlot: string|null, attemptsForSlot: int, lastAttemptAt: string|null, lastResult: string|null, lastError: string|null}
     */
    public function getState(): array
    {
        return $this->readState(false);
    }

    /**
     * @return int
     */
    public function getRecoveryWindow(): int
    {
        return $this->recoveryWindowMinutes;
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return $this->maxAttemptsPerSlot;
    }

    /**
     * @return string
     */
    public function getStateDirectory(): string
    {
        return $this->stateDirectory ?? basepath('app/cache/cronjobs');
    }

    /**
     * @return string
     */
    public function getStatePath(): string
    {
        return append_to_path_system($this->getStateDirectory(), $this->getStateSlug() . '.json');
    }

    /**
     * @return string
     */
    public function getLockPath(): string
    {
        return append_to_path_system($this->getStateDirectory(), $this->getStateSlug() . '.lock');
    }

    /**
     * @param \Closure(DateTimeImmutable): DateTimeImmutable $resolver
     * @return void
     */
    protected function setSlotResolver(\Closure $resolver): void
    {
        $this->slotResolver = $resolver;
        //El método de programación sustituye la condición entera, when() anteriores incluidos: igual con la franja.
        $this->extraConditions = [];
    }

    /**
     * @param DateTime $now
     * @param array{lastSuccessSlot: string|null, lastAttemptSlot: string|null, attemptsForSlot: int, lastAttemptAt: string|null, lastResult: string|null, lastError: string|null} $state
     * @return string
     */
    protected function slotStatus(DateTime $now, array $state): string
    {
        $slot = $this->lastDueSlot($now);
        if ($slot === null) {
            return self::STATUS_NOT_DUE;
        }
        $slotKey = $slot->format(self::SLOT_FORMAT);
        //La franja se guarda como 'Y-m-d H:i': comparar el texto ordena igual que la fecha.
        if ($state['lastSuccessSlot'] !== null && strcmp($slotKey, $state['lastSuccessSlot']) <= 0) {
            return self::STATUS_ALREADY_DONE;
        }
        if (intdiv($now->getTimestamp() - $slot->getTimestamp(), 60) > $this->recoveryWindowMinutes) {
            return self::STATUS_OUTSIDE_WINDOW;
        }
        $attempts = $state['lastAttemptSlot'] === $slotKey ? $state['attemptsForSlot'] : 0;
        if ($attempts >= $this->maxAttemptsPerSlot) {
            return self::STATUS_EXHAUSTED;
        }
        //when() recibe la tarea y lee getEvalDate(): que vea el mismo instante que la franja.
        $this->setEvalDate($now);
        foreach ($this->extraConditions as $condition) {
            if (!(bool) call_user_func($condition, $this)) {
                return self::STATUS_NOT_DUE;
            }
        }
        return self::STATUS_DUE;
    }

    /**
     * Llama al manejador y normaliza su respuesta. Captura Exception, como siempre hizo execute().
     *
     * @return array<string,mixed>
     */
    protected function runHandler(): array
    {
        try {
            $result = call_user_func($this->taskHandler);

            // Aseguramos que el resultado contenga las propiedades mínimas requeridas.
            $success = is_array($result) && array_key_exists('success', $result) ? (bool) $result['success'] : true;
            $message = is_array($result) && array_key_exists('message', $result) ? (string) $result['message'] : 'Tarea ejecutada con éxito.';

            $baseResponse = [
                'success' => $success,
                'message' => $message,
                'skipped' => false,
            ];

            return array_merge($baseResponse, is_array($result) ? $result : ['output' => $result]);
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Excepción durante la ejecución: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'skipped' => false,
            ];
        }
    }

    /**
     * El manejador sin que nada escape, ni un Error. El mensaje de un fallo es el de la excepción, sin traza.
     *
     * @return array{0: bool, 1: string}
     */
    protected function runHandlerSafely(): array
    {
        try {
            $result = $this->runHandler();
        } catch (\Throwable $e) {
            return [false, $e->getMessage()];
        }
        $success = (bool) ($result['success'] ?? false);
        $message = $result['error'] ?? $result['message'] ?? '';
        return [$success, is_scalar($message) ? (string) $message : ''];
    }

    /**
     * @param string $status
     * @param bool $success
     * @param string $message
     * @param int $attempt
     * @param string|null $slot
     * @return array{status: string, success: bool, message: string, attempt: int, maxAttempts: int, slot: string|null}
     */
    protected function runResult(string $status, bool $success, string $message, int $attempt, ?string $slot): array
    {
        return [
            'status' => $status,
            'success' => $success,
            'message' => $message,
            'attempt' => $attempt,
            'maxAttempts' => $this->slotResolver !== null ? $this->maxAttemptsPerSlot : 1,
            'slot' => $slot,
        ];
    }

    /**
     * @param bool $logProblems
     * @return array{lastSuccessSlot: string|null, lastAttemptSlot: string|null, attemptsForSlot: int, lastAttemptAt: string|null, lastResult: string|null, lastError: string|null}
     */
    protected function readState(bool $logProblems): array
    {
        $empty = [
            'lastSuccessSlot' => null,
            'lastAttemptSlot' => null,
            'attemptsForSlot' => 0,
            'lastAttemptAt' => null,
            'lastResult' => null,
            'lastError' => null,
        ];
        $path = $this->getStatePath();
        if (!is_file($path)) {
            if ($logProblems) {
                self::logLine("cron «{$this->name}»: sin estado previo en {$path}; se empieza de cero.");
            }
            return $empty;
        }
        $raw = file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data)) {
            if ($logProblems) {
                self::logLine("cron «{$this->name}»: estado ilegible en {$path}; se trata como sin estado.");
            }
            return $empty;
        }
        $text = static fn (string $key): ?string => isset($data[$key]) && is_string($data[$key]) ? $data[$key] : null;
        return [
            'lastSuccessSlot' => $text('lastSuccessSlot'),
            'lastAttemptSlot' => $text('lastAttemptSlot'),
            'attemptsForSlot' => isset($data['attemptsForSlot']) && is_int($data['attemptsForSlot']) ? $data['attemptsForSlot'] : 0,
            'lastAttemptAt' => $text('lastAttemptAt'),
            'lastResult' => $text('lastResult'),
            'lastError' => $text('lastError'),
        ];
    }

    /**
     * Escritura atómica: un temporal y luego rename().
     *
     * @param array<string,mixed> $state
     * @return bool
     */
    protected function writeState(array $state): bool
    {
        if (!$this->ensureStateDirectory()) {
            return false;
        }
        $path = $this->getStatePath();
        $temporary = $path . '.tmp-' . bin2hex(random_bytes(4));
        $json = json_encode($state, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
        if ($json === false || file_put_contents($temporary, $json) === false) {
            return false;
        }
        if (!rename($temporary, $path)) {
            //RETORNO-IGNORADO: el temporal que no se pudo renombrar se retira si se puede; el fallo ya lo devuelve el rename.
            @unlink($temporary);
            return false;
        }
        return true;
    }

    /**
     * @return array{0: string, 1: resource|null} STATUS_DUE y el handle si tomó el bloqueo; si no, STATUS_LOCKED o STATUS_NO_STATE y null
     */
    protected function acquireLock(): array
    {
        if (!$this->ensureStateDirectory()) {
            return [self::STATUS_NO_STATE, null];
        }
        $handle = fopen($this->getLockPath(), 'c');
        if ($handle === false) {
            return [self::STATUS_NO_STATE, null];
        }
        if (!flock($handle, \LOCK_EX | \LOCK_NB)) {
            //RETORNO-IGNORADO: el handle sin bloqueo solo se cierra; si fallara, la tarea se salta igual.
            fclose($handle);
            return [self::STATUS_LOCKED, null];
        }
        return [self::STATUS_DUE, $handle];
    }

    /**
     * @param resource $handle
     * @return bool
     */
    protected function releaseLock($handle): bool
    {
        $released = flock($handle, \LOCK_UN);
        return fclose($handle) && $released;
    }

    /**
     * @return bool
     */
    protected function ensureStateDirectory(): bool
    {
        $directory = $this->getStateDirectory();
        return is_dir($directory) || mkdir($directory, 0775, true) || is_dir($directory);
    }

    /**
     * @return string
     */
    protected function getStateSlug(): string
    {
        $slug = StringManipulate::friendlyURLString($this->name);
        return $slug !== '' ? $slug : md5($this->name);
    }

    /**
     * @param string $status
     * @return string
     */
    protected static function describeStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_ALREADY_DONE => 'La franja ya se ejecutó con éxito.',
            self::STATUS_OUTSIDE_WINDOW => 'Fuera de la ventana de recuperación.',
            self::STATUS_EXHAUSTED => 'Intentos agotados para esta franja.',
            self::STATUS_NOT_DUE => 'No cumple con la condición de ejecución.',
            default => '',
        };
    }

    /**
     * Una línea en el log del framework (el manejador de errores solo registra excepciones).
     *
     * @param string $message
     * @return void
     */
    protected static function logLine(string $message): void
    {
        log_exception(new \RuntimeException($message));
    }

    /**
     * @param string $time "H:i"
     * @return array{0: int, 1: int}
     */
    protected static function parseTime(string $time): array
    {
        $parts = explode(':', $time);
        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
    }

    /**
     * Agrega múltiples tareas programadas.
     * @param array<CronJobTask> $cronJobs
     * @return void
     */
    public static function addCronJobs(array $cronJobs): void
    {
        foreach ($cronJobs as $cronJob) {
            self::addCronJob($cronJob);
        }
    }

    /**
     * Agrega una tarea programada.
     * @param CronJobTask $cronJobTask
     * @return string
     */
    public static function addCronJob(CronJobTask $cronJobTask): string
    {
        $cronJobs = self::getCronJobs();
        $cronJobs[$cronJobTask->getName()] = $cronJobTask;
        set_config(self::$configKey, $cronJobs);
        return $cronJobTask->getName();
    }

    /**
     * Elimina una tarea programada.
     * @param string $name
     * @return bool
     */
    public static function removeCronJob(string $name): bool
    {
        $cronJobs = self::getCronJobs();
        if (array_key_exists($name, $cronJobs)) {
            unset($cronJobs[$name]);
            set_config(self::$configKey, $cronJobs);
            return true;
        }
        return false;
    }

    /**
     * Obtiene todas las tareas programadas.
     * @return array<CronJobTask>
     */
    public static function getCronJobs(): array
    {
        $cronJobs = get_config(self::$configKey);
        return is_array($cronJobs) ? $cronJobs : [];
    }
}
