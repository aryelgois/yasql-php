<?php
/**
 * This Software is part of aryelgois/yasql-php and is provided "as is".
 *
 * @see LICENSE
 */

namespace aryelgois\YaSql;

use aryelgois\YaSql\Populator;
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
     * Build log
     *
     * @var string[]
     */
    protected $log;

    /**
     * Path to build output
     *
     * @var string
     */
    protected $output;

    /**
     * Path to vendors directory
     *
     * @var string|false
     */
    protected $vendors;

    /**
     * Creates a new Builder object
     *
     * @param string $output  Where files will be stored
     * @param string $vendors Path to vendors directory
     *
     * @throws \RuntimeException Can not create output directory
     */
    public function __construct(string $output, string $vendors = null)
    {
        if (!is_dir($output) && !mkdir($output, 0775, true)) {
            throw new \RuntimeException('Can not create output directory');
        }

        $this->output = realpath($output);
        $this->vendors = realpath($vendors ?? 'vendor');

        $this->log = 'Build start: ' . date('c') . "\n"
            . "Output: $this->output\n\n";
    }

    /**
     * Builds databases in a config file
     *
     * @param string $config Path to YAML with build configurations
     * @param string $root   From where to solve the paths
     */
    public function build(string $config, string $root = null)
    {
        $root = $root ?? getcwd();

        $config = $root . '/' . $config;
        $this->log .= "Load config file $config\n";
        $config = Yaml::parse(file_get_contents($config));
        $indent = $config['indentation'] ?? null;

        $databases = $config['databases'] ?? [];
        if (!empty($databases)) {
            $generated = '';
            foreach ($config['databases'] as $database) {
                $path = $root . '/' . ($database['path'] ?? $database);
                $file = realpath($path);
                if ($file === false) {
                    $this->log .= "E: Database '$path' not found\n";
                    continue;
                }
                $sql = Controller::generate(file_get_contents($file), $indent);

                $post_list = (array) ($database['post'] ?? []);
                foreach ($post_list as $post) {
                    if (is_array($post)) {
                        $post_name = $class = $post['call'];
                        if (is_subclass_of($class, Populator::class)) {
                            $obj = new $class($root);
                            $result = [];
                            foreach ((array) $post['with'] as $with) {
                                $obj->load($with);
                                $result = array_merge(
                                    $result,
                                    [
                                        '--',
                                        "-- With '" . basename($with) . "'",
                                        '--',
                                        '',
                                        $obj->run(),
                                    ]
                                );
                            }
                            $post = implode("\n", $result);
                        } else {
                            $this->log .= "E: Class '$class' does not extend "
                                . Populator::class . "\n";
                            $post = null;
                        }
                    } else {
                        $post = $root . '/' . $post;
                        $post_file = realpath($post);
                        if ($post_file !== false) {
                            $post_name = basename($post);
                            $post = file_get_contents($post_file);
                        } else {
                            $this->log .= "W: Post file '$post' not found\n";
                            $post = null;
                        }
                    }
                    if (!is_null($post)) {
                        $sql .= "\n--\n-- Post '" . $post_name . "'"
                              . "\n--\n\n"
                              . $post;
                    }
                }

                $outfile = basename(substr($file, 0, strrpos($file, '.')))
                         . '.sql';
                file_put_contents($this->output . '/' . $outfile, $sql);
                $generated .= "- $outfile\n";
            }

            $this->log .= "Files generated:\n$generated";
        }

        foreach ($config['vendors'] ?? [] as $vendor => $vendor_configs) {
            $this->log .= "\nSwitch to vendor $vendor\n\n";

            if (is_null($vendor_configs)) {
                $vendor_configs = [null];
            }

            foreach ((array) $vendor_configs as $vendor_config) {
                $this->build(
                    $vendor_config ?? 'config/databases.yml',
                    $this->vendors . '/' . $vendor
                );
            }
        }
    }

    /**
     * Returns the build log
     *
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }
}
