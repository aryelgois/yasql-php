<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

/**
 * Create SQL database schemas with YAML
 *
 * Wrapper to simplify the package usage
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class Controller
{
    /**
     * Generates the SQL from a YASQL
     *
     * @param string $yasql  A string following YAML Ain't SQL specifications
     * @param int    $indent How many spaces per indentation level
     *
     * @return string
     */
    public static function generate(string $yasql, int $indent = null)
    {
        $model = new Parser($yasql);
        $view = new Generator($model, $indent);
        return $view->output();
    }

    /**
     * Parses a YASQL and returns the parsed data
     *
     * @param string $yasql A string following YAML Ain't SQL specifications
     *
     * @return array
     */
    public static function parse(string $yasql)
    {
        $parser = new Parser($yasql);
        return $parser->getData();
    }
}
