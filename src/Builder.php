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
 * @link https://www.github.com/aryelgois/yasql-php
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
     * List of configs already read
     *
     * @var string[]
     */
    protected $track = [];

    /**
     * Path to vendors directory
     *
     * @var string|false
     */
    protected $vendor;

    /**
     * Creates a new Builder object
     *
     * @param string $output Where files will be stored
     * @param string $vendor Path to vendors directory
     *
     * @throws \RuntimeException Can not create output directory
     */
    public function __construct(string $output = null, string $vendor = null)
    {
        if ($output === null) {
            $output = getcwd() . '/build';
        }

        if (!is_dir($output) && !mkdir($output, 0775, true)) {
            throw new \RuntimeException('Can not create output directory');
        }

        $this->output = realpath($output);
        $this->vendor = realpath($vendor ?? 'vendor');

        $this->log = 'Build start: ' . date('c') . "\n"
            . "Output: $this->output\n\n";

        if ($this->vendor === false) {
            $this->log .= "N: Could not find vendor dir\n\n";
        }
    }

    /**
     * Builds databases in a config file
     *
     * @param string $config  Path to YAML with build configurations
     * @param array  $vendors List of additional vendors to include
     *                        (using the default config file location)
     */
    public function build(string $config, array $vendors = null)
    {
        if ($config !== '') {
            $config_path = realpath($config);
            if (in_array($config_path, $this->track)) {
                $this->log .= "Skiping repeated config file '$config_path'\n";
                return;
            }
            $this->track[] = $config_path;

            $this->log .= "Load config file '$config_path'\n";
            $config = Yaml::parse(file_get_contents($config_path));
            $indent = $config['indentation'] ?? null;
        } else {
            $this->log = trim($this->log) . "\n";
        }

        if (!empty($config['databases'] ?? [])) {
            $generated = '';
            foreach ($config['databases'] as $database) {
                $path = $database['path'] ?? $database;
                if ($path[0] !== '/') {
                    $path = dirname($config_path) . '/' . $path;
                }
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
                        if (!is_subclass_of($class, Populator::class)) {
                            $this->log .= "E: Class '$class' does not extend "
                                . Populator::class . "\n";
                            continue;
                        }
                        $obj = new $class();
                        $post_sql = '';
                        foreach ((array) $post['with'] as $with) {
                            if ($with[0] !== '/') {
                                $with = dirname($config_path) . '/' . $with;
                            }
                            $obj->load($with);
                            $post_sql .= "--\n-- With '" . basename($with)
                                . "'\n--\n\n" . trim($obj->run()) . "\n\n";
                        }
                    } else {
                        if ($post[0] !== '/') {
                            $post = dirname($config_path) . '/' . $post;
                        }
                        $post_file = realpath($post);
                        if ($post_file === false) {
                            $this->log .= "W: Post file '$post' not found\n";
                            continue;
                        }
                        $post_name = basename($post);
                        $post_sql = file_get_contents($post_file);
                    }
                    $sql .= "\n--\n-- Post '$post_name'\n--\n\n"
                        . trim($post_sql) . "\n";
                }

                $outfile = basename(substr($file, 0, strrpos($file, '.')))
                    . '.sql';
                file_put_contents($this->output . '/' . $outfile, $sql);
                $generated .= "- $outfile\n";
            }
            $this->log .= "Files generated:\n$generated";
        }

        if ($this->vendor === false) {
            return;
        }
        $vendors = array_merge_recursive(
            array_fill_keys($vendors ?? [], null),
            $config['vendors'] ?? []
        );
        foreach ($vendors as $vendor => $vendor_configs) {
            $this->log .= "\nSwitch to vendor $vendor\n\n";

            if ($vendor_configs === null) {
                $vendor_configs = [null];
            }

            foreach ((array) $vendor_configs as $vendor_config) {
                $this->build("$this->vendor/$vendor/"
                    . ($vendor_config ?? 'config/databases.yml'));
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
