<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use ApplicationCalls\Mappers\ApplicationCallsMapper;
/**
 * @var ApplicationCallsMapper $mapper
 */
$avatar = $mapper->currentLangData('thumbImage');
?>
<div class='custom-point application-call'>
    <img src='<?= $avatar; ?>'>
    <i class="icon user outline"></i>
</div>