<?php

/**
 * TestQueueRequest.php
 */

namespace PiecesPHP\Core;

use PiecesPHP\Core\Forms\UploadedFileAdapter;
use PiecesPHP\Core\Http\FreezeRequest;
use PiecesPHP\Terminal\QueueTask;
use \PiecesPHP\Core\Routing\RequestRoute as Request;
use \PiecesPHP\Core\Routing\ResponseRoute as Response;

/**
 * TestQueueRequest.
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 */
class TestQueueRequest
{

    const QUEUE_NAME = 'piecesphp-core-test-queue-request-testing-queue';

    /** @ignore */
    public function __construct()
    {}

    public function form(Request $request, Response $response, array $args): Response
    {
        include __DIR__ . '/../system-views/queue-request.php';
        return $response;
    }

    public function process(Request $request, Response $response, array $args): Response
    {
        $reponseJSON = [
            'success' => false,
            'retry' => false,
            'message' => 'Procesado',
        ];

        $baseUploadDir = get_config('upload_dir');
        $uploadsDirectory = append_to_path_system($baseUploadDir, 'testing-queue-request/' . uniqid());

        if (file_exists($baseUploadDir) && is_dir($baseUploadDir)) {
            if (!file_exists($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0777, true);
            }

            $namesOnFiles = [
                'single_file',
                'collection',
                'nested',
                'portfolio',
                'collection2',
            ];
            foreach ($namesOnFiles as $nameOnFiles) {
                $associativePathUploade = UploadedFileAdapter::findAssociativePathsByName($nameOnFiles, $_FILES);
                foreach ($associativePathUploade as $path) {
                    $file = new UploadedFileAdapter($path);
                    $file->validate(true);
                    $file->copyTo($uploadsDirectory, null, null, false, true, true);
                }
            }
            $reponseJSON['success'] = true;
            $reponseJSON['message'] = 'Procesado';
        }
        return $response->withJson($reponseJSON);
    }

    public function handle(Request $request, Response $response, array $args): Response
    {
        $freezeRequest = FreezeRequest::capture($request->getBody()->getContents(), array_merge($args, ['param' => 'value']), uniqid(), basepath('tmp/queue/testing'));
        QueueTask::dispatch(self::QUEUE_NAME, $freezeRequest->toArray(), 3);
        return $response->withJson([
            'status' => 'success',
        ]);
    }
}
