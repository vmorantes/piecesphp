<?php

/**
 * ExifHelper.php
 */

namespace PiecesPHP\Core\Utilities;

/**
 * ExifHelper.
 *
 * @package     PiecesPHP\Core\Utilities
 * @author      Vicsen Morantes <sir.vamb@gmail.com>
 * @copyright   Copyright (c) 2019
 */
class ExifHelper
{

    const GPS_TYPE_LONGITUDE = 'LONGITUDE';
    const GPS_TYPE_LATITUDE = 'LATITUDE';

    /**
     * @var array
     */
    protected $exifData = [];

    /**
     * @param string $file
     * @return static
     */
    public function __construct(string $file)
    {
        $this->exifData = $this->convertArrayToUTF8(exif_read_data($file, null));
    }

    /**
     * @return \DateTime|null
     */
    public function getOriginalDate()
    {

        $value = null;
        $exifData = $this->exifData;
        $requiredKey = 'DateTimeOriginal';
        $DateTimeOriginal = isset($exifData[$requiredKey]) ? $exifData[$requiredKey] : null;

        if (!is_null($DateTimeOriginal)) {

            $datetimeParts = explode(' ', $DateTimeOriginal);

            $date = $datetimeParts[0];
            $time = $datetimeParts[1];

            $date = str_replace(':', '-', $date);

            $datetimeString = "{$date} {$time}";

            $value = new \DateTime($datetimeString);

        }

        return $value;

    }

    /**
     * @return \DateTime|null
     */
    public function getDigitizedDate()
    {
        $value = null;
        $exifData = $this->exifData;
        $requiredKey = 'DateTimeDigitized';
        $DateTimeDigitized = isset($exifData[$requiredKey]) ? $exifData[$requiredKey] : null;

        if (!is_null($DateTimeDigitized)) {

            $datetimeParts = explode(' ', $DateTimeDigitized);

            $date = $datetimeParts[0];
            $time = $datetimeParts[1];

            $date = str_replace(':', '-', $date);

            $datetimeString = "{$date} {$time}";

            $value = new \DateTime($datetimeString);

        }

        return $value;

    }

    /**
     * @return \DateTime|null
     */
    public function getFileDate()
    {
        return isset($this->exifData['FileDateTime']) ? (new \DateTime())->setTimestamp($this->exifData['FileDateTime']) : null;
    }

    /**
     * @return array|null [lng=>number, lat=>number] o null en caso de no tener la información
     */
    public function getGPSCoordinates()
    {

        $longitude = $this->getGPSLongitude();
        $latitude = $this->getGPSLatitude();

        if ($longitude !== null && $latitude !== null) {

            return [
                'lng' => $longitude,
                'lat' => $latitude,
            ];

        }

        return null;
    }

    /**
     * @return float|null
     */
    public function getGPSLongitude()
    {
        $result = null;
        $value = isset($this->exifData['GPSLongitude']) ? $this->exifData['GPSLongitude'] : null;
        $sign = $this->getGPSSign(self::GPS_TYPE_LONGITUDE);
        $number = $this->getGPSDataToNumber(self::GPS_TYPE_LONGITUDE);

        if (is_array($value) && !is_null($sign) && !is_null($number)) {
            $result = $sign * $number;
        }

        return $result;
    }

    /**
     * @return float|null
     */
    public function getGPSLatitude()
    {
        $result = null;
        $value = isset($this->exifData['GPSLatitude']) ? $this->exifData['GPSLatitude'] : null;
        $sign = $this->getGPSSign(self::GPS_TYPE_LATITUDE);
        $number = $this->getGPSDataToNumber(self::GPS_TYPE_LATITUDE);

        if (is_array($value) && !is_null($sign) && !is_null($number)) {
            $result = $sign * $number;
        }

        return $result;
    }

    /**
     * @param string $type Alguna de las constantes:
     * - GPS_TYPE_LONGITUDE
     * - GPS_TYPE_LATITUDE
     * @return int|null
     */
    public function getGPSSign(string $type = null)
    {

        $value = null;
        $positiveReference = '';
        $negativeReference = '';

        if ($type == self::GPS_TYPE_LONGITUDE || $type === null) {

            $type = 'GPSLongitudeRef';
            $value = isset($this->exifData[$type]) ? $this->exifData[$type] : null;
            $positiveReference = 'E';
            $negativeReference = 'W';

        } elseif ($type == self::GPS_TYPE_LATITUDE) {

            $type = 'GPSLatitudeRef';
            $value = isset($this->exifData[$type]) ? $this->exifData[$type] : null;
            $positiveReference = 'N';
            $negativeReference = 'S';

        }

        if (is_int($value)) {

            $value = $value > 0 ? 1 : -1;

        } elseif (is_string($value)) {

            $value = trim(mb_strtoupper($value));
            $value = mb_str_split($value);
            $lastChar = end($value);

            if ($lastChar == $positiveReference) {
                $value = 1;
            } elseif ($lastChar == $negativeReference) {
                $value = -1;
            } else {
                $value = null;
            }

        }

        return $value;
    }

    /**
     * @param string $type Alguna de las constantes:
     * - GPS_TYPE_LONGITUDE
     * - GPS_TYPE_LATITUDE
     * @return float|int|null
     */
    public function getGPSDataToNumber(string $type = null)
    {

        $value = null;
        $number = null;

        if ($type == self::GPS_TYPE_LONGITUDE || $type === null) {
            $type = 'GPSLongitude';
        } elseif ($type == self::GPS_TYPE_LATITUDE) {
            $type = 'GPSLatitude';
        }

        $value = isset($this->exifData[$type]) ? $this->exifData[$type] : null;

        if ($value !== null) {

            $segments = [
                'degrees' => !empty($value) ? $value[0] : 0,
                'minutes' => count($value) > 1 ? $value[1] : 0,
                'seconds' => count($value) > 2 ? $value[2] : 0,
            ];

            foreach ($segments as $segment => $segmentValue) {

                $parts = explode('/', $segmentValue);

                if (count($parts) <= 0) {
                    $segments[$segment] = 0;
                } elseif (count($parts) == 1) {
                    $segments[$segment] = $parts[0];
                } else {
                    $partOne = floatval($parts[0]);
                    $partTwo = floatval($parts[1]);
                    $areDifferentToZero = $partOne != 0 && $partTwo != 0;
                    $segments[$segment] = $areDifferentToZero ? floatval($parts[0]) / floatval($parts[1]) : 0;
                }

            }

            $number = ($segments['degrees'] + $segments['minutes'] / 60 + $segments['seconds'] / 3600);

        }

        return $number;

    }

    /**
     * @param array $array
     * @return array
     */
    public function convertArrayToUTF8(array $array)
    {

        foreach ($array as $index => $element) {

            $fixValue = $element;

            if (is_string($element)) {
                $fixValue = mb_convert_encoding($element, 'UTF-8', 'UTF-8');
            } elseif (is_array($element)) {
                $fixValue = $this->convertArrayToUTF8($element);
            }

            $array[$index] = $fixValue;

        }

        return $array;
    }

    /**
     * @return array
     */
    public function getExifData()
    {
        return $this->exifData;
    }

}
