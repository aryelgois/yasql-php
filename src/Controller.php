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
     */
    public static function build(Event $event)
    {
        $args = $event->getArguments();

        if (empty($args)) {
            echo "Usage:\n\n"
               . "composer yasql-builder -- OUTPUT_DIR [CONFIG_FILE]\n";
            die(1);
        }

        $output = $args[0];
        $vendors = $event->getComposer()->getConfig()->get('vendor-dir');

        $builder = new Builder($output);

        $root = getcwd();
        $config = $args[1] ?? 'config/databases.yml';

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
