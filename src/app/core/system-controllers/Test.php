<?php

/**
 * Test.php
 */

namespace PiecesPHP\Core;

use PiecesPHP\Core\BaseController;
use PiecesPHP\Core\BaseHashEncryption;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\StringManipulate;
use \Slim\Http\Request as Request;
use \Slim\Http\Response as Response;

/**
 * Test.
 *
 * Pruebas.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Test extends BaseController
{
    /** @ignore */
    public function __construct()
    {
        parent::__construct(false);
        $this->setViewDir(app_basepath('core/system-views'));
        import_jquery();
        import_nprogress();
        import_cropper();
        import_jquerymask();
        import_datatables();
        import_quilljs();
        import_semantic();
        import_izitoast();
        import_swal2();
        import_alertifyjs();
        import_app_libraries();
    }

    /** @ignore */
    public function index(Request $request, Response $response, array $args)
    {
        $url_1 = get_route('home-test') . "overview-1";
        $url_2 = get_route('home-test') . "overview-2";

        $html = "<a href='$url_1'>Muestra de librerías front-end implementadas</a>";
        $html .= '<br>';
        $html .= "<a href='$url_2 '>Muestra de algunas funcionalidades del back-end</a>";
        return $response->write($html);
    }

    public function generateImage(Request $request, Response $response, array $args)
    {
        if (isset($args['w']) && isset($args['h'])) {

            $ancho = $args['w'];
            $alto = $args['h'];

            $imagen = imagecreate($ancho, $alto);
            $color_gris = imagecolorallocate($imagen, 183, 183, 183);
            $color_negro = imagecolorallocate($imagen, 0, 0, 0);
            imagefill($imagen, 0, 0, $color_gris);

            $texto = $ancho . "x" . $alto;
            $font = realpath(get_config('statics_path') . "/vendor/test/font/Oswald-ExtraLight.ttf");

            $font_size = imagesx($imagen) * 0.05;
            $x = imagesx($imagen) / 2.6;
            $y = (imagesy($imagen) / 2);
            $angle = 0;
            $text_imagen = imagettftext($imagen, $font_size, $angle, $x, $y, $color_negro, $font, $texto);
            imagepng($imagen);
            imagedestroy($imagen);

            return $response->withHeader('Content-type', 'image/png');
        } else {
            return $response->withStatus(404)->write('<h1>Recurso inexistente.</h1>');
        }
    }
    /** @ignore */
    public function overviewBack(Request $request, Response $response, array $args)
    {
        $string = 'El ñandú es un ave que no vuela.';
        $key = isset($_GET['key']) ? $_GET['key'] : Config::app_key();

        $hash = BaseHashEncryption::hash($string, $key);
        $verificar_hash = BaseHashEncryption::hashVerify($string, $hash, $key);

        $encriptado = BaseHashEncryption::encrypt($string, $key);
        $desencriptado = BaseHashEncryption::decrypt($encriptado, $key);

        $url_encode = StringManipulate::urlSafeB64Encode($string);
        $url_decode = StringManipulate::urlSafeB64Decode($url_encode);

        $pass = StringManipulate::generatePass(10);

        $arr = [
            '$string' => $string,
            '$key' => $key,
            'BaseHashEncryption::encrypt($string,$key)' => $encriptado,
            'BaseHashEncryption::decrypt($encriptado,$key)' => $desencriptado,
            'sha1(BaseHashEncryption::hash($string, $key))' => sha1($hash),
            'BaseHashEncryption::hashVerify($string,$hash,$key)' => $verificar_hash,
            'StringManipulate::urlSafeB64Encode($string)' => $url_encode,
            'StringManipulate::urlSafeB64Decode($url_encode)' => $url_decode,
            'StringManipulate::generatePass(10)' => $pass,
            'friendly_url($string)' => friendly_url($string),
            'get_request()' => get_request(),
            'get_part_request()' => get_part_request(),
            'get_part_request(1)' => get_part_request(1),
            'get_part_request(2)' => get_part_request(2),
            'get_part_request(3)' => get_part_request(3),
            "require_keys(['indice','indice2'], ['indice'=>'value','indice2'=>'value'])" => require_keys(['indice', 'indice2'], ['indice' => 'value', 'indice2' => 'value']),
            "require_keys(['indice','indice2'], ['indice'=>'value'])" => require_keys(['indice', 'indice2'], ['indice' => 'valor']),
            'url_safe_base64_encode($string)' => url_safe_base64_encode($string),
            'url_safe_base64_encode($base64_string)' => url_safe_base64_decode(url_safe_base64_encode($string)),
        ];

        $response_html = '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <title>Tests</title>
        </head>
        <body>';
        $response_html .= "<h1>Tests:</h1>";
        foreach ($arr as $i => $v) {
            if (!is_array($v)) {
                $value = $v;
                if ($value === true || $value === false) {
                    $value = $value ? 'true' : 'false';
                }
                $response_html .= "<p><strong>$i:</strong> $value</p>";
            } else {
                foreach ($v as $j => $vj) {
                    $value = $vj;
                    if ($value === true || $value === false) {
                        $value = $value ? 'true' : 'false';
                    }
                    $value = is_string($value) ? "'$value'" : $value;
                    $value = is_string($j) ? "['$j'=>$value]" : "[$j=>$value]";
                    $response_html .= "<p><strong>$i:</strong> $value</p>";
                }
            }
        }

        $response_html .= '</body>
        </html>';

        return $response->write($response_html);

    }
    /** @ignore */
    public function overviewFront(Request $request, Response $response, array $args)
    {
        set_custom_assets([
            'statics/vendor/test/js/main.js',
        ], 'js');
        $this->render('layout/header');
        $this->render('pages/test/overview');
        $this->render('layout/footer');

        return $response;
    }

}
