<?php
namespace PiecesPHP;

/**
 * Cli
 *
 * Gestiona los argumentos de la línea de comandos.
 *
 * @package PiecesPHP
 * @author  Vicsen Morantes <sir.vamb@gmail.com>
 */
class Cli
{
    /** @var string */
    public readonly string $scriptPath;

    /** @var string */
    public readonly string $scriptName;

    /** @var string|null */
    protected $command = null;

    /** @var array<string,mixed> */
    protected $arguments = [];
    /** @var array<int, string> */
    protected $argumentsOrder = [];

    /** @var array<string,mixed> Array original de argumentos originales de la instancia */
    public readonly array $orginalArguments;
    /** @var string|null Comando original de la instancia */
    public readonly ?string $originalCommand;
    /** @var array<int, string> Array original de argumentos originales de la instancia */
    public readonly array $orginalArgumentsOrder;
    /** @var bool Indica si la instancia fue creada desde la plataforma CLI */
    public readonly bool $isCliPlatform;

    /**
     * @param string[] $argv Debe ser el array global $argv
     * @param array{addLines:bool} $options Opciones adicionales
     * - option.addLines: Si es true, se le agrega -- a los argumentos que no lo tengan
     */
    public function __construct(array $argv, array $options = [])
    {
        $addLines = $options['addLines'] ?? true;
        $this->scriptPath = $argv[0] ?? uniqid('ERROR_');
        $this->scriptName = basename($this->scriptPath);
        $this->parse($argv, $addLines);
        $this->orginalArguments = $this->arguments;
        $this->originalCommand = $this->command;
        $this->orginalArgumentsOrder = $this->argumentsOrder;
        $isCli = defined('STDIN');
        if (defined('PHP_SAPI')) {
            $isCli = PHP_SAPI == 'cli' && $isCli;
        }
        $this->isCliPlatform = $isCli;
    }

    public function setCommand(string $command): self
    {
        $this->command = $command;
        return $this;
    }

    public function removeArgument(string $name): self
    {
        unset($this->arguments[$name]);
        $this->argumentsOrder = array_values(array_filter($this->argumentsOrder, function ($argumentName) use ($name) {
            return $argumentName !== $name;
        }));
        return $this;
    }

    /**
     * Procesa los argumentos de la línea de comandos.
     *
     * @param string[] $argv
     * @param bool $addLines
     * @return void
     */
    protected function parse(array $argv, bool $addLines): void
    {
        $this->command = $argv[1] ?? null;
        $argvCopy = array_slice($argv, 2);
        $this->argumentsOrder = [];
        foreach ($argvCopy as $arg) {
            $parts = explode('=', $arg);
            $argumentName = $parts[0] ?? null;
            $argumentValue = $parts[1] ?? true;
            if ($argumentName !== null) {
                if ($addLines) {
                    // Si no tiene -- se le agrega
                    $argumentName = mb_strpos($argumentName, '--') === 0 ? $argumentName : '--' . $argumentName;
                }
                $this->arguments[$argumentName] = $argumentValue;
                $this->argumentsOrder[] = $argumentName;
            }
        }
    }

    /**
     * @return string|null
     */
    public function getCommand(): ?string
    {
        return $this->command;
    }

    /**
     * @param int $position Posiciones desde 0
     * @return array{name:string,value:mixed}
     */
    public function getArgumentByPosition(int $position): ?array
    {
        $argumentName = $this->argumentsOrder[$position] ?? null;
        if ($argumentName !== null) {
            return [
                'name' => $argumentName,
                'value' => $this->arguments[$argumentName],
            ];
        }
        return null;
    }

    /**
     * Retorna los argumentos en el formato de lista solicitado originalmente.
     *
     * @return array{name:string,value:mixed}[]
     */
    public function getArguments(): array
    {
        $result = [];
        foreach ($this->arguments as $name => $value) {
            $result[] = [
                'name' => $name,
                'value' => $value,
            ];
        }
        return $result;
    }

    /**
     * Verifica si un argumento existe.
     *
     * @param string $name
     * @return bool
     */
    public function argumentExists(string $name): bool
    {
        return isset($this->arguments[$name]);
    }

    /**
     * Obtiene el valor de un argumento.
     *
     * @param string $name
     * @return mixed
     */
    public function getArgumentValue(string $name): mixed
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Retorna la estructura de array compatible con la función original cli().
     *
     * @return array{command:?string,arguments:array{name:string,value:mixed,order:int}[],operations:array{argumentExists:callable(string):bool,getArgumentValue:callable(string):mixed}}
     */
    public function toArray(): array
    {
        return [
            'command' => $this->getCommand(),
            'arguments' => $this->getArguments(),
            'operations' => [
                'argumentExists' => function (string $name): bool {
                    return $this->argumentExists($name);
                },
                'getArgumentValue' => function (string $name): mixed {
                    return $this->getArgumentValue($name);
                },
            ],
        ];
    }

    /**
     * Genera una salida en la terminal con formato (colores y estilos)
     *
     * @param string $text Texto a mostrar
     * @param array{
     *  color:?string|int,
     *  background:?string|int,
     *  bold:?bool|int,
     *  dim:?bool|int,
     *  italic:?bool|int,
     *  underline:?bool|int,
     *  blink:?bool|int,
     *  reverse:?bool|int,
     *  hidden:?bool|int,
     *  strike:?bool|int,
     *  newLine:?bool,
     *  newLineChars:?string
     * }|string[]|int[] $format Configuración: color, background, estilos, o lista de formatos (nombres o códigos ANSI)
     * @return string Texto formateado con secuencias ANSI
     * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting
     */
    public static function systemOutFormatted(string $text, array $format = []): string
    {
        $colorsMapping = [
            'default' => 39,
            'black' => 30,
            'red' => 31,
            'green' => 32,
            'yellow' => 33,
            'blue' => 34,
            'magenta' => 35,
            'cyan' => 36,
            'light-gray' => 37,
            'dark-gray' => 90,
            'light-red' => 91,
            'light-green' => 92,
            'light-yellow' => 93,
            'light-blue' => 94,
            'light-magenta' => 95,
            'light-cyan' => 96,
            'white' => 97,
        ];
        $backgroundColorsMapping = [
            'default' => 49,
            'black' => 40,
            'red' => 41,
            'green' => 42,
            'yellow' => 43,
            'blue' => 44,
            'magenta' => 45,
            'cyan' => 46,
            'light-gray' => 47,
            'dark-gray' => 100,
            'light-red' => 101,
            'light-green' => 102,
            'light-yellow' => 103,
            'light-blue' => 104,
            'light-magenta' => 105,
            'light-cyan' => 106,
            'white' => 107,
        ];
        $optionsMapping = [
            'bold' => 1,
            'dim' => 2,
            'italic' => 3,
            'underline' => 4,
            'blink' => 5,
            'reverse' => 7,
            'hidden' => 8,
            'strike' => 9,
        ];

        $getConfig = function_exists('get_config') ? 'get_config' : null;
        $globalColor = $getConfig ? $getConfig('terminal_color') : null;
        $globalOptions = $getConfig ? $getConfig('terminal_format_options') : null;

        $defaults = [
            'background' => 'default',
            'color' => $globalColor ?? 'default',
            'newLine' => true,
            'newLineChars' => "\r\n",
        ];

        if (is_array($globalOptions)) {
            $defaults = array_merge($defaults, $globalOptions);
        }

        $format = array_merge($defaults, $format);

        $codes = [];

        foreach ($format as $key => $value) {

            if (is_numeric($key)) {
                // Valores de lista: systemOutFormatted('txt', ['red', 'bold'])
                if (isset($colorsMapping[$value])) {
                    $codes[] = $colorsMapping[$value];
                } elseif (isset($optionsMapping[$value])) {
                    $codes[] = $optionsMapping[$value];
                } elseif (is_numeric($value)) {
                    $codes[] = (int) $value;
                }
            } else {
                // Claves nominales: ['color' => 'red', 'bold' => true]
                if ($key === 'color') {
                    if (isset($colorsMapping[$value])) {
                        $codes[] = $colorsMapping[$value];
                    } elseif (is_numeric($value)) {
                        $codes[] = (int) $value;
                    }
                } elseif ($key === 'background') {
                    if (isset($backgroundColorsMapping[$value])) {
                        $codes[] = $backgroundColorsMapping[$value];
                    } elseif (is_numeric($value)) {
                        $codes[] = (int) $value;
                    }
                } elseif (isset($optionsMapping[$key])) {
                    if ($value === true || $value === 1 || $value === (string) $optionsMapping[$key]) {
                        $codes[] = $optionsMapping[$key];
                    }
                }
            }
        }

        $codes = array_unique($codes);
        sort($codes); // Ordenar para consistencia
        $prefix = count($codes) > 0 ? "\033[" . implode(';', $codes) . "m" : "";
        $suffix = count($codes) > 0 ? "\033[0m" : "";

        $formattedString = $prefix . $text . $suffix;

        if (defined('STDOUT')) {
            fwrite(STDOUT, $formattedString . ($format['newLine'] ? $format['newLineChars'] : ''));
            flush();
        }

        return $formattedString;
    }
}
