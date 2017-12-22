<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use Composer\Script\Event;

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
     * Use it with Composer's run-script. An argument pointing to the config
     * file (relative to the package root) is required.
     *
     * @param Event $event Composer run-script event
     *
     * @throws \BadMethodCallException Missing config file argument
     * @throws \RuntimeException       Can not read config file
     */
    public static function build(Event $event)
    {
        $args = $event->getArguments();

        if (empty($args)) {
            throw new \BadMethodCallException('The config file argument is missing');
        }

        $root = getcwd();
        $path = realpath($root . '/' . $args[0]);

        if (!$path || !is_readable($path)) {
            $message = 'File "' . $path . '" does not exist or cannot be read.';
            throw new \RuntimeException($message);
        }

        $builder = new Builder(file_get_contents($path), $root);
        echo $builder->getResult();
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
