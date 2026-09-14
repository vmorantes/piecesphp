<?php

use Documents\Controllers\DocumentsController;
use News\Controllers\NewsCategoryController;
use Organizations\Controllers\OrganizationsController;
use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use PiecesPHP\Core\Routing\RequestRoute as Request;
use PiecesPHP\Core\SessionToken;
use Publications\Controllers\PublicationsController;

$uploadsDir = get_config('upload_dir');

//ARQUETIPO: cada carpeta de subidas se protege aquí, con el UPLOAD_DIR de su controlador y el validador que
//decida su dueño. La que se sirve sin proteger se declara en files/dev/upload-dirs.json (verify-integrity, 29).
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR), [PublicationsController::class, 'uploadedFileValidator']);

$activeSession = function (Request $request, string $filePath): bool {
    return SessionToken::isActiveSession((string) SessionToken::getJWTReceived());
};
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, DocumentsController::UPLOAD_DIR), $activeSession);
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, OrganizationsController::UPLOAD_DIR), $activeSession);
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, NewsCategoryController::UPLOAD_DIR), $activeSession);
