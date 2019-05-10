<?php
/**
 * ImporterCollection.php
 */
namespace PiecesPHP\Core\Importer\Collections;

use PiecesPHP\Core\DataStructures\ArrayOf;
use PiecesPHP\Core\Importer\Importer;

/**
 * ImporterCollection.
 *
 * @package     PiecesPHP\Core\Importer\Collections
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @version     v.1.0
 * @copyright   Copyright (c) 2018
 * @use PiecesPHP\Core\DataStructures\ArrayOf
 */

class ImporterCollection extends ArrayOf
{
    protected $schema = [];

    public function __construct($input = [])
    {
        parent::__construct($input, self::TYPE_OBJECT, Importer::class);
    }

}
