<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use Composer\Script\Event;

/**
 * Composer scripts for command line use
 *
 * Use it with Composer's run-script
 *
 * Paths are relative to the package root
 *
 * @author Aryel Mota Góis
 * @license MIT
 * @link https://www.github.com/aryelgois/yasql-php
 */
class Composer
{
    /**
     * Builds database schemas into a directory
     *
     * Arguments: (any order)
     *
     *    config=path/to/config_file.yml (default 'config/databases.yml')
     *    output=path/to/output/         (default 'build/')
     *    vendor=vendor/package          (multiple allowed)
     *
     * @param Event $event Composer run-script event
     */
    public static function build(Event $event)
    {
        $args = $event->getArguments();
        $config = null;
        $output = null;
        $vendors = [];

        foreach ($args as $arg) {
            $tokens = explode('=', $arg, 2);
            if (count($tokens) == 1) {
                throw new \InvalidArgumentException("Invalid argument '$arg'");
            }
            switch ($tokens[0]) {
                case 'config':
                    if ($config === null) {
                        $config = $tokens[1];
                    } else {
                        throw new \LogicException("Repeated 'config' argument");
                    }
                    break;

                case 'output':
                    if ($output === null) {
                        $output = $tokens[1];
                    } else {
                        throw new \LogicException("Repeated 'output' argument");
                    }
                    break;

                case 'vendor':
                    $vendors[] = $tokens[1];
                    break;

                default:
                    $message = "Unknown argument '$tokens[0]' in '$arg'";
                    throw new \DomainException($message);
                    break;
            }
        }

        Controller::build(
            $output ?? 'build/',
            $config ?? 'config/databases.yml',
            self::getVendorDir($event),
            $vendors
        );
    }

    /**
     * Generates the SQL from a YASQL file
     *
     * Arguments:
     *
     *    string $1 Path to YASQL file
     *    int    $2 How many spaces per indentation level
     *
     * @param Event $event Composer run-script event
     */
    public static function generate(Event $event)
    {
        $args = $event->getArguments();
        if (empty($args)) {
            echo "Usage:\n\n"
               . "    composer yasql-generate -- YASQL_FILE [INDENTATION]\n\n"
               . "By default, INDENTATION is 2\n";
            die(1);
        }

        echo Controller::generate(file_get_contents($args[0]), $args[1] ?? 2);
    }

    /**
     * Gets Composer's vendor dir
     *
     * @param Event $event Composer run-script event
     *
     * @return string Path to Vendor Directory
     */
    protected static function getVendorDir(Event $event)
    {
        return $event->getComposer()->getConfig()->get('vendor-dir');
    }
}
