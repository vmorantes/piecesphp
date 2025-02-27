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

}
