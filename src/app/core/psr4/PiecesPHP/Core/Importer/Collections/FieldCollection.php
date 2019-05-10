<?php
/**
 * FieldCollection.php
 */
namespace PiecesPHP\Core\Importer\Collections;

use PiecesPHP\Core\DataStructures\ArrayOf;
use PiecesPHP\Core\Importer\Field;


/**
 * FieldCollection.
 *
 * @package     PiecesPHP\Core\Importer\Collections
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */
class FieldCollection extends ArrayOf
{
    protected $schema = [];

    public function __construct($input = [])
    {
		parent::__construct($input, self::TYPE_OBJECT, Field::class);
	}

	/**
	 * getByName
	 *
	 * @param string $name
	 * @return Field|null
	 */
	public function getByName(string $name){
		$field = null;
		foreach($this as $index => $field){
			if($field->getName() == $name){
				return $field;
			}
		}
		return null;
	}

	/**
	 * getPositionField
	 *
	 * @param string $name
	 * @return int|null
	 */
	public function getPositionField(string $name){
		$field = null;
		foreach($this as $index => $field){
			if($field->getName() == $name){
				return $index;
			}
		}
		return null;
	}
	
	/**
	 * getNames
	 *
	 * @return array
	 */
	public function getNames(){
		$names = [];
		$fields = $this->getArrayCopy();
		foreach ($fields as $field) {
            $names[] = $field->getName();
		}
		return $names;
	}
	

}
