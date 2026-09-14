<?php

use PiecesPHP\Core\Helpers\Directories\ProtectFileMiddleware;
use Publications\Controllers\PublicationsController;

$uploadsDir = get_config('upload_dir');

//ARQUETIPO: cada carpeta de subidas se protege aquí, con el UPLOAD_DIR de su controlador y el validador que
//decida su dueño. La que se sirve sin proteger se declara en files/dev/upload-dirs.json (verify-integrity, 29).
ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR), [PublicationsController::class, 'uploadedFileValidator']);
