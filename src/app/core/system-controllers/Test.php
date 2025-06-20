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
 * Controlador de pruebas para generar imágenes de placeholder.
 *
 * @package     App\Controller
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @version     2.0.0
 */
class Test
{
    /** @ignore */
    public function __construct()
    {}

    /**
     * Genera una imagen de placeholder con las dimensiones especificadas
     *
     * @param Request $request Objeto de solicitud HTTP
     * @param Response $response Objeto de respuesta HTTP
     * @param array $args Argumentos de la ruta (w: ancho, h: alto)
     * @return Response Respuesta HTTP con la imagen generada o error
     *
     * @throws \Exception Si hay errores en la generación de la imagen
     */
    public function generateImage(Request $request, Response $response, array $args): Response
    {
        // Validar que se proporcionen las dimensiones requeridas
        if (!isset($args['w']) || !isset($args['h'])) {
            return $this->generateErrorResponse($response, 'Dimensiones no especificadas');
        }

        // Validar que las dimensiones sean números positivos
        $width = (int) $args['w'];
        $height = (int) $args['h'];
        $width = $width > 0 ? $width : 100;
        $height = $height > 0 ? $height : 100;

        try {
            // Generar la imagen de placeholder
            $imageData = $this->createPlaceholderImage($width, $height);

            return $response
                ->write($imageData)
                ->withHeader('Content-Type', 'image/jpeg')
                ->withHeader('Cache-Control', 'public, max-age=' . 3600 * 24 * 7 * 4 * 12); // Cache por 1 año

        } catch (\Exception $e) {
            log_exception($e);
            return $this->generateErrorResponse($response, 'Error interno del servidor');
        }
    }

    /**
     * Crea una imagen de placeholder con texto centrado
     *
     * @param int $width Ancho de la imagen
     * @param int $height Alto de la imagen
     * @return string Datos binarios de la imagen JPEG
     * @throws \Exception Si hay errores en la creación de la imagen
     */
    private function createPlaceholderImage(int $width, int $height): string
    {
        // Iniciar buffer de salida para capturar la imagen
        ob_start();

        // Crear la imagen base
        $image = imagecreate($width, $height);

        if (!$image) {
            throw new \Exception('No se pudo crear la imagen');
        }

        // Definir colores
        $backgroundColor = imagecolorallocate($image, 183, 183, 183); // Gris claro
        $textColor = imagecolorallocate($image, 0, 0, 0); // Negro

        // Rellenar el fondo
        imagefill($image, 0, 0, $backgroundColor);

        // Configurar el texto
        $text = "{$width}x{$height}";
        $fontPath = $this->getFontPath();
        $fontSize = $this->calculateFontSize($width, $height);

        // Calcular posición del texto centrado
        $textPosition = $this->calculateTextPosition($image, $text, $fontPath, $fontSize);

        // Dibujar el texto
        imagettftext(
            $image,
            $fontSize,
            0,
            $textPosition['x'],
            $textPosition['y'],
            $textColor,
            $fontPath,
            $text
        );

        // Generar imagen JPEG
        imagejpeg($image, null, 90); // Calidad 90%

        // Limpiar memoria
        imagedestroy($image);

        // Obtener datos de la imagen
        $imageData = ob_get_contents();
        ob_end_clean();

        return $imageData;
    }

    /**
     * Obtiene la ruta del archivo de fuente
     *
     * @return string Ruta absoluta del archivo de fuente
     * @throws \Exception Si no se encuentra el archivo de fuente
     */
    private function getFontPath(): string
    {
        $fontPath = realpath(__DIR__ . "/../system-views/Oswald-ExtraLight.ttf");

        if (!$fontPath || !file_exists($fontPath)) {
            throw new \Exception('Archivo de fuente no encontrado');
        }

        return $fontPath;
    }

    /**
     * Calcula el tamaño de fuente apropiado para la imagen
     *
     * @param int $width Ancho de la imagen
     * @param int $height Alto de la imagen
     * @return float Tamaño de fuente calculado
     */
    private function calculateFontSize(int $width, int $height): float
    {
        // Usar la dimensión más pequeña para calcular el tamaño de fuente
        $minDimension = min($width, $height);
        return max(8, $minDimension * 0.1); // Mínimo 8px, máximo 10% de la dimensión menor
    }

    /**
     * Calcula la posición centrada del texto en la imagen
     *
     * @param mixed $image Recurso de imagen GD
     * @param string $text Texto a centrar
     * @param string $fontPath Ruta del archivo de fuente
     * @param float $fontSize Tamaño de la fuente
     * @return array Coordenadas x, y para centrar el texto
     */
    private function calculateTextPosition($image, string $text, string $fontPath, float $fontSize): array
    {
        // Obtener dimensiones de la imagen
        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        // Calcular dimensiones del texto
        $textBox = imagettfbbox($fontSize, 0, $fontPath, $text);
        $textWidth = abs($textBox[4] - $textBox[0]);
        $textHeight = abs($textBox[5] - $textBox[1]);

        // Calcular posición centrada
        $x = (int) round(($imageWidth - $textWidth) / 2);
        $y = (int) round(($imageHeight + $textHeight) / 2);

        return [
            'x' => $x,
            'y' => $y,
        ];
    }

    /**
     * Genera una respuesta de error HTTP
     *
     * @param Response $response Objeto de respuesta HTTP
     * @param string $message Mensaje de error
     * @return Response Respuesta HTTP con error
     */
    private function generateErrorResponse(Response $response, string $message): Response
    {
        $errorHtml = sprintf(
            '<!DOCTYPE html>
            <html>
            <head>
                <title>Error - Imagen no disponible</title>
                <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    .error { color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 20px; border-radius: 5px; }
                </style>
            </head>
            <body>
                <div class="error">
                    <h1>Error</h1>
                    <p>%s</p>
                </div>
            </body>
            </html>',
            htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
        );

        return $response
            ->withStatus(400)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->write($errorHtml);
    }
}
