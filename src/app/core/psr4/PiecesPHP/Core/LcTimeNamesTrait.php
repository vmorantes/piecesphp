<?php

/**
 * LcTimeNamesTrait.php
 */

namespace PiecesPHP\Core;

use PiecesPHP\Core\Database\Database;

/**
 * Fija `lc_time_names`. Un candidato descartado NO es un error y el `SET` interpolado NO es
 * inyectable —lista blanca de `config/lang.php`—: el porqué, en T145 y en `12-convenciones.md`.
 */
trait LcTimeNamesTrait
{
    /**
     * Una vez por proceso. Cada clase que usa el trait tiene su copia, como antes.
     *
     * @var bool
     */
    protected static $localeSetted = false;

    /**
     * @param \Closure $databaseResolver Devuelve la conexión. Va perezoso A PROPÓSITO:
     *                                   `getDatabase()` llama por debajo a
     *                                   `checkDbConnectionFallback()`, que puede RECONECTAR.
     *                                   Resolverlo antes de saber si hay candidatos añadiría
     *                                   una reconexión que hoy no ocurre.
     * @return void
     */
    protected function setLcTimeNamesOnce(\Closure $databaseResolver)
    {
        if (self::$localeSetted) {
            return;
        }

        //Se marca ANTES: `getDatabase()` puede reconectar y volver a entrar aquí. Ver T145.
        self::$localeSetted = true;

        $lcTimeNameOptions = get_config('lc_time_names_mysql');

        if (!is_array($lcTimeNameOptions) || empty($lcTimeNameOptions)) {
            return;
        }

        $currentLang = Config::get_lang();
        $lcTimeNameList = array_key_exists($currentLang, $lcTimeNameOptions) ? $lcTimeNameOptions[$currentLang] : null;
        $lcTimeNameList = is_array($lcTimeNameList) ? $lcTimeNameList : [$lcTimeNameList];

        $candidates = [];

        foreach ($lcTimeNameList as $lcTimeName) {
            if (is_string($lcTimeName) && mb_strlen($lcTimeName) > 0) {
                $candidates[] = $lcTimeName;
            }
        }

        if (count($candidates) === 0) {
            return;
        }

        $lastException = null;
        $discarded = [];

        foreach ($candidates as $candidate) {

            $databaseInstance = $databaseResolver();

            if (!$databaseInstance instanceof Database) {
                continue;
            }

            try {
                $prepareStatement = $databaseInstance->prepare("SET lc_time_names = '{$candidate}';");
                $prepareStatement->execute();
                $prepareStatement->closeCursor();
                //UNO VALIÓ. Los descartados de antes no se registran: no eran errores.
                return;
            } catch (\Exception $e) {
                $discarded[] = $candidate;
                $lastException = $e;
            }
        }

        if ($lastException === null) {
            //Ni un solo intento llegó a la base: no hay conexión que juzgar.
            return;
        }

        log_exception(new \RuntimeException(
            "lc_time_names: ninguno de los candidatos del idioma «{$currentLang}» fue aceptado por la base de datos. " .
            'Se probaron: ' . implode(', ', $discarded) . '. ' .
            'Revise `lc_time_names_mysql` en `config/lang.php` contra lo que soporta este servidor.',
            0,
            $lastException
        ));
    }
}
