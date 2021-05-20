<?php

/**
 * CrontabManager.php
 */

namespace PiecesPHP;

/**
 * CrontabManager.
 *
 * @package     PiecesPHP
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2021
 */
class CrontabManager
{

    /**
     * @return string[]
     */
    public static function getJobs()
    {
        $output = shell_exec('crontab -l');
        $jobs = self::stringToArray($output);
        foreach ($jobs as $k => $i) {
            if (!ctype_digit($i[0]) && $i[0] != '*') {
                unset($jobs[$k]);
            }
        }
        return $jobs;
    }

    /**
     * @param array $jobs
     * @return string
     */
    public static function saveJobs($jobs = array())
    {
        $output = shell_exec('echo "' . self::arrayToString($jobs) . '" | crontab -');
        return $output;
    }

    /**
     * @param string $job
     * @return bool
     */
    public static function doesJobExist($job = '')
    {
        $jobs = self::getJobs();
        if (in_array($job, $jobs)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string $job
     * @return string|null|false False en caso de que ya exista
     */
    public static function addJob($job = '')
    {
        if (self::doesJobExist($job)) {
            return false;
        } else {
            $jobs = self::getJobs();
            $jobs[] = $job;
            return self::saveJobs($jobs);
        }
    }

    /**
     * @param string $job
     * @return string|null|false False en caso de que no exista
     */
    public static function removeJob($job = '')
    {
        if (self::doesJobExist($job)) {
            $jobs = self::getJobs();
            unset($jobs[array_search($job, $jobs)]);
            return self::saveJobs($jobs);
        } else {
            return false;
        }
    }

    /**
     * @param string $jobs
     * @return string[]
     */
    private static function stringToArray($jobs = '')
    {
        $array = explode("\n", trim($jobs));
        foreach ($array as $key => $item) {
            if ($item == '') {
                unset($array[$key]);
            }
        }
        return $array;
    }

    /**
     * @param array $jobs
     * @return string
     */
    private static function arrayToString($jobs = array())
    {
        $string = implode("\n", $jobs);
        return $string;
    }

}
