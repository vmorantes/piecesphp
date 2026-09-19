<?php

/**
 * InterestResearchAreasMapper.php
 */

namespace PiecesPHP\UserSystem\Profile\SubMappers;

use App\Model\UsersModel;

/**
 * InterestResearchAreasMapper.
 *
 * @package     PiecesPHP\UserSystem\Profile\SubMappers
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2025
 * @property int|null $id
 * @property string|null $preferSlug Es un token usado para acceso individual sin exponer el ID
 * @property string|null $areaName
 * @property string|\DateTime $createdAt
 * @property string|\DateTime $updatedAt
 * @property int|UsersModel|null $createdBy
 * @property int|UsersModel $modifiedBy
 * @property \stdClass|string|null $meta
 * @property string $color
 * @property string $baseLang
 * @property \stdClass|null $langData
 */
class InterestResearchAreasMapper extends \InterestResearchAreas\Mappers\InterestResearchAreasMapper
{}
