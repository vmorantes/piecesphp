<?php

/**
 * AvatarController.php
 */

namespace PiecesPHP\UserSystem\Controllers;

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\Forms\FileValidator;
use PiecesPHP\Core\Validation\Validator;
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\ORM\UsersModel;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * AvatarController.
 *
 * Controlador de avatares
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class AvatarController extends BaseController
{

    const LANG_GROUP = 'avatarModule';

    public function __construct()
    {
        parent::__construct(false);
    }

    /**
     * Registra un avatar nuevo.
     *
     * Este método espera recibir:
     * POST:  [user_id]
     * FILES:  [image]
     *
     * @param Request $request Petición
     * @param Response $response Respuesta Argumentos pasados por GET
     * @return Response
     */
    public function register(Request $request, Response $response)
    {

        $userId = $request->getParsedBodyParam('user_id', null);

        $files_uploaded = $request->getUploadedFiles();
        $image = $files_uploaded['image'] ?? null;

        $json_response = [
            'success' => false,
            'error' => 'NO_ERROR',
            'message' => '',
        ];

        //El avatar de otro, solo quien puede gestionarlo (su formulario de edición); el propio, siempre. Antes que la
        //imagen: sin permiso no importa qué se mande.
        if (!is_null($userId)) {
            $actor = new UsersModel(getLoggedFrameworkUserOrFail()->id);
            if (!Validator::isInteger($userId) || !UsersController::canManageUser($actor, new UsersModel((int) $userId))) {
                return throw403($request);
            }
        }

        if (!is_null($userId) && !is_null($image)) {

            if (!AvatarModel::isAcceptedImage($image)) {
                $json_response['message'] = __(FileValidator::LANG_GROUP, 'Tipo de archivo inválido.');
                return $response->withJson($json_response);
            }

            $uploaded = AvatarModel::save((int) $userId, $image);

            if ($uploaded) {
                $json_response['success'] = $uploaded;
                $json_response['message'] = __(self::LANG_GROUP, 'Imagen de perfil modificada');
            } else {
                $json_response['message'] = __(self::LANG_GROUP, 'Ha ocurrido un error desconocido, intente más tarde.');
            }

        } else {
            $json_response['error'] = 'MISSING_OR_UNEXPECTED_PARAMS';
            $json_response['message'] = __(self::LANG_GROUP, 'MISSING_OR_UNEXPECTED_PARAMS');
        }

        return $response->withJson($json_response);
    }

}
