<?php

/**
 * OrganizationsAssignCodesTask.php
 */

namespace Terminal\Tasks;

use PiecesPHP\UserSystem\ORM\UsersModel;
use Organizations\Mappers\OrganizationMapper;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * OrganizationsAssignCodesTask - Pone el código público a las organizaciones que se crearon antes de que existiera.
 *
 * Es IDEMPOTENTE y no se ejecuta sola en ningún arranque: solo toca las filas cuyo código falta o está mal formado,
 * y la segunda pasada no toca ninguna. La organización global recibe el código reservado.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class OrganizationsAssignCodesTask extends TerminalTaskAbstract
{

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     */
    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $this->description = new StringArray([
            "Pone el código público a las organizaciones que no lo tienen. Idempotente: la segunda pasada toca 0.\r\n",
            "\tParámetros:\r\n",
            "\t  N/A\r\n",
        ]);
        $this->route = "{$startRoute}/organizations-assign-codes[/]";
        $this->controller = self::class . '::main';
        $this->name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'organizations-assign-codes';
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray([UsersModel::TYPE_USER_ROOT]);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    /**
     * @param RequestRoute|null $requestRoute
     * @param ResponseRoute|null $responseRoute
     * @param array|null $parameters
     * @return void
     */
    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {
        $resultado = self::assign();

        foreach ($resultado['fallos'] as $fallo) {
            echoTerminal("\e[31mFALLO:\e[39m {$fallo}");
        }

        echoTerminal('Organizaciones: ' . $resultado['total'] . '.');
        echoTerminal('Ya tenían código: ' . $resultado['conCodigo'] . '.');
        echoTerminal('Código asignado ahora: ' . $resultado['asignados'] . '.');
        if (count($resultado['fallos']) > 0) {
            echoTerminal('Sin asignar por fallo: ' . count($resultado['fallos']) . '.');
        }
    }

    /**
     * Recorre TODAS las organizaciones, incluidas las eliminadas: el código es único en la tabla entera, así que
     * una fila sin él también lo necesita para que el índice signifique algo.
     *
     * @return array{total:int,conCodigo:int,asignados:int,fallos:string[]}
     */
    public static function assign(): array
    {
        $model = OrganizationMapper::model();
        $model->resetAll();
        $model->select()->execute();
        $rows = (array) $model->result();

        $total = 0;
        $conCodigo = 0;
        $asignados = 0;
        $fallos = [];

        foreach ($rows as $row) {
            $total++;
            $id = (int) $row->id;
            $codigo = isset($row->code) && is_string($row->code) ? $row->code : '';

            if (OrganizationMapper::codeIsWellFormed($codigo)) {
                $conCodigo++;
                continue;
            }

            try {
                $mapper = new OrganizationMapper($id);
                $mapper->code = $id === OrganizationMapper::INITIAL_ID_GLOBAL
                    ? OrganizationMapper::CODE_GLOBAL
                    : OrganizationMapper::generateCode();
                //Sin tocar las fechas: repartir un código no es una edición de la organización.
                if ($mapper->update(true)) {
                    $asignados++;
                } else {
                    $fallos[] = 'organización ' . $id . ': la base no aceptó la actualización.';
                }
            } catch (\Throwable $e) {
                $fallos[] = 'organización ' . $id . ': ' . $e->getMessage();
            }
        }

        return [
            'total' => $total,
            'conCodigo' => $conCodigo,
            'asignados' => $asignados,
            'fallos' => $fallos,
        ];
    }

    /**
     * @param string $startRoute
     * @param string|null $namePrefix
     * @return Route
     */
    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new OrganizationsAssignCodesTask($startRoute, $namePrefix);
        $route = new Route(
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
        return $route;
    }

}
