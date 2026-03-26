<?php

use PiecesPHP\Terminal\CliActions;
use React\EventLoop\Loop;

CliActions::make('loop-sample', function ($args) {
    set_config('terminal_color', 'green');
    echoTerminal("Iniciando motor reactivo...");
    $maxLoops = 100;
    $counter = 0;
    Loop::addPeriodicTimer(0.005, function () use ($maxLoops, &$counter) {
        $counter++;
        echoTerminal("Revisando tareas pendientes en DB ({$counter}/{$maxLoops})...");
        if ($counter >= $maxLoops) {
            Loop::stop();
        }
    });
    Loop::run();
})->setDescription('Ejecuta un loop de muestra')->register();
