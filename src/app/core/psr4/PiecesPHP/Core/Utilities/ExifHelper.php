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
     * $exifData
     *
     * @var array
     */
    protected $exifData = [];

    /**
     * __construct
     *
     * @param string $file
     * @return static
     */
    public function __construct(string $file)
    {
        $this->exifData = exif_read_data($file, null);
    }

    /**
     * getOriginalDate
     *
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
     * getDigitizedDate
     *
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
     * getFileDate
     *
     * @return \DateTime|null
     */
    public function getFileDate()
    {
        return isset($this->exifData['FileDateTime']) ? (new \DateTime())->setTimestamp($this->exifData['FileDateTime']) : null;
    }

    /**
     * getGPSCoordinates
     *
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
     * getGPSLongitude
     *
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
     * getGPSLatitude
     *
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
     * getGPSSign
     *
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
            $value = str_split($value);
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
     * getGPSDataToNumber
     *
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
                'degrees' => count($value) > 0 ? $value[0] : 0,
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
                    $segments[$segment] = floatval($parts[0]) / floatval($parts[1]);
                }

            }

            $number = ($segments['degrees'] + $segments['minutes'] / 60 + $segments['seconds'] / 3600);

        }

        return $number;

    }

    /**
     * getExifData
     *
     * @return array
     */
    public function getExifData()
    {
        return $this->exifData;
    }

}
