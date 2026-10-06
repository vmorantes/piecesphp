<?php

/**
 * MailLogMapper.php
 */

namespace PiecesPHP\SystemStatus\Mappers;

use PiecesPHP\Core\AppEnvironment;
use PiecesPHP\Core\BaseEntityMapper;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\Database\ORM\Statements\Critery\WhereItem;
use PiecesPHP\Core\Database\ORM\Statements\WhereSegment;

/**
 * MailLogMapper - Una línea por correo, con su resultado.
 *
 * Existe porque un envío que falla solo dejaba una línea en un registro que nadie lee, y desde que
 * el correo retenido se guarda en el buzón, un `sink` mal puesto en producción **guarda en
 * silencio** en vez de fallar a gritos (ADR 0043 §5, y la consecuencia medida en pendientes 322.11).
 *
 * **Guarda el cuerpo, CIFRADO** con la clave del framework, porque un correo que sale sin guardarlo
 * se pierde para siempre (ADR 0048). **Nunca guarda los adjuntos**: no hay camino para que lleguen.
 *
 * @property int|null $id
 * @property string $sentAt
 * @property string $recipients
 * @property string|null $subject
 * @property string|null $origin
 * @property string $delivery
 * @property string $result
 * @property string|null $reason
 * @property string|null $body Cifrado con BaseHashEncryption; null si no se guardó cuerpo
 *
 * @package     PiecesPHP\SystemStatus
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class MailLogMapper extends BaseEntityMapper
{

    const TABLE_PREFIX = 'pcsphp_';
    const TABLE_NAME = 'mail_log';
    const TABLE = self::TABLE_PREFIX . self::TABLE_NAME;

    /**
     * @var string
     */
    protected $table = self::TABLE;

    /**
     * @var array<string,array<string,mixed>>
     */
    protected $fields = [
        'id' => [
            'type' => 'int',
            'primary_key' => true,
        ],
        'sentAt' => [
            'type' => 'datetime',
            'default' => 'timestamp',
        ],
        'recipients' => [
            'type' => 'text',
        ],
        'subject' => [
            'type' => 'text',
            'null' => true,
        ],
        'origin' => [
            'type' => 'text',
            'null' => true,
        ],
        'delivery' => [
            'type' => 'text',
        ],
        'result' => [
            'type' => 'text',
        ],
        'reason' => [
            'type' => 'text',
            'null' => true,
        ],
        //`longtext` y sin recorte: recortar un cifrado lo destruye.
        'body' => [
            'type' => 'longtext',
            'null' => true,
        ],
    ];

    const LANG_GROUP = 'SystemStatus-lang';

    /**
     * Llegó al destino que tocaba: el SMTP real o el sumidero.
     *
     * @var string
     */
    const RESULT_DELIVERED = 'delivered';

    /**
     * El correo estaba retenido y el sumidero no respondía: está en el buzón en disco.
     *
     * @var string
     */
    const RESULT_OUTBOX = 'outbox';

    /**
     * No llegó a ningún sitio. **Es el caso que nadie veía.**
     *
     * @var string
     */
    const RESULT_FAILED = 'failed';

    /**
     * @var string[]
     */
    const RESULTS = [self::RESULT_DELIVERED, self::RESULT_OUTBOX, self::RESULT_FAILED];

    /**
     * La ventana del aviso de envíos fallidos y de `mail-doctor`, en horas. **Una sola cifra para
     * los dos**: si el aviso mirara una ventana y el diagnóstico otra, el panel podría avisar de
     * algo que `bin/cli mail-doctor` no encuentra, y quien lo comprobara creería que el aviso miente.
     *
     * 24 h, decidido así porque un fallo de esta noche todavía se puede arreglar cuando alguien
     * entra por la mañana, y uno de la semana pasada ya es historia: eso se mira en la pantalla del
     * registro, no en un aviso que no se calla.
     *
     * @var int
     */
    const ALERT_WINDOW_HOURS = 24;

    /**
     * Desde cuántos fallos avisa. **Uno.** No hay un número aceptable de correos que no llegaron a
     * ningún sitio: cada uno es una persona que no recibió su recuperación de contraseña o su
     * código de acceso. Poner el umbral por encima de uno sería decidir a cuántas personas se
     * puede perder sin avisar.
     *
     * @var int
     */
    const ALERT_FAILED_THRESHOLD = 1;

    /**
     * El archivo que un clon tiene que aplicar para tener esta tabla. Es una CONSTANTE y no un
     * texto suelto porque el aviso de «falta la tabla» lo nombra: si el archivo se renombrara y el
     * aviso siguiera diciendo el nombre viejo, el aviso mandaría a aplicar algo que no existe.
     *
     * @var string
     */
    const UPDATE_FILE = 'databases/actualizaciones/2026-10-03-registro-de-correos.sql';

    /**
     * El archivo que añade la columna del cuerpo. Constante por lo mismo que `UPDATE_FILE`: la
     * nombran el aviso y `mail-doctor`, y un nombre escrito dos veces acaba diciendo dos cosas.
     *
     * @var string
     */
    const BODY_UPDATE_FILE = 'databases/actualizaciones/2026-10-05-cuerpo-del-correo.sql';

    /**
     * Lo contado en esta petición, por ventana. Los dos avisos del panel y la página de avisos
     * preguntan lo mismo en la misma petición, y sin esto se pagaría una consulta por pregunta.
     *
     * @var array<int,array{failed:int,outbox:int,delivered:int}>
     */
    protected static $recentMemo = [];

    /**
     * Si la tabla existe, averiguado una sola vez por petición.
     *
     * @var bool|null
     */
    protected static $tableExistsMemo = null;

    /**
     * Si la columna del cuerpo existe, averiguado una sola vez por petición.
     *
     * @var bool|null
     */
    protected static $bodyColumnExistsMemo = null;

    /**
     * La tabla de prueba en uso, o null para la real.
     *
     * @var string|null
     */
    protected static $tableForTesting = null;

    /**
     * Lo que el clon decide guardar del cuerpo. Null = sin transformer: se guarda entero.
     *
     * @var (callable(string):?string)|null
     */
    protected static $bodyTransformer = null;

    /**
     * Cuántas líneas se conservan. El tope es por NÚMERO y no por antigüedad: una ráfaga de miles
     * de correos en un día llenaría la tabla aunque todos fueran de hoy.
     *
     * 20.000, MEDIDO el 2026-10-05 con cinco correos del catálogo (recuperación, solo código, OTP,
     * aprobaciones y tokens): su cuerpo cifrado ocupa de 427 a 1.515 bytes, media 1.216 —se comprime
     * antes de cifrar—. Con el máximo y 500 bytes para el resto de la fila, 20.000 × 2.015 ≈ 40 MB.
     * Un clon que mande cuerpos mucho mayores crece en proporción: su palanca es el transformer, o
     * bajar el tope con `setMaxRows()`.
     *
     * @var int
     */
    const MAX_ROWS = 20000;

    /**
     * El suelo del tope. Por debajo, una ráfaga normal borraría los fallos de hoy antes de que el aviso
     * `mail-con-fallos` los contara: el registro dejaría de responder «¿está saliendo el correo?».
     *
     * @var int
     */
    const MIN_ROWS = 1000;

    /**
     * Para qué es la clave derivada del cuerpo (ADR 0051). No es un secreto: lo secreto es la de la app.
     *
     * @var string
     */
    const BODY_KEY_PURPOSE = 'mail-log-body';

    /**
     * El tope en uso: `MAX_ROWS`, salvo que el clon ponga otro con `setMaxRows()`.
     *
     * @var int
     */
    protected static $maxRows = self::MAX_ROWS;

    /**
     * @param int|null $value
     * @param string $fieldCompare
     */
    public function __construct($id = null, string $field_compare = 'primary_key')
    {
        $this->table = self::tableName();
        parent::__construct($id, $field_compare);
    }

    /**
     * Registra el transformer del clon (ADR 0048 §4), desde `src/app/config/extensions/mail-log.php`.
     *
     * Recibe el cuerpo y devuelve lo que se guarda: **`null` = no se guarda cuerpo**, y la fila se
     * guarda igual; otra cadena = se guarda esa. Con `null` como argumento se retira el transformer.
     * Es un setter y no `set_config()` porque no está medido que la configuración guarde un invocable.
     *
     * @param (callable(string):?string)|null $transformer
     * @return void
     */
    public static function setBodyTransformer(?callable $transformer): void
    {
        self::$bodyTransformer = $transformer;
    }

    /**
     * El tope de líneas del clon, desde `src/app/config/extensions/mail-log.php`. **Por debajo de
     * `MIN_ROWS` se queda en `MIN_ROWS`**, sin lanzar: lanzar ahí tumbaría el arranque por una cifra.
     * Con null vuelve a `MAX_ROWS`. Sin techo: el disco es del clon.
     *
     * @param int|null $rows
     * @return void
     */
    public static function setMaxRows(?int $rows): void
    {
        self::$maxRows = $rows === null ? self::MAX_ROWS : max(self::MIN_ROWS, $rows);
    }

    /**
     * @return int
     */
    public static function maxRows(): int
    {
        return self::$maxRows;
    }

    /**
     * El cuerpo listo para la columna: transformado por el clon y cifrado. **Nunca lanza**: un
     * cuerpo que no se puede preparar se queda fuera y la fila se guarda igual (ADR 0043 §5).
     *
     * @param string|null $body
     * @param string[] $recipients
     * @return string|null
     */
    protected static function bodyToStore(?string $body, array $recipients): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }
        try {
            //Fuera de `local`, con una clave conocida «cifrado» sería mentira: la fila va sin cuerpo (ADR 0052).
            if (static::bodyKey() === null) {
                self::logBodyFailure('la clave de la aplicación es la de relleno (vacía o «TODO…») y esta instalación no se declara `local`: con esa clave el cifrado no protege nada; genere una con `bin/cli generate-app-key`', $recipients);
                return null;
            }
            if (self::$bodyTransformer !== null) {
                $transformado = (self::$bodyTransformer)($body);
                if ($transformado === null || $transformado === '') {
                    return null;
                }
                if (!is_string($transformado)) {
                    self::logBodyFailure('el transformer del clon devolvió ' . get_debug_type($transformado) . ' en vez de una cadena o null', $recipients);
                    return null;
                }
                $body = $transformado;
            }
            $cifrado = static::encryptBody($body);
            //Ida y vuelta: si `openssl_encrypt()` falla devuelve false y el resultado sería solo el
            //IV codificado. Lo que no se descifra igual que el original no se guarda.
            if ($cifrado === '' || static::decryptBody($cifrado) !== $body) {
                self::logBodyFailure('el cifrado no devolvió algo que se pudiera descifrar', $recipients);
                return null;
            }
            return $cifrado;
        } catch (\Throwable $throwable) {
            self::logBodyFailure(get_class($throwable) . ': ' . $throwable->getMessage(), $recipients);
            return null;
        }
    }

    /**
     * Cifra el cuerpo con la clave derivada (ADR 0051), nunca con la cruda: esa descifra también lo que
     * llega de fuera. Aparte para que una prueba pueda hacerlo fallar y comprobar que la fila se guarda.
     *
     * @param string $body
     * @return string
     */
    protected static function encryptBody(string $body): string
    {
        $key = static::bodyKey();
        if ($key === null) {
            throw new \RuntimeException('Sin clave para cifrar el cuerpo: la de la aplicación es la de relleno.');
        }
        return (string) BaseHashEncryption::encryptBidirectionalHash($body, $key);
    }

    /**
     * El cuerpo en claro, o null si no se puede descifrar: otra clave, dañado o sin clave. **Nunca
     * lanza**: con otra clave el AES a veces «pasa» y lo que lanza es `gzdecode()`; eso tampoco se descifra.
     *
     * @param string $encrypted
     * @return string|null
     */
    public static function decryptBody(string $encrypted): ?string
    {
        $key = static::bodyKey();
        if ($key === null) {
            return null;
        }
        try {
            return BaseHashEncryption::decryptBidirectionalHash($encrypted, $key);
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * La clave del cuerpo, derivada de la de la app para este uso (ADR 0051). **null si la de la app es
     * de relleno FUERA de `local`** (ADR 0052): ahí los datos son de verdad. En `local` se usa igual: son
     * de prueba, y así la pantalla se prueba de punta a punta. Sin `environment.php` NO es `local`.
     *
     * @return string|null
     */
    public static function bodyKey(): ?string
    {
        $appKey = static::appKey();
        if (Config::app_key_is_placeholder($appKey) && !(AppEnvironment::isConfigured() && AppEnvironment::get() === AppEnvironment::LOCAL)) {
            return null;
        }
        return Config::app_key_derived(self::BODY_KEY_PURPOSE, $appKey);
    }

    /**
     * Si la clave de la app es la de relleno, para que el diagnóstico lo diga (ADR 0052 §3).
     *
     * @return bool
     */
    public static function appKeyIsPlaceholder(): bool
    {
        return Config::app_key_is_placeholder(static::appKey());
    }

    /**
     * La clave de la app. Aparte para que una prueba pueda poner una de relleno sin tocar la real.
     *
     * @return string
     */
    protected static function appKey(): string
    {
        return (string) Config::app_key();
    }

    /**
     * La fila se guardó, pero SIN cuerpo. Mismo criterio que `logRecordFailure()`: los
     * destinatarios van contados, no escritos.
     *
     * @param string $why
     * @param string[] $recipients
     * @return void
     */
    protected static function logBodyFailure(string $why, array $recipients): void
    {
        try {
            if (!function_exists('log_exception')) {
                return;
            }
            log_exception(new \RuntimeException(sprintf(
                'Un correo se anotó en %s SIN su cuerpo (%d destinatario(s)): %s. El envío no se vio afectado.',
                self::tableName(),
                count(array_filter($recipients, 'is_string')),
                $why
            )));
        } catch (\Throwable $second) {
            unset($second);
        }
    }

    /**
     * Anota un correo y recorta la tabla si pasó del tope. Devuelve si pudo anotar: **nunca lanza**,
     * porque un registro que rompe el envío es peor que no tener registro.
     *
     * @param string[] $recipients
     * @param string $subject
     * @param string|null $origin
     * @param string $delivery
     * @param string $result
     * @param string|null $reason
     * @param string|null $body El cuerpo tal como salió; se transforma, se cifra y, si algo falla, se queda fuera
     * @return bool
     */
    public static function record(array $recipients, string $subject, ?string $origin, string $delivery, string $result, ?string $reason = null, ?string $body = null): bool
    {
        try {
            $mapper = new self();
            $mapper->recipients = implode(', ', array_filter($recipients, 'is_string'));
            $mapper->subject = mb_substr($subject, 0, 500);
            $mapper->origin = $origin !== null ? mb_substr($origin, 0, 255) : null;
            $mapper->delivery = $delivery;
            $mapper->result = in_array($result, self::RESULTS, true) ? $result : self::RESULT_FAILED;
            $mapper->reason = $reason !== null ? mb_substr($reason, 0, 500) : null;
            //Sin la columna, un INSERT con `body` no guarda la fila: se pierde el correo entero (medido el
            //2026-10-05). Por eso se mira antes, y si falta la fila va sin cuerpo y queda su línea.
            if (self::bodyColumnExists()) {
                $mapper->body = static::bodyToStore($body, $recipients);
            } elseif ($body !== null && $body !== '') {
                self::logBodyFailure('la tabla no tiene la columna `body`; aplique ' . self::BODY_UPDATE_FILE, $recipients);
            }
            $saved = $mapper->save();
            self::trim();
            return $saved !== false;
        } catch (\Throwable $throwable) {
            self::logRecordFailure($throwable, $recipients, $result);
            return false;
        }
    }

    /**
     * Deja constancia de que un correo NO se pudo anotar. **No lanza**: si el registro rompiera el
     * envío sería peor que no tener registro, y eso no cambia (ADR 0043 §5).
     *
     * Existe porque el silencio dejaba el peor caso sin rastro en NINGÚN sitio: con la base caída,
     * un envío que falla no deja fila, así que el aviso del panel no puede encenderse y el registro
     * dice cero. **Con la base caída no hay panel ni avisos**, así que ese caso no se cubre con un
     * aviso: se cubre con esta línea en el registro de errores del framework, y con nada más.
     *
     * Solo lo hace `record()`, no las lecturas (`recentCounts()`, `latest()`, `tableExists()`): una
     * lectura que falla no pierde nada, y registrarlas escribiría una línea por página servida
     * mientras la base estuviera caída.
     *
     * @param \Throwable $throwable
     * @param string[] $recipients
     * @param string $result
     * @return void
     */
    protected static function logRecordFailure(\Throwable $throwable, array $recipients, string $result): void
    {
        try {
            if (!function_exists('log_exception')) {
                return;
            }
            //Los destinatarios van CONTADOS, no escritos: el registro de errores no es el sitio de
            //los datos personales, y para diagnosticar basta saber cuántos eran.
            $cuantos = count(array_filter($recipients, 'is_string'));
            log_exception(new \RuntimeException(sprintf(
                'No se pudo anotar un correo en %s (resultado «%s», %d destinatario(s)): %s. El correo SÍ se intentó enviar; lo que falló es el registro, así que el panel no podrá avisar de este envío.',
                self::tableName(),
                $result,
                $cuantos,
                $throwable->getMessage()
            ), 0, $throwable));
        } catch (\Throwable $second) {
            //Si ni el registro de errores responde, no queda nada que hacer sin romper el envío.
            unset($second);
        }
    }

    /**
     * Deja las `maxRows()` más recientes. Se hace por id, que es creciente: dos correos del mismo
     * segundo tienen el mismo `sentAt` y por fecha no se podrían ordenar entre sí.
     *
     * @return int Cuántas líneas se retiraron.
     */
    public static function trim(): int
    {
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return 0;
            }
            $table = self::tableName();
            $limite = $database->query("SELECT `id` FROM `{$table}` ORDER BY `id` DESC LIMIT 1 OFFSET " . (self::maxRows() - 1));
            $corte = $limite !== false ? $limite->fetchColumn() : false;
            if ($corte === false || $corte === null) {
                return 0;
            }
            $statement = $database->prepare("DELETE FROM `{$table}` WHERE `id` < ?");
            $statement->execute([(int) $corte]);
            return $statement->rowCount();
        } catch (\Throwable $throwable) {
            return 0;
        }
    }

    /**
     * Cuántos envíos no llegaron en las últimas horas. Es lo que `mail-doctor` enseña y lo que
     * contesta a «¿está saliendo el correo?».
     *
     * @param int $hours
     * @return array{failed:int,outbox:int,delivered:int}
     */
    public static function recentCounts(int $hours = self::ALERT_WINDOW_HOURS): array
    {
        $counts = ['failed' => 0, 'outbox' => 0, 'delivered' => 0];
        $hours = max(1, $hours);
        if (array_key_exists($hours, self::$recentMemo)) {
            return self::$recentMemo[$hours];
        }
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return $counts;
            }
            $table = self::tableName();
            $statement = $database->prepare("SELECT `result`, COUNT(*) AS `total` FROM `{$table}` WHERE `sentAt` >= (NOW() - INTERVAL ? HOUR) GROUP BY `result`");
            $statement->execute([max(1, $hours)]);
            foreach ((array) $statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
                $key = is_array($row) ? (string) ($row['result'] ?? '') : '';
                if (array_key_exists($key, $counts)) {
                    $counts[$key] = (int) ($row['total'] ?? 0);
                }
            }
        } catch (\Throwable $throwable) {
            //NO se memoriza un fallo: si la consulta revienta por algo pasajero, la siguiente
            //pregunta de esta misma petición tiene derecho a volver a intentarlo.
            return $counts;
        }
        self::$recentMemo[$hours] = $counts;
        return $counts;
    }

    /**
     * Si la tabla existe. Un clon que no aplicó el archivo de `databases/actualizaciones/` NO la
     * tiene, y entonces `record()` calla a propósito: el registro estaría mintiendo por silencio,
     * porque «ningún fallo» y «ningún dato» se leen igual.
     *
     * Se pregunta por `information_schema` y acotando a la base ACTUAL: un `SHOW TABLES LIKE`
     * trataría el `_` del nombre como un comodín, y habría acertado con una tabla parecida.
     *
     * @return bool
     */
    public static function tableExists(): bool
    {
        if (self::$tableExistsMemo !== null) {
            return self::$tableExistsMemo;
        }
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return false;
            }
            $statement = $database->prepare('SELECT COUNT(*) FROM `information_schema`.`tables` WHERE `table_schema` = DATABASE() AND `table_name` = ?');
            $statement->execute([self::tableName()]);
            self::$tableExistsMemo = ((int) $statement->fetchColumn()) > 0;
            return self::$tableExistsMemo;
        } catch (\Throwable $throwable) {
            //Falla CERRADO: si no se puede saber, no se afirma que la tabla esté.
            return false;
        }
    }

    /**
     * Si la tabla tiene la columna del cuerpo. Un clon que aplicó el archivo del 2026-10-03 y no el
     * del 2026-10-05 tiene la tabla SIN ella. Mismo patrón que `tableExists()`.
     *
     * Falla CERRADO: si no se puede saber, se responde que no está, y la fila se guarda sin cuerpo.
     *
     * @return bool
     */
    public static function bodyColumnExists(): bool
    {
        if (self::$bodyColumnExistsMemo !== null) {
            return self::$bodyColumnExistsMemo;
        }
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return false;
            }
            $statement = $database->prepare("SELECT COUNT(*) FROM `information_schema`.`columns` WHERE `table_schema` = DATABASE() AND `table_name` = ? AND `column_name` = 'body'");
            $statement->execute([self::tableName()]);
            self::$bodyColumnExistsMemo = ((int) $statement->fetchColumn()) > 0;
            return self::$bodyColumnExistsMemo;
        } catch (\Throwable $throwable) {
            return false;
        }
    }

    /**
     * El último envío que no llegó dentro de la ventana, para que un aviso pueda decir el MOTIVO y
     * no solo el número: «3 fallos» no dice qué arreglar, y el motivo del último casi siempre sí.
     *
     * @param int $hours
     * @return \stdClass|null
     */
    public static function lastFailure(int $hours = self::ALERT_WINDOW_HOURS): ?\stdClass
    {
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return null;
            }
            $table = self::tableName();
            //Sin `SELECT *`: el aviso se evalúa en cada página y no necesita destinatarios ni el cuerpo.
            $statement = $database->prepare("SELECT `id`, `sentAt`, `result`, `reason` FROM `{$table}` WHERE `result` = ? AND `sentAt` >= (NOW() - INTERVAL ? HOUR) ORDER BY `id` DESC LIMIT 1");
            $statement->execute([self::RESULT_FAILED, max(1, $hours)]);
            $row = $statement->fetch(\PDO::FETCH_OBJ);
            return $row instanceof \stdClass ? $row : null;
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * El cuerpo CIFRADO de una fila, tal como está en la base, o null si la fila no existe. Lo
     * descifra quien lo va a servir: aquí no se descifra nada que no se vaya a enseñar.
     *
     * @param int $id
     * @return array{id:int,body:string|null}|null
     */
    public static function bodyOf(int $id): ?array
    {
        if ($id < 1 || !self::bodyColumnExists()) {
            return null;
        }
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return null;
            }
            $statement = $database->prepare('SELECT `id`, `body` FROM `' . self::tableName() . '` WHERE `id` = ?');
            $statement->execute([$id]);
            $fila = $statement->fetch(\PDO::FETCH_ASSOC);
            if (!is_array($fila)) {
                return null;
            }
            return ['id' => (int) $fila['id'], 'body' => is_string($fila['body'] ?? null) ? $fila['body'] : null];
        } catch (\Throwable $throwable) {
            return null;
        }
    }

    /**
     * La tabla en uso: la real, salvo que una prueba haya puesto la suya.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return self::$tableForTesting ?? self::TABLE;
    }

    /**
     * SOLO PARA PRUEBAS: apunta el registro a una tabla propia, para probar sin renombrar, borrar
     * ni alterar la real. Mismo patrón que `AppEnvironment::useForTesting()`. Con null vuelve a la real.
     *
     * Solo acepta nombres `pcsphp_mail_log_zz_…`: así no puede desviar el registro a OTRA tabla real.
     *
     * @param string|null $table
     * @return void
     * @throws \InvalidArgumentException si el nombre no es de una tabla de prueba
     */
    public static function useTableForTesting(?string $table): void
    {
        if ($table !== null && preg_match('/^' . self::TABLE . '_zz_[a-z0-9_]+$/', $table) !== 1) {
            throw new \InvalidArgumentException('Solo una tabla de prueba `' . self::TABLE . '_zz_…`.');
        }
        self::$tableForTesting = $table;
        self::forgetMemo();
    }

    /**
     * Olvida lo contado en esta petición. Es para las pruebas: provocan una condición y vuelven a
     * preguntar en el mismo proceso, y la memoria les daría la cifra de antes.
     *
     * @return void
     */
    public static function forgetMemo(): void
    {
        self::$recentMemo = [];
        self::$tableExistsMemo = null;
        self::$bodyColumnExistsMemo = null;
    }

    /**
     * @param int $limit
     * @return array<int,\stdClass>
     */
    public static function latest(int $limit = 50): array
    {
        try {
            $model = self::model();
            $model->resetAll();
            $database = $model->getDatabase();
            if ($database === null) {
                return [];
            }
            $table = self::tableName();
            $statement = $database->prepare("SELECT * FROM `{$table}` ORDER BY `id` DESC LIMIT " . max(1, $limit));
            $statement->execute();
            return array_values((array) $statement->fetchAll(\PDO::FETCH_OBJ));
        } catch (\Throwable $throwable) {
            return [];
        }
    }

    /**
     * Las líneas de una prueba, para que pueda retirarlas sin tocar las demás.
     *
     * @param string $mark
     * @return int
     */
    public static function deleteByRecipientMark(string $mark): int
    {
        try {
            $model = self::model();
            $model->resetAll();
            $model->delete(new WhereSegment([WhereItem::like('recipients', "%{$mark}%")]))->execute();
            return 1;
        } catch (\Throwable $throwable) {
            return 0;
        }
    }
}
