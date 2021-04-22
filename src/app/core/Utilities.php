<?php

/**
 * Utilities.php
 * Funciones globales
 *
 * Grupo de funciones utilitarias.
 *
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2018
 */

/**
 * Obtiene la url actual
 *
 * @return string La url
 */
function get_current_url()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    return $url;
}

/**
 * Obtiene el request de la ruta actual de la aplicación.
 *
 * Para /host/directorio/app_base/sub_directorio/test?param=123456
 * Devuelve: sub_directorio/test?param=123456
 * @return string El request uri
 */
function get_request()
{
    $script_name = $_SERVER['SCRIPT_NAME'];

    $basename = basename($script_name);

    $str_replace_1 = str_replace(
        $basename,
        "",
        $script_name
    );

    $deletable_match = [
        $_SERVER['HTTP_HOST'],
        $_SERVER['HTTP_HOST'] . '/',
        '/',
    ];

    if (in_array($str_replace_1, $deletable_match)) {
        $str_replace_1 = '';
    }

    $requet_uri = $_SERVER['REQUEST_URI'];

    $str_replace_2 = str_replace(
        $str_replace_1,
        "",
        $requet_uri
    );

    $request = mb_substr($str_replace_2, 0);

    if (last_char($request) == '/') {
        $request = remove_last_char($request);
    }

    if (mb_substr($request, 0, 1) == '/') {
        $request = remove_first_char($request);
    }

    return $request;
}

/**
 * Obtiene el segmento solicitado del request.
 *
 * Para /host/directorio/app_base/sub_directorio/test?param=123456
 * Devuelve: sub_directorio/test?param=123456
 * @param int $part El segmento deseado
 * @return string El segmento del request
 * @use get_request()
 */
function get_part_request($part = 1)
{
    $part = $part - 1;
    $part_uri = explode("/", get_request());
    if (count($part_uri) > 0) {
        if (mb_strlen($part_uri[count($part_uri) - 1]) == 0) {
            unset($part_uri[count($part_uri) - 1]);
        }

        if (count($part_uri) > 0) {
            return array_key_exists($part, $part_uri) ? $part_uri[$part] : "";
        } else {
            return "";
        }
    } else {
        return "";
    }
}

/**
 * Une un segmento a una URL
 *
 * @param string $url La URL
 * @param string $segment El segmento que se añadirá
 * @param bool $complete Si la URL podría incluir user, pass y port
 * @return string La URL
 */
function append_to_url(string $url, string $segment, bool $complete = false)
{

    $parts = [
        'scheme' => parse_url($url, \PHP_URL_SCHEME),
        'user' => parse_url($url, \PHP_URL_USER),
        'pass' => parse_url($url, \PHP_URL_PASS),
        'host' => parse_url($url, \PHP_URL_HOST),
        'port' => parse_url($url, \PHP_URL_PORT),
        'path' => parse_url($url, \PHP_URL_PATH),
    ];

    $getKeyValue = function (string $key) use ($parts) {
        return isset($parts[$key]) && $parts[$key] !== null ? $parts[$key] : '';
    };

    $scheme = ($getKeyValue)('scheme');
    $user = ($getKeyValue)('user');
    $pass = ($getKeyValue)('pass');
    $host = ($getKeyValue)('host');
    $port = ($getKeyValue)('port');
    $path = ($getKeyValue)('path');

    if ($complete) {

        $url = [];

        if (mb_strlen($user) > 0) {

            $authString = "{$user}";

            if (mb_strlen($pass) > 0) {
                $authString .= ":{$pass}";
            }

            $authString .= '@';

            $url[] = $authString;

        }

        $url[] = "{$host}";

        if (mb_strlen($port) > 0) {
            $url[] = ":{$port}";
        }

        $url[] = "/{$path}/$segment";
        $url = implode('', $url);

    } else {
        $url = "$host/$path/$segment";
    }

    $url = preg_replace('|\/{2,}|', '/', $url);

    if (mb_strlen($scheme) > 0) {
        $url = "$scheme://$url";
    } else {
        $url = "$url";
    }

    return $url;
}

/**
 * Genera una contraseña con la longitud especificada y la encripta.
 *
 * Usa password_hash() para la encriptación.
 * @param int $length Longitud de la contraseña.
 * @link http://php.net/manual/en/function.password-hash.php password_hash()
 * @return array La contraseña generada y el hash
 * <pre>
 *  [
 *      'password'=>pass,
 *      'encrypt'=>hash
 *  ]
 * </pre>
 */
function generate_pass(int $length = 5)
{
    //Se inicia una cadena vacía para la contraseña
    $new_pass = "";
    //Se definen caracteres a usar
    $chars = "-_$*.ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
    //Se obtiene la longitud de la cadena
    $len_chars = strlen($chars);
    //Se define una longitud para la contraseña
    $len_pass = $length;

    for ($i = 1; $i <= $len_pass; $i++) {
        $random_pos = rand(0, $len_chars - 1);
        $random_char = mb_substr($chars, $random_pos, 1);
        $new_pass .= $random_char;
    }
    $new_pass_encrypt = password_hash($new_pass, PASSWORD_DEFAULT);
    return [
        'password' => $new_pass,
        'encrypt' => $new_pass_encrypt,
    ];
}

/**
 * Genera una cadena aleatoria con la longitud especificada y la encripta.
 *
 * @param int $length Tamaño de la cadena
 * @param bool $only_numeric Generar solo números
 */
function generate_code(int $length = 6, bool $only_numeric = true)
{
    //Se inicia una cadena vacía para la contraseña
    $new_pass = "";
    //Se definen caracteres a usar
    if ($only_numeric) {
        $chars = "0123456789";
    } else {
        $chars = "-_$*.ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
    }
    //Se obtiene la longitud de la cadena
    $len_chars = mb_strlen($chars);
    //Se define una longitud para la contraseña
    $len_pass = $length;

    for ($i = 1; $i <= $len_pass; $i++) {
        $random_pos = rand(0, $len_chars - 1);
        $random_char = mb_substr($chars, $random_pos, 1);
        $new_pass .= $random_char;
    }

    return $new_pass;
}

/**
 * Decodifica un string en base64 hecho con url_safe_base64_encode()
 * @param string $input El string
 * @return string
 *  El base64
 */
function url_safe_base64_decode($input)
{
    $remainder = strlen($input) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $input .= str_repeat('=', $padlen);
    }
    return base64_decode(strtr($input, '-_', '+/'));
}

/**
 * Codifica un string en base64 seguro para URL
 * @param string $input El string
 * @return string
 *  El base64
 */
function url_safe_base64_encode(string $input)
{
    return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
}

/**
 * clean_string
 *
 * Limpia un string con:
 *
 * - /(\t|\r\n|\r|\n){1,}/ => ''
 * - /(\x{00A0}){1,}/u => ''
 * - /(\s){2,}/ => ' '
 *
 * @param string $string
 * @return string
 */
function clean_string(string $string)
{
    $string = preg_replace("/(\t|\r\n|\r|\n){1,}/", '', $string);
    $string = preg_replace("/(\x{00A0}){1,}/u", ' ', $string);
    $string = preg_replace("/(\s){2,}/", ' ', $string);
    return $string;
}

/**
 * Verifica si un conjunto de indices está en un array.
 *
 * @param array $keys Array cuyos valores sean los índices que se buscan.
 * @param array $array Array examinado
 *
 * @return boolean|array True si los indices existen o un array con los indices que faltan
 * <pre>
 *      //Ejemplo:
 *      $keys = ['indice','indice2'];
 *      $array = ['indice'=>value,'indice2'=>value];
 *      $array2 = ['indice'=>value];
 *
 *      require_keys($keys, $array); //Devuelve true
 *      require_keys($keys, $array2); //Devuelve array('indice2')
 * </pre>
 */
function require_keys(array $keys, array $array)
{
    $faltantes = [];
    foreach ($keys as $key) {
        if (!array_key_exists($key, $array)) {
            $faltantes[] = $key;
        }
    }
    if (count($faltantes) > 0) {
        return $faltantes;
    } else {
        return true;
    }
}

/**
 * Obtiene el id de una url de video youtube
 * @param string $url URL del video
 * @return string id
 */
function get_youtube_id(string $url)
{
    if (strstr($url, 'youtu.be')) {
        return str_ireplace(array('https://youtu.be/', 'http://youtu.be/'), '', $url);
    }

    $id = "";

    $query_string = array();

    parse_str(parse_url($url, PHP_URL_QUERY), $query_string);

    if (array_key_exists("v", $query_string)) {
        $id = $query_string["v"];
    }

    return $id;
}

/**
 * Verificar si está en local.
 *
 * @return boolean true si $_SERVER['HTTP_HOST'] == "localhost", false si no
 */
function is_local()
{
    return $_SERVER['HTTP_HOST'] == "localhost" ? true : false;
}

/**
 * Obtiene el último caracter de un cadena
 * @param string $string La cadena
 * @return string
 */
function last_char(string $string)
{
    return mb_substr($string, strlen($string) - 1);
}

/**
 * Obtiene la posición último caracter de un cadena
 * @param string $string La cadena
 * @return int
 */
function last_char_pos(string $string)
{
    return strlen($string) - 1;
}

/**
 * Elimina el último carácter de una cadena y devuelve la nueva cadena.
 * @param string $string La cadena
 * @return string
 */
function remove_last_char(string $string)
{
    return mb_substr($string, 0, (strlen($string) - 1));
}

/**
 * Elimina el último carácter de la cadena si coincide con el carácter proporcionado
 *
 * @param string $char El carácter a eliminar
 * @param string $string La cadena
 * @return string La cadena
 */
function remove_last_char_on($char, $string)
{
    $last_char = mb_substr($string, strlen($string) - 1);
    if ($last_char == $char) {
        $string = mb_substr($string, 0, (strlen($string) - 1));
    }
    return $string;
}

/**
 * Elimina el primer carácter de una cadena y devuelve la nueva cadena.
 * @param string $string La cadena
 * @return string
 */
function remove_first_char(string $string)
{
    return mb_substr($string, 1);
}

/**
 * Elimina el carácter indicado de una cadena y devuelve la nueva cadena.
 * (Se cuenta desde 0).
 * @param string $string La cadena
 * @param int $pos Posición
 * @return string
 */
function remove_char(string $string, int $pos)
{
    $string[$pos] = '';
    return $string;
}

/**
 * Reemplaza el primer carácter de una cadena.
 * @param string $string La cadena
 * @param string $replace Reemplazo
 * @return string
 */
function replace_first_char(string $string, string $replace)
{
    return $replace . mb_substr($string, 1);
}

/**
 * Reemplaza el último carácter de una cadena.
 * @param string $string La cadena
 * @param string $replace Reemplazo
 * @return string
 */
function replace_last_char(string $string, string $replace)
{
    return mb_substr($string, 0, (strlen($string) - 1)) . $replace;
}

/**
 * Compara un string con otro o un conjunto de otros
 * @param string $str1 La cadena a comparar
 * @param string|array $compare La cadena o array de cadenas con la que se comparará
 * @return boolean
 * Devuelve true si coincide con alguna de las cadenas o false si no coincide con ninguna.
 */
function string_compare(string $str1, $compare)
{
    if (is_string($compare)) {
        $cmp = strcmp($str1, $compare);
        return ($cmp === 0);
    }
    if (is_array($compare)) {
        foreach ($compare as $string) {
            $coincidencia = false;
            if (is_string($string)) {
                $cmp = strcmp($str1, $string);
                if ($cmp === 0) {
                    return true;
                }
            } else {
                return false;
            }
        }
        return false;
    }
}

/**
 * Elimina las posiciones vacías.
 * NO se considera vacío: 0, 0.0, "0", false, null ni []
 * @param array $array
 * @return array El array nuevo
 */
function remove_emptys(array $array)
{
    foreach ($array as $key => $value) {
        if (($value !== 0 && $value !== 0.0 && $value !== "0" && $value !== false && $value !== null && $value !== []) && empty($value)) {
            unset($array[$key]);
        }
    }
    return $array;
}

/**
 * Compara el tamaño de dos arrays.
 * @param array $array1
 * @param array $array2
 * @return boolean true si son de igual tamaño
 */
function compare_array_length(array $array1, array $array2)
{
    return (count($array1) === count($array2));
}

/**
 * Hace validaciones sobre los valores de un array
 * (que deben corresponder a archivos como en $_FILES). Aplica is_uploaded_file()
 *
 * @param  array $data Array con los archivos.
 * @return bool  Devuelve true si cada archivo fue cargada, false si no.
 */
function is_uploaded_all_files(array $data)
{
    foreach ($data as $key => $value) {
        if (!is_uploaded_file($data[$key]['tmp_name'])) {
            return false;
            break;
        }
    }
    return true;
}

/**
 * Valida que el índice indicado en $_FILES exista, haya sido subido
 * desde un formulario.
 *
 * @param  string $index_name Índice en $_FILES.
 * @return bool
 */
function verify_expected_file(string $index_name)
{
    if (isset($_FILES[$index_name])) {
        $file = $_FILES[$index_name];
        if (is_uploaded_file($file['tmp_name'])) {
            if ($file['error'] == \UPLOAD_ERR_OK) {
                if ($file['size'] > 0) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Crea un directorio y todos los directorios superiores
 * que sean necesarios.
 *
 * @param string $path Ruta del directorio
 * @param string $name Nombre del directorio
 * @return bool
 */
function make_directory(string $path, string $name = null)
{
    if (!is_null($name)) {

        $bars = ['/', "\\"];

        if (string_compare(last_char($name), $bars)) {
            $name = remove_last_char($name);
        }

        if (string_compare($name[0], $bars)) {
            $name = remove_first_char($name);
        }

        if (string_compare(last_char($path), $bars)) {
            $path .= $name;
        } else {
            $path .= '/' . $name;
        }
    }

    mkdir($path, 0777, true);
    chmod($path, 0777);

    return file_exists($path);
}

/**
 * Elimina un directorio y su contenido
 *
 * @param string $path Ruta del directorio
 * @return void
 */
function remove_directory(string $path)
{
    if (file_exists($path)) {

        foreach (glob(append_to_url($path, '*')) as $element) {

            if (is_dir($element)) {

                remove_directory($element);
            } else {

                unlink($element);
            }
        }

        rmdir($path);
    }
}

/**
 * directory_to_zip
 *
 * @param string $root
 * @param string $output_directory
 * @param string $output_name
 * @param array $exclude
 * @param string $parent
 * @param ZipArchive &$zipInstance
 * @return string La ruta del zip
 */
function directory_to_zip(string $root, string $output_directory = null, string $output_name = null, bool $self = false, array $exclude = [], string $parent = null, \ZipArchive &$zipInstance = null)
{
    $zip_path = '';

    if (file_exists($root) && is_dir($root)) {

        $root = rtrim($root, \DIRECTORY_SEPARATOR);
        $root_name = basename($root);
        if ($parent == null) {
            if ($self) {
                $base = $root_name;
            } else {
                $base = '';
            }
        } else {
            $base = $parent . \DIRECTORY_SEPARATOR . $root_name;
        }

        $base = str_replace([
            '//',
            "\\\\",
        ], [
            '/',
            "\\",
        ], $base);

        $base = trim($base);

        if ($zipInstance === null) {

            $zip = new \ZipArchive();

            $output_name = !is_null($output_name) ? trim(trim($output_name), \DIRECTORY_SEPARATOR) : '';
            $output_directory = !is_null($output_directory) ? rtrim(trim($output_directory), \DIRECTORY_SEPARATOR) : '';

            if (is_null($output_name) || strlen($output_name) < 1) {
                $output_name = uniqid() . '.zip';
            }

            if (is_null($output_directory) || strlen($output_directory) < 1) {
                $output_directory = sys_get_temp_dir();
            }

            $output_name = str_replace([
                '//',
                "\\\\",
            ], [
                '/',
                "\\",
            ], $output_name);

            $output_directory = rtrim($output_directory, \DIRECTORY_SEPARATOR);
            $output_directory = str_replace([
                '//',
                "\\\\",
            ], [
                '/',
                "\\",
            ], $output_directory);

            $name_zip = $output_directory . \DIRECTORY_SEPARATOR . $output_name;
            $zip->open($name_zip, \ZIPARCHIVE::CREATE);

            $zip_path = $name_zip;
        } else {

            $zip = $zipInstance;
        }

        if (strlen($base) > 0) {
            $zip->addEmptyDir($base);
        }

        $handler = opendir($root);

        $ignore = ['.', '..'];
        $file = readdir($handler);

        while ($file !== false) {

            if (!in_array($file, $ignore)) {

                $path_file = $root . '/' . $file;
                $skip = false;

                foreach ($exclude as $regexp) {

                    $matchs = preg_match_all("|$regexp|", $file);
                    if ($matchs !== false && $matchs > 0) {
                        $skip = true;
                    }
                }

                if (!$skip) {

                    if (is_dir($path_file)) {

                        directory_to_zip($path_file, $output_directory, $output_name, true, $exclude, $base, $zip);
                    } else {
                        $filename = $base . '/' . $file;
                        $zip->addFile($path_file, $filename);
                    }
                }
            }

            $file = readdir($handler);
        }

        closedir($handler);

        if ($zipInstance === null) {
            $zip->close();
        }
    }

    return $zip_path;
}

/**
 * directory_mapper
 *
 * @param string $path
 * @param string $base_path
 * @param int $mode
 * @return array
 */
function directory_mapper(string $path, string $base_path = null, int $mode = null)
{

    if ($mode == 1) {

        $map = [
            'content' => [
                'directories' => [],
                'files' => [],
            ],
        ];
    } else {

        $map = [
            'type' => 'directory',
            'path' => '',
            'name' => '',
            'content' => [],
        ];
    }

    if (is_null($base_path)) {
        $base_path = '';
    } else {
        $base_path = trim($base_path);
    }

    if (strlen($base_path) > 0 && file_exists($base_path)) {
        $base_path = realpath($base_path);
    }

    if (file_exists($path) && is_dir($path)) {

        $map['path'] = str_replace($base_path, '', realpath($path));
        $map['path'] = str_replace([
            '//',
            "\\\\",
        ], [
            '/',
            "\\",
        ], $map['path']);
        $map['name'] = basename($path);

        $handler = opendir($path);
        $ignore = ['.', '..'];
        $file = readdir($handler);

        while ($file !== false) {

            if (!in_array($file, $ignore)) {

                $file_path = rtrim($path, \DIRECTORY_SEPARATOR) . \DIRECTORY_SEPARATOR . $file;
                $file_path = realpath($file_path);

                if (file_exists($file_path)) {
                    if (is_dir($file_path)) {

                        if ($mode == 1) {

                            $result = directory_mapper($file_path, $base_path, $mode);
                            $map['content']['directories'][] = $file_path;
                            $map['content']['directories'] = array_merge($map['content']['directories'], $result['content']['directories']);
                            $map['content']['files'] = array_merge($map['content']['files'], $result['content']['files']);
                        } else {

                            $map['content'][] = directory_mapper($file_path, $base_path);
                        }
                    } else {

                        if ($mode == 1) {

                            $map['content']['files'][] = str_replace($base_path, '', $file_path);
                        } else {

                            $map['content'][] = [
                                'type' => 'file',
                                'path' => str_replace($base_path, '', $file_path),
                                'name' => basename($file_path),
                            ];
                        }
                    }
                }
            }

            $file = readdir($handler);
        }

        closedir($handler);
    }

    return $map;
}

/**
 * A partir de los segundos, devuelve la duración hasta con horas, con el
 * formato HORAS hora/horas MINUTOS minuto/minutos SEGUNDOS segundo/segundos
 * Ejemplo:
 * seconds_to_duration()
 *
 * @param int $seconds_count Los segundos
 * @param array $options Array con las opciones
 *     - hour = Texto junto a la hora, por defecto hora
 *     - hours = Texto plural junto a la hora, por defecto agrega una s
 *     - minute = Texto junto al minuto, por defecto minuto
 *     - minutes = Texto plural junto al minuto, por defecto agrega una s
 *     - second = Texto junto al segundo, por defecto segundo
 *     - seconds = Texto plural junto al segundo, por defecto agrega una s
 * @return string La duración
 */
function seconds_to_duration(int $seconds_count, array $options = [])
{

    $default_options = [
        'hour' => [
            'value' => 'h',
            'validate' => function ($e) {
                return is_string($e);
            },
        ],
        'hours' => [
            'value' => 'hrs',
            'validate' => function ($e) {
                return is_string($e);
            },
        ],
        'minute' => [
            'value' => 'mn',
            'validate' => function ($e) {
                return is_string($e);
            },
        ],
        'minutes' => [
            'value' => 'mns',
            'validate' => function ($e) {
                return is_string($e);
            },
        ],
        'second' => [
            'value' => 'seg',
            'validate' => function ($e) {
                return is_string($e);
            },
        ],
        'seconds' => [
            'value' => 'segs',
            'validate' => function ($e) {
                return is_string($e);
            },
        ],
    ];

    foreach ($options as $key => $value) {
        if (array_key_exists($key, $default_options)) {
            $validation = $default_options[$key]['validate'];

            if (is_callable($validation)) {
                $valid = $validation($value);
            } else {
                $valid = (bool) $validation;
            }

            if ($valid) {
                $default_options[$key]['value'] = $value;
            }
        }
    }

    $hour = $default_options['hour']['value'];
    $hours = $default_options['hours']['value'];
    $minute = $default_options['minute']['value'];
    $minutes = $default_options['minutes']['value'];
    $second = $default_options['second']['value'];
    $seconds = $default_options['seconds']['value'];

    $time = explode(':', gmdate("H:i:s", $seconds_count));

    $h = $time[0];
    $m = $time[1];
    $s = $time[2];

    if ($h == 0 || $h > 1) {
        $h .= $hours;
    } else {
        $h .= $hour;
    }

    if ($m == 0 || $m > 1) {
        $m .= $minutes;
    } else {
        $m .= $minute;
    }

    if ($s == 0 || $s > 1) {
        $s .= $seconds;
    } else {
        $s .= $second;
    }

    $time = "$h $m $s";

    return $time;
}

/**
 * Convierte una índice que representa una columna de excel en su cadena correspondiente
 * Nota: Una columna excel corresponde al patrón de conteo A-Z ... AA-AZ ... ZA-ZZ ... etc...
 *
 * @param int $index
 * @param bool $startOnZero Para definir el índice inicial como cero. Si es true, A=0; si es false, A=1
 * @return string
 */
function excelColumnByIndex(int $index, bool $startOnZero = true)
{

    $index = $startOnZero ? $index : $index - 1;
    $strColumn = '';

    while ($index >= 0) {
        $strColumn = chr($index % 26 + 0x41) . $strColumn;
        $index = intval($index / 26) - 1;
    }

    return $strColumn;
}

/**
 * Convierte una cadena que representa una columna de excel en su índice
 * Nota: Una columna excel corresponde al patrón de conteo A-Z ... AA-AZ ... ZA-ZZ ... etc...
 *
 * @param string $column
 * @param boolean $startOnZero Para definir el índice inicial como cero. Si es true, A=0; si es false, A=1
 * @return int
 */
function indexByExcelColumn(string $column, bool $startOnZero = true)
{
    $l = strlen($column);
    $n = 0;
    for ($i = 0; $i < $l; $i++) {
        $n = $n * 26 + ord($column[$i]) - 0x40;
    }
    return (int) ($startOnZero ? $n - 1 : $n);
}

/**
 * Convierte una notación de coordenatas decimal a Grados, minutos y segundos (degrees, minutes, seconds)
 *
 * @param string $value El valor decimal
 * @param string $type El tipo: longitude|latitude
 * @param string $decimalPoint La puntuación que se usa para separar decimales
 * @return array|null
 * En caso de éxito devuelve un array con los índices: degrees, minutes, seconds y hemisphere (N,S,E,W)-
 * Devuelve NULL en caso de que la entrada sea incorrecta
 */
function decimalCoordinatesToDMS(string $value, string $type = 'longitude', string $decimalPoint = '.')
{
    $decimalPoint = $decimalPoint == ',' || $decimalPoint == '.' ? $decimalPoint : '.';
    $pointDelete = $decimalPoint == '.' ? ',' : '.';
    $valid = false;

    $value = trim(str_replace($pointDelete, '', $value));

    if (is_numeric($value)) {

        $pointsCount = substr_count($value, $decimalPoint);

        if ($pointsCount === 1 || $pointsCount === 0) {

            $valid = true;

            if($pointsCount === 0){
                $value .= "{$decimalPoint}0";
            }

        }

    }

    if ($valid) {

        $parts = explode(".", $value);
        $degrees = (int) $parts[0];
        $time = $parts[1];
        $temporalReference = "0." . $time;

        $temporalReference = $temporalReference * 3600;
        $minutes = floor($temporalReference / 60);
        $seconds = $temporalReference - ($minutes * 60);

        $hemisphere = '';

        if ($type == 'longitude') {
            if ($degrees < 0) {
                $hemisphere = 'W';
            } else {
                $hemisphere = 'E';
            }
        } elseif ($type == 'latitude') {
            if ($degrees < 0) {
                $hemisphere = 'S';
            } else {
                $hemisphere = 'N';
            }
        }

        return [
            "hemisphere" => $hemisphere,
            "degrees" => $degrees,
            "minutes" => $minutes,
            "seconds" => $seconds,
        ];

    } else {
        return null;
    }

}

//========================================================================================
/*                                                                                      *
 *                                       Polyfills                                      *
 *                                                                                      */
//========================================================================================

if (!function_exists('array_key_last')) {

    /**
     * @param array $array
     * @return mixed
     */
    function array_key_last(array $array)
    {
        if (!empty($array)) {
            return key(array_slice($array, -1, 1, true));
        }

    }
}

if (!function_exists('array_key_first')) {

    /**
     * @param array $array
     * @return mixed
     */
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }

    }

}
