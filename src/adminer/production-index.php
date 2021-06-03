<?php
function adminer_object()
{

    include_once "./plugins/plugin.php";
    foreach (glob("plugins/*.php") as $filename) {
        include_once "./$filename";
    }

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
        new FillLoginForm(
            'server',
            'localhost'
        ),
    );

    return new AdminerPlugin($plugins);
}

// include original Adminer or Adminer Editor
include "./adminer.php";
