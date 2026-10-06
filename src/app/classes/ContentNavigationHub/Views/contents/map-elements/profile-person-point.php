<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use PiecesPHP\UserSystem\ORM\AvatarModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
/**
 * @var \stdClass $element
 */
$mapper = UserProfileMapper::objectToMapper($element);
if ($mapper === null) {
    //Igual que en la tarjeta: fila incompleta, sin punto en el mapa y sin 500.
    return;
}
$avatar = AvatarModel::getUserAvatarNameURLOrDefault($mapper->belongsTo);
?>
<div class='custom-point profile-user'>
    <img src='<?= $avatar; ?>'>
    <i class="icon user outline"></i>
</div>