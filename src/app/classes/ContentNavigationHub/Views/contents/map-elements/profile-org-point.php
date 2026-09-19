<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use Organizations\Mappers\OrganizationMapper;
/**
 * @var OrganizationMapper $mapper
 */
$avatar = $mapper->getLogoURL();
?>
<div class='custom-point profile-org'>
    <img src='<?= $avatar; ?>'>
    <i class="icon user outline"></i>
</div>