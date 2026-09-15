<?php

use Documents\Controllers\DocumentsController;
use News\Controllers\NewsCategoryController;
use Organizations\Controllers\OrganizationsController;
use PiecesPHP\Core\Statics\ProtectedUploads;
use PiecesPHP\Core\Statics\ProtectFileMiddleware;
use Publications\Controllers\PublicationsController;

$uploadsDir = get_config('upload_dir');

//ARQUETIPO: cada carpeta de subidas se protege aquí, con el UPLOAD_DIR de su controlador y el validador que
//decida su dueño. La que se sirve sin proteger se declara en files/dev/upload-dirs.json (verify-integrity, 29).
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR), [PublicationsController::class, 'uploadedFileValidator']);
ProtectFileMiddleware::protectWithSession(append_to_path_system($uploadsDir, DocumentsController::UPLOAD_DIR));
ProtectFileMiddleware::protectWithSession(append_to_path_system($uploadsDir, OrganizationsController::UPLOAD_DIR));
ProtectFileMiddleware::protectWithSession(append_to_path_system($uploadsDir, NewsCategoryController::UPLOAD_DIR));

//Lo privado lleva el sufijo en disco (foto.jpg.protected): este .htaccess niega pedirlo por ese nombre.
if (!ProtectedUploads::writeDenyHtaccess($uploadsDir)) {
    log_exception(new \RuntimeException("statics: no se pudo escribir el .htaccess que niega el sufijo en {$uploadsDir}."));
}
