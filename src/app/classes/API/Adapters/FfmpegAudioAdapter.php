<?php
namespace API\Adapters;

use API\APILang;
use Exception;
use FFMpeg\FFMpeg;
use FFMpeg\Format\Audio\Wav;

/**
 * FfmpegAudioAdapter.
 *
 * @package     API\Adapters
 */
class FfmpegAudioAdapter
{
    /**
     * @var string
     */
    protected $ffmpegPath;
    /**
     * @var string
     */
    protected $ffprobePath;
    /**
     * @var FFMpeg
     */
    protected $ffmpeg;

    public function __construct(?string $ffmpegPath = null, ?string $ffprobePath = null)
    {
        // Posibles rutas por defecto comunes en servidores Linux/Mac
        $commonPathsFfmpeg = [
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            '/bin/ffmpeg',
            '/opt/homebrew/bin/ffmpeg',
        ];

        $commonPathsFfprobe = [
            '/usr/bin/ffprobe',
            '/usr/local/bin/ffprobe',
            '/bin/ffprobe',
            '/opt/homebrew/bin/ffprobe',
        ];

        // Función ayudante interna para encontrar el ejecutable
        $findBinary = function (array $paths, ?string $providedPath = null) {
            if ($providedPath !== null && file_exists($providedPath)) {
                return $providedPath;
            }
            // Escaneo silencioso en rutas comunes
            foreach ($paths as $path) {
                if (file_exists($path) && is_executable($path)) {
                    return $path;
                }
            }
            return null; // No lo encontramos
        };

        $this->ffmpegPath = $findBinary($commonPathsFfmpeg, $ffmpegPath);
        $this->ffprobePath = $findBinary($commonPathsFfprobe, $ffprobePath);

        $ffmpegExists = is_string($this->ffmpegPath) && file_exists($this->ffmpegPath);
        $ffprobeExists = is_string($this->ffprobePath) && file_exists($this->ffprobePath);

        if ($ffmpegExists && $ffprobeExists) {
            //Configurar FFmpeg
            $this->ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => $this->ffmpegPath,
                'ffprobe.binaries' => $this->ffprobePath,
            ]);

        } else {
            throw new Exception(__(APILang::LANG_GROUP, 'Debe instalar ffmpeg y ffprobe (o configurar las rutas correctamente)'));
        }
    }

    /**
     * Convierte un archivo de audio a WAV.
     *
     * @param string $inputFile Ruta del archivo de entrada
     * @param string $outputFile Ruta del archivo de salida (WAV)
     * @return string Ruta del archivo convertido
     */
    public function convertToWav(string $inputFile, string $outputFile): string
    {
        $audio = $this->ffmpeg->open($inputFile);
        $audio->save(new Wav(), $outputFile);
        return $outputFile;
    }

    /**
     * Repara la duración de un archivo WebM procesándolo a través de FFMpeg.
     *
     * @param string $inputFile  Ruta al archivo original (.webm)
     * @param string $tmpFile    Ruta temporal de trabajo (.tmp.wav)
     * @param string $outputFile Ruta de salida corregida (.fix.webm)
     * @return bool True si tuvo éxito, False si falló
     */
    public function fixWebmDuration(string $inputFile, string $tmpFile, string $outputFile): bool
    {
        try {
            // Utilizamos el driver interno de PHP-FFMpeg (que maneja procesos de forma segura
            // saltándose las restricciones de shell_exec/exec típicamente mediante Symfony Process)
            $driver = $this->ffmpeg->getFFMpegDriver();

            // 1. Convertir de webm averiado a wav temporal
            $command1 = [
                '-y',
                '-v', 'warning',
                '-i', $inputFile,
                $tmpFile,
            ];
            $driver->command($command1);

            // 2. Convertir de wav temporal a webm limpio usando Opus (reparando duración)
            $command2 = [
                '-y',
                '-v', 'warning',
                '-i', $tmpFile,
                '-c:a', 'libopus',
                $outputFile,
            ];
            $driver->command($command2);

            // 3. Limpieza del temporal
            if (file_exists($tmpFile)) {
                @unlink($tmpFile);
            }

            return true;

        } catch (Exception $e) {
            log_exception($e);
            return false;
        }
    }

}