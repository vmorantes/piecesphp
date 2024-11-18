<?php

/**
 * Test.php
 */

namespace PiecesPHP\Core;

use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * Test.
 *
 * Pruebas.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class Test
{
    /** @ignore */
    public function __construct()
    {
    }

    public function generateImage(Request $request, Response $response, array $args)
    {
        if (isset($args['w']) && isset($args['h'])) {
            ob_start();
            $ancho = $args['w'];
            $alto = $args['h'];

            // Crear la imagen
            $imagen = imagecreate($ancho, $alto);
            $color_gris = imagecolorallocate($imagen, 183, 183, 183);
            $color_negro = imagecolorallocate($imagen, 0, 0, 0);
            imagefill($imagen, 0, 0, $color_gris);

            // Configuración del texto
            $texto = $ancho . "x" . $alto;
            $font = realpath(__DIR__ . "/../system-views/Oswald-ExtraLight.ttf");
            $font_size = $ancho * 0.1; // Tamaño de fuente proporcional al ancho de la imagen

            // Calcular el tamaño del cuadro de texto
            $text_box = imagettfbbox($font_size, 0, $font, $texto);
            $text_width = abs($text_box[4] - $text_box[0]);
            $text_height = abs($text_box[5] - $text_box[1]);

            // Calcular coordenadas para centrar el texto
            $x = (int) round(($ancho - $text_width) / 2); // Conversión explícita a int
            $y = (int) round(($alto + $text_height) / 2); // Conversión explícita a int

            // Agregar el texto a la imagen
            imagettftext($imagen, $font_size, 0, $x, $y, $color_negro, $font, $texto);

            // Generar la imagen y limpiar
            imagejpeg($imagen);
            imagedestroy($imagen);
            $output = ob_get_contents();
            ob_end_clean();

            return $response->write($output)->withHeader('Content-type', 'image/jpg');
        } else {
            return $response->withStatus(404)->write('<h1>Recurso inexistente.</h1>');
        }
    }

}
