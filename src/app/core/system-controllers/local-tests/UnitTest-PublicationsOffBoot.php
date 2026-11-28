<?php

//H368: encendido, todo sigue registrado; y cada sitio que depende de Publicaciones mira su interruptor.
//El arranque con el módulo apagado y sin tablas pide cambiar constants.php: no cabe en una suite.
//@areas src/app/classes/Publications/ src/app/classes/SystemApprovals/

use PiecesPHP\Core\Statics\ProtectFileMiddleware;
use PiecesPHP\Terminal\CliActions;
use PiecesPHP\Terminal\CronJobTask;
use Publications\PublicationsRoutes;
use SystemApprovals\SystemApprovalsRoutes;
use SystemApprovals\Util\Packages\PublicationsApprovalHandler;
use SystemApprovals\Util\SystemApprovalManager;

CliActions::make('unit-tests:core/publications-off-boot', function ($args) {

    echoTerminal("\e[33m[TEST:publications-off-boot] Publicaciones apagado no toca sus tablas\e[39m");
    echoTerminal('');

    $passed = 0;
    $failed = 0;
    $check = function (bool $condition, string $name, string $detail = '') use (&$passed, &$failed): void {
        if ($condition) {
            $passed++;
            echoTerminal("   \e[32m[PASÓ]\e[39m {$name}");
        } else {
            $failed++;
            echoTerminal("   \e[31m[FALLÓ]\e[39m {$name}" . ($detail !== '' ? " — {$detail}" : ''));
        }
    };

    /** Código de un archivo sin comentarios: una mención en un comentario no es una guarda. */
    $code = function (string $relative): string {
        $clean = '';
        foreach (token_get_all((string) file_get_contents(basepath($relative))) as $token) {
            if (is_array($token)) {
                if ($token[0] !== T_COMMENT && $token[0] !== T_DOC_COMMENT) {
                    $clean .= $token[1];
                }
            } else {
                $clean .= $token;
            }
        }
        return (string) preg_replace('/\s+/', ' ', $clean);
    };

    try {

        //──── 1. Con el módulo encendido, como antes ─────────────────────────────────────────
        echoTerminal('[1] Con Publicaciones y las aprobaciones encendidas, todo sigue registrado');
        if (!PublicationsRoutes::ENABLE || !SystemApprovalsRoutes::enabled()) {
            echoTerminal("   \e[33m[OMITIDA]\e[39m esta instalación tiene Publicaciones o las aprobaciones apagadas.");
        } else {
            $handlers = (new \ReflectionProperty(SystemApprovalManager::class, 'configurations'))->getValue(SystemApprovalManager::getInstance());
            $check(in_array(PublicationsApprovalHandler::class, $handlers, true) && PublicationsApprovalHandler::isEnabled(), '1a el manejador de aprobaciones de publicaciones está cargado');
            $names = array_map(fn ($task): string => $task instanceof CronJobTask ? $task->getName() : '', CronJobTask::getCronJobs());
            $check(in_array('Sincronizar visibilidad de publicaciones', $names, true), '1b la tarea de visibilidad está registrada');
            $protected = array_filter(array_keys(ProtectFileMiddleware::getProtectedDirectories()), fn ($directory): bool => str_ends_with(str_replace('\\', '/', (string) $directory), '/statics/uploads/publications'));
            $check(count($protected) === 1, '1c statics/uploads/publications/ está protegida por el framework');
            $check(array_key_exists('api-admin-publications-actions', (array) get_routes()), '1d la acción de publicaciones de la API existe');
        }
        echoTerminal(' ');

        //──── 2. Cada sitio que depende de Publicaciones mira su interruptor ──────────────────
        echoTerminal('[2] Lo que depende de Publicaciones mira PublicationsRoutes::ENABLE, el interruptor de routes.php y del menú');
        $guards = [
            '2a el manejador de aprobaciones (también mira las aprobaciones)' => ['app/classes/SystemApprovals/Util/Packages/PublicationsApprovalHandler.php', 'return \\Publications\\PublicationsRoutes::ENABLE && \\SystemApprovals\\SystemApprovalsRoutes::enabled() && parent::isEnabled();'],
            '2b la tarea de visibilidad' => ['app/core/extensions/cronjobs.php', "if (\\Publications\\PublicationsRoutes::ENABLE) { \$cronjobs[] = CronJobTask::make('Sincronizar visibilidad de publicaciones'"],
            '2c la protección de su carpeta' => ['app/core/extensions/protected-files.php', 'if (PublicationsRoutes::ENABLE) { ProtectFileMiddleware::protect(append_to_path_system($uploadsDir, PublicationsController::UPLOAD_DIR)'],
            '2d la acción de la API' => ['app/classes/API/Controllers/APIController.php', '...(\\Publications\\PublicationsRoutes::ENABLE ? [new Route('],
            '2e «Acerca de»' => ['app/classes/PiecesPHP/AdminPanel/Controllers/AdminPanelController.php', "\$publicationsListLink = \\Publications\\PublicationsRoutes::ENABLE ? PublicationsController::routeName('list') : '';"],
            '2f el informe general' => ['app/classes/ReportsManage/Queries/ReportsManageQueries.php', 'if (!\\Publications\\PublicationsRoutes::ENABLE) { return 0; }'],
            '2g el formulario de aprobación de una publicación' => ['app/classes/SystemApprovals/Controllers/SystemApprovalsController.php', "if (\\Publications\\PublicationsRoutes::ENABLE) { \$formByType[PublicationMapper::TABLE] = 'forms/approval-publications'; }"],
        ];
        foreach ($guards as $name => [$file, $needle]) {
            $check(str_contains($code($file), $needle), $name, "{$file} no lleva: {$needle}");
        }
        echoTerminal(' ');

    } catch (\Throwable $e) {
        $check(false, 'la suite corre entera', get_class($e) . ': ' . mb_substr($e->getMessage(), 0, 300) . ' @' . $e->getLine());
    }

    echoTerminal(' ');
    $total = $passed + $failed;
    echoTerminal($failed === 0
        ? "\e[32m BALANCE FINAL: {$passed}/{$total} PASADAS \e[39m"
        : "\e[31m BALANCE FINAL: {$passed}/{$total} PASADAS, {$failed} FALLIDAS \e[39m");

    return ['success' => $failed === 0, 'message' => "{$passed}/{$total}"];

})->setDescription('H368: con Publicaciones apagado, nada del framework toca sus tablas ni su carpeta.')->setEffects([CliActions::EFFECT_NONE])->register();
