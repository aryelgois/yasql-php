<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use Composer\Script\Event;

/**
 * Composer events for command line use
 *
 * Use it with Composer's run-script
 *
 * Paths are relative to the package root
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class Composer
{
    /**
     * Builds database schemas into a directory
     *
     * @argument string $1 Path to output directory
     * @argument string $2 Path to config file (default 'config/databases.yml')
     *
     * @param Event $event Composer run-script event
     */
    public static function build(Event $event)
    {
        $args = $event->getArguments();
        if (empty($args)) {
            echo "Usage:\n\n"
               . "composer yasql-builder -- OUTPUT_DIR [CONFIG_FILE]\n\n"
               . "By default, CONFIG_FILE is in `config/databases.yml`\n";
            die(1);
        }

        Controller::build(
            getcwd(),
            $args[0],
            $args[1] ?? 'config/databases.yml'
        );
    }

    /**
     * Generates the SQL from a YASQL file
     *
     * @argument string $1 Path to YASQL file
     * @argument int    $2 How many spaces per indentation level
     *
     * @param Event $event Composer run-script event
     */
    public static function generate(Event $event)
    {
        $args = $event->getArguments();
        if (empty($args)) {
            echo "Usage:\n\n"
               . "composer yasql-generate -- YASQL_FILE [INDENTATION]\n\n"
               . "By default, INDENTATION is 2\n";
            die(1);
        }

        echo Controller::generate(file_get_contents($args[0]), $args[1] ?? 2);
    }
}
