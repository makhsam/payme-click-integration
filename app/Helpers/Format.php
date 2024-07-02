<?php

namespace App\Helpers;

use App\Models\Country;

class Format
{
    /**
     * Converts date time string to timestamp value.
     * 
     * @param string $datetime date time string.
     * @return int timestamp as milliseconds.
     */
    public static function toTimestamp($datetime)
    {
        return 1000 * strtotime($datetime);
    }

    /**
     * Converts timestamp to date time string.
     * 
     * @param int $timestamp value in milliseconds.
     * @return string string representation of the timestamp value in 'Y-m-d H:i:s' format.
     */
    public static function toDatetime($timestamp)
    {
        return date('Y-m-d H:i:s', floor(1 * $timestamp / 1000));
    }

    /**
     * Get current timestamp in seconds or milliseconds.
     * 
     * @param bool $milliseconds true - get timestamp in milliseconds, false - in seconds.
     * @return int current timestamp value
     */
    public static function currentTime($milliseconds = false)
    {
        if ($milliseconds) {
            return round(microtime(true)) * 1000; // milliseconds
        }

        return time(); // seconds
    }
}
