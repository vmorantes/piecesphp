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

    /**
     */
    public function __construct(?string $ffmpegPath, ?string $ffprobePath)
    {
        $this->ffmpegPath = $ffmpegPath !== null ? $ffmpegPath : clean_string(shell_exec('which ffmpeg') ?: shell_exec('where ffmpeg'));
        $this->ffprobePath = $ffprobePath !== null ? $ffprobePath : clean_string(shell_exec('which ffprobe') ?: shell_exec('where ffprobe'));
        $ffmpegExists = is_string($this->ffmpegPath) && file_exists($this->ffmpegPath);
        $ffprobeExists = is_string($this->ffprobePath) && file_exists($this->ffprobePath);

        if ($ffmpegExists && $ffprobeExists) {
            //Configurar FFmpeg
            $this->ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => $this->ffmpegPath,
                'ffprobe.binaries' => $this->ffprobePath,
            ]);

        } else {
            throw new Exception(__(APILang::LANG_GROUP, 'Debe instalar ffmpeg y ffprobe'));
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
                $tmpFile
            ];
            $driver->command($command1);

            // 2. Convertir de wav temporal a webm limpio usando Opus (reparando duración)
            $command2 = [
                '-y',
                '-v', 'warning',
                '-i', $tmpFile,
                '-c:a', 'libopus',
                $outputFile
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
