<?php // adminer-plugins.php

$plugins = [
    'AdminerDisplayForeignKeyName' => new AdminerDisplayForeignKeyName(),
    'AdminerDumpAlter' => new AdminerDumpAlter(),
    'AdminerDumpArray' => new AdminerDumpArray(),
    'AdminerDumpBz2' => new AdminerDumpBz2(),
    'AdminerDumpDate' => new AdminerDumpDate(),
    'AdminerDumpJson' => new AdminerDumpJson(),
    'AdminerDumpXml' => new AdminerDumpXml(),
    'AdminerDumpZip' => new AdminerDumpZip(),
    'AdminerFkDisable' => new AdminerFkDisable(),
    'AdminerForeignKeys' => new AdminerForeignKeys(),
    'AdminerForeignSystem' => new AdminerForeignSystem(),
    'AdminerImportFromFolder' => new AdminerImportFromFolder(),
    'AdminerPHPSerializedColumn' => new AdminerPHPSerializedColumn(),
    'FillLoginForm' => new FillLoginForm(
        'server',
        'localhost'
    ),
    //Para configurarlo en producción sin que funcione
    'AdminerLoginPasswordLess' => new AdminerLoginPasswordLess(
        password_hash(sha1(uniqid("", true)), \PASSWORD_DEFAULT),
    ),
];
if (_DEV_MODE_) {
    $lessPasswordLoginPhrase = '123456789456123456';
    $plugins['AdminerLoginPasswordLess'] = new AdminerLoginPasswordLess(password_hash($lessPasswordLoginPhrase, \PASSWORD_DEFAULT)); //Solo para inicio sin contraseña en desarrollo
    $plugins['FillLoginForm'] = new FillLoginForm(
        'server',
        'localhost',
        'admin', //Solo para inicio sin contraseña en desarrollo
        $lessPasswordLoginPhrase //Solo para inicio sin contraseña en desarrollo
    );
}

return $plugins;
