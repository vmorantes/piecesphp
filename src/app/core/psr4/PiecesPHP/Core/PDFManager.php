<?php

/**
 * PDFManager.php
 */
namespace PiecesPHP\Core;

use \Mpdf\Mpdf as PDF;

/**
 * PDFManager - Manipular PDF
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 * @extends <a target='blank' href='https://mpdf.github.io/'>\Mpdf\Mpdf</a>
 */
class PDFManager extends PDF
{

    /**
     * @param string $orientation
     * @param string $format
     * @param string $mode
     * @param array $extraConfig
     * @param float $marginLeft
     * @param float $marginRight
     * @param float $marginTop
     * @param float $marginBottom
     * @param float $marginHeader
     * @param float $marginFooter
     */
    public function __construct(
        $orientation = 'P',
        $format = 'A4',
        string $mode = 'utf-8',
        array $extraConfig = [],
        float $marginLeft = 15,
        float $marginRight = 15,
        float $marginTop = 16,
        float $marginBottom = 16,
        float $marginHeader = 9,
        float $marginFooter = 9
    ) {

        $config = [
            'orientation' => $orientation,
            'format' => $format,
            'mode' => $mode,
            'margin_left' => $marginLeft,
            'margin_right' => $marginRight,
            'margin_top' => $marginTop,
            'margin_bottom' => $marginBottom,
            'margin_header' => $marginHeader,
            'margin_footer' => $marginFooter,
            'tempDir' => basepath('tmp'),
        ];

        foreach ($extraConfig as $key => $value) {
            if (is_string($key)) {
                $config[$key] = $value;
            }
        }

        parent::__construct($config);
    }
}
