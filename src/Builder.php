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

        $this->log = [
            'Build start: ' . date('c'),
            'Output: ' . $this->output,
            '',
        ];
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
        $this->log[] = 'Load config file ' . $config;
        $config = Yaml::parse(file_get_contents($config));
        $indent = $config['indentation'] ?? null;

        $databases = $config['databases'] ?? [];
        if (!empty($databases)) {
            $generated = [];
            foreach ($config['databases'] as $database) {
                $path = $root . '/' . ($database['path'] ?? $database);
                $file = realpath($path);
                if ($file === false) {
                    $this->log[] = 'E: Database "' . $path . '" not found';
                    continue;
                }
                $sql = Controller::generate(file_get_contents($file), $indent);

                $post_list = (array) ($database['post'] ?? []);
                foreach ($post_list as $post) {
                    $post = $root . '/' . $post;
                    $post_file = realpath($post);
                    if ($post_file === false) {
                        $this->log[] = 'W: Post file "' . $post . '" not found';
                    } else {
                        $sql .= "\n--\n-- Post '" . basename($post) . "'"
                              . "\n--\n\n"
                              . file_get_contents($post_file);
                    }
                }

                $outfile = basename(substr($file, 0, strrpos($file, '.')))
                         . '.sql';
                file_put_contents($this->output . '/' . $outfile, $sql);
                $generated[] = '- ' . $outfile;
            }

            $this->log = array_merge(
                $this->log,
                ['Files generated:'],
                $generated
            );
        }

        foreach ($config['vendors'] ?? [] as $vendor => $vendor_configs) {
            $this->log = array_merge(
                $this->log,
                [
                    '',
                    'Switch to vendor ' . $vendor,
                    '',
                ]
            );

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
        return implode("\n", $this->log);
    }
}
