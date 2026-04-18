<?php

/**
 * ScanMissingLangTask.php
 */

namespace Terminal\Tasks;

use App\Model\UsersModel;
use PiecesPHP\Core\Config;
use PiecesPHP\Core\DataStructures\IntegerArray;
use PiecesPHP\Core\DataStructures\StringArray;
use PiecesPHP\Core\Route;
use PiecesPHP\Core\Routing\RequestRoute;
use PiecesPHP\Core\Routing\ResponseRoute;
use PiecesPHP\TerminalData;
use PiecesPHP\Terminal\Tasks\Abstracts\TerminalTaskAbstract;

/**
 * ScanMissingLangTask.
 *
 * Revisa los mensajes faltantes por traducción y genera un archivo php con ellos.
 *
 * @package     Terminal\Tasks
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2026
 * @see https://misc.flogisoft.com/bash/tip_colors_and_formatting Colores para texto de terminal
 */
class ScanMissingLangTask extends TerminalTaskAbstract
{

    public function __construct(string $startRoute = '', ?string $namePrefix = null)
    {
        //Procesar entrada
        $lastIsBar = last_char($startRoute) == '/';
        if ($startRoute == '/') {
            $startRoute = '';
        } elseif ($lastIsBar) {
            $startRoute = mb_substr($startRoute, 0, mb_strlen($startRoute) - 1);
        }
        $name = ($namePrefix !== null ? $namePrefix . '-' : '') . 'scan-missing-lang';

        //Permisos
        $permissions = [
            UsersModel::TYPE_USER_ROOT,
        ];
        //Establecer propiedades
        $this->description = new StringArray([
            "Revisa los mensajes faltantes por traducción y genera un archivo php con ellos.\r\n",
            "\tParámetros:\r\n",
            "\t  --exclude-lang Cadena separada por comas de idiomas a ignorar, i.e.: --exclude-lang=es,en\r\n",
            "\t  --exclude-group Cadena separada por comas de grupos a ignorar, i.e.: --exclude-group=general,public\r\n",
        ]);
        $this->route = "{$startRoute}/scan-missing-lang[/]";
        $this->controller = self::class . '::main';
        $this->name = $name;
        $this->alias = null;
        $this->method = 'GET';
        $this->requireLogin = true;
        $this->rolesAllowed = new IntegerArray($permissions);
        $this->defaultParamsValues = [];
        $this->middlewares = [];
    }

    public static function main(?RequestRoute $requestRoute = null, ?ResponseRoute $responseRoute = null, ?array $parameters = []): void
    {

        //Parámetros
        $arguments = TerminalData::getInstance()->arguments();

        $excludeLangName = '--exclude-lang';
        $excludeLang = isset($arguments[$excludeLangName]) && is_string($arguments[$excludeLangName]) ? explode(',', $arguments[$excludeLangName]) : [];

        $ignoreGroupName = '--exclude-group';
        $ignoreGroup = isset($arguments[$ignoreGroupName]) && is_string($arguments[$ignoreGroupName]) ? explode(',', $arguments[$ignoreGroupName]) : [];

        $noScanLangs = get_config('no_scan_langs');
        $noScanLangs = is_array($noScanLangs) ? $noScanLangs : [];
        $excludeLang = array_merge($excludeLang, $noScanLangs);

        //Mensaje de respuesta
        $titleTask = "Examinando mensajes de traducción faltantes";
        $message = [
            "\e[32m*** {$titleTask} ***\e[39m",
        ];
        $missingMessagesBaseFolderName = 'lang/missing-lang-messages';
        $missingMessagesBaseFolderPath = app_basepath("{$missingMessagesBaseFolderName}");

        //──── Acciones ──────────────────────────────────────────────────────────────────────────
        try {

            //Ejecutar llamadas a __ para verificar faltantes en cada lenguaje activo
            $allowedLangs = Config::get_allowed_langs();
            $additionalLangsToScan = get_config('additional_langs_to_scan');
            $additionalLangsToScan = is_array($additionalLangsToScan) ? $additionalLangsToScan : [];
            $allowedLangs = array_unique(array_merge($allowedLangs, $additionalLangsToScan));
            $langsMessagesCalls = self::searchFunctionUsesWithParser('__', app_basepath());
            $paramGroupNames = [];
            foreach ($langsMessagesCalls as $filePath => $calls) {
                foreach ($calls as $call) {
                    if (is_array($call)) {
                        $functionName = array_key_exists('function', $call) ? $call['function'] : null;
                        $functionParams = array_key_exists('params', $call) ? $call['params'] : [];
                        $paramGroupName = isset($functionParams[0]) ? $functionParams[0] : null;
                        $paramMessage = isset($functionParams[1]) ? $functionParams[1] : null;
                        $isValid = $functionName == '__' && count($functionParams) == 2;
                        $isValid = $isValid && is_string($paramGroupName) && is_string($paramMessage) && !str_starts_with($paramGroupName, '$') && !str_starts_with($paramMessage, '$');
                        if ($isValid) {
                            //Si el grupo es constante se resuelve el valor
                            if (str_starts_with($paramGroupName, '\\')) {
                                $paramGroupName = defined($paramGroupName) ? constant($paramGroupName) : $paramGroupName;
                            }
                            $paramGroupNames[] = $paramGroupName;
                            //Ejecutar mensaje
                            foreach ($allowedLangs as $lang) {
                                lang($paramGroupName, $paramMessage, $lang);
                            }
                        }
                    }
                }
            }
            $paramGroupNames = array_unique($paramGroupNames);

            //Agregar listado de mensajes faltantes
            if (file_exists($missingMessagesBaseFolderPath)) {

                $messagesFiles = glob($missingMessagesBaseFolderPath . '/*/*/*.to-translate');
                $messagesDataByLang = [];

                foreach ($messagesFiles as $messageFile) {

                    $messageFileRelative = trim(str_replace($missingMessagesBaseFolderPath, '', $messageFile));
                    $messageText = file_get_contents($messageFile);
                    $messageData = array_values(array_filter(explode('/', $messageFileRelative), fn($e) => is_string($e) && mb_strlen(trim($e)) > 0));

                    if (count($messageData) == 3) {
                        $groupName = $messageData[0];
                        $lang = $messageData[1];
                        if (!in_array($lang, $allowedLangs)) {
                            continue;
                        }
                        if (in_array($lang, $excludeLang)) {
                            continue;
                        }
                        if (in_array($groupName, $ignoreGroup)) {
                            continue;
                        }
                        if (!array_key_exists($lang, $messagesDataByLang)) {
                            $messagesDataByLang[$lang] = [];
                        }
                        if (!array_key_exists($groupName, $messagesDataByLang[$lang])) {
                            $messagesDataByLang[$lang][$groupName] = [];
                        }
                        if (!array_key_exists($messageText, $messagesDataByLang[$lang][$groupName])) {
                            $messagesDataByLang[$lang][$groupName][$messageText] = $messageText;
                        }
                    }

                    $message[] = "\e[34mLeyendo: {$messageFileRelative}\e[39m";
                }

                $fileMissingLangMessagePath = app_basepath("logs/missing-lang-messages.json");
                if (!file_exists($fileMissingLangMessagePath)) {
                    touch($fileMissingLangMessagePath);
                    chmod($fileMissingLangMessagePath, 0644);
                }
                file_put_contents($fileMissingLangMessagePath, json_encode($messagesDataByLang, \JSON_PRETTY_PRINT  | \JSON_UNESCAPED_UNICODE  | \JSON_UNESCAPED_SLASHES));
                $message[] = "\e[34mArchivo generado en: {$fileMissingLangMessagePath}\e[39m";
            }

        } catch (\Exception $e) {

            $message[] = "\e[31mHa ocurrido un error: {$e->getMessage()}\e[39m";
            log_exception($e);

        }

        $message[] = "\e[32m*** {$titleTask}, tarea finalizada ***\e[39m";
        if (count($message) > 1) {
            echoTerminal(implode("\r\n", $message));
        }
    }

    /**
     * Busca llamadas a una función específica dentro de archivos PHP de un directorio utilizando PHP-Parser.
     *
     * Analiza recursivamente todos los archivos PHP en un directorio (excluyendo rutas opcionales)
     * para encontrar llamadas a una función dada y extraer los argumentos de la llamada, incluyendo constantes de clase,
     * constantes globales, strings, y variables.
     *
     * @param string $funcion      Nombre de la función a buscar (ej. 'get_value_constant').
     * @param string $directorio   Ruta base del proyecto o directorio donde buscar.
     * @param array  $opciones     Arreglo de opciones:
     *                             - excludeDirs: directorios a excluir.
     *                             - excludePatterns: expresiones regulares para excluir rutas.
     *                             - extensions: extensiones de archivos a incluir (por defecto ['php']).
     *
     * @return array Resultados con archivos que contienen llamadas a la función y sus argumentos,
     *               junto con estadísticas del análisis.
     */
    public static function searchFunctionUsesWithParser($function, $directory, $options = [])
    {
        // Crear el parser y el localizador de nodos
        $parser = (new \PhpParser\ParserFactory)->createForHostVersion();
        $nodeFinder = new \PhpParser\NodeFinder;
        $nameResolver = new \PhpParser\NodeVisitor\NameResolver();
        $results = [];
        $totalCalls = 0;
        $filesProcessed = 0;
        $filesWithError = 0;

        // Opciones por defecto
        $options = array_merge([
            'excludeDirs' => [
                $directory . '/vendor',
                $directory . '/node_modules',
            ],
            'excludePatterns' => [
                '/\.git/',
                '/\.svn/',
                '/\.idea/',
            ],
            'extensions' => ['php'],
        ], $options);

        // Función para verificar si un archivo debe ser excluido
        $shouldExclude = function ($path) use ($options) {
            foreach ($options['excludeDirs'] as $excludeDir) {
                if (strpos($path, $excludeDir) === 0) {
                    return true;
                }
            }
            foreach ($options['excludePatterns'] as $pattern) {
                if (preg_match($pattern, $path)) {
                    return true;
                }
            }
            return false;
        };

        // Función recursiva para obtener todos los archivos del directorio
        $findFiles = function ($dir) use (&$findFiles, $shouldExclude, $options) {
            $files = [];
            $items = scandir($dir);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                $path = $dir . '/' . $item;
                if ($shouldExclude($path)) {
                    continue;
                }
                if (is_dir($path)) {
                    $files = array_merge($files, $findFiles($path));
                } elseif (is_file($path) && in_array(pathinfo($path, PATHINFO_EXTENSION), $options['extensions'])) {
                    $files[] = $path;
                }
            }
            return $files;
        };

        $files = $findFiles($directory);
        $totalFiles = count($files);

        foreach ($files as $file) {
            try {
                $content = file_get_contents($file);
                $ast = $parser->parse($content);
                if ($ast === null) {
                    continue;
                }

                // Resolver nombres completamente calificados (FQNs)
                $traverser = new \PhpParser\NodeTraverser();
                $traverser->addVisitor($nameResolver);
                $ast = $traverser->traverse($ast);

                $namespace = '';
                $currentClassName = null;

                // Detectar el namespace y la clase actual si existe
                foreach ($ast as $node) {
                    if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                        $namespace = $node->name ? $node->name->toString() : '';
                    }
                    if ($node instanceof \PhpParser\Node\Stmt\ClassLike  && $node->hasAttribute('namespacedName')) {
                        $currentClassName = $node->getAttribute('namespacedName')->toString();
                    }
                }

                if ($currentClassName == null) {
                    // Buscar namespace y clase actual
                    foreach ($ast as $node) {
                        if ($node instanceof \PhpParser\Node\Stmt\Namespace_) {
                            $namespace = $node->name ? $node->name->toString() : '';
                            foreach ($node->stmts as $stmt) {
                                if ($stmt instanceof \PhpParser\Node\Stmt\Class_  && $stmt->name) {
                                    $currentClassName = $namespace ? $namespace . '\\' . $stmt->name->toString() : $stmt->name->toString();
                                    break 2;
                                }
                            }
                        } elseif ($node instanceof \PhpParser\Node\Stmt\Class_  && $node->name) {
                            $currentClassName = $node->name->toString();
                            break;
                        }
                    }
                    if ($currentClassName !== null) {
                        $currentClassName = "\\" . ltrim($currentClassName, '\\');
                    }
                }

                // Buscar llamadas a la función indicada
                $calls = $nodeFinder->find($ast, function ($node) use ($function) {
                    return $node instanceof \PhpParser\Node\Expr\FuncCall
                    && $node->name instanceof \PhpParser\Node\Name
                    && $node->name->toString() === $function;
                });

                if (!empty($calls)) {
                    $callsInfo = [];
                    foreach ($calls as $call) {
                        /** @var \PhpParser\Node\Expr\FuncCall $call */
                        $params = [];

                        foreach ($call->args as $arg) {
                            $value = null;

                            if ($arg->value instanceof \PhpParser\Node\Scalar\String_) {
                                // Strings normales
                                $value = $arg->value->value;

                            } elseif ($arg->value instanceof \PhpParser\Node\Expr\ClassConstFetch) {
                                // Constantes de clase: Clase::CONSTANTE
                                $class = $arg->value->class instanceof \PhpParser\Node\Name
                                ? $arg->value->class->toString()
                                : '';
                                $constName = $arg->value->name instanceof \PhpParser\Node\Identifier
                                ? $arg->value->name->toString()
                                : '';

                                $lower = strtolower($class);
                                if (in_array($lower, ['self', 'static'])) {
                                    if ($currentClassName) {
                                        // Aseguramos que tenga prefijo \ para nombre completo
                                        $classFull = ltrim($currentClassName, '\\');
                                        $value = '\\' . $classFull . '::' . $constName;
                                    } else {
                                        // No se pudo resolver self o static, asignamos null para evitar errores
                                        $value = null;
                                    }
                                } elseif ($lower === 'parent') {
                                    // parent:: se deja como está
                                    $value = 'parent::' . $constName;
                                } else {
                                    // En otros casos agregamos el prefijo \
                                    $value = '\\' . ltrim($class, '\\') . '::' . $constName;
                                }

                            } elseif ($arg->value instanceof \PhpParser\Node\Expr\ConstFetch) {
                                // Constantes globales
                                $name = $arg->value->name->toString();
                                $value = '\\' . ltrim($name, '\\');

                            } elseif ($arg->value instanceof \PhpParser\Node\Expr\Variable) {
                                // Variables
                                $value = '$' . $arg->value->name;
                            }

                            if ($value !== null) {
                                $params[] = $value;
                            }
                        }

                        $callsInfo[] = [
                            'function' => $function,
                            'params' => $params,
                            'line' => $call->getLine(),
                        ];
                    }

                    $results[$file] = $callsInfo;
                    $totalCalls += count($calls);
                }

                $filesProcessed++;

            } catch (\Exception $e) {
                // Si ocurre un error en el archivo, lo marcamos y seguimos con el resto
                $filesWithError++;
                continue;
            }
        }

        // Agregar estadísticas generales al resultado
        $results['_stats'] = [
            'total_files' => $totalFiles,
            'files_processed' => $filesProcessed,
            'files_with_error' => $filesWithError,
            'total_calls' => $totalCalls,
            'files_with_calls' => count($results) - 1, // Descontar _stats
        ];

        return $results;
    }

    public static function route(string $startRoute = '', ?string $namePrefix = null): Route
    {
        $instance = new ScanMissingLangTask($startRoute, $namePrefix);
        $route = new Route(
            $instance->route,
            $instance->controller,
            $instance->name,
            $instance->method,
            $instance->requireLogin,
            null,
            $instance->rolesAllowed->getArrayCopy(),
            $instance->defaultParamsValues,
            $instance->middlewares
        );
        return $route;
    }

}
