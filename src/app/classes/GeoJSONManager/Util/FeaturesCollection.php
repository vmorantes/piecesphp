<?php
/**
 * FeaturesCollection.php
 */

namespace GeoJSONManager\Util;

use PiecesPHP\Core\DataStructures\ArrayOf;
use PiecesPHP\Core\DataStructures\Exceptions\NotAllowedTypeException;
use PiecesPHP\GeoJson\Feature;

/**
 * FeaturesCollection.
 *
 * @package     GeoJSONManager\Util
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 */
class FeaturesCollection extends ArrayOf
{
    /**
     * @param Feature|Feature[] $input
     *
     * @throws NotAllowedTypeException
     */
    public function __construct($input = [])
    {
        parent::__construct($input, self::TYPE_OBJECT, Feature::class);
    }
}
