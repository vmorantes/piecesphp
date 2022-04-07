<?php

$resultPath = __DIR__ . '/../PHPStanResult.txt';

if (file_exists($resultPath)) {
    $content = file_get_contents($resultPath);
    $content = preg_replace('/(  )Line   (.*)/im', '$1project://src/app/$2', $content);
    $content = preg_replace('/Parameter #(\d)/im', 'Parameter Nr.$1', $content);    
    if (is_string($content) && mb_strlen($content) > 0) {
        file_put_contents($resultPath, $content);
    }
}
