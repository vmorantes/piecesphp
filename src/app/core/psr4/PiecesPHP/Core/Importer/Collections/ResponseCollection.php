<?php
/**
 * ResponseCollection.php
 */
namespace PiecesPHP\Core\Importer\Collections;

use PiecesPHP\Core\DataStructures\ArrayOf;
use PiecesPHP\Core\Importer\Response;

/**
 * ResponseCollection.
 *
 * @package     PiecesPHP\Core\Importer\Collections
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class ResponseCollection extends ArrayOf implements \JsonSerializable
{
    protected $schema = [];

    public function __construct($input = [])
    {
        parent::__construct($input, self::TYPE_OBJECT, Response::class);
	}
	

	public function jsonSerialize()
	{
		return $this->getArrayCopy();
	}

}
