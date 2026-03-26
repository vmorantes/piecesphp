<?php

/**
 * Parche para librería aminyazdanpanah/php-ffmpeg-video-streaming
 * No declara tipos nullables.
 * @internal vendor/aminyazdanpanah/php-ffmpeg-video-streaming/src/helpers.php
 */
if (!function_exists('ffmpeg')) {
    /**
     * @param array $config
     * @param ?\Psr\Log\LoggerInterface $logger
     * @param ?\FFMpeg\FFProbe $probe
     * @return \Streaming\FFMpeg
     */
    function ffmpeg(array $config = [],  ? \Psr\Log\LoggerInterface $logger = null,  ? \FFMpeg\FFProbe $probe = null) : \Streaming\FFMpeg
    {
        return \Streaming\FFMpeg::create($config, $logger, $probe);
    }
}
