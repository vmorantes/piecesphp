<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");
use App\Model\AvatarModel;
use PiecesPHP\UserSystem\Profile\UserProfileMapper;
/**
 * @var \stdClass $element
 */
$mapper = UserProfileMapper::objectToMapper($element);
$avatar = AvatarModel::getUserAvatarNameURLOrDefault($mapper->belongsTo);
?>
<div class='custom-point profile-user'>
    <img src='<?= $avatar; ?>'>
    <i class="icon user outline"></i>
</div>