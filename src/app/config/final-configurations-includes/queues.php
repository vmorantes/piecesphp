<?php

use PiecesPHP\Core\Http\FreezeRequest;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\Core\Routing\Slim3Compatibility\Http\StatusCode;
use PiecesPHP\Core\TestQueueRequest;
use PiecesPHP\Terminal\QueueHandlerResponse;
use PiecesPHP\Terminal\QueueTask;

/**
 * @var array<int, QueueTask>
 */
$queueHandlers = [];

// Manejador de prueba robusto
$queueHandlers[] = QueueTask::make(TestQueueRequest::QUEUE_NAME, function ($data) {

    $freezeRequest = FreezeRequest::fromArray($data);
    $request = $freezeRequest->inject();
    $response = new ResponseRoute(StatusCode::HTTP_OK);
    $controller = new TestQueueRequest();
    $response = $controller->process($request, $response, $freezeRequest->getCustomData());
    $responseJSON = $response->getRawJsonDataInserted();
    /*
        Puede devolver:
        - QueueHandlerResponse::success()
        - QueueHandlerResponse::fail()
        - QueueHandlerResponse::wait()
    */
    $queueResult = $responseJSON['success'] ?? false
    ? QueueHandlerResponse::success($responseJSON['message'] ?? 'Tarea completada')
    : (
        $responseJSON['retry'] ?? false
        ? QueueHandlerResponse::wait($responseJSON['message'] ?? 'Tarea pospuesta')
        : QueueHandlerResponse::fail($responseJSON['message'] ?? 'Tarea fallida')
    );
    if ($responseJSON['success'] ?? false) {
        $freezeRequest->cleanupFiles();
    }
    return $queueResult;
});

// Registrar los manejadores en la configuración global
QueueTask::addQueueHandlers($queueHandlers);