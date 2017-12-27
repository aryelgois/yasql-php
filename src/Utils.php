<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

/**
 * Util methods
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class Utils
{
    /**
     * Appends a string to the last item in an array
     *
     * Optionally, appends a string to the other items
     *
     * @param string[] $array  Array to receive data
     * @param string   $last   Appended to the last item
     * @param string   $others Appended to the other items
     */
    public static function arrayAppendLast(
        array $array,
        string $last,
        string $others = null
    ) {
        $count = count($array);
        foreach ($array as $key => $value) {
            $array[$key] = $value . (--$count > 0 ? $others : $last);
        }
        return $array;
    }
}
