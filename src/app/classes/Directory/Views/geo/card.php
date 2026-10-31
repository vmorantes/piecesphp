<?php
defined("BASEPATH") or die("<h1>El script no puede ser accedido directamente</h1>");

use Directory\DirectoryLang;

//La tarjeta de una persona en el geovisor: el cliente rellena cada data-geo-field con textContent.
?>
<div class="geo-card">
    <div class="geo-card-title" data-geo-field="name"></div>
    <div class="geo-card-line" data-geo-field="jobPosition"></div>
    <div class="geo-card-line" data-geo-field="organization"></div>
    <div class="geo-card-line geo-card-location">
        <span data-geo-field="city"></span>
        <span data-geo-field="country"></span>
    </div>
    <a class="ui button" data-geo-href="url"><?= __(DirectoryLang::LANG_GROUP, 'Ver perfil'); ?></a>
</div>
