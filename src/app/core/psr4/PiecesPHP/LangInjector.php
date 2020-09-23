<?php
/**
 * LangInjector.php
 */

namespace PiecesPHP;

use PiecesPHP\Core\Config;
use PiecesPHP\Core\Helpers\Directories\DirectoryObject;

/**
 * LangInjector.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2020
 */
class LangInjector
{

    /**
     * @var string
     */
    protected $fullPathLangDirectorty = '';
    /**
     * @var array
     */
    protected $allowedLangs = [];

    /**
     * @param string $fullPathLangDirectorty
     * @param array $allowedLangs
     */
    public function __construct(string $fullPathLangDirectorty, array $allowedLangs)
    {
        $this->fullPathLangDirectorty = rtrim($fullPathLangDirectorty, '/');
        $this->allowedLangs = $allowedLangs;
    }

    /**
     * @return void
     */
    public function inject()
    {

        $allowedLangs = $this->allowedLangs;

        foreach ($allowedLangs as $langName) {

            $langFile = "{$this->fullPathLangDirectorty}/{$langName}.php";

            if (file_exists($langFile)) {

                $langData = include_once $langFile;

                if (is_array($langData)) {

                    foreach ($langData as $groupName => $messages) {

                        if (is_array($messages)) {

                            foreach ($messages as $messageKey => $messageValue) {

                                if (is_scalar($messageKey) && is_scalar($messageValue)) {
                                    Config::addLangMessage($langName, $groupName, $messageKey, $messageValue);
                                }

                            }

                        }

                    }

                }

            }

        }

        $directoryFilesLang = new DirectoryObject("{$this->fullPathLangDirectorty}/files");

        $directoryFilesLang->process();

        $directories = $directoryFilesLang->getDirectories();

        foreach ($directories as $directory) {

            $groupName = $directory->getBasename();
            $files = $directory->getFiles();

            foreach ($files as $file) {

                $filename = $file->getBasename();

                if (strpos(mb_strtolower($filename), '.html') !== false) {

                    $messageKey = preg_replace("|^(.*)\.html$|i", '$1', $filename);
                    $belongToLang = mb_strtolower(preg_replace("|^.*\-(.*)\.html$|i", '$1', $filename));
                    $messageValue = @file_get_contents($file->getPath());

                    if (in_array($belongToLang, $allowedLangs)) {
                        $messageKey = preg_replace("|^(.*)\-{$belongToLang}$|i", '$1', $messageKey);
                    } else {
                        $belongToLang = 'default';
                    }

                    Config::addLangMessage($belongToLang, $groupName, $messageKey, $messageValue);

                }

            }
        }
    }

    /**
     * @param string $groupName
     * @return void
     */
    public function injectGroup(string $groupName)
    {

        $allowedLangs = $this->allowedLangs;

        foreach ($allowedLangs as $langName) {

            $langFile = "{$this->fullPathLangDirectorty}/{$langName}.php";

            if (file_exists($langFile)) {

                $messages = include_once $langFile;

                if (is_array($messages)) {

                    foreach ($messages as $messageKey => $messageValue) {

                        if (is_scalar($messageKey) && is_scalar($messageValue)) {
                            Config::addLangMessage($langName, $groupName, $messageKey, $messageValue);
                        }

                    }

                }

            }

        }

        $directoryFilesLang = new DirectoryObject("{$this->fullPathLangDirectorty}/files");

        $directoryFilesLang->process();

        $files = $directoryFilesLang->getFiles();

        foreach ($files as $file) {

            $filename = $file->getBasename();

            if (strpos(mb_strtolower($filename), '.html') !== false) {

                $messageKey = preg_replace("|^(.*)\.html$|i", '$1', $filename);
                $belongToLang = mb_strtolower(preg_replace("|^.*\-(.*)\.html$|i", '$1', $filename));
                $messageValue = @file_get_contents($file->getPath());

                if (in_array($belongToLang, $allowedLangs)) {
                    $messageKey = preg_replace("|^(.*)\-{$belongToLang}$|i", '$1', $messageKey);
                } else {
                    $belongToLang = 'default';
                }

                Config::addLangMessage($belongToLang, $groupName, $messageKey, $messageValue);

            }

        }

    }

}
