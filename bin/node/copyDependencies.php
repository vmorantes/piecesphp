<?php

# Definimos origen y destino separados por un espacio
$filesToCopy=[
    //Mapbox
    "node_modules/mapbox-v3.19.0/dist/mapbox-gl.js" => "src/statics/plugins/mapbox/v3.19.0/mapbox-gl.js",
    "node_modules/mapbox-v3.19.0/dist/mapbox-gl.css" => "src/statics/plugins/mapbox/v3.19.0/mapbox-gl.css",
    //Mapbox Geocoder
    "node_modules/mapbox-geocoder-v2.3.0/dist/mapbox-gl-geocoder.min.js" => "src/statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.min.js",
    "node_modules/mapbox-geocoder-v2.3.0/dist/mapbox-gl-geocoder.css" => "src/statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.css",
    //Cropper
    "node_modules/cropperjs/dist/cropper.min.js" => "src/statics/plugins/cropper/cropper.min.js",
    "node_modules/cropperjs/dist/cropper.min.css" => "src/statics/plugins/cropper/cropper.min.css"
];

$nuevos = 0;
$cambiados = 0;
$ausentes = 0;

foreach ($filesToCopy as $src => $dest) {


    $baseProjectPatch = realpath(__DIR__ . "/../../");
    $src = $baseProjectPatch . "/" . $src;
    $dest = $baseProjectPatch . "/" . $dest;
    
    # Creamos el directorio de destino si no existe
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0777, true);
    }    
    # Copiamos el archivo
    if (file_exists($src)) {

        # LO QUE VA A CAMBIAR SE DICE ANTES DE CAMBIARLO. Copiar callando convierte una
        # actualizacion de libreria en una linea de «archivo copiado». Ver T90.
        if (file_exists($dest)) {
            $antes = sha1_file($dest);
            $ahora = sha1_file($src);
            if ($antes !== $ahora) {
                echo "AVISO: el destino CAMBIA de contenido.\n";
                echo "       {$dest}\n";
                echo "       sha1 {$antes} -> {$ahora}\n";
                $cambiados++;
            }
        } else {
            echo "NUEVO: {$dest}\n";
            $nuevos++;
        }

        copy($src, $dest);
        echo "Archivo copiado: $src -> $dest\n";
    } else {
        echo "Archivo NO ENCONTRADO en node_modules: $src\n";
        echo "       Si el alias de package.json cambio de nombre o de version, corre `npm install`.\n";
        $ausentes++;
    }
}

echo "\n";
echo "Resumen: {$nuevos} nuevo(s), {$cambiados} con contenido distinto, {$ausentes} sin origen.\n";
if ($cambiados > 0) {
    echo "REVISA los que cambian ANTES de commitearlos: un cambio de contenido aqui es un cambio\n";
    echo "de libreria, y el mensaje del commit tiene que decirlo.\n";
}
exit($ausentes > 0 ? 1 : 0);