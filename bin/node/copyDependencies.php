<?php

# Definimos origen y destino separados por un espacio
$filesToCopy=[
    //Mapbox
    "node_modules/mapbox-v2.6.0/dist/mapbox-gl.js" => "src/statics/plugins/mapbox/v2.6.0/mapbox-gl.js",
    "node_modules/mapbox-v2.6.0/dist/mapbox-gl.js" => "src/statics/plugins/mapbox/v2.6.0/mapbox-gl.js",
    "node_modules/mapbox-v2.6.0/dist/mapbox-gl.css" => "src/statics/plugins/mapbox/v2.6.0/mapbox-gl.css",
    "node_modules/mapbox-v3.4.0/dist/mapbox-gl.js" => "src/statics/plugins/mapbox/v3.4.0/mapbox-gl.js",
    "node_modules/mapbox-v3.4.0/dist/mapbox-gl.css" => "src/statics/plugins/mapbox/v3.4.0/mapbox-gl.css",
    //Mapbox Geocoder
    "node_modules/mapbox-geocoder-v2.3.0/dist/mapbox-gl-geocoder.min.js" => "src/statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.min.js",
    "node_modules/mapbox-geocoder-v2.3.0/dist/mapbox-gl-geocoder.css" => "src/statics/plugins/mapbox/geocoder/v2.3.0/mapbox-gl-geocoder.css",
    //Cropper
    "node_modules/cropperjs/dist/cropper.min.js" => "src/statics/plugins/cropper/cropper.min.js",
    "node_modules/cropperjs/dist/cropper.min.css" => "src/statics/plugins/cropper/cropper.min.css"
];

foreach ($filesToCopy as $src => $dest) {


    $baseProjectPatch = realpath(__DIR__ . "/../../");
    $src = $baseProjectPatch . "/" . $src;
    $dest = $baseProjectPatch . "/" . $dest;
    
    # Creamos el directorio de destino si no existe
    if (!is_dir(dirname($dest))) {
        mkdir(dirname($dest), 0777, true);
    }    
    # Copiamos el archivo
    if(file_exists($src)) {
        copy($src, $dest);
        echo "Archivo copiado: $src -> $dest\n";
    }else{
        echo "Archivo no encontrado: $src\n";
    }
}