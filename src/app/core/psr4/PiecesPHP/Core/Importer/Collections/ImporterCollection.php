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
 * @copyright   Copyright (c) 2018
 */
class ImporterCollection extends ArrayOf
{
    protected $schema = [];

    public function __construct($input = [])
    {
        parent::__construct($input, self::TYPE_OBJECT, Importer::class);
    }

}
