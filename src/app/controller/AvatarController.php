<?php

/**
 * AvatarController.php
 */

namespace App\Controller;

use App\Model\AvatarModel;
use PiecesPHP\Core\BaseController;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

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
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function register(Request $request, Response $response, array $args)
    {

        $user_id = $request->getParsedBodyParam('user_id', null);

        $files_uploaded = $request->getUploadedFiles();
        $image = isset($files_uploaded['image']) ? $files_uploaded['image'] : null;

        $json_response = [
            'success' => false,
            'error' => 'NO_ERROR',
            'message' => '',
        ];

        if (!is_null($user_id) && !is_null($image)) {

            $uploaded = AvatarModel::save($user_id, $image);

            if ($uploaded) {
                $json_response['success'] = $uploaded;
                $json_response['message'] = __('avatarModule', 'Ha ocurrido un error desconocido, intente más tarde.');
            }

        } else {
            $json_response['error'] = 'MISSING_OR_UNEXPECTED_PARAMS';
            $json_response['message'] = __('avatarModule', 'MISSING_OR_UNEXPECTED_PARAMS');
        }

        return $response->withJson($json_response);
    }

    /**
     * avatar
     *
     * Avatars JSON
     *
     * @param Request $request Petición
     * @param Request $response Respuesta
     * @param array $args Argumentos pasados por GET
     * @return void
     */
    public function avatar(Request $request, Response $response, array $args)
    {
        $baseFolder = 'statics/images/avatares';
        $categories = [
            'all' => [
                'base' => 'all',
                'label' => 'Todo',
                'elements' => [
                    'cabello' => [
                        'base' => 'cabello',
                        'subElements' => [
                            'fondo' => [
                                'base' => 'fondo',
                                'files' => [],
                                'colors' => [
                                    '#BF7E20',
                                    '#D65514',
                                    '#2F2820',
                                    '#7F311C',
                                    '#422F20',
                                ],
                            ],
                            'frente' => [
                                'base' => 'frente',
                                'files' => [],
                                'colors' => [
                                    '#BF7E20',
                                    '#D65514',
                                    '#2F2820',
                                    '#7F311C',
                                    '#422F20',
                                ],
                            ],
                            'accesorio' => [
                                'base' => 'accesorio',
                                'files' => [],
                                'colors' => [
                                    '#F95006',
                                    '#F70825',
                                    '#0A7C7C',
                                    '#34600D',
                                    '#F4A00B',
                                    '#BE40F2',
                                ],
                            ],
                        ],
                    ],
                    'silueta' => [
                        'base' => 'silueta',
                        'files' => [],
                        'colors' => [
                            '#F1AB6D',
                            '#FFC58E',
                            '#FCB87E',
                            '#A36837',
                            '#F9B67C',
                        ],
                    ],
                    'ojo' => [
                        'base' => 'ojo',
                        'files' => [],
                    ],
                    'boca' => [
                        'base' => 'boca',
                        'files' => [],
                        'colors' => [
                            '#D6815C',
                            '#F9B28F',
                            '#F7987F',
                            '#F4A071',
                            '#B2582B',
                        ],
                    ],
                    'nariz' => [
                        'base' => 'nariz',
                        'files' => [],
                        'colors' => [
                            '#F1AB6D',
                            '#FFC58E',
                            '#FCB87E',
                            '#A36837',
                            '#F9B67C',
                        ],
                    ],
                    'ropa' => [
                        'base' => 'ropa',
                        'files' => [],
                        'colors' => [
                            '#F1AB6D',
                            '#FFC58E',
                            '#FCB87E',
                            '#A36837',
                            '#F9B67C',
                        ],
                    ],
                ],
            ],
        ];
        foreach ($categories as $index => $category) {

            $baseCategory = $baseFolder . '/' . $category['base'];
            $elements = $category['elements'];

            foreach ($elements as $name => $element) {

                $baseElement = $baseCategory . '/' . $element['base'];
                $hasSubElements = isset($element['subElements']);

                $elements[$name]['base'] = $baseElement;
                $elements[$name]['current'] = 0;

                if (!$hasSubElements) {

                    $files = $this->listFiles($baseElement);
                    $elements[$name]['files'] = $files;
                    $elements[$name]['total'] = count($files);

                } else {

                    $subElements = $element['subElements'];
                    $totalSubElements = count($subElements);
                    $total = 0;
                    foreach ($subElements as $nameSub => $subElement) {

                        $baseSubElement = $baseElement . '/' . $subElement['base'];
                        $filesSubElement = $this->listFiles($baseSubElement);
                        $subElements[$nameSub]['base'] = $baseSubElement;
                        $subElements[$nameSub]['files'] = $filesSubElement;
                        $total += count($filesSubElement);

                    }
                    $elements[$name]['subElements'] = $subElements;
                    $elements[$name]['total'] = floor($total / $totalSubElements);

                }

            }

            $categories[$index]['base'] = $baseCategory;
            $categories[$index]['elements'] = $elements;
        }

        return $response->withJson([
            'base' => $baseFolder,
            'categories' => $categories,
        ]);
    }

    /**
     * listFiles
     *
     * @param string $folderRelativePath
     * @return void
     */
    public function listFiles(string $folderRelativePath)
    {
        $path = basepath($folderRelativePath);

        $directory = opendir($path);
        $file = readdir($directory);
        $files = [];
        while ($file !== false) {
            if ($file != '.' && $file != '..') {
                $filePath = basepath($folderRelativePath . '/' . $file);
                $files[] = $filePath;
            }
            $file = readdir($directory);
        }

        sort($files, \SORT_NATURAL);

        foreach ($files as $index => $file) {
            $files[$index] = file_get_contents($file);
        }

        closedir($directory);
        return $files;
    }

}
