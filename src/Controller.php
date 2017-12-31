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
     * Builds database schemas into a directory
     *
     * @param string $root   Path to project root directory
     * @param string $output Path to output directory
     * @param string $config Path to config file
     */
    public static function build(string $root, string $output, string $config)
    {
        $builder = new Builder($output);

        try {
            $builder->build($config, $root);
        }
        catch (Exception $e) {
            throw $e;
        } finally {
            echo $builder->getLog() . "\n";
        }
    }

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
