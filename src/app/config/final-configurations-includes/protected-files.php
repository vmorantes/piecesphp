<?php

use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use Publications\Controllers\PublicationsController;

$uploadsDir = get_config('upload_dir');

//Ejemplo de protección de archivos
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR), function (Request $request, string $filePath) {
    //return SessionToken::isActiveSession(SessionToken::getJWTReceived()); //Validar sesión
    return true;
});
