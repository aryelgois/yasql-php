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
     * Describes the build result
     *
     * @var string
     */
    protected $result;

    /**
     * Creates a new Builder object
     *
     * @param string $config YAML with build configurations
     * @param string $root   From where to solve the paths
     *
     * @throws \RuntimeException Missing keys in the config
     * @throws \RuntimeException Can not create output directory
     * @throws \RuntimeException Can not find YASQL database
     * @throws \RuntimeException Can not find post file
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

        $list = [];
        foreach ($config['databases'] as $database) {
            $path = $database['path'] ?? $database;
            $post = $database['post'] ?? null;

            $file = realpath($root . '/' . $path);
            if ($file === false) {
                $message = 'Database "' . $path . '" not found';
                throw new \RuntimeException($message);
            }

            $sql = Controller::generate(file_get_contents($path), $indent);

            if ($post) {
                $file_post = realpath($root . '/' . $post);
                if ($file_post === false) {
                    $message = 'File "' . $post . '" not found';
                    throw new \RuntimeException($message);
                }
                $sql .= "\n--\n-- Post\n--\n\n" . file_get_contents($file_post);
            }

            $outfile = basename(substr($file, 0, strrpos($file, '.'))) . '.sql';
            file_put_contents($output . '/' . $outfile, $sql);
            $list[] = $outfile;
        }

        $this->result = "\nOutput at: " . $output
            . "\nFiles generated:\n- " . implode("\n- ", $list) . "\n";
    }

    /**
     * Returns the build result
     *
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
