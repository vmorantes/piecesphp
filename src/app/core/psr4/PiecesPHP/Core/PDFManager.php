<?php

/**
 * PDFManager.php
 */
namespace PiecesPHP\Core;

use Spipu\Html2Pdf\Html2Pdf as PDF;

/**
 * PDFManager - Manipular PDF
 *
 * @package     PiecesPHP\Core
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 * @extends <a target='blank' href='https://github.com/spipu/html2pdf'>\Spipu\Html2Pdf\Html2Pdf</a>
 */
class PDFManager extends PDF
{
    /**
     * __construct
     *
     * Constructor
     *
     * @return void
     */
    public function __construct(
        $orientation = 'P',
        $format = 'A4',
        $lang = 'es',
        $unicode = true,
        $encoding = 'UTF-8',
        $margins = array(5, 5, 5, 8),
        $pdfa = false
    ) {
        parent::__construct($orientation, $format, $lang, $unicode, $encoding, $margins, $pdfa);
    }
}
