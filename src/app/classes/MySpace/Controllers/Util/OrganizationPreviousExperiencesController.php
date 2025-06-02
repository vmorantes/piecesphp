<?php

/**
 * OrganizationPreviousExperiencesController.php
 */

namespace MySpace\Controllers\Util;

use App\Controller\AdminPanelController;
use MySpace\Controllers\HelperController;
use MySpace\Controllers\MyOrganizationProfileController;
use MySpace\MySpaceLang;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\Routing\ResponseRoute as Response;
use PiecesPHP\Core\Utilities\Helpers\DataTablesHelper;
use PiecesPHP\Core\Utilities\ReturnTypes\ResultOperations;
use PiecesPHP\Core\Validation\Parameters\Exceptions\InvalidParameterValueException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\MissingRequiredParamaterException;
use PiecesPHP\Core\Validation\Parameters\Exceptions\ParsedValueException;
use PiecesPHP\Core\Validation\Parameters\Parameter;
use PiecesPHP\Core\Validation\Parameters\Parameters;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\Profile\SubMappers\OrganizationPreviousExperiencesMapper;
use PiecesPHP\UserSystem\UserDataPackage;

/**
 * OrganizationPreviousExperiencesController.
 *
 * @package     MySpace\Controllers\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class OrganizationPreviousExperiencesController extends AdminPanelController
{

    /**
     * @var HelperController
     */
    protected $helpController = null;

    const BASE_JS_DIR = 'js';
    const BASE_CSS_DIR = 'css';
    const LANG_GROUP = MySpaceLang::LANG_GROUP;

    public function __construct()
    {
        parent::__construct();
        $this->helpController = new HelperController($this->user, $this->getGlobalVariables());
        $this->setInstanceViewDir(__DIR__ . '/../../Views/my-organization-profile');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function dataTables(Request $request, Response $response, array $args)
    {

        $currentUser = getLoggedFrameworkUser();
        $adminUser = $currentUser;
        $organizationID = $adminUser->organization !== null ? $adminUser->organization : -1;
        //Verificar si tiene privilegios superiores
        if (MyOrganizationProfileController::hasSuperPrivileges($currentUser)) {
            $organizationMapper = MyOrganizationProfileController::getOrganizationFromParams($args);
            $organizationID = $organizationMapper !== null ? $organizationMapper->id : -1;
            $organizationAdministratorID = $organizationMapper->administrator->id;
            if ($organizationAdministratorID !== null) {
                $adminUser = new UserDataPackage($organizationAdministratorID);
            }
        }

        $table = OrganizationPreviousExperiencesMapper::TABLE;
        $whereString = null;
        $havingString = null;
        $and = 'AND';

        $where = [
            "{$table}.profile = {$organizationID}",
        ];
        $having = [];

        if (false) {
            $beforeOperator = !empty($having) ? $and : '';
            $critery = "FIELD = CRITERY";
            $having[] = "{$beforeOperator} ({$critery})";
        }

        if (!empty($where)) {
            $whereString = trim(implode(' ', $where));
        }

        if (!empty($having)) {
            $havingString = trim(implode(' ', $having));
        }

        $selectFields = OrganizationPreviousExperiencesMapper::fieldsToSelect();

        $columnsOrder = [
            'experienceName',
        ];

        DataTablesHelper::setTablePrefixOnOrder(false);
        DataTablesHelper::setTablePrefixOnSearch(false);

        $result = DataTablesHelper::process([

            'where_string' => $whereString,
            'having_string' => $havingString,
            'select_fields' => $selectFields,
            'columns_order' => $columnsOrder,
            'mapper' => new OrganizationPreviousExperiencesMapper(),
            'request' => $request,
            'on_set_data' => function ($e) {
                return [
                    '',
                    '',
                ];
            },

        ]);

        $rawData = $result->getValue('rawData');

        foreach ($rawData as $index => $element) {

            $mapper = new OrganizationPreviousExperiencesMapper($element->id);

            $rawData[$index] = $this->render(
                'util/experience-list-card',
                [
                    'mapper' => $mapper,
                    'deleteRoute' => MyOrganizationProfileController::routeName('actions-delete-experience', ['id' => $mapper->id]),
                    'hasDelete' => MyOrganizationProfileController::allowedRoute('actions-delete-experience', ['id' => $mapper->id]),
                    'langGroup' => self::LANG_GROUP,
                ],
                false
            );

        }

        $result->setValue('rawData', $rawData);

        return $response->withJson($result->getValues());
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     */
    public function toDelete(Request $request, Response $response, array $args)
    {

        //──── Entrada ───────────────────────────────────────────────────────────────────────────

        //Definición de validaciones y procesamiento
        $expectedParameters = new Parameters([
            new Parameter(
                'id',
                -1,
                function ($value) {
                    return Validator::isInteger($value);
                },
                true,
                function ($value) {
                    return (int) $value;
                }
            ),
        ]);

        //Obtención de datos
        $inputData = $args;

        //Asignación de datos para procesar
        $expectedParameters->setInputValues($inputData);

        //──── Estructura de respuesta ───────────────────────────────────────────────────────────

        $resultOperation = new ResultOperations([], __(self::LANG_GROUP, 'Eliminar experiencia'));
        $resultOperation->setSingleOperation(true); //Se define que es de una única operación

        //Valores iniciales de la respuesta
        $resultOperation->setSuccessOnSingleOperation(false);
        $resultOperation->setValue('redirect', false);
        $resultOperation->setValue('redirect_to', null);
        $resultOperation->setValue('reload', false);
        $resultOperation->setValue('received', $inputData);

        //Mensajes de respuesta
        $successMessage = __(self::LANG_GROUP, 'Experiencia eliminada.');
        $unknowErrorMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido.');
        $unknowErrorWithValuesMessage = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido al procesar los valores ingresados.');

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Intenta validar, si todo sale bien el código continúa
            $expectedParameters->validate();

            //Información del formulario

            /**
             * @var int $id
             */
            $id = $expectedParameters->getValue('id');

            try {

                $exists = OrganizationPreviousExperiencesMapper::existsByID($id);

                if ($exists) {

                    $table = OrganizationPreviousExperiencesMapper::TABLE;

                    $transactionSQLDeleteQueries = [
                        [
                            'query' => "DELETE FROM {$table} WHERE id = :ID",
                            'aliasConfig' => [
                                ':ID' => $id,
                            ],
                        ],
                    ];

                    $pdo = OrganizationPreviousExperiencesMapper::model()->getDatabase();

                    try {

                        $pdo->beginTransaction();

                        foreach ($transactionSQLDeleteQueries as $sqlQueryConfig) {

                            $query = $sqlQueryConfig['query'];
                            $aliasConfig = $sqlQueryConfig['aliasConfig'];

                            $preparedStatement = $pdo->prepare($query);
                            $preparedStatement->execute($aliasConfig);

                        }

                        $pdo->commit();

                        $resultOperation->setSuccessOnSingleOperation(true);

                        $resultOperation->setMessage($successMessage);

                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        $resultOperation->setValue('transactionError', $e->getMessage());
                        $resultOperation->setMessage($unknowErrorMessage);
                        log_exception($e);
                    }

                } else {
                    $resultOperation->setSuccessOnSingleOperation(true);
                    $resultOperation->setMessage($successMessage);
                }

            } catch (\Exception $e) {

                $resultOperation->setMessage($e->getMessage());
                log_exception($e);

            }

        } catch (MissingRequiredParamaterException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        } catch (ParsedValueException $e) {

            $resultOperation->setMessage($unknowErrorWithValuesMessage);
            log_exception($e);

        } catch (InvalidParameterValueException $e) {

            $resultOperation->setMessage($e->getMessage());
            log_exception($e);

        }

        return $response->withJson($resultOperation);
    }

    /**
     * @inheritDoc
     */
    public function render(string $name = "index", array $data = [], bool $mode = true, bool $format = false)
    {
        return parent::render(trim($name, '/'), $data, $mode, $format);
    }

}
