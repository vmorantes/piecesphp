<?php

/**
 * UsersMetaNormalizeTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\Core\BaseModel;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;
use PiecesPHP\UserSystem\ORM\UsersModel;

/**
 * UsersMetaNormalizeTask.
 *
 * Reescribe UNA vez el `meta` de los usuarios que quedó con el formato de MySQL («{"k": "v"}», de las escrituras con JSON_SET
 * de antes de P329 a) con el formato del mapper (UsersModel::metaToStore()). Sin esto, el siguiente guardado sin cambios de
 * cada uno de esos usuarios cuenta una fila y reabre una aprobación rechazada. Por omisión solo cuenta; no imprime valores.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class UsersMetaNormalizeTask extends TerminalTaskAbstract
{

    /**
     * @var string|null null: la carpeta del producto
     */
    private static $dumpsDirectoryOverride = null;

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }

        $this->description = new StringArray([
            "Reescribe con el formato del mapper el meta de los usuarios guardado con el de MySQL (el de las versiones anteriores).\r\n",
            "\tPOR DEFECTO SOLO CUENTA: a reescribir, ya bien, vacíos y los que no son un objeto JSON. No imprime valores.\r\n",
            "\t  bin/cli users-meta-normalize\r\n",
            "\t  apply=yes  reescribe. Exige un volcado de la última hora en dumps/ (bin/cli db-backup antes)\r\n",
            "\t  bin/cli users-meta-normalize apply=yes\r\n",
            "\tUna segunda pasada no encuentra nada que reescribir.",
        ]);
        $this->route = "{$startRoute}/users-meta-normalize[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'users-meta-normalize';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $titleTask = 'Normalizar el meta de los usuarios';
        echoTerminal("\e[32m*** {$titleTask} ***\e[39m");

        $apply = TerminalData::instance()->getArgument('apply', '') === 'yes';

        if (!$apply) {
            $count = self::analyze();
            echoTerminal("\e[94mSIMULACRO:\e[39m {$count['total']} usuario(s): {$count['toRewrite']} a reescribir, {$count['alreadyOk']} ya con el formato del mapper, {$count['empty']} vacío(s) y {$count['notObject']} que no son un objeto JSON (no se tocan). Nada se escribe sin apply=yes.");
            echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
            exit(0);
        }

        $result = self::apply(time());
        if ($result['refused'] !== null) {
            echoTerminal("\e[31mERROR:\e[39m {$result['refused']}");
            exit(1);
        }
        echoTerminal("\e[94mINFO:\e[39m {$result['rewritten']} reescrito(s), {$result['failed']} sin reescribir (su meta cambió entre la lectura y la escritura: repite la tarea).");
        echoTerminal("\e[32m*** {$titleTask}, tarea finalizada ***\e[39m");
        exit($result['failed'] > 0 ? 1 : 0);
    }

    /**
     * Cuenta, sin escribir. Con $onlyIds, solo esos usuarios (las pruebas).
     *
     * @param int[]|null $onlyIds
     * @return array{total:int,toRewrite:int,alreadyOk:int,empty:int,notObject:int,ids:int[]}
     */
    public static function analyze(?array $onlyIds = null): array
    {
        $count = ['total' => 0, 'toRewrite' => 0, 'alreadyOk' => 0, 'empty' => 0, 'notObject' => 0, 'ids' => []];
        foreach (self::rows($onlyIds) as $id => $raw) {
            $count['total']++;
            if ($raw === null || $raw === '' || $raw === '[]') {
                $count['empty']++;
                continue;
            }
            $normalized = UsersModel::normalizedMeta($raw);
            if ($normalized === null) {
                $count['notObject']++;
            } elseif ($normalized === $raw) {
                $count['alreadyOk']++;
            } else {
                $count['toRewrite']++;
                $count['ids'][] = $id;
            }
        }
        return $count;
    }

    /**
     * Reescribe los del formato viejo, cada uno con lo leído en el WHERE (si cambió entre medias, no se pisa). Sin un
     * volcado de la última hora no escribe nada.
     *
     * @param int $now
     * @param int[]|null $onlyIds Solo esos usuarios (las pruebas)
     * @return array{refused:string|null,rewritten:int,failed:int}
     */
    public static function apply(int $now, ?array $onlyIds = null): array
    {
        $result = ['refused' => null, 'rewritten' => 0, 'failed' => 0];
        if (!RepairEscapedTextTask::hasRecentDump(self::dumpsDirectory(), $now)) {
            $result['refused'] = 'no hay un volcado de la última hora en dumps/. Haz bin/cli db-backup antes.';
            return $result;
        }
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            $result['refused'] = 'sin conexión a base de datos.';
            return $result;
        }
        $statement = $database->prepare('UPDATE `' . UsersModel::TABLE . '` SET meta = ? WHERE id = ? AND meta = ?');
        foreach (self::rows($onlyIds) as $id => $raw) {
            $normalized = $raw === null ? null : UsersModel::normalizedMeta($raw);
            if ($normalized === null || $normalized === $raw) {
                continue;
            }
            //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o escribe o lanza; lo que cuenta es el rowCount().
            $statement->execute([$normalized, $id, $raw]);
            if ($statement->rowCount() > 0) {
                $result['rewritten']++;
            } else {
                $result['failed']++;
            }
        }
        return $result;
    }

    /**
     * En el producto, dumps/ de src/.
     *
     * @return string
     */
    public static function dumpsDirectory(): string
    {
        return self::$dumpsDirectoryOverride ?? rtrim(str_replace('\\', '/', basepath('dumps')), '/');
    }

    /**
     * SOLO PARA PRUEBAS: cambia la carpeta de volcados (null: la del producto) y devuelve la de antes.
     *
     * @internal
     * @param string|null $dumpsDirectory
     * @return string|null
     */
    public static function swapForTesting(?string $dumpsDirectory = null): ?string
    {
        $previous = self::$dumpsDirectoryOverride;
        self::$dumpsDirectoryOverride = $dumpsDirectory !== null ? rtrim($dumpsDirectory, '/') : null;
        return $previous;
    }

    /**
     * id => meta crudo.
     *
     * @param int[]|null $onlyIds
     * @return array<int,string|null>
     */
    private static function rows(?array $onlyIds): array
    {
        $database = (new BaseModel())->getDatabase();
        if ($database === null) {
            return [];
        }
        $statement = $database->prepare('SELECT id, meta FROM `' . UsersModel::TABLE . '` ORDER BY id');
        //RETORNO-IGNORADO: PDO va en ERRMODE_EXCEPTION: o lee o lanza; lo que cuenta es el fetchAll().
        $statement->execute();
        $only = $onlyIds === null ? null : array_flip(array_map('intval', $onlyIds));
        $rows = [];
        foreach ($statement->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            if ($only !== null && !isset($only[(int) $row['id']])) {
                continue;
            }
            $rows[(int) $row['id']] = $row['meta'] === null ? null : (string) $row['meta'];
        }
        return $rows;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new UsersMetaNormalizeTask($startRoute, $namePrefix);
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
