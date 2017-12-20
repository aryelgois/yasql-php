<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use Symfony\Component\Yaml\Yaml;

/**
 * Automated generation of database schemas into a directory
 *
 * @author Aryel Mota Góis
 * @license MIT
 */
class Builder
{
    /**
     * Creates a new Builder object
     *
     * @param string $config YAML with build configurations
     * @param string $root   From where to solve the paths
     *
     * @throws \RuntimeException Missing keys in the config
     * @throws \RuntimeException Can not create output directory
     * @throws \RuntimeException Can not find YASQL database
     */
    public function __construct(string $config, string $root = null)
    {
        $config = Yaml::parse($config);
        $root = $root ?? getcwd();
        $indent = $config['indentation'] ?? null;

        foreach (['databases', 'output'] as $key) {
            if (!array_key_exists($key, $config)) {
                throw new \RuntimeException('Missing key "' . $key . '"');
            }
        }

        $output = $root . '/' . trim($config['output'], '/');
        if (!is_dir($output) && !mkdir($output, 0775, true)) {
            throw new \RuntimeException('Can not create output directory');
        }

        foreach ($config['databases'] as $database) {
            $file = realpath($root . '/' . $database);
            if ($file === false) {
                $message = 'Database "' . $database . '" not found';
                throw new \RuntimeException($message);
            }

            $sql = Controller::generate(file_get_contents($database), $indent);

            $outfile = basename(substr($file, 0, strrpos($file, '.'))) . '.sql';
            file_put_contents($output . '/' . $outfile, $sql);
        }
    }
}
