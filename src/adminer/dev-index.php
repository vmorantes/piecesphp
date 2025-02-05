<?php
function adminer_object()
{

    include_once "./plugins/plugin.php";
    foreach (glob("plugins/*.php") as $filename) {
        include_once "./$filename";
    }

    class CustomAdminer extends AdminerPlugin
    {

        function permanentLogin($i = false)
        {
            return '4476c9dccdc083dd8bcf72bcb1c8f1db';
        }

    }

    $lessPasswordLoginPhrase = '123456789456123456';

    $plugins = array(
        new AdminerDisplayForeignKeyName(),
        new AdminerDumpAlter(),
        new AdminerDumpArray(),
        new AdminerDumpBz2(),
        new AdminerDumpDate(),
        new AdminerDumpJson(),
        new AdminerDumpXml(),
        new AdminerDumpZip(),
        new AdminerFkDisable(),
        new AdminerForeignKeys(),
        new AdminerForeignSystem(),
        new AdminerImportFromFolder(),
        new AdminerPHPSerializedColumn(),
        new AdminerLoginPasswordLess(password_hash($lessPasswordLoginPhrase, \PASSWORD_DEFAULT)), //Solo para inicio sin contraseña en desarrollo
        new FillLoginForm(
            'server',
            'localhost',
            'admin', //Solo para inicio sin contraseña en desarrollo
            $lessPasswordLoginPhrase //Solo para inicio sin contraseña en desarrollo
        ),
    );

    return new CustomAdminer($plugins);
}

// include original Adminer or Adminer Editor
include "./adminer.php";
